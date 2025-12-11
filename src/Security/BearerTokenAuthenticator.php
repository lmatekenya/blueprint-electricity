<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class BearerTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function supports(Request $request): ?bool
    {
        $auth = $request->headers->get('Authorization', '');
        return str_starts_with($auth, 'Bearer ');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $auth = $request->headers->get('Authorization', '');
        $token = substr($auth, 7);

        $userLoader = function (string $token): ?UserInterface {
            /** @var User|null $user */
            $user = $this->em->getRepository(User::class)->findOneBy(['apiToken' => $token]);

            if (!$user) {
                throw new AuthenticationException('Invalid API token.');
            }

            $expiresAt = $user->getApiTokenExpiresAt();

            if (!$expiresAt || $expiresAt <= new \DateTimeImmutable()) {
                throw new AuthenticationException('API token expired.');
            }

            return $user;
        };

        return new SelfValidatingPassport(new UserBadge($token, $userLoader));
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?JsonResponse
    {
        return null; // allow request to continue
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?JsonResponse
    {
        return new JsonResponse(
            [
                'message' => 'Unauthorized',
                'error' => $exception->getMessage()
            ],
            JsonResponse::HTTP_UNAUTHORIZED
        );
    }

    /**
     * ENTRY POINT for NO credentials / missing authorization header
     */
    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse(
            [
                'message' => 'Authentication Required',
                'error' => $authException?->getMessage() ?? 'No API token provided'
            ],
            JsonResponse::HTTP_UNAUTHORIZED
        );
    }
}
