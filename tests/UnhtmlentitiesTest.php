<?php

namespace Detain\MyAdminDirectAdminStorage\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the unhtmlentities standalone function in src/unhtmlentities.php.
 *
 * This file defines a global function (not namespaced) for converting
 * HTML numeric character references back to their character equivalents.
 */
class UnhtmlentitiesTest extends TestCase
{
    /**
     * Tests that the unhtmlentities.php source file exists.
     *
     * Static analysis: verifies the file is present in the src directory.
     */
    public function testFileExists(): void
    {
        $filePath = dirname(__DIR__) . '/src/unhtmlentities.php';
        $this->assertFileExists($filePath);
    }

    /**
     * Tests that the file defines the unhtmlentities function.
     *
     * Static analysis: checks the source for the function declaration.
     */
    public function testFileContainsFunctionDeclaration(): void
    {
        $filePath = dirname(__DIR__) . '/src/unhtmlentities.php';
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('function unhtmlentities', $content);
    }

    /**
     * Tests that the file uses preg_replace_callback for conversion.
     *
     * Static analysis: verifies the implementation strategy.
     */
    public function testFileUsesRegexCallback(): void
    {
        $filePath = dirname(__DIR__) . '/src/unhtmlentities.php';
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('preg_replace_callback', $content);
    }

    /**
     * Tests that the file references the chr() function for character conversion.
     *
     * Static analysis: the callback converts numeric values using chr().
     */
    public function testFileUsesChr(): void
    {
        $filePath = dirname(__DIR__) . '/src/unhtmlentities.php';
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('chr(', $content);
    }

    /**
     * Tests that the global unhtmlentities function is loadable.
     *
     * After including the file, the function should be available globally.
     */
    public function testFunctionIsLoadable(): void
    {
        require_once dirname(__DIR__) . '/src/unhtmlentities.php';
        $this->assertTrue(function_exists('unhtmlentities'));
    }

    /**
     * Tests that the function accepts a string parameter.
     *
     * Verifies the function signature via reflection.
     */
    public function testFunctionSignature(): void
    {
        require_once dirname(__DIR__) . '/src/unhtmlentities.php';
        $ref = new \ReflectionFunction('unhtmlentities');

        $this->assertCount(1, $ref->getParameters());
        $this->assertSame('string', $ref->getParameters()[0]->getName());
    }

    /**
     * Tests that a plain string with no entities is returned unchanged.
     *
     * Strings without &#NN patterns should pass through unmodified.
     */
    public function testPlainStringPassesThrough(): void
    {
        require_once dirname(__DIR__) . '/src/unhtmlentities.php';

        $this->assertSame('Hello World', unhtmlentities('Hello World'));
    }

    /**
     * Tests that an empty string returns empty.
     *
     * Edge case: empty input should produce empty output.
     */
    public function testEmptyString(): void
    {
        require_once dirname(__DIR__) . '/src/unhtmlentities.php';

        $this->assertSame('', unhtmlentities(''));
    }
}
