<?php

namespace bronsted;

use Exception;

class Room
{
    private string $id;
    private string $alias;
    private string $name;
    private array $members;

    public function __construct(string $id, string $alias, string $name, array $members)
    {
        $this->id = $id;
        $this->alias = $alias;
        $this->name = $name;
        $this->members = $members;
        $this->validate();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMembers(): array
    {
        return $this->members;
    }

    public static function create(MatrixClient $client, AppServiceConfig $config, string $subject, string $name, User $creator, bool $direct): Room
    {
        if (!$client->hasUser($creator)) {
            $client->createUser($creator);
        }
        $alias = self::toAlias($config, $subject);
        $id = $client->createRoom($name, $alias, $creator, $direct);
        $room = new Room($id, $alias, $name, [$creator]);
        return $room;
    }

    public function hasMember(User $user): bool
    {
        foreach ($this->members as $member) {
            if ($member->getId() == $user->getId()) {
                return true;
            }
        }
        return false;
    }

    public static function getBySubject(MatrixClient $client, AppServiceConfig $config, string $subject): Room
    {
        try {
            $alias = self::toAlias($config, $subject);
            $id = $client->getRoomIdByAlias($alias);
            $name = $client->getRoomName($id);
            $members = $client->getRoomMembers($id);
            return new Room($id, $alias, $name, $members);
        } catch (Exception $e) {
            if ($e->getCode() == 404) {
                throw new NotFoundException(__CLASS__);
            }
            throw $e;
        }
    }

    public static function getById(MatrixClient $client, AppServiceConfig $config, string $id)
    {
        try {
            $name = $client->getRoomName($id);
            $members = $client->getRoomMembers($id);

            try {
                // A matrix room create user by, does nessecary have and alias
                // This need because the service depsends on aliases for rooms
                $alias = $client->getRoomAlias($id);
            } catch (Exception $e) {
                if ($e->getCode() == 404) {
                    $alias = self::toAlias($config, $name);
                    $client->setRoomAlias($id, $alias);
                }
                else {
                    throw $e;
                }
            }
            return new Room($id, $alias, $name, $members);
        } catch (Exception $e) {
            if ($e->getCode() == 404) {
                throw new NotFoundException(__CLASS__);
            }
            throw $e;
        }
    }

    public function addUser(MatrixClient $client, User $user, Account $account): void
    {
        if (!$this->hasMember($user)) {
            $client->createUser($user);
            $client->invite($this, $user, $account);
            $client->join($this, $user);
            $this->members[] = $user;
        }
    }

    public static function toAlias(AppServiceConfig $config, string $name)
    {
        $sanitized = strtolower(str_replace(' ', '-', trim($name)));
        return sprintf("#mail_%s:%s", $sanitized, $config->domain);
    }

    private function validate()
    {
        foreach (['id', 'alias', 'name', 'members'] as $name) {
            if (empty($this->$name)) {
                throw new Exception("$name must not be empty");
            }
        }
        if ($this->alias[0] != '#') {
            throw new Exception("Wrong format for alias");
        }
    }
}
