<?php
namespace App;

use Dotenv\Dotenv;
use RuntimeException;

class Environment
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->loadEnv();
    }

    protected function loadEnv(): void
    {
        // Если .env существует (локальная разработка) – загружаем его
        if (file_exists(__DIR__.'/../.env')) {
            $dotenv = Dotenv::createImmutable(__DIR__.'/..');
            $dotenv->safeLoad(); // safeLoad() не выбрасывает исключение, если .env отсутствует
        }

        // Проверяем, что хотя бы один способ передачи переменных работает
        if (empty($_ENV['TELEGRAM_BOT_TOKEN']) && !getenv('TELEGRAM_BOT_TOKEN')) {
            throw new RuntimeException('Telegram token not found in environment');
        }
    }

    public function get(string $key, $default = null)
    {
        // Проверяем переменные в порядке приоритета:
        // 1. $_ENV (загружено из .env или Vercel)
        // 2. getenv() (системные переменные окружения)
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}