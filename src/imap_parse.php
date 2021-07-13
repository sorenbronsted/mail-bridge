<?php

namespace bronsted;

require dirname(__DIR__) . '/vendor/autoload.php';

use ZBateson\MailMimeParser\Message;

$app = bootstrap();
$http = $app->getContainer()->get(Http::class);
$user = $app->getContainer()->get(User::class);

for ($i = 1; $i <= 100; $i++) {
    $filename = $i . '.mime';
    $fh = fopen(dirname(__DIR__) . '/mails/' . $filename, 'r');
    $message = Message::from($fh);

    $to = $message->getHeader('to');

    $subject = $message->getHeader('subject');
    if (is_object($subject)) {
        $subject = $subject->getValue();
    }

    $idx = strrpos($subject, ':');
    if ($idx !== false) {
        $subject = trim(substr($subject, $idx + 1));
    }
    if (empty($subject)) {
        $subject = 'Unknown';
    }

    $room = null;
    $from = $message->getHeader('from')->getAddresses()[0];
    if (empty($from->getEmail())) {
        continue;
    }
    $from = User::getOrCreate($http, $from->getName(), $from->getEmail());

    if (count($to->getAddresses()) > 1) {
        try {
            $room = Room::getOneBy(['name' => $subject]);
            if (!$room->hasMember($from)) {
                $room->invite($http, $from); // rate limit
            }
            foreach($to->getAddresses() as $address) {
                $member = User::getOrCreate($http, $address->getName(), $address->getEmail());
                if (!$room->hasMember($member)) {
                    $room->invite($http, $member); // rate limit
                }
            }
        }
        catch(NotFoundException $e) {
            $invitations = [];
            foreach($to->getAddresses() as $address) {
                $member = User::getOrCreate($http, $address->getName(), $address->getEmail());
                $invitations[] = $member;
            }
            $room = Room::create($http, $subject, $from, $invitations);

        }
        echo $i . " room: " . $subject . ', participants: ' . count($to->getAddresses()) . PHP_EOL;
    } else {
        try {
            $name = $from->name;
            if (empty($name)) {
                $name = $from->email;
            }
            $room = Room::getOneBy(['name' => $name]);
        }
        catch(NotFoundException $e) {
            $invitations = [$user];
            $room = Room::create($http, $name, $from, $invitations);
        }
        echo $i . " direct: " . $from->name . ', ' . $from->email . ', ' . $subject . PHP_EOL;
    }

    if ($room) {
        for ($j = 0; $j < $message->getTextPartCount(); $j++) {
            $room->send($http, $from, $message->getTextContent($j), $message->getHtmlContent($j));
        }
    }

    fclose($fh);
}
