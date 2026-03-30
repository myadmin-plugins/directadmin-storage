# DirectAdmin Storage Plugin

MyAdmin plugin — backup storage lifecycle via DirectAdmin API. Handles provisioning, suspension, reactivation, termination, and IP changes.

## Commands

```bash
composer install                        # install deps
vendor/bin/phpunit                      # run all tests
vendor/bin/phpunit --coverage-text      # coverage report
vendor/bin/phpunit tests/PluginTest.php # single test file
```

## Architecture

**Namespace:** `Detain\MyAdminDirectAdminStorage\` → `src/`
**Test namespace:** `Detain\MyAdminDirectAdminStorage\Tests\` → `tests/`

**CI:** `.github/` contains workflows for automated test runs and deployment pipelines.

**Core classes:**
- `src/Plugin.php` — Symfony EventDispatcher hooks: `getActivate`, `getReactivate`, `getDeactivate`, `getTerminate`, `getChangeIp`, `getSettings`
- `src/HTTPSocket.php` — curl-based DirectAdmin API client (port 2222, SSL via `ssl://` prefix)
- `src/unhtmlentities.php` — standalone function, no class

**Hook registration** (`src/Plugin.php::getHooks()`):
```php
return [
    self::$module.'.activate'   => [__CLASS__, 'getActivate'],
    self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
    self::$module.'.terminate'  => [__CLASS__, 'getTerminate'],
    'api.register'              => [__CLASS__, 'apiRegister'],
];
```

**DirectAdmin API pattern** (used in `src/Plugin.php` and `bin/` scripts):
```php
$sock = new HTTPSocket();
$sock->connect('ssl://'.$ip, 2222);
$sock->set_login('admin', $hash);
$sock->query('/CMD_API_SELECT_USERS', ['action' => 'create', ...]);
$result = $sock->fetch_parsed_body(); // returns assoc array
if ($result['error'] != '0') { /* handle error via $result['text'] / $result['details'] */ }
```

**Event handler pattern** (all handlers in `src/Plugin.php`):
```php
public static function getActivate(GenericEvent $event) {
    if (in_array($event['type'], [get_service_define('DIRECTADMIN_STORAGE')])) {
        $serviceClass = $event->getSubject();
        $settings = get_module_settings(self::$module); // self::$module = 'backups'
        // ... do work ...
        $event['success'] = true;
        $event->stopPropagation();
    }
}
```

**Module:** `self::$module = 'backups'` — use `get_module_settings('backups')` for PREFIX/TABLE/TBLNAME

**Logging:** `myadmin_log('myadmin', 'info', $message, __LINE__, __FILE__, self::$module, $serviceClass->getId())`

**Request logging:** `request_log(self::$module, $custid, __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $result, $id)`

## Bin Scripts

`bin/` contains standalone CLI tools using `HTTPSocket` directly — `add_user.php`, `delete_user.php`, `suspend_user.php`, `unsuspend_user.php`, `show_all_users.php`, `show_user.php`, `change_pass.php`, `add_reseller.php`, etc. Scripts that need DB access include `../../../../include/functions.inc.php` and call `get_module_db('backups')`.

## Conventions

- Commit messages: lowercase, descriptive (`fix suspension logic`, `add domain support`)
- Never commit credentials — `$server_pass`, `$hash`, API keys are always runtime vars
- `fetch_parsed_body()` for key=value DA responses; `fetch_body()` for raw responses
- SSL connections: prepend `ssl://` to host string, always port `2222`
- Error check: `$result['error'] != '0'` (string comparison, not `!== 0`)
- After changes: `caliber refresh && git add CLAUDE.md .claude/` before committing

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
