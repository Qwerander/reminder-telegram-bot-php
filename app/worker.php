<?php
require __DIR__.'/../vendor/autoload.php';

use App\Telegram\TelegramApiImpl;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('worker');
$logger->pushHandler(new StreamHandler('php://stdout'));

$bot = new TelegramApiImpl(null, $logger);
$offset = 0;

while (true) {
    $updates = $bot->getMessages($offset);
    $offset = $updates['offset'];

    foreach ($updates['result'] as $chatId => $messages) {
        foreach ($messages as $text) {
            $bot->sendMessage($chatId, "Ответ на: $text");
        }
    }

    sleep(1);
}