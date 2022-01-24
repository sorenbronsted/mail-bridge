<?php

namespace bronsted;

use Exception;
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
        $accessToken = null;
        // Is it part of the request?
        $args = $request->getQueryParams();
        if (empty($args)) {
            // Is it a Bearer token?
            $args = $request->getHeader('Authorization');
            if (!empty($args)) {
                // Format: Bearer: <value>
                $parts = explode(':', $args[0]);
                if (count($parts) >= 2) {
                    $accessToken = $parts[1];
                }
            }
        }
        else {
            $accessToken = $args['access_token'];
        }
        if (empty($accessToken)) {
            return $this->responseFactory->createResponse(403);
        }
        if (!in_array($accessToken, $this->config->tokenGuest)) {
            return $this->responseFactory->createResponse(401);
        }
        return $handler->handle($request);
    }
}
