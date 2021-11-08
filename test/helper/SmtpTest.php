<?php

namespace bronsted;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Slim\Psr7\Factory\StreamFactory;
use stdClass;

use function PHPUnit\Framework\equalTo;

class SmtpTest extends TestCase
{
    private object $mailerMock;
    private object $httpMock;
    private Smtp $smtp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mailerMock = $this->mock(PHPMailer::class);
        $this->mailerMock->expects($this->once())->method('isSMTP');
        $this->httpMock = $this->mock(Http::class);
        $this->smtp = $this->container->get(Smtp::class);
    }

    public function testOpen()
    {
        $accountData = Fixtures::accountData();
        $this->smtp->open($accountData);
        $this->assertEquals($accountData->smtp_host, $this->mailerMock->Host);
        $this->assertEquals($accountData->user, $this->mailerMock->Username);
        $this->assertEquals($accountData->password, $this->mailerMock->Password);
        $this->assertEquals(true, $this->mailerMock->SMTPAuth);
        $this->assertEquals(465, $this->mailerMock->Port);
        $this->assertEquals(PHPMailer::ENCRYPTION_SMTPS, $this->mailerMock->SMTPSecure);
    }

    public function testSetFrom()
    {
        $user = Fixtures::user();
        $this->mailerMock->method('setFrom')->with($this->equalTo($user->email), $this->equalTo($user->name));
        $this->smtp->from($user);
        $this->assertTrue(true);
    }

    public function testAddRecipient()
    {
        $user = Fixtures::user();
        $users = User::getAll();
        $this->mailerMock->method('addAddress')->with($this->equalTo($user->email, $user->name));
        $this->smtp->addRecipients($users);
        $this->assertTrue(true);
    }

    public function testSubject()
    {
        $this->smtp->subject('test');
        $this->assertEquals('test', $this->mailerMock->Subject);
    }

    public function testBodyPlain()
    {
        $this->mailerMock->method('isHtml')->with(false);
        $this->smtp->body('test');
        $this->assertEquals('test', $this->mailerMock->Body);
    }

    public function testBodyPlainAndHtml()
    {
        $this->mailerMock->method('isHtml')->with(true);
        $this->smtp->body('test', 'html');
        $this->assertEquals('html', $this->mailerMock->Body);
        $this->assertEquals('test', $this->mailerMock->AltBody);
    }

    public function testSend()
    {
        $this->mailerMock->expects($this->once())->method('send');
        $this->smtp->send();
    }

    public function testCanConnect()
    {
        $accountData = Fixtures::accountData();
        $this->mailerMock->expects($this->once())->method('smtpConnect');
        $this->smtp->canConnect($accountData);
    }

    public function testAddAttachment()
    {
        $path = '/my-path';
        $name = 'deleteme';
        $this->httpMock->expects($this->once())
            ->method('getStream')
            ->with($this->equalTo($path))
            ->willReturn((new StreamFactory())->createStream('Hello World'));

        $this->mailerMock->expects($this->once())->method('addAttachment');

        $this->smtp->addAttachment($name, $path);
    }

    public function testSendByAccount()
    {
        $user = Fixtures::user();
        $recipient = Fixtures::user();
        $accountData = Fixtures::accountData();

        $data = new stdClass();
        $data->sender = $user;
        $data->recipients = [$recipient];
        $data->subject = 'test';
        $data->accountData = $accountData;
        $data->event = Fixtures::event();

        $this->mailerMock->expects($this->once())->method('send');
        $this->smtp->sendByAccount($data);
    }

    public function testSendByAccountWithAttachments()
    {
        $user = Fixtures::user();
        $recipient = Fixtures::user();
        $accountData = Fixtures::accountData();

        $data = new stdClass();
        $data->sender = $user;
        $data->recipients = [$recipient];
        $data->subject = 'test';
        $data->accountData = $accountData;
        $data->event = Fixtures::eventUrl();

        $this->mailerMock->expects($this->once())->method('addAttachment');
        $this->mailerMock->expects($this->once())->method('send');
        $this->smtp->sendByAccount($data);
    }

    public function testSendByAccountWithUnknownEvent()
    {
        $user = Fixtures::user();
        $recipient = Fixtures::user();
        $accountData = Fixtures::accountData();

        $data = new stdClass();
        $data->sender = $user;
        $data->recipients = [$recipient];
        $data->subject = 'test';
        $data->accountData = $accountData;
        $data->event = Fixtures::eventUnknown();

        $this->mailerMock->expects($this->never())->method('addAttachment');
        $this->mailerMock->expects($this->never())->method('send');
        $this->expectException(Exception::class);
        $this->smtp->sendByAccount($data);
    }
}