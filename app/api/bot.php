<?php
require __DIR__.'/../vendor/autoload.php';

use App\Telegram\TelegramApiImpl;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Инициализация логгера
$logger = new Logger('bot');
$logger->pushHandler(new StreamHandler('php://stdout'));

try {
    $bot = new TelegramApiImpl(null, $logger);

    $input = json_decode(file_get_contents('php://input'), true);
    $chatId = $input['message']['chat']['id'] ?? null;
    $text = $input['message']['text'] ?? null;

    if ($chatId && $text) {
        $bot->sendMessage($chatId, "Вы сказали: $text");
    }

    echo json_encode(['status' => 'ok']);
} catch (Throwable $e) {
    $logger->error('Webhook error', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}