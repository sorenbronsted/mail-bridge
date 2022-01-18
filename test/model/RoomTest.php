<?php

namespace bronsted;

use Exception;
use stdClass;
use ZBateson\MailMimeParser\Header\Part\AddressPart;
use ZBateson\MbWrapper\MbWrapper;

class RoomTest extends TestCase
{
    private AppServiceConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(MatrixClient::class);
        $this->config = $this->container->get(AppServiceConfig::class);
    }

    public function testCreate()
    {
        $id = '#some-id:nowhere';
        $alias = 'some-alias';
        $name = 'My room';
        $user = Fixtures::puppet($this->config->domain);

        $mock = $this->container->get(MatrixClient::class);
        $mock->method('createRoom')->willReturn($id);
        $members = [Fixtures::puppet($this->config->domain)];
        $mock->method('getRoomMembers')->willReturn($members);

        $creator = Fixtures::puppet($this->config->domain);
        $room = Room::create($mock, $alias, $name, $creator, false);
        $this->assertEquals($id, $room->getId());
        $this->assertEquals($alias, $room->getAlias());
        $this->assertEquals($name, $room->getName());
        $this->assertEquals(1, count($room->getMembers()));
        $this->assertEquals($user, $room->getMembers()[0]);
    }

    public function testHasMember()
    {
        $mock = $this->container->get(MatrixClient::class);
        $user = Fixtures::puppet($this->config->domain);
        $room = Fixtures::room($mock, $this->config->domain, $user);
        $this->assertTrue($room->hasMember($user));
        $this->assertFalse($room->hasMember(new User('@baz:bar.com', 'Baz Bar')));
    }

    public function testGetByAliasOk()
    {
        $id = '#some-id:nowhere';
        $alias = 'some-alias';
        $name = 'My room';
        $user = Fixtures::puppet($this->config->domain);

        $mock = $this->container->get(MatrixClient::class);
        $mock->method('getRoomIdByAlias')->willReturn($id);
        $mock->method('getRoomName')->willReturn($name);
        $mock->method('getRoomMembers')->willReturn([$user]);

        $room = Room::getByAlias($mock, $alias);
        $this->assertEquals($id, $room->getId());
        $this->assertEquals($alias, $room->getAlias());
        $this->assertEquals($name, $room->getName());
        $this->assertEquals(1, count($room->getMembers()));
        $this->assertEquals($user, $room->getMembers()[0]);
    }

    public function testGetByAliasNotFound()
    {
        $mock = $this->container->get(MatrixClient::class);
        $mock->method('getRoomIdByAlias')->willThrowException(new Exception('Some remote error', 404));
        $this->expectException(NotFoundException::class);
        Room::getByAlias($mock, 'some-alias');
    }

    public function testGetByAliasFail()
    {
        $mock = $this->container->get(MatrixClient::class);
        $mock->method('getRoomIdByAlias')->willThrowException(new Exception('Some remote error', 500));
        $this->expectExceptionCode(500);
        Room::getByAlias($mock, 'some-alias');
    }

    public function testGetByIdOk()
    {
        $id = '#some-id:nowhere';
        $alias = 'some-alias';
        $name = 'My room';
        $user = Fixtures::puppet($this->config->domain);

        $mock = $this->container->get(MatrixClient::class);
        $mock->method('getRoomAlias')->willReturn($alias);
        $mock->method('getRoomName')->willReturn($name);
        $mock->method('getRoomMembers')->willReturn([$user]);

        $room = Room::getById($mock, $id);
        $this->assertEquals($id, $room->getId());
        $this->assertEquals($alias, $room->getAlias());
        $this->assertEquals($name, $room->getName());
        $this->assertEquals(1, count($room->getMembers()));
        $this->assertEquals($user, $room->getMembers()[0]);
    }

    public function testGetByIdMissingAlias()
    {
        $id = '#some-id:nowhere';
        $name = 'My room';
        $alias = Room::toAlias($name);
        $user = Fixtures::puppet($this->config->domain);

        $mock = $this->container->get(MatrixClient::class);
        $mock->method('getRoomAlias')->willThrowException(new Exception('Not found', 404));
        $mock->method('getRoomName')->willReturn($name);
        $mock->method('getRoomMembers')->willReturn([$user]);

        $room = Room::getById($mock, $id);
        $this->assertEquals($id, $room->getId());
        $this->assertEquals($alias, $room->getAlias());
        $this->assertEquals($name, $room->getName());
        $this->assertEquals(1, count($room->getMembers()));
        $this->assertEquals($user, $room->getMembers()[0]);
    }

    public function testGetByIdNotFound()
    {
        $mock = $this->container->get(MatrixClient::class);
        $mock->method('getRoomName')->willThrowException(new Exception('Not found', 404));
        $this->expectException(NotFoundException::class);
        Room::getByid($mock, '1');
    }

    public function testGetByIdFailOnName()
    {
        $mock = $this->container->get(MatrixClient::class);
        $mock->method('getRoomName')->willThrowException(new Exception('Some remote error', 500));
        $this->expectExceptionCode(500);
        Room::getByid($mock, '1');
    }

    public function testGetByIdFailOnAlias()
    {
        $mock = $this->container->get(MatrixClient::class);
        $mock->method('getRoomAlias')->willThrowException(new Exception('Some remote error', 500));
        $this->expectExceptionCode(500);
        Room::getByid($mock, '1');
    }

    public function testAddUser()
    {
        $name = 'Baz Bar';
        $email = 'baz@bar.com';
        $newUser = User::fromMail(new AddressPart(new MbWrapper, $name, $email), $this->config->domain);

        $mock = $this->container->get(MatrixClient::class);
        $mock->method('invite');
        $mock->method('join');

        $room = Fixtures::room($mock, $this->config->domain);
        $account = Fixtures::account(Fixtures::puppet($this->config->domain));
        $room->addUser($newUser, $account);

        $this->assertEquals(2, count($room->getMembers()));
    }

    public function testInvalidRoomProperties()
    {
        $mock = $this->container->get(MatrixClient::class);
        $this->expectExceptionMessageMatches('/empty/');
        new Room($mock, '', '', '', []);
    }
}