<?php

use App\Application;
use Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';

// Загружаем .env, если файл существует (для локальной разработки)
if (file_exists(dirname(__DIR__).'/.env')) {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

// Создаём экземпляр приложения
$app = new Application(dirname(__DIR__));

// Инициализируем Environment (если нужно)
$app->bind('env', function() use ($app) {
    return new \App\Environment($app);
});

return $app;