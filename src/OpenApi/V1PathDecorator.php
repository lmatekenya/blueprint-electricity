<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\OpenApi;

class V1PathDecorator implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $components = $openApi->getComponents();

        // Schemas
        $schemas = $components->getSchemas() ?? [];
        $schemas['LoginRequest'] = [
            'type' => 'object',
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email'],
                'password' => ['type' => 'string', 'minLength' => 6, 'maxLength' => 128],
            ],
            'required' => ['email', 'password'],
        ];
        $schemas['VerifyCustomerRequest'] = [
            'type' => 'object',
            'properties' => [
                'transID' => ['type' => 'string', 'minLength' => 5, 'maxLength' => 50],
                'meterNumber' => ['type' => 'string', 'minLength' => 10, 'maxLength' => 20],
                'amount' => ['type' => 'number', 'minimum' => 10],
                'email' => ['type' => 'string', 'format' => 'email'],
                'password' => ['type' => 'string', 'minLength' => 6, 'maxLength' => 128],
            ],
            'required' => ['transID', 'meterNumber', 'amount', 'email', 'password'],
        ];
        $schemas['PurchaseElectricityRequest'] = [
            'type' => 'object',
            'properties' => [
                'transID' => ['type' => 'string', 'minLength' => 5, 'maxLength' => 50],
                'meterNumber' => ['type' => 'string', 'minLength' => 10, 'maxLength' => 20],
                'amount' => ['type' => 'number', 'minimum' => 10],
                'elec_token' => ['type' => 'string', 'minLength' => 8, 'maxLength' => 255],
            ],
            'required' => ['transID', 'meterNumber', 'amount', 'elec_token'],
        ];
        $components = $components->withSchemas($schemas);
        $openApi = $openApi->withComponents($components);

        // Paths
        $paths = $openApi->getPaths();

        // POST /api/v1/auth/login
        $paths->addPath('/api/v1/auth/login', new PathItem(
            ref: 'AuthLogin',
            post: (new Operation(
                operationId: 'postApiV1AuthLogin',
                tags: ['v1', 'Auth'],
                responses: [
                    '200' => [
                        'description' => 'Login successful',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'api_token' => ['type' => 'string'],
                                            ],
                                            'required' => ['api_token'],
                                        ],
                                    ],
                                    'required' => ['data'],
                                ],
                            ],
                        ],
                    ],
                    '400' => ['description' => 'Invalid body'],
                    '401' => ['description' => 'Invalid credentials'],
                ],
                summary: 'Authenticate and obtain API token'
            ))->withRequestBody(new RequestBody(
                description: 'Login payload',
                content: [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/LoginRequest'],
                    ],
                ],
                required: true
            ))
        ));

        // GET /api/v1/auth/me
        $paths->addPath('/api/v1/auth/me', new PathItem(
            ref: 'AuthMe',
            get: new Operation(
                operationId: 'getApiV1AuthMe',
                tags: ['v1', 'Auth'],
                responses: [
                    '200' => [
                        'description' => 'Current auth status',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'authenticated' => ['type' => 'boolean'],
                                                'api_token' => ['type' => 'string', 'nullable' => true],
                                                'email' => ['type' => 'string', 'nullable' => true],
                                            ],
                                            'required' => ['authenticated'],
                                        ],
                                    ],
                                    'required' => ['data'],
                                ],
                            ],
                        ],
                    ],
                ],
                summary: 'Get current session or token details'
            )
        ));

        // POST /api/v1/electricity/verify-customer
        $paths->addPath('/api/v1/electricity/verify-customer', new PathItem(
            ref: 'VerifyCustomer',
            post: (new Operation(
                operationId: 'postApiV1ElectricityVerifyCustomer',
                tags: ['v1', 'Electricity'],
                responses: [
                    '200' => [
                        'description' => 'Verification results',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'results' => ['type' => 'object'],
                                        'api_token' => ['type' => 'string'],
                                        'elec_token' => ['type' => 'string'],
                                    ],
                                    'required' => ['results', 'api_token', 'elec_token'],
                                ],
                            ],
                        ],
                    ],
                    '400' => ['description' => 'Invalid body'],
                    '429' => ['description' => 'Too many requests'],
                ],
                summary: 'Verify customer meter and eligibility'
            ))->withRequestBody(new RequestBody(
                description: 'Verify customer payload',
                content: [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/VerifyCustomerRequest'],
                    ],
                ],
                required: true
            ))
        ));

        // POST /api/v1/electricity/purchase
        $paths->addPath('/api/v1/electricity/purchase', new PathItem(
            ref: 'PurchaseElectricity',
            post: (new Operation(
                operationId: 'postApiV1ElectricityPurchase',
                tags: ['v1', 'Electricity'],
                responses: [
                    '200' => [
                        'description' => 'Purchase results and receipt',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'results' => ['type' => 'object'],
                                        'receiptItems' => ['type' => 'object'],
                                    ],
                                    'required' => ['results', 'receiptItems'],
                                ],
                            ],
                        ],
                    ],
                    '400' => ['description' => 'Invalid body'],
                    '401' => ['description' => 'Unauthorized'],
                ],
                summary: 'Purchase electricity units'
            ))->withRequestBody(new RequestBody(
                description: 'Purchase electricity payload',
                content: [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/PurchaseElectricityRequest'],
                    ],
                ],
                required: true
            ))
        ));

        return $openApi->withPaths($paths);
    }
}
