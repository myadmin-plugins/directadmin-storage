---
name: directadmin-api-call
description: Creates a DirectAdmin API call using HTTPSocket for connect/authenticate/query/parse pattern. Use when user says 'call DirectAdmin API', 'query DA endpoint', 'add DA command', or adds new bin/ scripts. Covers SSL connection on port 2222, set_login, query with options array, fetch_parsed_body, and error checking via $result['error']. Do NOT use for modifying src/HTTPSocket.php itself or for non-DirectAdmin API integrations.
---
# DirectAdmin API Call

## Critical

- **Never hardcode credentials** in committed files — `$hash`/`$password` must come from DB (`backup_key`) or be placeholder comments only
- Always use the `'ssl://'` stream prefix for production connections — plain HTTP is only for local debug (seen commented out in bin scripts)
- Check `$result['error'] != "0"` (string `"0"`, not int) — this is DirectAdmin's convention
- Always call `fetch_parsed_body()` immediately after `query()` before any other query
- In `src/Plugin.php` event handlers, always call `$event->stopPropagation()` after handling

## Instructions

1. **Import HTTPSocket** at the top of the file:
   ```php
   use Detain\MyAdminDirectAdminStorage\HTTPSocket;
   ```
   For `bin/` scripts also add: `require_once('../vendor/autoload.php');`  
   For DB-accessing bin scripts: `include_once __DIR__.'/../../../../include/functions.inc.php';`

2. **Get server credentials** — in Plugin event handlers use:
   ```php
   $settings = get_module_settings(self::$module); // self::$module = 'backups'
   $serverdata = get_service_master($serviceClass->getServer(), self::$module);
   $hash = $serverdata[$settings['PREFIX'].'_key'];
   $ip   = $serverdata[$settings['PREFIX'].'_ip'];
   ```
   In bin/ scripts query the DB directly:
   ```php
   $db = get_module_db('backups');
   $db->query("select * from backup_masters where backup_name='".$db->real_escape($argv[1])."'", __LINE__, __FILE__);
   $db->next_record(MYSQL_ASSOC);
   $ip       = $db->Record['backup_ip'];
   $password = $db->Record['backup_key'];
   ```
   Verify credentials are non-empty before continuing.

3. **Connect and authenticate:**
   ```php
   $sock = new HTTPSocket();
   $sock->connect('ssl://'.$ip, 2222);
   $sock->set_login('admin', $hash);
   ```
   Use `($server_ssl == 'Y' ? 'ssl://' : '').$ip` if SSL is configurable.

4. **Build `$apiCmd` and `$apiOptions`, then query:**
   - POST with params array (most mutating commands):
     ```php
     $apiCmd = '/CMD_API_SELECT_USERS';
     $apiOptions = [
         'location' => 'CMD_SELECT_USERS',
         'suspend'  => 'Suspend',
         'select0'  => $username,
     ];
     $sock->query($apiCmd, $apiOptions);
     ```
   - GET with query string (read-only commands):
     ```php
     $sock->query('/CMD_API_SHOW_USER_CONFIG?user='.$username);
     ```
   Verify `$apiCmd` starts with `/CMD_API_`.

5. **Fetch and check result:**
   ```php
   $result = $sock->fetch_parsed_body();
   if ($result['error'] != "0") {
       // error path
       myadmin_log('directadmin', 'error', 'Error: Text:'.$result['text'].' Details:'.$result['details'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
       $event['success'] = false;
       $event->stopPropagation();
       return;
   }
   ```
   Special terminate case — also treat `$result['text'] == "System user {$username} does not exist!"` as success.

6. **Log the call** (required in Plugin event handlers):
   ```php
   request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $result, $serviceClass->getId());
   myadmin_log('myadmin', 'info', 'DirectAdmin '.$apiCmd.' '.json_encode($apiOptions).' Response: '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
   ```

## Examples

**User says:** "Add a bin script to unsuspend a user by hostname"

**Actions taken:**
1. Create `bin/unsuspend_user.php`
2. Include functions, query `backup_masters` for credentials by hostname arg
3. Connect with `'ssl://'.$ip` on port 2222, `set_login('admin', $password)`
4. Query `/CMD_API_SELECT_USERS` with `dounsuspend=y`, `select0=$username`
5. `fetch_parsed_body()`, `print_r($result)`

**Result:**
```php
<?php
use Detain\MyAdminDirectAdminStorage\HTTPSocket;
include_once __DIR__.'/../../../../include/functions.inc.php';
$db = get_module_db('backups');
if (count($_SERVER['argv']) < 3) {
    die("Usage: {$_SERVER['argv'][0]} <hostname> <username>\n");
}
$db->query("select * from backup_masters where backup_name='".$db->real_escape($_SERVER['argv'][1])."'", __LINE__, __FILE__);
if ($db->num_rows() == 0) { die("Invalid server\n"); }
$db->next_record(MYSQL_ASSOC);
$sock = new HTTPSocket();
$sock->connect('ssl://'.$db->Record['backup_ip'], 2222);
$sock->set_login('admin', $db->Record['backup_key']);
$sock->query('/CMD_API_SELECT_USERS', [
    'location'    => 'CMD_SELECT_USERS',
    'dounsuspend' => 'y',
    'select0'     => $_SERVER['argv'][2],
]);
$result = $sock->fetch_parsed_body();
print_r($result);
```

## Common Issues

- **`$result['error']` is always `"1"` / empty response:** SSL handshake failed. Verify the server actually has a valid cert on port 2222: `openssl s_client -connect $ip:2222`. If self-signed, `HTTPSocket` still accepts it (verify_peer is off by default).
- **`fetch_parsed_body()` returns `null` or non-array:** Called a second time after already fetching, or `query()` was never called. Always call `query()` then `fetch_parsed_body()` in sequence.
- **`result['text']` = `"Cannot Login"` :** Wrong `$hash`/password. Confirm `backup_key` column value in `backup_masters` matches the DirectAdmin admin password.
- **`result['details']` = `"Sorry, the password..."` :** DirectAdmin rejects the generated password. Retry with `generateRandomString(10, 2, 2, 2, 1)` in a loop as done in `src/Plugin.php` around the activate method.
- **`result['details']` = `"Sorry, a group for that username already exists."` :** Username collision. Append/rotate a character: `$username = mb_substr($username.'a', 1)` and retry (see `src/Plugin.php` activate handler).
