<?php
require __DIR__.'/../vendor/autoload.php';

use App\Telegram\TelegramApiImpl;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Инициализация логгера
$logger = new Logger('poll');
$logger->pushHandler(new StreamHandler('php://stdout'));

try {
    $bot = new TelegramApiImpl(null, $logger);
    $offset = file_exists('last_offset.txt') ? (int)file_get_contents('last_offset.txt') : 0;

    $updates = $bot->getMessages($offset);

    // Сохраняем новый offset
    file_put_contents('last_offset.txt', $updates['offset']);

    // Обработка сообщений
    foreach ($updates['result'] as $chatId => $messages) {
        foreach ($messages as $text) {
            $bot->sendMessage($chatId, "Ответ на: $text");
        }
    }

    echo json_encode(['processed' => count($updates['result'])]);
} catch (Throwable $e) {
    $logger->error('Poll error', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}