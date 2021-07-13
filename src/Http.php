<?php

namespace bronsted;

use Exception;
use GuzzleHttp\Client;
use stdClass;

class Http
{
    private Client $client;
    public AppServerConfig $config;

    public function __construct(AppServerConfig $config, Client $client)
    {
        $this->client = $client;
        $this->config = $config;
        $this->header = ['Authorization' => 'Bearer ' . $config->tokenAppServer];
    }

    public function post(string $url, stdClass $data): stdClass
    {
        $response = $this->client->post($this->config->baseUrl . $url, ['headers' => $this->header, 'json' => $data]);
        $code = $response->getStatusCode();
        if ($code != 200) {
            throw new Exception("Http request message failed with: " . $code);
        }
        return json_decode($response->getBody()->getContents());
    }

    public function put(string $url, stdClass $data): stdClass
    {
        $response = $this->client->put($this->config->baseUrl . $url, ['headers' => $this->header, 'json' => $data]);
        $code = $response->getStatusCode();
        if ($code != 200) {
            throw new Exception("Http request message failed with: " . $code);
        }
        return json_decode($response->getBody()->getContents());
    }

    public function get(string $url): stdClass
    {
        $response = $this->client->get($this->config->baseUrl. $url, ['headers' => $this->header]);
        $code = $response->getStatusCode();
        if ($code != 200) {
            throw new Exception("Request failed: " . $url, $code);
        }
        return json_decode($response->getBody()->getContents());
    }
}