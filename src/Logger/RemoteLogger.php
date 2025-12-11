<?php

namespace App\Logger;

use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

class RemoteLogger implements LoggerInterface
{
    use LoggerTrait;

    private $logServerUrl;
    private $logServerToken;
    private $client;
    private $fallbackLogger;

    public function __construct(
        string $logServerUrl,
        string $logServerToken,
        LoggerInterface $fallbackLogger = null
    ) {
        $this->logServerUrl = $logServerUrl;
        $this->logServerToken = $logServerToken;
        $this->client = HttpClient::create();
        $this->fallbackLogger = $fallbackLogger;
    }

    public function log($level, $message, array $context = []): void
    {
        // Log locally as fallback if fallback logger is provided
        if ($this->fallbackLogger) {
            $this->fallbackLogger->log($level, $message, $context);
        }

        // Send to remote log server (optional - can be disabled)
        if ($this->logServerUrl && $this->logServerToken) {
            try {
                $this->client->request('POST', $this->logServerUrl . '/api/logs', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->logServerToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'level' => $level,
                        'message' => $message,
                        'context' => $context,
                        'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                        'service' => 'blueprint-electricity-api',
                        'environment' => $_ENV['APP_ENV'] ?? 'unknown',
                    ],
                    'timeout' => 2,
                ]);
            } catch (\Exception $e) {
                // Silently fail
                if ($this->fallbackLogger) {
                    $this->fallbackLogger->error('Failed to send log to remote server: ' . $e->getMessage());
                }
            }
        }
    }
}
