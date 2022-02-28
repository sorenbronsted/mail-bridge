<?php

namespace bronsted;

use Exception;
use React\Http\Browser;
use Slim\Psr7\Factory\StreamFactory;
use stdClass;

class HttpTest extends TestCase
{
    public function testGetOk()
    {
        $fixture = new stdClass();
        $fixture->key = 'value';

        $mock = $this->mock(Browser::class);
        $mock->method('get')->willReturn($this->createPromiseResolved($this->createResponse(200, json_encode($fixture))));

        $http = $this->container->get(Http::class);
        $result = $http->get('/somewhere');
        $this->assertEquals($fixture, $result);
    }

    public function testGetFail()
    {
        $mock = $this->mock(Browser::class);
        $mock->method('get')->willReturn($this->createPromiseResolved($this->createResponse(500)));
        $http = $this->container->get(Http::class);

        $this->expectExceptionCode(500);
        $http->get('/somewhere');
    }

    public function testPutAndPostOk()
    {
        $fixture = new stdClass();
        $fixture->key = 'value';

        $mock = $this->mock(Browser::class);
        $http = $this->container->get(Http::class);
        foreach (['put', 'post'] as $method) {
            $mock->method($method)->willReturn($this->createPromiseResolved($this->createResponse(200, json_encode((object)[]))));
            $result = $http->$method('/somewhere', $fixture);
            $this->assertEquals((object)[], $result, $method);
        }
    }

    public function testPutAndPostFail()
    {
        $fixture = new stdClass();
        $fixture->key = 'value';

        $mock = $this->mock(Browser::class);
        $http = $this->container->get(Http::class);
        foreach (['put', 'post'] as $method) {
            $mock->method($method)->willReturn($this->createPromiseResolved($this->createResponse(500)));
            try {
                $http->$method('/somewhere', $fixture);
            } catch (Exception $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function testPostStreamOk()
    {
        $fixture = new stdClass();
        $fixture->key = 'value';

        $mock = $this->mock(Browser::class);
        $mock->method('post')->willReturn($this->createPromiseResolved($this->createResponse(200, json_encode((object)[]))));

        $http = $this->container->get(Http::class);
        $stream = (new StreamFactory())->createStream(json_encode($fixture));
        $result = $http->postStream('/somewhere', 'application/json', $stream);
        $this->assertEquals((object)[], $result);
    }

    public function testPostStreamFail()
    {
        $fixture = new stdClass();
        $fixture->key = 'value';

        $mock = $this->mock(Browser::class);
        $mock->method('post')->willReturn($this->createPromiseResolved($this->createResponse(500)));

        $http = $this->container->get(Http::class);

        $stream = (new StreamFactory())->createStream(json_encode($fixture));
        $this->expectExceptionCode(500);
        $http->postStream('/somewhere', 'application/json', $stream);
    }

    public function testGetStreamOk()
    {
        $fixture = new stdClass();
        $fixture->key = 'value';

        $mock = $this->mock(Browser::class);
        $mock->method('get')->willReturn(
            $this->createPromiseResolved(
                $this->createResponse(
                    200,
                    (new StreamFactory())->createStream(json_encode($fixture))
                )
            )
        );

        $http = $this->container->get(Http::class);
        $result = json_decode(
            $http->getStream('/somewhere/file.json')->getContents()
        );
        $this->assertEquals($fixture, $result);
    }

    public function testGetStreamFail()
    {
        $fixture = new stdClass();
        $fixture->key = 'value';

        $mock = $this->mock(Browser::class);
        $mock->method('get')->willReturn(
            $this->createPromiseResolved(
                $this->createResponse(
                    500,
                    (new StreamFactory())
                        ->createStream(json_encode($fixture))
                )
            )
        );

        $http = $this->container->get(Http::class);
        $this->expectExceptionCode(500);
        $result = json_decode(
            $http->getStream('/somewhere/file.json')->getContents()
        );
    }
}
