<?php
//
//namespace App\RateLimiter;
//
//use Symfony\Component\RateLimiter\RateLimiterFactory;
//
//use Symfony\Component\HttpFoundation\RequestStack;
//use Symfony\Component\HttpFoundation\Response;
//use Psr\Log\LoggerInterface;
//
//class ApiRateLimiter
//{
//    private $rateLimiterFactory;
//    private $requestStack;
//    private $logger;
//
//    public function __construct(
//        RateLimiterFactory $apiRateLimiterFactory,
//        RequestStack $requestStack,
//        LoggerInterface $logger
//    ) {
//        $this->rateLimiterFactory = $apiRateLimiterFactory;
//        $this->requestStack = $requestStack;
//        $this->logger = $logger;
//    }
//
//    public function checkRateLimit(?string $userIdentifier = null): array
//    {
//        $request = $this->requestStack->getCurrentRequest();
//        $ip = $request->getClientIp();
//
//        // Use user ID if authenticated, otherwise use IP
//        $limiterKey = $userIdentifier ?? $ip;
//
//        $limiter = $this->rateLimiterFactory->create($limiterKey);
//
//        if (false === $limiter->consume(1)->isAccepted()) {
//            $this->logger->warning('Rate limit exceeded', [
//                'ip' => $ip,
//                'user' => $userIdentifier,
//                'path' => $request->getPathInfo()
//            ]);
//
//            return [
//                'allowed' => false,
//                'statusCode' => Response::HTTP_TOO_MANY_REQUESTS,
//                'message' => 'Too many requests. Please try again later.'
//            ];
//        }
//
//        return [
//            'allowed' => true
//        ];
//    }
//}


namespace App\RateLimiter;

use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

class ApiRateLimiter
{
    private RateLimiterFactory $rateLimiterFactory;
    private RequestStack $requestStack;
    private LoggerInterface $logger;

    public function __construct(
        RateLimiterFactory $apiLimiter,  // <-- FIXED ARG NAME
        RequestStack       $requestStack,
        LoggerInterface    $logger
    )
    {
        $this->rateLimiterFactory = $apiLimiter; // <-- MATCHES THE ARGUMENT
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    public function checkRateLimit(?string $userIdentifier = null): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $ip = $request->getClientIp();

        $limiterKey = $userIdentifier ?? $ip;

        $limiter = $this->rateLimiterFactory->create($limiterKey);

        if (!$limiter->consume(1)->isAccepted()) {
            $this->logger->warning('Rate limit exceeded', [
                'ip' => $ip,
                'user' => $userIdentifier,
                'path' => $request->getPathInfo(),
            ]);

            return [
                'allowed' => false,
                'statusCode' => Response::HTTP_TOO_MANY_REQUESTS,
                'message' => 'Too many requests. Please try again later.',
            ];
        }

        return ['allowed' => true];
    }
}
