<?php

namespace bronsted;

use Psr\Http\Message\StreamInterface;
use React\Http\Browser;
use function Clue\React\Block\await;
use Exception;
use React\Promise\Deferred;
use stdClass;

class Http
{
    private Browser $client;
    public AppServiceConfig $config;

    public function __construct(AppServiceConfig $config, Browser $client)
    {
        $this->client = $client;
        $this->config = $config;
        $this->header = ['Authorization' => 'Bearer ' . $config->tokenMine];
    }

    public function postStream(string $url, string $contentType, $stream): stdClass
    {
        $this->header['Content-Type'] = $contentType;
        $response = await($this->client->post($this->config->matrixUrl . $url, $this->header, $stream));
        return json_decode($response->getBody()->getContents());
    }

    public function getStream(string $url): StreamInterface
    {
        $urlParts = parse_url($url);
        $response = await($this->client->get($this->config->matrixUrl . $urlParts['path'], $this->header));
        return $response->getBody();
    }

    public function post(string $url, stdClass $data): stdClass
    {
        $this->header['Content-Type'] = 'application/json';
        $response = await($this->client->post($this->config->matrixUrl . $url, $this->header, json_encode($data)));
        return json_decode($response->getBody()->getContents());
    }

    public function put(string $url, stdClass $data): stdClass
    {
        $this->header['Content-Type'] = 'application/json';
        $response = await($this->client->put($this->config->matrixUrl . $url, $this->header, json_encode($data)));
        return json_decode($response->getBody()->getContents());
    }

    public function get(string $url): stdClass
    {
        try {
            $response = await($this->client->get($this->config->matrixUrl . $url, $this->header));
        }
        catch(Exception $e) {
            throw $e;
        }
        return json_decode($response->getBody()->getContents());
    }
}
