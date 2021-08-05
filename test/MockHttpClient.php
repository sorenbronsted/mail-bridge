<?php

namespace bronsted;

use JustSteveKing\HttpSlim\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;

class MockHttpClient implements HttpClientInterface
{
    private static $responses = [];

    public static function add(ResponseInterface $response)
    {
        self::$responses[] = $response;
    }

    public function get(string $uri, array $headers = []): ResponseInterface
    {
        return array_shift(self::$responses);
    }

    public function post(string $uri, array $body, array $headers = []): ResponseInterface
    {
        return array_shift(self::$responses);
    }

    public function put(string $uri, array $body, array $headers = []): ResponseInterface
    {
        return array_shift(self::$responses);
    }

    public function patch(string $uri, array $body, array $headers = []): ResponseInterface
    {
        return array_shift(self::$responses);
    }

    public function delete(string $uri, array $headers = []): ResponseInterface
    {
        return array_shift(self::$responses);
    }
}
