<?php

namespace App\Service;

/**
 * Lightweight remote logger that ships JSON log records to a remote HTTP endpoint.
 * Avoids extra dependencies while remaining safe for production use (best-effort, non-blocking).
 */
class RemoteLogger
{
    private string $endpoint;

    public function __construct(?string $endpoint = null)
    {
        $this->endpoint = $endpoint ?? ($_ENV['LOG_SERVER_URL'] ?? 'http://localhost:9000/logs');
    }

    public function info(string $message, array $context = []): void
    {
        $this->send('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->send('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->send('error', $message, $context);
    }

    private function send(string $level, string $message, array $context): void
    {
        $payload = json_encode([
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'app_env' => $_ENV['APP_ENV'] ?? 'prod',
            'app_name' => $_ENV['APP_NAME'] ?? 'app',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            return; // silently ignore encoding errors
        }

        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $payload,
                'timeout' => 1.5, // don't block requests
            ],
        ];

        try {
            // Fire-and-forget; suppress warnings if remote is down
            @file_get_contents($this->endpoint, false, stream_context_create($opts));
        } catch (\Throwable $e) {
            // Never let logging break the app
        }
    }
}
