<?php

namespace bronsted;

use Psr\Http\Message\MessageInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use stdClass;
use Throwable;

class AppService
{
    private AppServerConfig $config;
    private LoggerInterface $log;
    private Http $http;

    public function __construct(LoggerInterface $log, AppServerConfig $source, Http $http)
    {
        $this->log = $log;
        $this->config = $source;
        $this->http = $http;
    }

    public function events(Request $request, Response $response, array $args): MessageInterface
    {
        $data = (object)$request->getParsedBody();
        $args = (object)$args;

        try {
            $this->consumeEvents($args->txnId, $data);
        } catch (Throwable $t) {
            $this->log->error($t->getMessage());
        }

        $response->getBody()->write(json_encode(new stdClass()));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function hasUser(Request $request, Response $response, array $args): MessageInterface
    {
        $args = (object)$args;
        $this->validateCredentials($request);
        try {
            $user = User::getOneBy(['id' => $args->userId]);
            $response->getBody()->write('{}');
            return $response->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            return $response->withStatus(404);
        }
    }

    public function hasRoom(Request $request, Response $response, array $args): MessageInterface
    {
        $args = (object)$args;
        $this->validateCredentials($request);
        try {
            Room::getOneBy(['alias' => $args->roomAlias]);
            $response->getBody()->write('{}');
            return $response->withHeader('Content-Type', 'application/json');
        } catch (NotFoundException $e) {
            return $response->withStatus(404);
        }
    }

    private function validateCredentials(Request $request)
    {
        $args = (object)$request->getQueryParams();
        //401 = missing credentials
        if (!isset($args->access_token)) {
            throw new CredentialException('Missing token', 401);
        }
        //403 = wrong credentials
        if ($args->access_token != $this->config->tokenHomeServer) {
            throw new CredentialException('Wrong token', 403);
        }
    }

    private function consumeEvents(string $txnId, ?stdClass $events = null)
    {
        if ($events == null) {
            return;
        }

        /* capture events
        $file = __DIR__ . '/data/events/' . $txnId . '.json';
        file_put_contents($file, json_encode($events));
        */

        foreach ($events->events as $event) {
            if (!isset($event->type)) {
                continue;
            }
            // This will perform an auto join of user/puppets
            if ($event->type == 'm.room.member' && $event->content->membership == 'invite' && strpos($event->state_key, '@mail_') !== false) {
                // Don't use model objects because this will cause a syncronisation problem.
                $url = '/_matrix/client/r0/rooms/' . urlencode($event->room_id) . '/join?user_id=' . urlencode($event->state_key);
                $data  = new stdClass();
                $this->http->post($url, $data);
            }
        }
    }
}
