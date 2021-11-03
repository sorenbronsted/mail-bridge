<?php

namespace bronsted;

use PHPMailer\PHPMailer\PHPMailer;
use stdClass;

class SmtpTest extends TestCase
{
    private object $mock;
    private Smtp $smtp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock = $this->mock(PHPMailer::class);
        $this->mock->expects($this->once())->method('isSMTP');
        $this->smtp = new Smtp($this->mock);
    }

    public function testOpen()
    {
        $accountData = Fixtures::accountData();
        $this->smtp->open($accountData);
        $this->assertEquals($accountData->smtp_host, $this->mock->Host);
        $this->assertEquals($accountData->user, $this->mock->Username);
        $this->assertEquals($accountData->password, $this->mock->Password);
        $this->assertEquals(true, $this->mock->SMTPAuth);
        $this->assertEquals(465, $this->mock->Port);
        $this->assertEquals(PHPMailer::ENCRYPTION_SMTPS, $this->mock->SMTPSecure);
    }

    public function testSetFrom()
    {
        $user = Fixtures::user();
        $this->mock->method('setFrom')->with($this->equalTo($user->email), $this->equalTo($user->name));
        $this->smtp->from($user);
        $this->assertTrue(true);
    }

    public function testAddRecipient()
    {
        $user = Fixtures::user();
        $users = User::getAll();
        $this->mock->method('addAddress')->with($this->equalTo($user->email, $user->name));
        $this->smtp->addRecipients($users);
        $this->assertTrue(true);
    }

    public function testSubject()
    {
        $this->smtp->subject('test');
        $this->assertEquals('test', $this->mock->Subject);
    }

    public function testBodyPlain()
    {
        $this->mock->method('isHtml')->with(false);
        $this->smtp->body('test');
        $this->assertEquals('test', $this->mock->Body);
    }

    public function testBodyPlainAndHtml()
    {
        $this->mock->method('isHtml')->with(true);
        $this->smtp->body('test', 'html');
        $this->assertEquals('html', $this->mock->Body);
        $this->assertEquals('test', $this->mock->AltBody);
    }

    public function testSend()
    {
        $this->mock->expects($this->once())->method('send');
        $this->smtp->send();
    }

    public function testCanConnect()
    {
        $accountData = Fixtures::accountData();
        $this->mock->expects($this->once())->method('smtpConnect');
        $this->smtp->canConnect($accountData);
    }

    public function testSendByAccount()
    {
        $config = $this->container->get(AppServiceConfig::class);
        $user = Fixtures::user();
        $recipient = Fixtures::user();
        $account = Fixtures::account($user);
        $accountData = Fixtures::accountData();
        $account->setAccountData($config, $accountData);

        $data = new stdClass();
        $data->sender = $user;
        $data->recipients = [$recipient];
        $data->subject = 'test';
        $data->text = 'plain text';
        $data->html = 'plain html';
        $data->account = $account;

        $this->mock->expects($this->once())->method('send');
        $this->smtp->sendByAccount($config, $data);
    }
}