<?php

namespace bronsted;

use DI\Container;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use stdClass;
use UnexpectedValueException;

/**
 * App Test Trait.
 */
trait AppTestTrait
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var App
     */
    protected $app;

    /**
     * Bootstrap app.
     *
     * @throws UnexpectedValueException
     *
     * @return void
     */
    protected function boot(): void
    {
        $this->app = bootstrap();
        $container = $this->app->getContainer();
        if ($container === null) {
            throw new UnexpectedValueException('Container must be initialized');
        }
        $this->container = $container;
    }

    /**
     * Add mock to container.
     *
     * @param string $class The class or interface
     *
     * @return MockObject The mock
     */
    protected function mock(string $class): MockObject
    {
        if (!(class_exists($class) || interface_exists($class))) {
            throw new InvalidArgumentException(sprintf('Class not found: %s', $class));
        }

        $mock = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->set($class, $mock);

        return $mock;
    }

    /**
     * Create a server request.
     *
     * @param string $method The HTTP method
     * @param string|UriInterface $uri The URI
     * @param array $serverParams The server parameters
     *
     * @return ServerRequestInterface
     */
    protected function createRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface {
        return (new ServerRequestFactory())->createServerRequest($method, $uri, $serverParams);
    }

    /**
     * Create a server response.
     *
     * @param int $code
     * @param string $returnValue
     * @return ResponseInterface
     * @throws RuntimeException
     */
    protected function createResponse(int $code, string $returnValue = ''): ResponseInterface
    {
        $stream = (new StreamFactory())->createStream($returnValue);
        return $this->app->getResponseFactory()->createResponse($code)->withBody($stream);
    }

    /**
     * Create a JSON request.
     *
     * @param string $method The HTTP method
     * @param string|UriInterface $uri The URI
     * @param array|null $data The json data
     *
     * @return ServerRequestInterface
     */
    protected function createJsonRequest(string $method, $uri, array $data = null): ServerRequestInterface {
        $request = $this->createRequest($method, $uri);

        if ($data !== null) {
            $request = $request->withParsedBody($data);
        }

        return $request->withHeader('Content-Type', 'application/json');
    }

    /**
     * Verify that the given array is an exact match for the JSON returned.
     *
     * @param array $expected The expected array
     * @param ResponseInterface $response The response
     *
     * @throws JsonException
     * @return void
     */
    protected function assertJsonData(stdClass $expected, ResponseInterface $response): void
    {
        $actual = (string)$response->getBody();
        //$this->assertSame($expected, json_decode($actual, true, 512, JSON_THROW_ON_ERROR));
        $this->assertEquals($expected, json_decode($actual));
    }
}
