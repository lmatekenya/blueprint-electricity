<?php

namespace App\Service\ExternalApi;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class CraftApiService
{
    private $client;
    private $logger;
    private $baseUrl;
    private $username;
    private $password;

    public function __construct(HttpClientInterface $client, LoggerInterface $logger, string $baseUrl, string $username, string $password)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->baseUrl = $baseUrl;
        $this->username = $username;
        $this->password = $password;
    }

    private function getAuthToken(): ?string
    {
        $url = $this->baseUrl . '/rest/authentication/login';

        $payload = [
            'identity' => $this->username,
            'credential' => $this->password,
            'identityType' => 'USERNAME'
        ];

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                // Fail fast to prevent hitting PHP's max_execution_time
                'connect_timeout' => 3,
                'timeout' => 5,
                'max_duration' => 5,
            ]);

            $headers = $response->getHeaders();
            $token = $headers['x-auth-token'][0] ?? ($headers['X-Auth-Token'][0] ?? null);

            return $token;

        } catch (\Exception $e) {
            $this->logger->error('CraftAPI authentication failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function executeTransaction(string $meterNumber, float $amount, string $transId, bool $isVerification = false): array
    {
        $authToken = $this->getAuthToken();

        if (!$authToken) {
            return [
                'success' => false,
                'error' => 'Authentication failed',
                'statusCode' => Response::HTTP_UNAUTHORIZED
            ];
        }

        $url = $this->baseUrl . '/rest/transaction/execute';

        $payload = [
            'txObjectId' => 271,
            'threadId' => $isVerification ? 72 : 71,
            'scopeId' => 116,
            'requesterIdentification' => [
                'identityType' => 'ANONYMOUS'
            ],
            'answerDeviceId' => 'craft_silicon',
            'answerTransactionId' => $transId,
            'parameters' => [
                'integration.kazang.hidden.productId' => '20004',
                'integration.kazang.MeterNumber' => $meterNumber,
                'SALE_VALUE' => $amount
            ]
        ];

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $authToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
                // Fail fast to avoid hitting PHP's 30s max_execution_time
                'connect_timeout' => 3,
                'timeout' => 5,
                'max_duration' => 5,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray();

            $this->logger->info('CraftAPI transaction executed', [
                'transId' => $transId,
                'isVerification' => $isVerification,
                'statusCode' => $statusCode
            ]);

            return [
                'success' => true,
                'data' => $content,
                'statusCode' => $statusCode
            ];

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('CraftAPI transaction failed', [
                'transId' => $transId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage(),
                'statusCode' => Response::HTTP_SERVICE_UNAVAILABLE
            ];
        } catch (\Exception $e) {
            $this->logger->error('CraftAPI transaction error', [
                'transId' => $transId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Transaction failed: ' . $e->getMessage(),
                'statusCode' => Response::HTTP_BAD_REQUEST
            ];
        }
    }
}
