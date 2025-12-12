<?php

namespace App\Tests\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ElectricityControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testVerifyCustomer(): void
    {
        $this->client->request('POST', '/api/v1/electricity/verify-customer', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'transID' => 'TXN_TEST_001',
            'meterNumber' => '12345678901',
            'amount' => 50.00,
            'email' => 'user@example.com',
            'password' => 'user123'
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('results', $response);
        $this->assertArrayHasKey('api_token', $response);
        $this->assertArrayHasKey('elec_token', $response);
    }

    public function testPurchaseElectricity(): void
    {
        // First, login to get token
        $this->client->request('POST', '/api/v1/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'user@smartplan.com',
            'password' => 'user123'
        ]));

        $loginResponse = json_decode($this->client->getResponse()->getContent(), true);
        $token = $loginResponse['data']['api_token'];

        // Then purchase electricity
        $this->client->request('POST', '/api/v1/electricity/purchase', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ], json_encode([
            'transID' => 'TXN_TEST_002',
            'meterNumber' => '12345678901',
            'amount' => 50.00,
            'elec_token' => 'electricity_token_test123'
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('results', $response);
        $this->assertArrayHasKey('receiptItems', $response);
    }

    public function testRateLimiting(): void
    {
        $token = 'test_token_' . bin2hex(random_bytes(16));

        // Make many requests quickly
        for ($i = 0; $i < 110; $i++) {
            $this->client->request('POST', '/api/v1/electricity/verify-customer', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'transID' => 'TXN_TEST_' . $i,
                'meterNumber' => '12345678901',
                'amount' => 50.00,
                'email' => 'user@smartplan.com',
                'password' => 'user123'
            ]));
        }

        // Last request should be rate limited
        $this->assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
    }

    public function testInvalidSchema(): void
    {
        // Missing required field
        $this->client->request('POST', '/api/v1/electricity/verify-customer', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'transID' => 'TXN_TEST_001',
            'meterNumber' => '12345678901',
            // Missing amount field
            'email' => 'user@smartplan.com',
            'password' => 'user123'
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
    }
}
