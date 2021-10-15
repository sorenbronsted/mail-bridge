<?php

namespace bronsted;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

class ServiceAuthenticateCtrl
{
    private AppServiceConfig $config;
    private ResponseFactoryInterface $responseFactory;

    public function __construct(AppServiceConfig $config, ResponseFactory $responseFactory)
    {
        $this->config = $config;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $args = (object)$request->getQueryParams();
        if (!isset($args->access_token)) {
            return $this->responseFactory->createResponse(403);
        }
        if (!in_array($args->access_token, $this->config->tokenGuest)) {
            return $this->responseFactory->createResponse(401);
        }
        return $handler->handle($request);
    }
}
