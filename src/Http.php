<?php

namespace bronsted;

use Exception;
use JustSteveKing\HttpSlim\HttpClient;
use stdClass;
use Symfony\Component\HttpClient\Psr18Client;

class Http
{
    private HttpClient $client;
    public AppServerConfig $config;

    public function __construct(AppServerConfig $config, HttpClient $client)
    {
        $this->client = $client;
        $this->config = $config;
        $this->header = ['Authorization' => 'Bearer ' . $config->tokenAppServer];
    }

    public function postStream(string $url, string $contentType, $stream): stdClass
    {
        //TODO: https://matrix.org/docs/spec/client_server/latest#post-matrix-media-r0-upload og HttpClient

        $requestFactory = new Psr18Client();
        $request = $requestFactory->createRequest('POST', $this->config->baseUrl . $url);
        foreach (array_merge($this->header, ['Content-Type' => $contentType]) as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        $request = $request->withBody($stream);

        $client = $this->client->getClient();
        $response = $client->sendRequest($request);
        $code = $response->getStatusCode();
        if ($code != 200) {
            throw new Exception("Request failed: " . $url, $code);
        }
        return json_decode($response->getBody()->getContents());
    }

    public function post(string $url, stdClass $data, array $additional = []): stdClass
    {
        $response = $this->client->post($this->config->baseUrl . $url, (array)$data, array_merge($this->header, $additional));
        $code = $response->getStatusCode();
        if ($code != 200) {
            throw new Exception("Http request message failed with: " . $code);
        }
        return json_decode($response->getBody()->getContents());
    }

    public function put(string $url, stdClass $data): stdClass
    {
        $response = $this->client->put($this->config->baseUrl . $url, (array)$data, $this->header);
        $code = $response->getStatusCode();
        if ($code != 200) {
            throw new Exception("Http request message failed with: " . $code);
        }
        return json_decode($response->getBody()->getContents());
    }

    public function get(string $url): stdClass
    {
        $response = $this->client->get($this->config->baseUrl. $url, $this->header);
        $code = $response->getStatusCode();
        if ($code != 200) {
            throw new Exception("Request failed: " . $url, $code);
        }
        return json_decode($response->getBody()->getContents());
    }
}