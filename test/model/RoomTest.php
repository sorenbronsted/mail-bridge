<?php

namespace bronsted;

use Exception;
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
        $subject = 'some-subject';
        $name = 'My room';
        $user = Fixtures::puppet($this->config);

        $mock = $this->container->get(MatrixClient::class);
        $mock->method('createRoom')->willReturn($id);
        $members = [Fixtures::puppet($this->config)];
        $mock->method('getRoomMembers')->willReturn($members);

        $creator = Fixtures::puppet($this->config);
        $room = Room::create($mock, $this->config, $subject, $name, $creator, false);
        $this->assertEquals($id, $room->getId());
        $this->assertStringContainsString($subject, $room->getAlias());
        $this->assertEquals($name, $room->getName());
        $this->assertEquals(1, count($room->getMembers()));
        $this->assertEquals($user, $room->getMembers()[0]);
    }

    public function testHasMember()
    {
        $user = Fixtures::puppet($this->config);
        $room = Fixtures::room($this->config, $user);
        $this->assertTrue($room->hasMember($user));
        $this->assertFalse($room->hasMember(new User('@baz:bar.com', 'Baz Bar')));
    }

    public function testGetByAliasOk()
    {
        $id = '#some-id:nowhere';
        $subject = 'some-alias';
        $name = 'My room';
        $user = Fixtures::puppet($this->config);

        $mock = $this->container->get(MatrixClient::class);
        $mock->method('getRoomIdByAlias')->willReturn($id);
        $mock->method('getRoomName')->willReturn($name);
        $mock->method('getRoomMembers')->willReturn([$user]);

        $room = Room::getBySubject($mock, $this->config, $subject);
        $this->assertEquals($id, $room->getId());
        $this->assertStringContainsString($subject, $room->getAlias());
        $this->assertEquals($name, $room->getName());
        $this->assertEquals(1, count($room->getMembers()));
        $this->assertEquals($user, $room->getMembers()[0]);
    }

    public function testGetByAliasNotFound()
    {
        $mock = $this->container->get(MatrixClient::class);
        $mock->method('getRoomIdByAlias')->willThrowException(new Exception('Some remote error', 404));
        $this->expectException(NotFoundException::class);
        Room::getBySubject($mock, $this->config, 'some-alias');
    }

    public function testGetByAliasFail()
    {
        $mock = $this->container->get(MatrixClient::class);
        $mock->method('getRoomIdByAlias')->willThrowException(new Exception('Some remote error', 500));
        $this->expectExceptionCode(500);
        Room::getBySubject($mock, $this->config, 'some-alias');
    }

    public function testGetByIdOk()
    {
        $id = '#some-id:nowhere';
        $subject = 'some-subject';
        $name = 'My room';
        $user = Fixtures::puppet($this->config);

        $mock = $this->container->get(MatrixClient::class);
        $mock->method('getRoomAlias')->willReturn(Room::toAlias($this->config, $subject));
        $mock->method('getRoomName')->willReturn($name);
        $mock->method('getRoomMembers')->willReturn([$user]);

        $room = Room::getById($mock, $this->config, $id);
        $this->assertEquals($id, $room->getId());
        $this->assertStringContainsString($subject, $room->getAlias());
        $this->assertEquals($name, $room->getName());
        $this->assertEquals(1, count($room->getMembers()));
        $this->assertEquals($user, $room->getMembers()[0]);
    }

    public function testGetByIdMissingAlias()
    {
        $id = '#some-id:nowhere';
        $name = 'My room';
        $alias = Room::toAlias($this->config, $name);
        $user = Fixtures::puppet($this->config);

        $mock = $this->container->get(MatrixClient::class);
        $mock->method('getRoomAlias')->willThrowException(new Exception('Not found', 404));
        $mock->method('getRoomName')->willReturn($name);
        $mock->method('getRoomMembers')->willReturn([$user]);

        $room = Room::getById($mock, $this->config, $id);
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
        Room::getById($mock, $this->config, '1');
    }

    public function testGetByIdFailOnName()
    {
        $mock = $this->container->get(MatrixClient::class);
        $mock->method('getRoomName')->willThrowException(new Exception('Some remote error', 500));
        $this->expectExceptionCode(500);
        Room::getById($mock, $this->config, '1');
    }

    public function testGetByIdFailOnAlias()
    {
        $mock = $this->container->get(MatrixClient::class);
        $mock->method('getRoomAlias')->willThrowException(new Exception('Some remote error', 500));
        $this->expectExceptionCode(500);
        Room::getByid($mock, $this->config, '1');
    }

    public function testAddUser()
    {
        $name = 'Baz Bar';
        $email = 'baz@bar.com';
        $newUser = User::fromMail(new AddressPart(new MbWrapper, $name, $email), $this->config);

        $mock = $this->container->get(MatrixClient::class);
        $mock->method('invite');
        $mock->method('join');

        $room = Fixtures::room($this->config);
        $account = Fixtures::account(Fixtures::puppet($this->config));
        $room->addUser($mock, $newUser, $account);

        $this->assertEquals(2, count($room->getMembers()));
    }

    public function testInvalidRoomProperties()
    {
        $mock = $this->container->get(MatrixClient::class);
        $this->expectExceptionMessageMatches('/empty/');
        new Room('', '', '', []);
    }
}