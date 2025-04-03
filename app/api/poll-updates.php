<?php
require __DIR__ . '/../vendor/autoload.php';

$token = $_ENV['TELEGRAM_BOT_TOKEN'];
$bot = new App\Telegram\TelegramApiImpl($token);
$bot->getMessages();