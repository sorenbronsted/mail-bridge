<?php 
namespace bronsted;
require 'vendor/autoload.php';


/* Less secure apps needs be enabled to be able to login
   https://support.google.com/accounts/answer/6010255?hl=en
 */

$server = '{imap.gmail.com:993/imap/ssl}INBOX';
$login  = 'sorenbronsted@gmail.com';
$password = '112Bytes.';

$connection = imap_open($server, $login, $password);
$count = imap_num_msg($connection);
echo "Current count $count\n";
if ($count > 100) {
    $count = 100;
}

for($i = 1; $i <= $count; $i++) {
    echo ".";
    $filename = $i . '.mime';
    $source = imap_fetchheader($connection, $i) . imap_body($connection, $i);
    file_put_contents(__DIR__ . '/mails/' . $filename, $source);
}
echo "\n";
imap_close($connection);