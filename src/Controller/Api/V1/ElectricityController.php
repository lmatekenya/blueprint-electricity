<?php

namespace App\Controller\Api\V1;

use App\Dto\V1\PurchaseElectricityRequest;
use App\Dto\V1\VerifyCustomerRequest;
use App\Service\RemoteLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1', name: 'api_v1_')]
class ElectricityController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'limiter.electricity_verify')] private RateLimiterFactory $verifyLimiter,
        private RemoteLogger $logger
    ) {
    }

    #[Route('/electricity/verify-customer', name: 'electricity_verify_customer', methods: ['POST'])]
    public function verifyCustomer(Request $request, ValidatorInterface $validator): JsonResponse
    {
        // Rate limit per client IP
        $limiter = $this->verifyLimiter->create($request->getClientIp() ?? 'anonymous');
        $limit = $limiter->consume(1);
        if (!$limit->isAccepted()) {
            return $this->json(
                ['message' => 'Too many requests'],
                JsonResponse::HTTP_TOO_MANY_REQUESTS
            );
        }

        try {
            $data = $request->toArray();
        } catch (\Throwable $e) {
            return $this->json(['errors' => [['message' => 'Invalid JSON body']]], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dto = new VerifyCustomerRequest();
        $dto->transID = $data['transID'] ?? null;
        $dto->meterNumber = $data['meterNumber'] ?? null;
        $dto->amount = $data['amount'] ?? null;
        $dto->email = $data['email'] ?? null;
        $dto->password = $data['password'] ?? null;

        $violations = $validator->validate($dto);
        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            return $this->json(['errors' => $errors], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->logger->info('Electricity verify requested', [
            'transID' => $dto->transID,
            'meterNumber' => $dto->meterNumber,
            'amount' => $dto->amount,
        ]);

        // Simulated verification results
        $results = [
            'customerName' => 'John Doe',
            'meterNumber' => $dto->meterNumber,
            'valid' => true,
        ];

        $response = [
            'results' => $results,
            'api_token' => hash('sha256', $dto->email . '|verify'),
            'elec_token' => 'electricity_token_test123',
        ];

        return $this->json($response, JsonResponse::HTTP_OK);
    }

    #[Route('/electricity/purchase', name: 'electricity_purchase', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function purchase(Request $request, ValidatorInterface $validator): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (\Throwable $e) {
            return $this->json(['errors' => [['message' => 'Invalid JSON body']]], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dto = new PurchaseElectricityRequest();
        $dto->transID = $data['transID'] ?? null;
        $dto->meterNumber = $data['meterNumber'] ?? null;
        $dto->amount = $data['amount'] ?? null;
        $dto->elec_token = $data['elec_token'] ?? null;

        $violations = $validator->validate($dto);
        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            return $this->json(['errors' => $errors], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->logger->info('Electricity purchase requested', [
            'transID' => $dto->transID,
            'meterNumber' => $dto->meterNumber,
            'amount' => $dto->amount,
            'user' => $this->getUser()?->getUserIdentifier(),
        ]);

        // Simulated purchase processing
        $receiptNo = 'RC' . (string) random_int(100000, 999999);
        $units = (int) floor((float) $dto->amount);

        $results = [
            'status' => 'success',
            'meterNumber' => $dto->meterNumber,
            'amount' => (float) $dto->amount,
        ];

        $receiptItems = [
            'receiptNo' => $receiptNo,
            'units' => $units,
            'provider' => 'Smartplan',
        ];

        return $this->json([
            'results' => $results,
            'receiptItems' => $receiptItems,
        ], JsonResponse::HTTP_OK);
    }
}
