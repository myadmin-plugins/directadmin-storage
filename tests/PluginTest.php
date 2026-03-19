<?php

namespace Detain\MyAdminDirectAdminStorage\Tests;

use Detain\MyAdminDirectAdminStorage\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests for the Plugin class.
 *
 * Since Plugin methods depend heavily on external functions (get_service_define,
 * myadmin_log, get_module_settings, etc.) and database calls, these tests focus
 * on class structure, static properties, hook registration, and method signatures
 * via ReflectionClass. Static analysis via file_get_contents validates source patterns.
 */
class PluginTest extends TestCase
{
    /**
     * Tests that the Plugin class exists and is loadable.
     *
     * Verifies the PSR-4 autoloader can find and load the class.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * Tests that Plugin can be instantiated.
     *
     * The constructor is empty, so instantiation should succeed without errors.
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Tests the $name static property value.
     *
     * The plugin identifies itself as 'DirectAdmin Storage'.
     */
    public function testNameProperty(): void
    {
        $this->assertSame('DirectAdmin Storage', Plugin::$name);
    }

    /**
     * Tests the $description static property is a non-empty string.
     *
     * The description provides information about the DirectAdmin control panel.
     */
    public function testDescriptionProperty(): void
    {
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
        $this->assertStringContainsString('DirectAdmin', Plugin::$description);
    }

    /**
     * Tests the $help static property defaults to empty string.
     *
     * No help text is defined for this plugin.
     */
    public function testHelpProperty(): void
    {
        $this->assertSame('', Plugin::$help);
    }

    /**
     * Tests the $module static property is 'backups'.
     *
     * This plugin belongs to the backups module.
     */
    public function testModuleProperty(): void
    {
        $this->assertSame('backups', Plugin::$module);
    }

    /**
     * Tests the $type static property is 'service'.
     *
     * The plugin type categorizes it as a service provider.
     */
    public function testTypeProperty(): void
    {
        $this->assertSame('service', Plugin::$type);
    }

    /**
     * Tests that all static properties are declared and public.
     *
     * Uses ReflectionClass to verify property visibility and static modifier.
     */
    public function testStaticPropertiesExist(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $expectedProps = ['name', 'description', 'help', 'module', 'type'];

        foreach ($expectedProps as $prop) {
            $this->assertTrue(
                $ref->hasProperty($prop),
                "Expected static property '{$prop}' not found"
            );
            $refProp = $ref->getProperty($prop);
            $this->assertTrue($refProp->isPublic(), "Property '{$prop}' should be public");
            $this->assertTrue($refProp->isStatic(), "Property '{$prop}' should be static");
        }
    }

    /**
     * Tests getHooks() returns the correct hook registration array.
     *
     * Each hook maps an event name to a [class, method] callable array.
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();

        $this->assertIsArray($hooks);
        $this->assertNotEmpty($hooks);
    }

    /**
     * Tests getHooks() contains all expected hook keys.
     *
     * The plugin registers hooks for settings, activate, reactivate,
     * deactivate, terminate, api.register, function.requirements, and ui.menu.
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();

        $expectedKeys = [
            'backups.settings',
            'backups.activate',
            'backups.reactivate',
            'backups.deactivate',
            'backups.terminate',
            'api.register',
            'function.requirements',
            'ui.menu',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $hooks, "Missing hook key: {$key}");
        }
    }

    /**
     * Tests that getHooks() returns exactly 8 hooks.
     *
     * Ensures no hooks have been accidentally added or removed.
     */
    public function testGetHooksCount(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(8, $hooks);
    }

    /**
     * Tests that each hook value is a valid callable array format.
     *
     * Each entry should be [ClassName, methodName] pointing to an existing method.
     */
    public function testGetHooksCallableFormat(): void
    {
        $hooks = Plugin::getHooks();

        foreach ($hooks as $eventName => $callable) {
            $this->assertIsArray($callable, "Hook '{$eventName}' should be an array");
            $this->assertCount(2, $callable, "Hook '{$eventName}' should have exactly 2 elements");
            $this->assertSame(
                Plugin::class,
                $callable[0],
                "Hook '{$eventName}' class should be Plugin"
            );
            $this->assertTrue(
                method_exists(Plugin::class, $callable[1]),
                "Hook '{$eventName}' references non-existent method '{$callable[1]}'"
            );
        }
    }

    /**
     * Tests that hook method names match the expected mapping.
     *
     * Verifies each event is wired to its correct handler method.
     */
    public function testGetHooksMethodMapping(): void
    {
        $hooks = Plugin::getHooks();

        $this->assertSame('getSettings', $hooks['backups.settings'][1]);
        $this->assertSame('getActivate', $hooks['backups.activate'][1]);
        $this->assertSame('getReactivate', $hooks['backups.reactivate'][1]);
        $this->assertSame('getDeactivate', $hooks['backups.deactivate'][1]);
        $this->assertSame('getTerminate', $hooks['backups.terminate'][1]);
        $this->assertSame('apiRegister', $hooks['api.register'][1]);
        $this->assertSame('getRequirements', $hooks['function.requirements'][1]);
        $this->assertSame('getMenu', $hooks['ui.menu'][1]);
    }

    /**
     * Tests that all expected public methods exist on the Plugin class.
     *
     * Verifies the complete set of event handler methods are declared.
     */
    public function testPublicMethodsExist(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $expectedMethods = [
            'getHooks',
            'apiRegister',
            'getActivate',
            'getReactivate',
            'getDeactivate',
            'getTerminate',
            'getChangeIp',
            'getMenu',
            'getRequirements',
            'getSettings',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                $ref->hasMethod($method),
                "Expected method '{$method}' not found on Plugin"
            );
            $this->assertTrue(
                $ref->getMethod($method)->isPublic(),
                "Method '{$method}' should be public"
            );
        }
    }

    /**
     * Tests that all handler methods are static.
     *
     * The plugin uses static methods for event handling to avoid instantiation.
     */
    public function testHandlerMethodsAreStatic(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $staticMethods = [
            'getHooks',
            'apiRegister',
            'getActivate',
            'getReactivate',
            'getDeactivate',
            'getTerminate',
            'getChangeIp',
            'getMenu',
            'getRequirements',
            'getSettings',
        ];

        foreach ($staticMethods as $method) {
            $this->assertTrue(
                $ref->getMethod($method)->isStatic(),
                "Method '{$method}' should be static"
            );
        }
    }

    /**
     * Tests that event handler methods accept a GenericEvent parameter.
     *
     * All event handlers (except getHooks) should accept exactly one
     * parameter of type GenericEvent.
     */
    public function testEventHandlerSignatures(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $eventHandlers = [
            'apiRegister',
            'getActivate',
            'getReactivate',
            'getDeactivate',
            'getTerminate',
            'getChangeIp',
            'getMenu',
            'getRequirements',
            'getSettings',
        ];

        foreach ($eventHandlers as $methodName) {
            $method = $ref->getMethod($methodName);
            $params = $method->getParameters();

            $this->assertCount(
                1,
                $params,
                "Method '{$methodName}' should accept exactly 1 parameter"
            );
            $this->assertSame(
                'event',
                $params[0]->getName(),
                "Method '{$methodName}' parameter should be named 'event'"
            );
        }
    }

    /**
     * Tests that getHooks() takes no parameters.
     *
     * The hook registration method requires no input.
     */
    public function testGetHooksSignature(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $method = $ref->getMethod('getHooks');

        $this->assertCount(0, $method->getParameters());
    }

    /**
     * Tests the total count of own public methods on Plugin.
     *
     * Verifies the expected number of methods declared directly on the class.
     */
    public function testOwnMethodCount(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $ownMethods = array_filter(
            $ref->getMethods(ReflectionMethod::IS_PUBLIC),
            function ($m) {
                return $m->getDeclaringClass()->getName() === Plugin::class;
            }
        );

        // 10 own methods + constructor = 11
        $this->assertCount(11, $ownMethods);
    }

    /**
     * Tests that the Plugin source file exists.
     *
     * Static analysis check for file presence.
     */
    public function testSourceFileExists(): void
    {
        $filePath = dirname(__DIR__) . '/src/Plugin.php';
        $this->assertFileExists($filePath);
    }

    /**
     * Tests that the Plugin source contains the correct namespace.
     *
     * Verifies the file declares the expected namespace.
     */
    public function testSourceFileNamespace(): void
    {
        $filePath = dirname(__DIR__) . '/src/Plugin.php';
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('namespace Detain\\MyAdminDirectAdminStorage;', $content);
    }

    /**
     * Tests that the Plugin source uses Symfony GenericEvent.
     *
     * The class depends on Symfony's event dispatcher component.
     */
    public function testSourceFileUsesGenericEvent(): void
    {
        $filePath = dirname(__DIR__) . '/src/Plugin.php';
        $content = file_get_contents($filePath);

        $this->assertStringContainsString(
            'use Symfony\\Component\\EventDispatcher\\GenericEvent;',
            $content
        );
    }

    /**
     * Tests that the Plugin source references the HTTPSocket class.
     *
     * The activation and management methods create HTTPSocket instances.
     */
    public function testSourceFileReferencesHTTPSocket(): void
    {
        $filePath = dirname(__DIR__) . '/src/Plugin.php';
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('new HTTPSocket', $content);
    }

    /**
     * Tests that getActivate references the DIRECTADMIN_STORAGE service define.
     *
     * Static analysis: verifies the method checks the service type.
     */
    public function testSourceGetActivateChecksServiceType(): void
    {
        $filePath = dirname(__DIR__) . '/src/Plugin.php';
        $content = file_get_contents($filePath);

        $this->assertStringContainsString("get_service_define('DIRECTADMIN_STORAGE')", $content);
    }

    /**
     * Tests that the source contains all expected DirectAdmin API commands.
     *
     * Static analysis: verifies the API endpoints used in the plugin.
     */
    public function testSourceContainsApiCommands(): void
    {
        $filePath = dirname(__DIR__) . '/src/Plugin.php';
        $content = file_get_contents($filePath);

        $apiCommands = [
            '/CMD_API_SHOW_RESELLER_IPS',
            '/CMD_API_ACCOUNT_RESELLER',
            '/CMD_API_ACCOUNT_USER',
            '/CMD_API_SELECT_USERS',
        ];

        foreach ($apiCommands as $cmd) {
            $this->assertStringContainsString(
                $cmd,
                $content,
                "Expected API command '{$cmd}' not found in source"
            );
        }
    }

    /**
     * Tests that hook keys use the module property prefix.
     *
     * All module-specific hooks should be prefixed with the module name.
     */
    public function testHookKeysUseModulePrefix(): void
    {
        $hooks = Plugin::getHooks();
        $module = Plugin::$module;

        $moduleHooks = [
            "{$module}.settings",
            "{$module}.activate",
            "{$module}.reactivate",
            "{$module}.deactivate",
            "{$module}.terminate",
        ];

        foreach ($moduleHooks as $key) {
            $this->assertArrayHasKey($key, $hooks);
        }
    }

    /**
     * Tests that Plugin has a constructor.
     *
     * The constructor is defined even though it's empty.
     */
    public function testHasConstructor(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $this->assertTrue($ref->hasMethod('__construct'));
        $constructor = $ref->getMethod('__construct');
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Tests that the Plugin class is not abstract.
     *
     * The class should be directly instantiable.
     */
    public function testClassIsNotAbstract(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $this->assertFalse($ref->isAbstract());
    }

    /**
     * Tests that the Plugin class is not final.
     *
     * The class should be extendable.
     */
    public function testClassIsNotFinal(): void
    {
        $ref = new ReflectionClass(Plugin::class);
        $this->assertFalse($ref->isFinal());
    }

    /**
     * Tests that source file contains event propagation stops.
     *
     * Static analysis: event handlers should stop propagation to prevent
     * other plugins from handling the same event.
     */
    public function testSourceContainsStopPropagation(): void
    {
        $filePath = dirname(__DIR__) . '/src/Plugin.php';
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('stopPropagation()', $content);
    }
}
