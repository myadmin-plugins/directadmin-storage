---
name: phpunit-test
description: Adds a PHPUnit 9 test file following conventions in tests/. Uses namespace Detain\MyAdminDirectAdminStorage\Tests, extends TestCase, bootstraps via tests/bootstrap.php. Trigger phrases: 'add test', 'write test for', 'test coverage', new src/ class added. Covers unit tests for standalone functions (like UnhtmlentitiesTest.php) and structural/reflection tests for classes (like PluginTest.php, HTTPSocketTest.php). Do NOT use for test runner configuration or phpunit.xml changes.
---
# PHPUnit Test

## Critical

- **Namespace must be** `Detain\MyAdminDirectAdminStorage\Tests` — no exceptions
- **Never make live network calls** in tests. `HTTPSocket::query()` and `connect()` are tested structurally (ReflectionClass / property-setting), not via real sockets
- **Never commit credentials** — `$server_pass`, `$hash`, or API keys must not appear in test files
- Verify `vendor/bin/phpunit tests/PluginTest.php` passes before declaring done

## Instructions

1. **Identify what to test.** Determine if the target is a standalone function (`src/unhtmlentities.php`) or a class (`src/Plugin.php`, `src/HTTPSocket.php`). This determines which testing strategy to use (Steps 3a vs 3b).

2. **Create the test file** in the `tests/` directory. Opening boilerplate is always:
   ```php
   <?php

   namespace Detain\MyAdminDirectAdminStorage\Tests;

   use Detain\MyAdminDirectAdminStorage\ClassName;
   use PHPUnit\Framework\TestCase;
   use ReflectionClass;

   class ClassNameTest extends TestCase
   {
   ```
   For standalone functions, omit the `use` import for the class.

3a. **Standalone function tests** (target is a plain PHP file with no class):
   - Use `require_once dirname(__DIR__) . '/src/file.php'` inside each test method that needs the function
   - Start with: file existence (`assertFileExists`), function declaration (`assertStringContainsString('function funcName', $content)`), implementation keywords via `file_get_contents` + `assertStringContainsString`
   - Add `function_exists()` test, then `ReflectionFunction` for signature, then behavioral tests with known inputs
   - Path pattern: `$filePath = dirname(__DIR__) . '/src/file.php';`

3b. **Class tests** (target is a namespaced class in `src/`):
   - Start with `testClassExists` (`class_exists(Foo::class)`) and `testCanBeInstantiated` (`new Foo()`)
   - Test each public static property value directly: `$this->assertSame('expected', Foo::$prop);`
   - Use `ReflectionClass` to verify: property existence/visibility/static modifier, method existence/visibility/static modifier, parameter names and counts, own-method count
   - Add static analysis tests: `$content = file_get_contents(dirname(__DIR__) . '/src/ClassName.php');` then `assertStringContainsString` for key patterns (namespace, imports, API commands, method calls)
   - For network-touching methods: set public properties directly and test getter return values; never call `connect()` with real hosts

4. **Each test method must:**
   - Be `public function testDescriptiveName(): void`
   - Have a docblock (2–3 lines) stating what it tests and why
   - Make exactly one logical assertion group

5. **Run and verify:**
   ```bash
   vendor/bin/phpunit tests/PluginTest.php
   vendor/bin/phpunit --coverage-text  # confirm coverage improved
   ```

## Examples

**User says:** "Add tests for the HTTPSocket class in `src/HTTPSocket.php`"

**Actions taken:**
1. Read `src/HTTPSocket.php` to identify namespace, public methods, static properties
2. Create `tests/HTTPSocketTest.php`:
```php
<?php

namespace Detain\MyAdminDirectAdminStorage\Tests;

use Detain\MyAdminDirectAdminStorage\HTTPSocket;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class HTTPSocketTest extends TestCase
{
    /**
     * Tests that the HTTPSocket class exists and is loadable.
     *
     * Verifies the PSR-4 autoloader can find and load the class.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(HTTPSocket::class));
    }

    /**
     * Tests that HTTPSocket can be instantiated without arguments.
     */
    public function testCanBeInstantiated(): void
    {
        $sock = new HTTPSocket();
        $this->assertInstanceOf(HTTPSocket::class, $sock);
    }

    /**
     * Tests that the source file declares the correct namespace.
     *
     * Static analysis: ensures file uses the package namespace.
     */
    public function testSourceFileNamespace(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/HTTPSocket.php');
        $this->assertStringContainsString('namespace Detain\\MyAdminDirectAdminStorage;', $content);
    }

    /**
     * Tests all expected public methods exist and are public.
     */
    public function testPublicMethodsExist(): void
    {
        $ref = new ReflectionClass(HTTPSocket::class);
        foreach (['connect', 'query', 'fetch_parsed_body', 'fetch_body'] as $method) {
            $this->assertTrue($ref->hasMethod($method), "Missing method: {$method}");
            $this->assertTrue($ref->getMethod($method)->isPublic());
        }
    }
}
```
3. Run `vendor/bin/phpunit tests/HTTPSocketTest.php` — all tests pass

**Result:** `tests/HTTPSocketTest.php` created, matching the namespace, docblock, and reflection patterns of `tests/PluginTest.php`.

## Common Issues

- **`Class 'Detain\MyAdminDirectAdminStorage\Foo' not found`**: PSR-4 autoloader hasn't mapped the class. Check that `src/Foo.php` exists and `composer.json` has `"Detain\\MyAdminDirectAdminStorage\\"` → `"src/"`. Run `composer dump-autoload`.

- **`Unable to find autoloader. Run composer install.`** (from bootstrap.php): Run `composer install` in the package root before running tests.

- **`Call to undefined function get_module_settings()`** in tests that instantiate Plugin methods: Plugin event handlers call MyAdmin global functions. Do NOT call `getActivate()` etc. directly — use `ReflectionClass` and static analysis instead.

- **`assertCount(11, $ownMethods)` fails after adding a method**: The own-method-count assertion in `tests/PluginTest.php` is an exact count. Update it when you add or remove methods from `src/Plugin.php`.

- **Test passes locally but fails in CI**: The CI bootstrap resolves the parent autoloader three directories up. Confirm `tests/bootstrap.php` path logic matches the installed location (`vendor/detain/myadmin-directadmin-storage/tests/`).
