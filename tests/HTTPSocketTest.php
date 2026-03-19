<?php

namespace Detain\MyAdminDirectAdminStorage\Tests;

use Detain\MyAdminDirectAdminStorage\HTTPSocket;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for the HTTPSocket class.
 *
 * Covers class structure, property defaults, pure/simple methods, and
 * static analysis. Network-dependent methods (query, get) are tested
 * for structural correctness rather than live HTTP calls.
 */
class HTTPSocketTest extends TestCase
{
    /**
     * Tests that the HTTPSocket class exists and can be instantiated.
     *
     * Verifies the class is loadable via the PSR-4 autoloader and
     * that the constructor does not throw.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(HTTPSocket::class));
    }

    /**
     * Tests that HTTPSocket can be instantiated without arguments.
     *
     * The constructor has no required parameters, so a bare new should work.
     */
    public function testCanBeInstantiated(): void
    {
        $socket = new HTTPSocket();
        $this->assertInstanceOf(HTTPSocket::class, $socket);
    }

    /**
     * Tests the version property default value.
     *
     * The class documents version 3.0.3 as its current release.
     */
    public function testVersionProperty(): void
    {
        $socket = new HTTPSocket();
        $this->assertSame('3.0.3', $socket->version);
    }

    /**
     * Tests the default HTTP method is GET.
     *
     * The class defaults to GET for requests when no method is explicitly set.
     */
    public function testDefaultMethodIsGet(): void
    {
        $socket = new HTTPSocket();
        $this->assertSame('GET', $socket->method);
    }

    /**
     * Tests that all expected public properties exist on the class.
     *
     * Uses ReflectionClass to verify the class declares the documented
     * set of public properties for connection state, results, and config.
     */
    public function testPublicPropertiesExist(): void
    {
        $ref = new ReflectionClass(HTTPSocket::class);
        $expectedProperties = [
            'version',
            'method',
            'remote_host',
            'remote_port',
            'remote_uname',
            'remote_passwd',
            'result',
            'result_header',
            'result_body',
            'result_status_code',
            'lastTransferSpeed',
            'bind_host',
            'error',
            'warn',
            'query_cache',
            'doFollowLocationHeader',
            'redirectURL',
            'max_redirects',
            'ssl_setting_message',
            'extra_headers',
        ];

        foreach ($expectedProperties as $prop) {
            $this->assertTrue(
                $ref->hasProperty($prop),
                "Expected public property '{$prop}' not found on HTTPSocket"
            );
            $this->assertTrue(
                $ref->getProperty($prop)->isPublic(),
                "Property '{$prop}' should be public"
            );
        }
    }

    /**
     * Tests that all expected public methods exist on the class.
     *
     * Verifies every documented method is declared and accessible.
     */
    public function testPublicMethodsExist(): void
    {
        $ref = new ReflectionClass(HTTPSocket::class);
        $expectedMethods = [
            'unhtmlentities',
            'connect',
            'bind',
            'set_method',
            'set_login',
            'query',
            'getTransferSpeed',
            'get',
            'get_status_code',
            'add_header',
            'clear_headers',
            'fetch_result',
            'fetch_header',
            'fetch_body',
            'fetch_parsed_body',
            'set_ssl_setting_message',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                $ref->hasMethod($method),
                "Expected public method '{$method}' not found on HTTPSocket"
            );
            $this->assertTrue(
                $ref->getMethod($method)->isPublic(),
                "Method '{$method}' should be public"
            );
        }
    }

    /**
     * Tests that unhtmlentities is a static method.
     *
     * The method is declared static so it can be called without an instance.
     */
    public function testUnhtmlentitiesIsStatic(): void
    {
        $ref = new ReflectionClass(HTTPSocket::class);
        $this->assertTrue($ref->getMethod('unhtmlentities')->isStatic());
    }

    /**
     * Tests the connect() method sets remote_host and remote_port.
     *
     * Verifying the basic connection setup stores the right host/port values.
     */
    public function testConnectSetsHostAndPort(): void
    {
        $socket = new HTTPSocket();
        $socket->connect('example.com', 8080);

        $this->assertSame('example.com', $socket->remote_host);
        $this->assertSame(8080, $socket->remote_port);
    }

    /**
     * Tests that connect() defaults port to 80 when a non-numeric value is passed.
     *
     * The connect method validates the port and falls back to 80 for non-numeric input.
     */
    public function testConnectDefaultsPortTo80WhenNonNumeric(): void
    {
        $socket = new HTTPSocket();
        $socket->connect('example.com', 'invalid');

        $this->assertSame(80, $socket->remote_port);
    }

    /**
     * Tests that connect() defaults port to 80 when no port is provided.
     *
     * The empty string default triggers the non-numeric fallback.
     */
    public function testConnectDefaultsPortWhenEmpty(): void
    {
        $socket = new HTTPSocket();
        $socket->connect('example.com');

        $this->assertSame(80, $socket->remote_port);
    }

    /**
     * Tests set_method() stores the method in uppercase.
     *
     * The method should normalize to uppercase regardless of input casing.
     */
    public function testSetMethodUppercases(): void
    {
        $socket = new HTTPSocket();
        $socket->set_method('post');

        $this->assertSame('POST', $socket->method);
    }

    /**
     * Tests set_method() defaults to GET when called without arguments.
     *
     * The default parameter value is 'GET'.
     */
    public function testSetMethodDefaultsToGet(): void
    {
        $socket = new HTTPSocket();
        $socket->set_method('POST');
        $socket->set_method();

        $this->assertSame('GET', $socket->method);
    }

    /**
     * Tests set_login() stores username and password.
     *
     * Credentials are stored for use in subsequent queries.
     */
    public function testSetLoginStoresCredentials(): void
    {
        $socket = new HTTPSocket();
        $socket->set_login('admin', 'secret');

        $this->assertSame('admin', $socket->remote_uname);
        $this->assertSame('secret', $socket->remote_passwd);
    }

    /**
     * Tests set_login() does not set username when empty string is passed.
     *
     * An empty username should not overwrite a previously set value.
     */
    public function testSetLoginIgnoresEmptyUsername(): void
    {
        $socket = new HTTPSocket();
        $socket->remote_uname = 'existing';
        $socket->set_login('', 'newpass');

        $this->assertSame('existing', $socket->remote_uname);
        $this->assertSame('newpass', $socket->remote_passwd);
    }

    /**
     * Tests set_login() does not set password when empty string is passed.
     *
     * An empty password should not overwrite a previously set value.
     */
    public function testSetLoginIgnoresEmptyPassword(): void
    {
        $socket = new HTTPSocket();
        $socket->remote_passwd = 'existing';
        $socket->set_login('newuser', '');

        $this->assertSame('newuser', $socket->remote_uname);
        $this->assertSame('existing', $socket->remote_passwd);
    }

    /**
     * Tests add_header() stores a custom header key-value pair.
     *
     * Extra headers are included with the next HTTP query.
     */
    public function testAddHeader(): void
    {
        $socket = new HTTPSocket();
        $socket->add_header('X-Custom', 'value123');

        $this->assertSame('value123', $socket->extra_headers['X-Custom']);
    }

    /**
     * Tests add_header() overwrites an existing header with the same key.
     *
     * Duplicate keys should result in the latest value being used.
     */
    public function testAddHeaderOverwritesExisting(): void
    {
        $socket = new HTTPSocket();
        $socket->add_header('X-Custom', 'first');
        $socket->add_header('X-Custom', 'second');

        $this->assertSame('second', $socket->extra_headers['X-Custom']);
    }

    /**
     * Tests clear_headers() removes all custom headers.
     *
     * After clearing, the extra_headers array should be empty.
     */
    public function testClearHeaders(): void
    {
        $socket = new HTTPSocket();
        $socket->add_header('X-One', '1');
        $socket->add_header('X-Two', '2');
        $socket->clear_headers();

        $this->assertEmpty($socket->extra_headers);
    }

    /**
     * Tests get_status_code() returns the stored status code.
     *
     * Directly setting the property and reading it through the getter.
     */
    public function testGetStatusCode(): void
    {
        $socket = new HTTPSocket();
        $socket->result_status_code = 200;

        $this->assertSame(200, $socket->get_status_code());
    }

    /**
     * Tests get_status_code() returns null by default.
     *
     * Before any query, the status code is not set.
     */
    public function testGetStatusCodeDefaultsToNull(): void
    {
        $socket = new HTTPSocket();
        $this->assertNull($socket->get_status_code());
    }

    /**
     * Tests fetch_result() returns the raw result.
     *
     * The result property holds the full HTTP response (header + body).
     */
    public function testFetchResult(): void
    {
        $socket = new HTTPSocket();
        $socket->result = 'full response content';

        $this->assertSame('full response content', $socket->fetch_result());
    }

    /**
     * Tests fetch_body() returns the response body.
     *
     * The body is the portion of the response after the header.
     */
    public function testFetchBody(): void
    {
        $socket = new HTTPSocket();
        $socket->result_body = 'body content here';

        $this->assertSame('body content here', $socket->fetch_body());
    }

    /**
     * Tests fetch_parsed_body() parses a query-string-style body into an array.
     *
     * The method uses parse_str internally, converting "key=value&key2=value2"
     * into an associative array.
     */
    public function testFetchParsedBody(): void
    {
        $socket = new HTTPSocket();
        $socket->result_body = 'error=0&text=Success&details=Created';

        $parsed = $socket->fetch_parsed_body();

        $this->assertIsArray($parsed);
        $this->assertSame('0', $parsed['error']);
        $this->assertSame('Success', $parsed['text']);
        $this->assertSame('Created', $parsed['details']);
    }

    /**
     * Tests fetch_parsed_body() returns empty array for empty body.
     *
     * An empty string parsed produces an empty array.
     */
    public function testFetchParsedBodyEmpty(): void
    {
        $socket = new HTTPSocket();
        $socket->result_body = '';

        $parsed = $socket->fetch_parsed_body();
        $this->assertIsArray($parsed);
        $this->assertEmpty($parsed);
    }

    /**
     * Tests fetch_header() with no argument returns all parsed headers.
     *
     * The method splits the raw result_header into an associative array.
     */
    public function testFetchHeaderReturnsArray(): void
    {
        $socket = new HTTPSocket();
        $socket->result_header = "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nX-Custom: hello\r\n\r\n";

        $headers = $socket->fetch_header();

        $this->assertIsArray($headers);
        $this->assertSame('HTTP/1.1 200 OK', $headers[0]);
        $this->assertSame('text/html', $headers['content-type']);
        $this->assertSame('hello', $headers['x-custom']);
    }

    /**
     * Tests fetch_header() with a specific header name returns that header's value.
     *
     * Passing a header name returns just that single value.
     */
    public function testFetchHeaderReturnsSpecificHeader(): void
    {
        $socket = new HTTPSocket();
        $socket->result_header = "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n";

        $contentType = $socket->fetch_header('Content-Type');
        $this->assertSame('text/html', $contentType);
    }

    /**
     * Tests getTransferSpeed() returns the stored transfer speed.
     *
     * The speed is recorded in KB/s after a query completes.
     */
    public function testGetTransferSpeed(): void
    {
        $socket = new HTTPSocket();
        $socket->lastTransferSpeed = 150.5;

        $this->assertSame(150.5, $socket->getTransferSpeed());
    }

    /**
     * Tests set_ssl_setting_message() updates the SSL message.
     *
     * This message is shown when x-use-https header is encountered.
     */
    public function testSetSslSettingMessage(): void
    {
        $socket = new HTTPSocket();
        $socket->set_ssl_setting_message('Use SSL please');

        $this->assertSame('Use SSL please', $socket->ssl_setting_message);
    }

    /**
     * Tests default property values after construction.
     *
     * Verifies arrays default to empty, booleans to expected values,
     * and numeric limits are set properly.
     */
    public function testDefaultPropertyValues(): void
    {
        $socket = new HTTPSocket();

        $this->assertSame([], $socket->error);
        $this->assertSame([], $socket->warn);
        $this->assertSame([], $socket->query_cache);
        $this->assertTrue($socket->doFollowLocationHeader);
        $this->assertSame(5, $socket->max_redirects);
        $this->assertSame([], $socket->extra_headers);
    }

    /**
     * Tests bind() stores the IP address.
     *
     * The bind_host is used with CURLOPT_INTERFACE for outgoing connections.
     */
    public function testBindStoresIp(): void
    {
        $socket = new HTTPSocket();
        $socket->bind('192.168.1.1');

        $this->assertSame('192.168.1.1', $socket->bind_host);
    }

    /**
     * Tests that the HTTPSocket source file exists and is readable.
     *
     * Static analysis check to ensure the source file is present.
     */
    public function testSourceFileExists(): void
    {
        $filePath = dirname(__DIR__) . '/src/HTTPSocket.php';
        $this->assertFileExists($filePath);
    }

    /**
     * Tests that the HTTPSocket source file contains the class declaration.
     *
     * Static analysis: verifies the file declares the expected class.
     */
    public function testSourceFileContainsClassDeclaration(): void
    {
        $filePath = dirname(__DIR__) . '/src/HTTPSocket.php';
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('class HTTPSocket', $content);
        $this->assertStringContainsString('namespace Detain\\MyAdminDirectAdminStorage', $content);
    }

    /**
     * Tests that the HTTPSocket source uses curl functions.
     *
     * Static analysis: the query method relies on curl for HTTP communication.
     */
    public function testSourceFileUsesCurl(): void
    {
        $filePath = dirname(__DIR__) . '/src/HTTPSocket.php';
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('curl_init', $content);
        $this->assertStringContainsString('curl_setopt', $content);
        $this->assertStringContainsString('curl_exec', $content);
        $this->assertStringContainsString('curl_close', $content);
    }

    /**
     * Tests the total number of public methods via reflection.
     *
     * Ensures no methods have been accidentally removed or added.
     */
    public function testMethodCount(): void
    {
        $ref = new ReflectionClass(HTTPSocket::class);
        $publicMethods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

        // Filter to only methods declared in HTTPSocket (not inherited)
        $ownMethods = array_filter($publicMethods, function ($m) {
            return $m->getDeclaringClass()->getName() === HTTPSocket::class;
        });

        $this->assertCount(16, $ownMethods);
    }

    /**
     * Tests the connect method signature has two parameters.
     *
     * The connect method accepts host (required) and port (optional).
     */
    public function testConnectMethodSignature(): void
    {
        $ref = new ReflectionClass(HTTPSocket::class);
        $method = $ref->getMethod('connect');
        $params = $method->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('host', $params[0]->getName());
        $this->assertSame('port', $params[1]->getName());
        $this->assertTrue($params[1]->isOptional());
    }

    /**
     * Tests the query method signature has three parameters.
     *
     * The query method accepts request (required), content (optional), and doSpeedCheck (optional).
     */
    public function testQueryMethodSignature(): void
    {
        $ref = new ReflectionClass(HTTPSocket::class);
        $method = $ref->getMethod('query');
        $params = $method->getParameters();

        $this->assertCount(3, $params);
        $this->assertSame('request', $params[0]->getName());
        $this->assertSame('content', $params[1]->getName());
        $this->assertSame('doSpeedCheck', $params[2]->getName());
        $this->assertTrue($params[1]->isOptional());
        $this->assertTrue($params[2]->isOptional());
    }

    /**
     * Tests that multiple headers can be stored independently.
     *
     * Each header key maintains its own value in the extra_headers array.
     */
    public function testMultipleHeaders(): void
    {
        $socket = new HTTPSocket();
        $socket->add_header('Authorization', 'Bearer token');
        $socket->add_header('Content-Type', 'application/json');
        $socket->add_header('X-Request-Id', '12345');

        $this->assertCount(3, $socket->extra_headers);
        $this->assertSame('Bearer token', $socket->extra_headers['Authorization']);
        $this->assertSame('application/json', $socket->extra_headers['Content-Type']);
        $this->assertSame('12345', $socket->extra_headers['X-Request-Id']);
    }

    /**
     * Tests that set_method supports HEAD method.
     *
     * HEAD is a valid HTTP method that the class should accept.
     */
    public function testSetMethodHead(): void
    {
        $socket = new HTTPSocket();
        $socket->set_method('HEAD');

        $this->assertSame('HEAD', $socket->method);
    }

    /**
     * Tests connect with SSL prefix host.
     *
     * SSL hosts should be stored as-is in remote_host (query() handles conversion).
     */
    public function testConnectWithSslHost(): void
    {
        $socket = new HTTPSocket();
        $socket->connect('ssl://example.com', 2222);

        $this->assertSame('ssl://example.com', $socket->remote_host);
        $this->assertSame(2222, $socket->remote_port);
    }

    /**
     * Tests that fetch_parsed_body handles URL-encoded values correctly.
     *
     * Characters like spaces encoded as + should be decoded properly.
     */
    public function testFetchParsedBodyUrlEncoded(): void
    {
        $socket = new HTTPSocket();
        $socket->result_body = 'name=John+Doe&city=New+York';

        $parsed = $socket->fetch_parsed_body();

        $this->assertSame('John Doe', $parsed['name']);
        $this->assertSame('New York', $parsed['city']);
    }
}
