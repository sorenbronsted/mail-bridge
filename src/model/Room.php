<?php

namespace bronsted;

use Exception;

class Room
{
    private MatrixClient $client;
    private string $id;
    private string $alias;
    private string $name;
    private array $members;

    public function __construct(MatrixClient $client, string $id, string $alias, string $name, array $members)
    {
        $this->client = $client;
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

    public static function create(MatrixClient $client, string $alias, string $name, User $creator, bool $direct): Room
    {
        $id = $client->createRoom($name, $alias, $creator, $direct);
        $room = new Room($client, $id, $alias, $name, [$creator]);
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

    public static function getByAlias(MatrixClient $client, string $alias): Room
    {
        try {
            $id = $client->getRoomIdByAlias($alias);
            $name = $client->getRoomName($id);
            $members = $client->getRoomMembers($id);
            return new Room($client, $id, $alias, $name, $members);
        } catch (Exception $e) {
            if ($e->getCode() == 404) {
                throw new NotFoundException(__CLASS__);
            }
            throw $e;
        }
    }

    public static function getById(MatrixClient $client, string $id)
    {
        try {
            $name = $client->getRoomName($id);
            $alias = $client->getRoomAlias($id);
            $members = $client->getRoomMembers($id);
            return new Room($client, $id, $alias, $name, $members);
        } catch (Exception $e) {
            if ($e->getCode() == 404) {
                throw new NotFoundException(__CLASS__);
            }
        }
    }

    public function addUser(User $user, Account $account): void
    {
        if (!$this->hasMember($user)) {
            $this->client->createUser($user);
            $this->client->invite($this, $user, $account);
            $this->client->join($this, $user);
            $this->members[] = $user;
        }
    }

    private function validate()
    {
        foreach (['id', 'alias', 'name', 'members'] as $name) {
            if (empty($this->$name)) {
                throw new Exception("$name can not be empty");
            }
        }
    }
}
