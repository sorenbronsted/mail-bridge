<?php

namespace bronsted;

use DI\Container;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

class UserAuthenticateCtrl
{
    private AppServiceConfig $config;
    private ResponseFactoryInterface $responseFactory;
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $this->container->get(AppServiceConfig::class);
        $this->responseFactory = $this->container->get(ResponseFactory::class);
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookies = $request->getCookieParams();
        if (!isset($cookies[$this->config->cookieName])) {
            return $this->responseFactory->createResponse(403);
        }
        //TODO P2 jwt cookie
        $id = $cookies[$this->config->cookieName];
        $user = User::fromId($id, 'TODO P2 fixme');
        $this->container->set(User::class, $user);
        return $handler->handle($request);
    }
}
