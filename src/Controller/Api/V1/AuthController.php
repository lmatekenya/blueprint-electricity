<?php
//
//namespace App\Controller\Api\V1;
//
//use App\Entity\User;
//use Doctrine\ORM\EntityManagerInterface;
//use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
//use Symfony\Component\HttpFoundation\JsonResponse;
//use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
//use Symfony\Component\Routing\Annotation\Route;
//use Symfony\Bundle\SecurityBundle\Security;
//
//use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
//
//#[Route('/auth')]
//class AuthController extends AbstractController
//{
//    private $entityManager;
//    private $passwordHasher;
//    private $jwtManager;
//    private $security;
//
//    public function __construct(
//        EntityManagerInterface $entityManager,
//        UserPasswordHasherInterface $passwordHasher,
//        JWTTokenManagerInterface $jwtManager,
//        Security $security
//    ) {
//        $this->entityManager = $entityManager;
//        $this->passwordHasher = $passwordHasher;
//        $this->jwtManager = $jwtManager;
//        $this->security = $security;
//    }
//
//    #[Route('/login', name: 'login', methods: ['POST'])]
//    public function login(Request $request): JsonResponse
//    {
//        $data = json_decode($request->getContent(), true);
//
//        if (!isset($data['email']) || !isset($data['password'])) {
//            return new JsonResponse([
//                'result' => 'FAILED',
//                'message' => 'Email and password are required'
//            ], Response::HTTP_BAD_REQUEST);
//        }
//
//        $user = $this->entityManager->getRepository(User::class)
//            ->findOneBy(['email' => $data['email']]);
//
//        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
//            return new JsonResponse([
//                'result' => 'FAILED',
//                'message' => 'Invalid credentials'
//            ], Response::HTTP_UNAUTHORIZED);
//        }
//
//        // Generate JWT token
//        $token = $this->jwtManager->create($user);
//
//        // Generate API token for backward compatibility
//        $apiToken = bin2hex(random_bytes(32));
//        $user->setApiToken(hash('sha256', $apiToken));
//        $user->setApiTokenExpiresAt((new \DateTimeImmutable())->modify('+1 hour'));
//
//        $this->entityManager->persist($user);
//        $this->entityManager->flush();
//
//        return new JsonResponse([
//            'result' => 'SUCCESS',
//            'message' => 'Login successful',
//            'data' => [
//                'user' => [
//                    'id' => $user->getId(),
//                    'name' => $user->getName(),
//                    'email' => $user->getEmail()
//                ],
//                'token' => $token,
//                'api_token' => $apiToken // For backward compatibility
//            ]
//        ]);
//    }
//
//    #[Route('/me', name: 'me', methods: ['GET'])]
//    public function me(): JsonResponse
//    {
//        $user = $this->security->getUser();
//
//        if (!$user) {
//            return new JsonResponse([
//                'result' => 'FAILED',
//                'message' => 'Not authenticated'
//            ], Response::HTTP_UNAUTHORIZED);
//        }
//
//        return new JsonResponse([
//            'result' => 'SUCCESS',
//            'message' => null,
//            'data' => [
//                'id' => $user->getId(),
//                'name' => $user->getName(),
//                'email' => $user->getEmail(),
//                'created_at' => $user->getCreatedAt()->format('Y-m-d\TH:i:s\Z')
//            ]
//        ]);
//    }
//
//    #[Route('/refresh-token', name: 'refresh_token', methods: ['POST'])]
//    public function refreshToken(): JsonResponse
//    {
//        $user = $this->security->getUser();
//
//        if (!$user) {
//            return new JsonResponse([
//                'result' => 'FAILED',
//                'message' => 'Not authenticated'
//            ], Response::HTTP_UNAUTHORIZED);
//        }
//
//        $newToken = $this->jwtManager->create($user);
//        $apiToken = bin2hex(random_bytes(32));
//
//        $user->setApiToken(hash('sha256', $apiToken));
//        $user->setApiTokenExpiresAt((new \DateTimeImmutable())->modify('+1 hour'));
//
//        $this->entityManager->persist($user);
//        $this->entityManager->flush();
//
//        return new JsonResponse([
//            'result' => 'SUCCESS',
//            'message' => 'Token refreshed successfully',
//            'data' => [
//                'token' => $newToken,
//                'api_token' => $apiToken
//            ]
//        ]);
//    }
//
//    #[Route('/logout', name: 'logout', methods: ['POST'])]
//    public function logout(): JsonResponse
//    {
//        $user = $this->security->getUser();
//
//        if ($user) {
//            $user->setApiToken(null);
//            $user->setApiTokenExpiresAt(null);
//            $this->entityManager->persist($user);
//            $this->entityManager->flush();
//        }
//
//        return new JsonResponse([
//            'result' => 'SUCCESS',
//            'message' => 'Logged out successfully',
//            'data' => null
//        ]);
//    }
//}


namespace App\Controller\Api\V1;

use App\Dto\V1\LoginRequest;
use App\Entity\User;
use App\Service\RemoteLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1', name: 'api_v1_')]
class AuthController extends AbstractController
{
//    #[Route('/auth/login', name: 'auth_login', methods: ['POST'])]
//    public function login(
//        Request $request,
//        ValidatorInterface $validator,
//        RemoteLogger $logger,
//        EntityManagerInterface $em,
//        UserPasswordHasherInterface $passwordHasher,
//        Security $security
//    ): JsonResponse {
//        try {
//            $data = $request->toArray();
//        } catch (\Throwable $e) {
//            return $this->json(['errors' => [['message' => 'Invalid JSON body']]], JsonResponse::HTTP_BAD_REQUEST);
//        }
//
//        $dto = new LoginRequest();
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
//        // Find or provision user (securely hashes password)
//        /** @var User|null $user */
//        $user = $em->getRepository(User::class)->findOneBy(['email' => $dto->email]);
//
//        if ($user === null) {
//            $user = new User();
//            $user->setEmail($dto->email)
//                ->setName(explode('@', $dto->email)[0] ?? 'user')
//                ->setRoles(['ROLE_USER']);
//            $user->setPassword($passwordHasher->hashPassword($user, (string) $dto->password));
//            $em->persist($user);
//        } else {
//            if (!$passwordHasher->isPasswordValid($user, (string) $dto->password)) {
//                return $this->json(['message' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
//            }
//        }
//
//        // Generate a strong API token with expiry
//        $token = bin2hex(random_bytes(32));
//        $user->setApiToken($token);
//        $user->setApiTokenExpiresAt((new \DateTimeImmutable())->modify('+1 hour'));
//        $em->flush();
//
//        // Start session and log the user into the security context
//        $session = $request->getSession();
//        if ($session && !$session->isStarted()) {
//            $session->start();
//        }
//        if ($session) {
//            $session->set('api_token', $token);
//            $session->set('email', $user->getEmail());
//        }
//
//        // Authenticate for the current request and persist in session
//        $security->login($user);
//
//        $logger->info('User logged in', ['email' => $dto->email]);
//
//        return $this->json([
//            'data' => [
//                'api_token' => $token,
//            ],
//        ], JsonResponse::HTTP_OK);
//    }

    #[Route('/auth/login', name: 'auth_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?: [];
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json(['message' => 'Email and password required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['message' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $token = bin2hex(random_bytes(32));
        $user->setApiToken($token);
        $user->setApiTokenExpiresAt((new \DateTimeImmutable())->modify('+1 hour'));
        $em->flush();

        return $this->json([
            'data' => [
                'api_token' => $token
            ]
        ]);
    }


//    #[Route('/auth/me', name: 'auth_me', methods: ['GET'])]
//    public function me(Request $request): JsonResponse
//    {
//        $user = $this->getUser();
//        $authHeader = $request->headers->get('Authorization', '');
//        $token = null;
//
//        if (\str_starts_with($authHeader, 'Bearer ')) {
//            $token = \substr($authHeader, 7);
//        } else {
//            $session = $request->getSession();
//            if ($session && $session->has('api_token')) {
//                $token = $session->get('api_token');
//            }
//        }
//
//        return $this->json([
//            'data' => [
//                'authenticated' => $user !== null || $token !== null,
//                'api_token' => $token,
//                'email' => $user ? $user->getUserIdentifier() : ($request->getSession()?->get('email')),
//            ],
//        ]);
//    }

    #[Route('/auth/me', name: 'auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName()
            ]
        ]);
    }

}
