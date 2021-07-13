<?php

namespace bronsted;

class AppServerConfig
{
    public string $baseUrl;
    public string $tokenAppServer;
    public string $tokenHomeServer;
    public string $domain;

    public function __construct(string $baseUrl, string $tokenAppServer, string $tokenHomeServer, string $domain)
    {
        $this->baseUrl = $baseUrl;
        $this->tokenAppServer = $tokenAppServer;
        $this->tokenHomeServer = $tokenHomeServer;
        $this->domain = $domain;
    }
}