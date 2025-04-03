<?php

namespace App\Telegram;

use Psr\Log\LoggerInterface;
use RuntimeException;

class TelegramApiImpl implements TelegramApi
{
    private const ENDPOINT = 'https://api.telegram.org/bot';
    private const TIMEOUT = 30; // Таймаут для getUpdates (секунды)

    private string $token;
    private ?LoggerInterface $logger;

    public function __construct(string $token = null, ?LoggerInterface $logger = null)
    {
        $this->token = $token ?? $this->getTokenFromEnv();
        $this->logger = $logger;
    }

    private function getTokenFromEnv(): string
    {
        $token = $_ENV['TELEGRAM_BOT_TOKEN'] ?? getenv('TELEGRAM_BOT_TOKEN');
        if (empty($token)) {
            throw new RuntimeException('Telegram token not found in environment');
        }
        return $token;
    }

    public function getMessages(int $offset): array
    {
        $url = self::ENDPOINT . $this->token . '/getUpdates';
        $result = [];
        $lastUpdateId = $offset;

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "{$url}?offset={$offset}&timeout=" . self::TIMEOUT,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => self::TIMEOUT + 5,
            ]);

            $response = json_decode(curl_exec($ch), true);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$response['ok']) {
                $this->logger?->error('Telegram API error', ['response' => $response]);
                return ['offset' => $offset, 'result' => []];
            }

            foreach ($response['result'] as $update) {
                $chatId = $update['message']['chat']['id'] ?? null;
                $text = $update['message']['text'] ?? null;
                if ($chatId && $text) {
                    $result[$chatId][] = $text;
                }
                $lastUpdateId = $update['update_id'] + 1;
            }

        } catch (\Throwable $e) {
            $this->logger?->error('getMessages failed', ['error' => $e->getMessage()]);
        }

        return [
            'offset' => $lastUpdateId,
            'result' => $result,
        ];
    }

    public function sendMessage(string $chatId, string $text): void
    {
        $url = self::ENDPOINT . $this->token . '/sendMessage';

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'chat_id' => $chatId,
                    'text' => $text,
                ]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
            ]);

            $response = json_decode(curl_exec($ch), true);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$response['ok']) {
                $this->logger?->error('sendMessage failed', ['response' => $response]);
            }

        } catch (\Throwable $e) {
            $this->logger?->error('sendMessage error', ['error' => $e->getMessage()]);
        }
    }

    public function handleWebhook(): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    $chatId = $input['message']['chat']['id'] ?? null;
    $text = $input['message']['text'] ?? null;

    if ($chatId && $text) {
        $this->sendMessage($chatId, "Webhook received: $text");
    }
}

public function pollUpdates(): void
{
    $offset = 0; // В реальном коде сохраняйте offset между запусками
    $updates = $this->getMessages($offset);

    foreach ($updates['result'] as $chatId => $messages) {
        foreach ($messages as $text) {
            $this->sendMessage($chatId, "Poll response: $text");
        }
    }
}
}