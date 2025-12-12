<?php
//
//namespace App\Controller\Api\V1;
//
//use App\Dto\V1\PurchaseElectricityRequest;
//use App\Dto\V1\VerifyCustomerRequest;
//use App\Service\RemoteLogger;
//use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
//use Symfony\Component\RateLimiter\RateLimiterFactory;
//use Symfony\Component\DependencyInjection\Attribute\Autowire;
//use Symfony\Component\HttpFoundation\JsonResponse;
//use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\Routing\Annotation\Route;
//use Symfony\Component\Security\Http\Attribute\IsGranted;
//use Symfony\Component\Validator\Validator\ValidatorInterface;
//
//#[Route('/api/v1', name: 'api_v1_')]
//class ElectricityController extends AbstractController
//{
//    public function __construct(
//        #[Autowire(service: 'limiter.electricity_verify')] private RateLimiterFactory $verifyLimiter,
//        private RemoteLogger $logger
//    ) {
//    }
//
//    #[Route('/electricity/verify-customer', name: 'electricity_verify_customer', methods: ['POST'])]
//    public function verifyCustomer(Request $request, ValidatorInterface $validator): JsonResponse
//    {
//        // Rate limit per client IP
//        $limiter = $this->verifyLimiter->create($request->getClientIp() ?? 'anonymous');
//        $limit = $limiter->consume(1);
//        if (!$limit->isAccepted()) {
//            return $this->json(
//                ['message' => 'Too many requests'],
//                JsonResponse::HTTP_TOO_MANY_REQUESTS
//            );
//        }
//
//        try {
//            $data = $request->toArray();
//        } catch (\Throwable $e) {
//            return $this->json(['errors' => [['message' => 'Invalid JSON body']]], JsonResponse::HTTP_BAD_REQUEST);
//        }
//
//        $dto = new VerifyCustomerRequest();
//        $dto->transID = $data['transID'] ?? null;
//        $dto->meterNumber = $data['meterNumber'] ?? null;
//        $dto->amount = $data['amount'] ?? null;
//        $dto->email = $data['email'] ?? null;
//        $dto->password = $data['password'] ?? null;
//
//        $violations = $validator->validate($dto);
//        if (\count($violations) > 0) {
//            $errors = [];
//            foreach ($violations as $violation) {
//                $errors[] = [
//                    'field' => $violation->getPropertyPath(),
//                    'message' => $violation->getMessage(),
//                ];
//            }
//
//            return $this->json(['errors' => $errors], JsonResponse::HTTP_BAD_REQUEST);
//        }
//
//        $this->logger->info('Electricity verify requested', [
//            'transID' => $dto->transID,
//            'meterNumber' => $dto->meterNumber,
//            'amount' => $dto->amount,
//        ]);
//
//        // Simulated verification results
//        $results = [
//            'customerName' => 'John Doe',
//            'meterNumber' => $dto->meterNumber,
//            'valid' => true,
//        ];
//
//        $response = [
//            'results' => $results,
//            'api_token' => hash('sha256', $dto->email . '|verify'),
//            'elec_token' => 'electricity_token_test123',
//        ];
//
//        return $this->json($response, JsonResponse::HTTP_OK);
//    }
//
//    #[Route('/electricity/purchase', name: 'electricity_purchase', methods: ['POST'])]
//    #[IsGranted('ROLE_USER')]
//    public function purchase(Request $request, ValidatorInterface $validator): JsonResponse
//    {
//        try {
//            $data = $request->toArray();
//        } catch (\Throwable $e) {
//            return $this->json(['errors' => [['message' => 'Invalid JSON body']]], JsonResponse::HTTP_BAD_REQUEST);
//        }
//
//        $dto = new PurchaseElectricityRequest();
//        $dto->transID = $data['transID'] ?? null;
//        $dto->meterNumber = $data['meterNumber'] ?? null;
//        $dto->amount = $data['amount'] ?? null;
//        $dto->elec_token = $data['elec_token'] ?? null;
//
//        $violations = $validator->validate($dto);
//        if (\count($violations) > 0) {
//            $errors = [];
//            foreach ($violations as $violation) {
//                $errors[] = [
//                    'field' => $violation->getPropertyPath(),
//                    'message' => $violation->getMessage(),
//                ];
//            }
//
//            return $this->json(['errors' => $errors], JsonResponse::HTTP_BAD_REQUEST);
//        }
//
//        $this->logger->info('Electricity purchase requested', [
//            'transID' => $dto->transID,
//            'meterNumber' => $dto->meterNumber,
//            'amount' => $dto->amount,
//            'user' => $this->getUser()?->getUserIdentifier(),
//        ]);
//
//        // Simulated purchase processing
//        $receiptNo = 'RC' . (string) random_int(100000, 999999);
//        $units = (int) floor((float) $dto->amount);
//
//        $results = [
//            'status' => 'success',
//            'meterNumber' => $dto->meterNumber,
//            'amount' => (float) $dto->amount,
//        ];
//
//        $receiptItems = [
//            'receiptNo' => $receiptNo,
//            'units' => $units,
//            'provider' => 'Smartplan',
//        ];
//
//        return $this->json([
//            'results' => $results,
//            'receiptItems' => $receiptItems,
//        ], JsonResponse::HTTP_OK);
//    }
//}



// src/Controller/Api/V1/ElectricityController.php
namespace App\Controller\Api\V1;

use App\Dto\V1\PurchaseElectricityRequest;
use App\Dto\V1\VerifyCustomerRequest;
use App\Entity\User;
use App\Service\ElectricityManager;
use App\Service\RemoteLogger;
use Doctrine\ORM\EntityManagerInterface;
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
        private RemoteLogger $logger,
        private ElectricityManager $electricityManager,
        private EntityManagerInterface $em
    ) {}

    #[Route('/electricity/verify-customer', name: 'electricity_verify_customer', methods: ['POST'])]
    public function verifyCustomer(
        Request $request,
        ValidatorInterface $validator
    ): JsonResponse {
        $limiter = $this->verifyLimiter->create($request->getClientIp() ?? 'anonymous');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['message' => 'Too many requests'], JsonResponse::HTTP_TOO_MANY_REQUESTS);
        }

        // Parse JSON
        try {
            $data = $request->toArray();
        } catch (\Throwable) {
            return $this->json(['errors' => [['message' => 'Invalid JSON body']]], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Build DTO
        $dto = new VerifyCustomerRequest();
        $dto->transID = $data['transID'] ?? null;
        $dto->meterNumber = $data['meterNumber'] ?? null;
        $dto->amount = $data['amount'] ?? null;
        $dto->email = $data['email'] ?? null;
        $dto->password = $data['password'] ?? null; // <-- FIXED

        // Validate DTO
        $violations = $validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = ['field' => $v->getPropertyPath(), 'message' => $v->getMessage()];
            }
            return $this->json(['errors' => $errors], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Authenticate user by email + password (PRODUCTION READY)
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $dto->email]);

        if (!$user || !password_verify($dto->password, $user->getPassword())) {
            return $this->json([
                'errors' => [['message' => 'Invalid email or password']]
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Generate token + store database
        try {
            $amount = (float) $dto->amount;

            $result = $this->electricityManager->createVerificationAndToken(
                transID: $dto->transID,
                meterNumber: $dto->meterNumber,
                amount: $amount,
                user: $user      // <-- FIXED (valid user always)
            );

        }
//        catch (\Throwable $e) {
//            $this->logger->error('Failed to create verification token', [
//                'exception' => $e->getMessage()
//            ]);
//            return $this->json([
//                'errors' => [['message' => 'Internal server error']]
//            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        catch (\Throwable $e) {
            // show full exception for debugging
            dd([
                'message' => $e->getMessage(),
                'class' => get_class($e),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }

        // Response
        return $this->json([
            'results' => [
                'customerName' => 'Verified Customer',
                'meterNumber' => $dto->meterNumber,
                'amount' => $amount,
                'valid' => true,
            ],
            'api_token' => hash('sha256', $dto->email . '|' . time()),
            'elec_token' => $result['plain_token'], // <-- returned ONCE
        ]);
    }


//    #[Route('/electricity/purchase', name: 'electricity_purchase', methods: ['POST'])]
//    #[IsGranted('ROLE_USER')]
//    public function purchase(Request $request, ValidatorInterface $validator): JsonResponse
//    {
//        try {
//            $data = $request->toArray();
//        } catch (\Throwable) {
//            return $this->json(['errors' => [['message' => 'Invalid JSON body']]], JsonResponse::HTTP_BAD_REQUEST);
//        }
//
//        $dto = new PurchaseElectricityRequest();
//        $dto->transID = $data['transID'] ?? null;
//        $dto->meterNumber = $data['meterNumber'] ?? null;
//        $dto->amount = $data['amount'] ?? null;
//        $dto->elec_token = $data['elec_token'] ?? null;
//
//        $violations = $validator->validate($dto);
//        if (count($violations) > 0) {
//            $errors = [];
//            foreach ($violations as $v) {
//                $errors[] = ['field' => $v->getPropertyPath(), 'message' => $v->getMessage()];
//            }
//            return $this->json(['errors' => $errors], JsonResponse::HTTP_BAD_REQUEST);
//        }
//
//        try {
//            $user = $this->getUser();
//            $amount = (float) $dto->amount;
//            $purchaseResult = $this->electricityManager->purchaseWithToken($dto->elec_token, $dto->transID, $dto->meterNumber, $amount, $user);
//        } catch (\RuntimeException $e) {
//            $this->logger->warning('Purchase validation failed', ['message' => $e->getMessage()]);
//            return $this->json(['errors' => [['message' => $e->getMessage()]]], JsonResponse::HTTP_BAD_REQUEST);
//        }
////        catch (\Throwable $e) {
////            $this->logger->error('Purchase failed', ['exc' => $e->getMessage()]);
////            return $this->json(['errors' => [['message' => 'Internal server error']]], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
////        }
//
//        catch (\Throwable $e) {
//            // show full exception for debugging
//            dd([
//                'message' => $e->getMessage(),
//                'class' => get_class($e),
//                'file'    => $e->getFile(),
//                'line'    => $e->getLine(),
//                'trace'   => $e->getTraceAsString(),
//            ]);
//        }
//
//        $transaction = $purchaseResult['transaction'];
//
//        return $this->json([
//            'results' => [
//                'status' => $transaction->getStatus(),
//                'meterNumber' => $transaction->getMeterNumber(),
//                'amount' => (float)$transaction->getAmount(),
//                'units' => $transaction->getUnits(),
//            ],
//            'receiptItems' => $purchaseResult['receipt']
//        ], JsonResponse::HTTP_OK);
//    }

    #[Route('/electricity/purchase', name: 'electricity_purchase', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function purchase(Request $request, ValidatorInterface $validator): JsonResponse
    {
        // Decode JSON body
        try {
            $data = $request->toArray();
        } catch (\Throwable) {
            return $this->json(
                ['errors' => [['message' => 'Invalid JSON body']]],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        // Map request to DTO
        $dto = new PurchaseElectricityRequest();
        $dto->transID = $data['transID'] ?? null;
        $dto->meterNumber = $data['meterNumber'] ?? null;
        $dto->amount = $data['amount'] ?? null;
        $dto->elec_token = $data['elec_token'] ?? 'auto'; // default to auto-generate

        if ($dto->elec_token === '') {
            return $this->json(
                ['errors' => [['message' => 'Token cannot be an empty string']]],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        // Validate DTO fields
        $violations = $validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = ['field' => $v->getPropertyPath(), 'message' => $v->getMessage()];
            }
            return $this->json(['errors' => $errors], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->getUser();
            $amount = (float) $dto->amount;

            // Unified purchase call; manager handles 'auto' internally
            $purchaseResult = $this->electricityManager->purchaseWithToken(
                $dto->elec_token,
                $dto->transID,
                $dto->meterNumber,
                $amount,
                $user
            );

        } catch (\RuntimeException $e) {
            $this->logger->warning('Purchase validation failed', ['message' => $e->getMessage()]);
            return $this->json(
                ['errors' => [['message' => $e->getMessage()]]],
                JsonResponse::HTTP_BAD_REQUEST
            );

        } catch (\Throwable $e) {
            $this->logger->error('Purchase failed', [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'transID' => $dto->transID,
                'meterNumber' => $dto->meterNumber,
            ]);

            return $this->json(
                ['errors' => [['message' => 'Internal server error. Please contact support.']]],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // Return successful purchase
        $transaction = $purchaseResult['transaction'];

        return $this->json([
            'results' => [
                'status' => $transaction->getStatus(),
                'meterNumber' => $transaction->getMeterNumber(),
                'amount' => (float)$transaction->getAmount(),
                'units' => $transaction->getUnits(),
            ],
            'receiptItems' => $purchaseResult['receipt'],
            'token' => $purchaseResult['token'] ?? null,
        ], JsonResponse::HTTP_OK);
    }



//    #[Route('/electricity/purchase', name: 'electricity_purchase', methods: ['POST'])]
//    #[IsGranted('ROLE_USER')]
//    public function purchase(Request $request, ValidatorInterface $validator): JsonResponse
//    {
//        try {
//            $data = $request->toArray();
//        } catch (\Throwable) {
//            return $this->json(
//                ['errors' => [['message' => 'Invalid JSON body']]],
//                JsonResponse::HTTP_BAD_REQUEST
//            );
//        }
//
//        // Accept "auto" as default when no token provided
//        $dto = new PurchaseElectricityRequest();
//        $dto->transID = $data['transID'] ?? null;
//        $dto->meterNumber = $data['meterNumber'] ?? null;
//        $dto->amount = $data['amount'] ?? null;
//        $dto->elec_token = $data['elec_token'] ?? 'auto';   // <── HERE: auto token if not sent
//
//        //  Reject empty string
//        if ($dto->elec_token === '') {
//            return $this->json(
//                ['errors' => [['message' => 'Token cannot be an empty string']]],
//                JsonResponse::HTTP_BAD_REQUEST
//            );
//        }
//
//        // Validate required DTO fields
//        $violations = $validator->validate($dto);
//        if (count($violations) > 0) {
//            $errors = [];
//            foreach ($violations as $v) {
//                $errors[] = ['field' => $v->getPropertyPath(), 'message' => $v->getMessage()];
//            }
//            return $this->json(['errors' => $errors], JsonResponse::HTTP_BAD_REQUEST);
//        }
//
//        try {
//            $user = $this->getUser();
//            $amount = (float) $dto->amount;
//
//            // purchaseWithToken will auto-generate a token if 'auto'
//            $purchaseResult = $this->electricityManager->purchaseWithToken(
//                $dto->elec_token,
//                $dto->transID,
//                $dto->meterNumber,
//                $amount,
//                $user
//            );
//
//        } catch (\RuntimeException $e) {
//            // Business validation error (token mismatch, invalid meter, etc.)
//            $this->logger->warning('Purchase validation failed', ['message' => $e->getMessage()]);
//            return $this->json(
//                ['errors' => [['message' => $e->getMessage()]]],
//                JsonResponse::HTTP_BAD_REQUEST
//            );
//
//        } catch (\Throwable $e) {
//            // Unexpected errors
//            $this->logger->error('Purchase failed', [
//                'exception_class' => get_class($e),
//                'message' => $e->getMessage(),
//                'trace' => $e->getTraceAsString(),
//                'transID' => $dto->transID,
//                'meterNumber' => $dto->meterNumber,
//            ]);
//
//            return $this->json(
//                ['errors' => [['message' => 'Internal server error. Please contact support.']]],
//                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
//            );
//        }
//
//        // successful purchase
//        $transaction = $purchaseResult['transaction'];
//
//        return $this->json([
//            'results' => [
//                'status' => $transaction->getStatus(),
//                'meterNumber' => $transaction->getMeterNumber(),
//                'amount' => (float)$transaction->getAmount(),
//                'units' => $transaction->getUnits(),
//            ],
//            'receiptItems' => $purchaseResult['receipt']
//        ], JsonResponse::HTTP_OK);
//    }


}

