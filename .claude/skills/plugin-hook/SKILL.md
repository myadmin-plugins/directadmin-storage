---
name: plugin-hook
description: Adds a new Symfony EventDispatcher hook handler to src/Plugin.php. Use when user says 'add hook', 'handle event', 'new lifecycle action', or needs to add entries to getHooks(). Covers the full pattern: type guard, getSubject(), get_module_settings(), HTTPSocket API call, myadmin_log(), request_log(), event['success'], and stopPropagation(). Do NOT use for non-Plugin.php event work or bin/ scripts.
---
# plugin-hook

## Critical

- ALL lifecycle handlers (`activate`, `deactivate`, `reactivate`, `terminate`) MUST open with `if (in_array($event['type'], [get_service_define('DIRECTADMIN_STORAGE')]))` — never skip this type guard.
- ALWAYS call `$event->stopPropagation()` before returning from inside the type guard, whether success or failure.
- NEVER commit credentials — `$hash`, `$server_pass`, API keys must come from `$serverdata[$settings['PREFIX'].'_key']`.
- Module constant is `'backups'` — use `self::$module` everywhere, never hardcode the string.

## Instructions

1. **Register the hook in `getHooks()`**
   Add an entry mapping the event name to the new static method:
   ```php
   self::$module.'.changepassword' => [__CLASS__, 'getChangePassword'],
   ```
   Verify the key matches the event name dispatched by the caller before continuing.

2. **Declare the static method** with a `@throws \Exception` docblock:
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    * @throws \Exception
    */
   public static function getChangePassword(GenericEvent $event)
   {
   ```

3. **Type guard** — wrap all logic in a type check (uses Step 1's event type):
   ```php
   if (in_array($event['type'], [get_service_define('DIRECTADMIN_STORAGE')])) {
       $serviceClass = $event->getSubject();
       myadmin_log('myadmin', 'info', 'DirectAdmin ChangePassword', __LINE__, __FILE__, self::$module, $serviceClass->getId());
   ```

4. **Load settings and server credentials** (required before any API call):
   ```php
   $settings = get_module_settings(self::$module);
   $serverdata = get_service_master($serviceClass->getServer(), self::$module);
   $hash = $serverdata[$settings['PREFIX'].'_key'];
   $ip   = $serverdata[$settings['PREFIX'].'_ip'];
   ```

5. **Connect via HTTPSocket** (always SSL on port 2222):
   ```php
   $server_ssl = 'Y';
   $sock = new HTTPSocket();
   $sock->connect(($server_ssl == 'Y' ? 'ssl://' : '').$ip, 2222);
   $sock->set_login('admin', $hash);
   ```

6. **Issue the API command, log request and response**:
   ```php
   $apiCmd = '/CMD_API_USER_PASSWD';
   $apiOptions = [
       'username' => $serviceClass->getUsername(),
       'passwd'   => $newPassword,
       'passwd2'  => $newPassword,
   ];
   $sock->query($apiCmd, $apiOptions);
   $result = $sock->fetch_parsed_body();
   request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $result, $serviceClass->getId());
   myadmin_log('myadmin', 'info', 'DirectAdmin '.$apiCmd.' '.json_encode($apiOptions).' Response: '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
   ```
   Verify `$result` is an array and `$result['error']` is present before the next step.

7. **Handle error vs success, always call `stopPropagation()`**:
   ```php
   if ($result['error'] != '0') {
       $event['success'] = false;
       myadmin_log('directadmin', 'error', 'Error Text:'.$result['text'].' Details:'.$result['details'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
       $event->stopPropagation();
       return;
   }
   $event['success'] = true;
   $event->stopPropagation();
   } // end type guard
   ```

## Examples

**User says:** "Add a hook to change a user's password on DirectAdmin when the `backups.changepassword` event fires."

**Actions taken:**
1. Add `self::$module.'.changepassword' => [__CLASS__, 'getChangePassword']` to `getHooks()`.
2. Add `getChangePassword(GenericEvent $event)` following Steps 2–7.
3. Run `vendor/bin/phpunit` to confirm no regressions.

**Result** — the new method in `src/Plugin.php`:
```php
public static function getChangePassword(GenericEvent $event)
{
    if (in_array($event['type'], [get_service_define('DIRECTADMIN_STORAGE')])) {
        $serviceClass = $event->getSubject();
        myadmin_log('myadmin', 'info', 'DirectAdmin ChangePassword', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $settings   = get_module_settings(self::$module);
        $serverdata = get_service_master($serviceClass->getServer(), self::$module);
        $hash = $serverdata[$settings['PREFIX'].'_key'];
        $ip   = $serverdata[$settings['PREFIX'].'_ip'];
        $server_ssl = 'Y';
        $sock = new HTTPSocket();
        $sock->connect(($server_ssl == 'Y' ? 'ssl://' : '').$ip, 2222);
        $sock->set_login('admin', $hash);
        $apiCmd     = '/CMD_API_USER_PASSWD';
        $apiOptions = ['username' => $serviceClass->getUsername(), 'passwd' => $event['password'], 'passwd2' => $event['password']];
        $sock->query($apiCmd, $apiOptions);
        $result = $sock->fetch_parsed_body();
        request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $result, $serviceClass->getId());
        myadmin_log('myadmin', 'info', 'DirectAdmin '.$apiCmd.' Response: '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
        if ($result['error'] != '0') {
            $event['success'] = false;
            myadmin_log('directadmin', 'error', 'Error Text:'.$result['text'].' Details:'.$result['details'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $event->stopPropagation();
            return;
        }
        $event['success'] = true;
        $event->stopPropagation();
    }
}
```

## Common Issues

- **`Call to undefined function get_service_define()`** — the bootstrap is missing. Confirm `tests/bootstrap.php` is loaded and `vendor/bin/phpunit` is run from the project root.
- **`$result['error']` undefined / `fetch_parsed_body()` returns `null`** — the `HTTPSocket::connect()` failed silently. Check that `ssl://` prefix is present and port 2222 is reachable: `curl -k https://<ip>:2222`.
- **Hook never fires** — the event name in `getHooks()` does not match what the caller dispatches. Grep for `run_event` in the parent myadmin codebase to confirm the exact string: `grep -r "run_event.*backups" /path/to/myadmin/include/`.
- **`$serverdata` is `false`** — `$serviceClass->getServer()` returned 0 or an invalid ID. Guard with `if ($serviceClass->getServer() > 0)` as done in `getDeactivate()`.
- **Tests fail after adding hook** — run `vendor/bin/phpunit tests/PluginTest.php` to isolate; add a corresponding test case asserting `$event['success'] === true` and `$event->isPropagationStopped() === true`.