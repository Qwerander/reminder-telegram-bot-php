<?php
require __DIR__.'/../vendor/autoload.php';

use App\Telegram\TelegramApiImpl;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Логирование
$logger = new Logger('bot');
$logger->pushHandler(new StreamHandler('php://stdout'));

$bot = new TelegramApiImpl(null, $logger);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка вебхука
    $input = json_decode(file_get_contents('php://input'), true);
    $chatId = $input['message']['chat']['id'] ?? null;
    $text = $input['message']['text'] ?? null;

    if ($chatId && $text) {
        $bot->sendMessage($chatId, "Вы сказали: $text");
    }
}