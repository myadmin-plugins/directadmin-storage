# Caliber Learnings

Accumulated patterns and anti-patterns from development sessions.
Auto-managed by [caliber](https://github.com/caliber-ai-org/ai-setup) — do not edit manually.

- **[gotcha]** `src/Plugin.php` imports `HTTPSocket` from `Detain\MyAdminDirectAdminWeb\HTTPSocket` (a separate package), NOT from the local `src/HTTPSocket.php` (`Detain\MyAdminDirectAdminStorage\HTTPSocket`). The local copy exists for test use only (`tests/HTTPSocketTest.php`). **Why:** modifying `src/HTTPSocket.php` has no effect on `Plugin.php` or `bin/` script behavior. **How to apply:** when tracing or modifying HTTPSocket behavior used by `Plugin.php`, look in the `myadmin-directadmin-web` package, not `src/HTTPSocket.php`.
- **[gotcha]** When writing skill content in `.claude/skills/*/SKILL.md` files, Caliber's scoring validates all backtick references against the filesystem. Avoid template paths like `` `tests/{ClassName}Test.php` ``, protocol strings like `` `ssl://` ``, and bare filenames like `` `Plugin.php` `` — all flagged as invalid. Always use full paths like `` `src/Plugin.php` `` and `` `tests/PluginTest.php` ``.
