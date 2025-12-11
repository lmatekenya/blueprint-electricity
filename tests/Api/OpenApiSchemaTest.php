<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OpenApiSchemaTest extends WebTestCase
{
    public function testV1EndpointsAreDocumented(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/docs.json');

        $this->assertResponseIsSuccessful();
        $spec = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($spec);
        $this->assertArrayHasKey('paths', $spec);

        $paths = $spec['paths'];
        $this->assertArrayHasKey('/api/v1/auth/login', $paths);
        $this->assertArrayHasKey('/api/v1/auth/me', $paths);
        $this->assertArrayHasKey('/api/v1/electricity/verify-customer', $paths);
        $this->assertArrayHasKey('/api/v1/electricity/purchase', $paths);
    }
}
