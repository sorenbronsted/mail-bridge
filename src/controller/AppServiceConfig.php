<?php

namespace bronsted;

use Exception;
use ReflectionClass;
use Symfony\Component\Yaml\Yaml;

class AppServiceConfig
{
    public string $matrixUrl;
    public string $tokenMine;
    public array  $tokenGuest;
    public string $domain;
    public string $key;
    public string $cookieName;
    public string $fileStoreRoot;
    public string $databaseUrl;

    public function __construct(string $yamlFilename)
    {
        $data = Yaml::parseFile($yamlFilename);
        if (empty($data)) {
            throw new Exception('Invalid content');
        }
        $data = (object)$data;
        $ref = new ReflectionClass(self::class);
        foreach($ref->getProperties() as $prop) {
            $name = $prop->getName();
            if (!isset($data->$name)) {
                throw new Exception("Missing config: $name");
            }
            $this->$name = $data->$name;
        }
    }
}