<?php

namespace bronsted;

use HansOtt\PSR7Cookies\SetCookie;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Throwable;

class AppServiceCtrl
{
    private MatrixClient $client;
    private AppServiceConfig $config;
    private Http $http;
    private FileStore $store;

    public function __construct(MatrixClient $client, AppServiceConfig $config, Http $http, FileStore $store)
    {
        $this->client = $client;
        $this->config = $config;
        $this->http = $http;
        $this->store = $store;
    }

    public function loginToken(ServerRequestInterface $request, ResponseInterface $response): MessageInterface
    {
        $params = (object)$request->getQueryParams();
        if (!isset($params->id)) {
            return $response->withStatus(422);
        }
        $token = Crypto::encrypt($params->id, $this->config->key);
        $response->getBody()->write(json_encode(['token' => $token]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): MessageInterface
    {
        $params = (object)$request->getQueryParams();
        if (!isset($params->token)) {
            return $response->withStatus(422);
        }

        $id = Crypto::decrypt($params->token, $this->config->key);
        //TODO P2 jwt cookie
        $cookie = new SetCookie($this->config->cookieName, $id, time() + 60 * 60 * 24 * 30 * 12, '/', 'localhost', true, true, 'lax');
        $response = $cookie->addToResponse($response);
        return $response->withHeader('Location', '/account')->withStatus(302);
    }


    public function events(ServerRequestInterface $request, ResponseInterface $response, string $txnId): MessageInterface
    {
        $data = (object)$request->getParsedBody();
        try {
            $this->consumeEvents($txnId, $data);
        } catch (Throwable $t) {
            Log::error($t->getMessage());
        }

        $response->getBody()->write(json_encode(new stdClass()));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function consumeEvents(string $txnId, ?stdClass $events = null)
    {
        if ($events == null) {
            return;
        }

        /* capture events */
        $file = __DIR__ . '/data/events/' . $txnId . '.json';
        //file_put_contents($file, json_encode($events));


        foreach ($events->events as $event) {
            if (!isset($event->type)) {
                continue;
            }

            if (User::isPuppet($event->sender)) {
                continue;
            }

            if ($event->type == 'm.room.message') {
                Mail::createFromEvent($this->client, $this->config, $this->http, $this->store, $event);
            }
        }
    }
}
