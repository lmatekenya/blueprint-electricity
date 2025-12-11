<?php

namespace App\Service\ExternalApi;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class PrepaidPlusService
{
    private $client;
    private $logger;
    private $baseUrl;
    private $authToken;

    public function __construct(HttpClientInterface $client, LoggerInterface $logger, string $baseUrl, string $authToken)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->baseUrl = $baseUrl;
        $this->authToken = $authToken;
    }

    public function verifyCustomer(string $meterNumber, float $amount, string $transId): array
    {
        $url = $this->baseUrl . '/api/trialcreditvendApiKey';

        $payload = [
            'meterNumber' => $meterNumber,
            'transactionAmount' => $amount,
            'clientSaleId' => $transId,
            'createdBy' => 'SmartPlan BluePrint'
        ];

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Basic ' . $this->authToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                // Fail fast to avoid hitting PHP's 30s max_execution_time
                'connect_timeout' => 3,
                'timeout' => 5,
                'max_duration' => 5,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray();

            $this->logger->info('PrepaidPlus verification successful', [
                'transId' => $transId,
                'statusCode' => $statusCode,
                'response' => $content
            ]);

            return [
                'success' => true,
                'data' => $content,
                'statusCode' => $statusCode
            ];

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('PrepaidPlus verification failed', [
                'transId' => $transId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage(),
                'statusCode' => Response::HTTP_SERVICE_UNAVAILABLE
            ];
        } catch (\Exception $e) {
            $this->logger->error('PrepaidPlus verification error', [
                'transId' => $transId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Verification failed: ' . $e->getMessage(),
                'statusCode' => Response::HTTP_BAD_REQUEST
            ];
        }
    }

    public function purchaseElectricity(string $meterNumber, float $amount, string $transId): array
    {
        $url = $this->baseUrl . '/api/bpcSaleApiKey';

        $payload = [
            'meterNumber' => $meterNumber,
            'transactionAmount' => $amount,
            'clientSaleId' => $transId,
            'createdBy' => 'Standard Chartered'
        ];

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Basic ' . $this->authToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                // Fail fast to avoid hitting PHP's 30s max_execution_time
                'connect_timeout' => 3,
                'timeout' => 5,
                'max_duration' => 5,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray();

            $this->logger->info('PrepaidPlus purchase successful', [
                'transId' => $transId,
                'statusCode' => $statusCode,
                'receiptNo' => $content['creditVendReceipt']['receiptNo'] ?? null
            ]);

            return [
                'success' => true,
                'data' => $content,
                'statusCode' => $statusCode
            ];

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('PrepaidPlus purchase failed', [
                'transId' => $transId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage(),
                'statusCode' => Response::HTTP_SERVICE_UNAVAILABLE
            ];
        } catch (\Exception $e) {
            $this->logger->error('PrepaidPlus purchase error', [
                'transId' => $transId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Purchase failed: ' . $e->getMessage(),
                'statusCode' => Response::HTTP_BAD_REQUEST
            ];
        }
    }
}
