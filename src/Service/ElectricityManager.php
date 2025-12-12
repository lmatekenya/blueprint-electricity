<?php
//namespace App\Service;
//
//use App\Entity\ElectricityProvider;
//use App\Entity\ElectricityTariff;
//use App\Entity\ElectricityToken;
//use App\Entity\ElectricityTransaction;
//use Doctrine\ORM\EntityManagerInterface;
//use Symfony\Component\Security\Core\User\UserInterface;
//
//class ElectricityManager
//{
//    private EntityManagerInterface $em;
//    private \Psr\Log\LoggerInterface $logger;
//
//    public function __construct(EntityManagerInterface $em, \Psr\Log\LoggerInterface $logger)
//    {
//        $this->em = $em;
//        $this->logger = $logger;
//    }
//
//    /**
//     * Generate a unique 16-digit token grouped as 4-4-4-4 (numbers only).
//     */
//    public function generateUnique16DigitToken(): string
//    {
//        // Retry loop in case of collision
//        $repo = $this->em->getRepository(ElectricityToken::class);
//        do {
//            $digits = '';
//            for ($i = 0; $i < 16; $i++) {
//                $digits .= (string) random_int(0, 9);
//            }
//            $groups = str_split($digits, 4);
//            $token = implode('-', $groups);
//            $exists = $repo->findOneBy(['token' => $token]);
//        } while ($exists !== null);
//
//        return $token;
//    }
//
//    /**
//     * Ensure there's at least one provider and one tariff in DB.
//     * If none exist, create sensible defaults (idempotent).
//     */
//    public function ensureDefaults(): void
//    {
//        $provRepo = $this->em->getRepository(ElectricityProvider::class);
//        $tarRepo = $this->em->getRepository(ElectricityTariff::class);
//
//        $provider = $provRepo->findOneBy([]) ?? null;
//        if ($provider === null) {
//            $provider = new ElectricityProvider('SmartPlan Botswana');
//            $this->em->persist($provider);
//        }
//
//        $tariff = $tarRepo->findOneBy([]) ?? null;
//        if ($tariff === null) {
//            // create a default tariff with rate 1.32
//            $tariff = new ElectricityTariff('1.32', $provider, 'default');
//            $this->em->persist($tariff);
//        }
//
//        $this->em->flush();
//    }
//
//    /**
//     * Verify: create a pending transaction and an electricity token, persist both.
//     */
//    public function createVerificationAndToken(string $transID, string $meterNumber, float $amount, UserInterface $user = null): array
//    {
//        $this->ensureDefaults();
//
//        $transaction = new ElectricityTransaction();
//        $transaction->setTransID($transID)
//            ->setMeterNumber($meterNumber)
//            ->setAmount((string) number_format($amount, 2, '.', ''))
//            ->setStatus('pending')
//            ->setUser($user)
//            ->setUpdatedAt(new \DateTimeImmutable());
//
//        $token = $this->generateUnique16DigitToken();
//
//        $elecToken = new ElectricityToken($token, $meterNumber, (string) number_format($amount, 2, '.', ''), (new \DateTimeImmutable())->modify('+30 minutes'));
//        // link token to transaction (optional; transaction persisted later)
//        $elecToken->setTransaction($transaction);
//
//        $this->em->persist($transaction);
//        $this->em->persist($elecToken);
//        $this->em->flush();
//
//        $this->logger->info('Created electricity verification and token', [
//            'transID' => $transID,
//            'meter' => $meterNumber,
//            'token' => $token,
//        ]);
//
//        return [
//            'transaction' => $transaction,
//            'elec_token' => $token,
//            'token_entity' => $elecToken
//        ];
//    }
//
//    /**
//     * Purchase flow: validate token, compute units from a tariff, update transaction and token.
//     *
//     * Returns array with 'transaction' and 'receipt' details on success or throws \RuntimeException on error.
//     */
//    public function purchaseWithToken(string $elecTokenString, string $transID, string $meterNumber, float $amount, ?UserInterface $user = null): array
//    {
//        $tokenRepo = $this->em->getRepository(ElectricityToken::class);
//        /** @var ElectricityToken|null $tokenEntity */
//        $tokenEntity = $tokenRepo->findOneBy(['token' => $elecTokenString]);
//
//        if ($tokenEntity === null) {
//            throw new \RuntimeException('Token not found');
//        }
//
//        if ($tokenEntity->isUsed()) {
//            throw new \RuntimeException('Token already used');
//        }
//
//        if ($tokenEntity->getMeterNumber() !== $meterNumber) {
//            throw new \RuntimeException('Token meter mismatch');
//        }
//
//        // expiry check
//        if ($tokenEntity->getExpiresAt() !== null && $tokenEntity->getExpiresAt() < new \DateTimeImmutable()) {
//            throw new \RuntimeException('Token expired');
//        }
//
//        // ensure defaults and choose a tariff (for now pick the cheapest tariff available)
//        $this->ensureDefaults();
//        $tariffRepo = $this->em->getRepository(ElectricityTariff::class);
//        /** @var ElectricityTariff[] $tariffs */
//        $tariffs = $tariffRepo->findBy([], ['rate' => 'ASC']);
//
//        if (empty($tariffs)) {
//            throw new \RuntimeException('No tariffs configured');
//        }
//
//        $tariff = $tariffs[0]; // pick cheapest for this example
//        $rate = (float) $tariff->getRate();
//
//        // calculate units as amount / rate (rounded down to 2 decimals, but store as int units if desired)
//        $units = (int) floor($amount / $rate);
//
//        // select provider associated with tariff, or any provider
//        $provider = $tariff->getProvider()?->getName() ?? ($this->em->getRepository(ElectricityProvider::class)->findOneBy([])->getName());
//
//        // create or update transaction record
//        $transaction = $tokenEntity->getTransaction();
//        if (!$transaction) {
//            $transaction = new ElectricityTransaction();
//            $transaction->setTransID($transID)
//                ->setMeterNumber($meterNumber)
//                ->setAmount((string) number_format($amount, 2, '.', ''))
//                ->setUser($user)
//                ->setStatus('pending')
//                ->setUpdatedAt(new \DateTimeImmutable());
//            $this->em->persist($transaction);
//        } else {
//            $transaction->setStatus('processing')->setUpdatedAt(new \DateTimeImmutable());
//        }
//
//        // produce receipt number securely
//        $receiptNo = strtoupper(bin2hex(random_bytes(6)));
//
//        $transaction->setReceiptNo($receiptNo)
//            ->setUnits($units)
//            ->setProvider($provider)
//            ->setToken($tokenEntity->getToken())
//            ->setStatus('success')
//            ->setUpdatedAt(new \DateTimeImmutable())
//            ->setDetails([
//                'tariff_rate' => $rate,
//                'tariff_band' => $tariff->getBand(),
//            ]);
//
//        // mark token used & link it
//        $tokenEntity->setUsed(true);
//        $tokenEntity->setTransaction($transaction);
//
//        $this->em->persist($transaction);
//        $this->em->persist($tokenEntity);
//        $this->em->flush();
//
//        $this->logger->info('Purchase completed', [
//            'transID' => $transaction->getTransID(),
//            'receipt' => $receiptNo,
//            'units' => $units,
//            'provider' => $provider,
//        ]);
//
//        return [
//            'transaction' => $transaction,
//            'receipt' => [
//                'receiptNo' => $receiptNo,
//                'units' => $units,
//                'provider' => $provider,
//                'tariff' => $rate,
//            ],
//        ];
//    }
//}


namespace App\Service;

use App\Entity\ElectricityProvider;
use App\Entity\ElectricityTariff;
use App\Entity\ElectricityToken;
use App\Entity\ElectricityTransaction;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Service that manages electricity verification tokens and purchases.
 *
 * - Generates hashed tokens and stores a preview.
 * - Retries on unique constraint collisions.
 * - Uses explicit DB transactions (via Connection) to avoid nested transaction issues.
 */
class ElectricityManager
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    private int $tokenRetryLimit = 5;
    private int $tokenExpiryMinutes = 30;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Generate a numeric 16-digit token grouped 4-4-4-4 (e.g. 1234-5678-9012-3456).
     */
    private function generatePlain16Digit(): string
    {
        $digits = '';
        for ($i = 0; $i < 16; $i++) {
            $digits .= random_int(0, 9);
        }

        return implode('-', str_split($digits, 4));
    }

    /**
     * Generate a unique 16-digit token that does not already exist in DB.
     *
     * @throws \RuntimeException if unique token cannot be generated
     */
    private function generateUnique16Digit(): string
    {
        $repo = $this->em->getRepository(ElectricityToken::class);
        $attempts = 0;

        do {
            $attempts++;
            $plain = $this->generatePlain16Digit();
            $hash = $this->hashToken($plain);

            // Check for existing token hash collision
            $exists = $repo->findOneBy(['tokenHash' => $hash]);
            if (!$exists) {
                return $plain;
            }
        } while ($attempts < 5);

        throw new \RuntimeException('Failed to generate unique token after multiple attempts');
    }

    /**
     * Hash the plain token for secure storage.
     */
    private function hashToken(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    /**
     * Verify a provided plain token against a stored hash.
     */
    private function verifyTokenHash(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    /**
     * Ensure a provider and tariff exist (idempotent).
     */
    public function ensureDefaults(): void
    {
        $provRepo = $this->em->getRepository(ElectricityProvider::class);
        $tarRepo = $this->em->getRepository(ElectricityTariff::class);

        $provider = $provRepo->findOneBy([]);
        if (!$provider) {
            $provider = new ElectricityProvider('SmartPlan Botswana');
            $this->em->persist($provider);
        }

        $tariff = $tarRepo->findOneBy([]);
        if (!$tariff) {
            $tariff = new ElectricityTariff('1.3200', $provider, 'default');
            $this->em->persist($tariff);
        }

        $this->em->flush();
    }

//    /**
//     * Create a verification transaction and electricity token.
//     *
//     * Returns:
//     *  - 'transaction' => ElectricityTransaction (managed)
//     *  - 'plain_token' => string (the plain token — returned once)
//     *  - 'tokenEntity' => ElectricityToken (managed)
//     *
//     * @throws \Throwable on unrecoverable errors
//     */
//    public function createVerificationAndToken(
//        string $transID,
//        string $meterNumber,
//        float $amount,
//        ?UserInterface $user = null
//    ): array {
//        $this->ensureDefaults();
//
////        // 1) If transID must be unique, check if it exists already and return / throw.
////        if ($transID) {
////            $existing = $this->em->getRepository(ElectricityTransaction::class)->findOneBy(['transID' => $transID]);
////            if ($existing) {
////                // Either reuse, update or throw. I choose to throw so caller can decide.
////                throw new \RuntimeException(sprintf('Transaction with transID "%s" already exists', $transID));
////            }
////        }
//
//        if ($transID) {
//            $existing = $this->em->getRepository(ElectricityTransaction::class)
//                ->findOneBy(['transID' => $transID]);
//
//            if ($existing) {
//                // token is already a string
//                $plainToken = $existing->getToken() ?? $this->generatePlain16Digit();
//
//                return [
//                    'transaction' => $existing,
//                    'plain_token' => $plainToken,
//                    'tokenEntity' => $plainToken, // you can keep the key for backward compatibility
//                ];
//            }
//        }
//
//
//
//
//        // Prepare canonical typed amount (float)
//        $amountNormalized = (float) number_format($amount, 2, '.', '');
//
//        $attempt = 0;
//
//        while ($attempt < $this->tokenRetryLimit) {
//            $attempt++;
//
//            // Create a fresh transaction object per attempt so EM state is clean
//            $transaction = new ElectricityTransaction();
//            $transaction->setTransID($transID)
//                ->setMeterNumber($meterNumber)
//                ->setAmount($amountNormalized)
//                ->setStatus('pending')
//                ->setUser($user)
//                ->setUpdatedAt(new \DateTimeImmutable());
//
//            $plain = $this->generatePlain16Digit();
//            $hash = $this->hashToken($plain);
//            $preview = substr(str_replace('-', '', $plain), -4);
//            $expiresAt = (new \DateTimeImmutable())->modify("+{$this->tokenExpiryMinutes} minutes");
//
//            $elecToken = new ElectricityToken(
//                $hash,
//                $meterNumber,
//                $amountNormalized,
//                $expiresAt,
//                $preview
//            );
//
//            $elecToken->setTransaction($transaction);
//
//            // Persist both just before flush so EM doesn't hold onto an already-persisted transaction between attempts
//            $this->em->persist($transaction);
//            $this->em->persist($elecToken);
//
//            $conn = $this->em->getConnection();
//            $conn->beginTransaction();
//
//            try {
//                $this->em->flush();
//                $conn->commit();
//
//                $this->logger->info('Generated electricity token', [
//                    'transID' => $transID,
//                    'meterNumber' => $meterNumber,
//                    'preview' => $preview,
//                    'attempt' => $attempt,
//                ]);
//
//                return [
//                    'transaction' => $transaction,
//                    'plain_token' => $plain,
//                    'tokenEntity' => $elecToken,
//                ];
//            } catch (UniqueConstraintViolationException $ex) {
//                // Token or transaction collision — rollback and prepare for retry
//                if ($conn->isTransactionActive()) {
//                    $conn->rollBack();
//                }
//
//                // Detach entities we just persisted so UnitOfWork is clean for next iteration
//                // detach both token and transaction so we don't re-attempt to insert the same transID
//                if ($this->em->contains($elecToken)) {
//                    $this->em->detach($elecToken);
//                }
//                if ($this->em->contains($transaction)) {
//                    $this->em->detach($transaction);
//                }
//
//                $this->logger->warning('Token or transaction collision, retrying', [
//                    'attempt' => $attempt,
//                    'exception' => $ex->getMessage(),
//                ]);
//
//                // small sleep to avoid tight loop on extreme collision rates (optional)
//                usleep(100000); // 100ms
//                continue;
//            } catch (\Throwable $e) {
//                if ($conn->isTransactionActive()) {
//                    $conn->rollBack();
//                }
//
//                // Detach to avoid stale managed state
//                if ($this->em->contains($elecToken)) {
//                    $this->em->detach($elecToken);
//                }
//                if ($this->em->contains($transaction)) {
//                    $this->em->detach($transaction);
//                }
//
//                $this->logger->error('Failed to create verification token', [
//                    'exception_class' => get_class($e),
//                    'exception_message' => $e->getMessage(),
//                    'trace' => $e->getTraceAsString(),
//                    'transID' => $transID,
//                    'meterNumber' => $meterNumber,
//                ]);
//
//                throw $e;
//            }
//        }
//
//        throw new \RuntimeException('Failed to generate unique token after multiple attempts');
//    }

    /**
     * Create a verification transaction and electricity token.
     *
     * Returns:
     *  - 'transaction' => ElectricityTransaction (managed)
     *  - 'plain_token' => string (the plain token — returned once)
     *  - 'tokenEntity' => ElectricityToken (managed)
     *
     * @throws \Throwable on unrecoverable errors
     */
    public function createVerificationAndToken(
        string $transID,
        string $meterNumber,
        float $amount,
        ?UserInterface $user = null
    ): array {
        $this->ensureDefaults();

        // Normalize amount
        $amountNormalized = (float) number_format($amount, 2, '.', '');

        // Check if transaction already exists
        if ($transID) {
            $existingTransaction = $this->em->getRepository(ElectricityTransaction::class)
                ->findOneBy(['transID' => $transID]);

            if ($existingTransaction) {
                // Try to find an unused token associated with this transaction
                $tokenEntity = $this->em->getRepository(ElectricityToken::class)
                    ->findOneBy(['transaction' => $existingTransaction, 'used' => false]);

                if (!$tokenEntity) {
                    // No token exists, generate a new one
                    $plain = $this->generateUnique16Digit();
                    $hash = $this->hashToken($plain);
                    $preview = substr(str_replace('-', '', $plain), -4);
                    $expiresAt = (new \DateTimeImmutable())->modify("+{$this->tokenExpiryMinutes} minutes");

                    $tokenEntity = new ElectricityToken(
                        $hash,
                        $existingTransaction->getMeterNumber(),
                        $amountNormalized,
                        $expiresAt,
                        $preview
                    );
                    $tokenEntity->setTransaction($existingTransaction);

                    $this->em->persist($tokenEntity);
                    $this->em->flush();

                    $plainToken = $plain;
                } else {
                    // Token already exists, return the plain token if possible
                    $plainToken = $tokenEntity->getTokenHash(); // If you store hash only, you may need to regenerate or store plain token elsewhere
                }

                return [
                    'transaction' => $existingTransaction,
                    'plain_token' => $plainToken,
                    'tokenEntity' => $tokenEntity,
                ];
            }
        }

        // If no existing transaction, generate a new transaction + token
        $attempt = 0;

        while ($attempt < $this->tokenRetryLimit) {
            $attempt++;

            $transaction = new ElectricityTransaction();
            $transaction->setTransID($transID)
                ->setMeterNumber($meterNumber)
                ->setAmount($amountNormalized)
                ->setStatus('pending')
                ->setUser($user)
                ->setUpdatedAt(new \DateTimeImmutable());

            $plain = $this->generateUnique16Digit();
            $hash = $this->hashToken($plain);
            $preview = substr(str_replace('-', '', $plain), -4);
            $expiresAt = (new \DateTimeImmutable())->modify("+{$this->tokenExpiryMinutes} minutes");

            $elecToken = new ElectricityToken(
                $hash,
                $meterNumber,
                $amountNormalized,
                $expiresAt,
                $preview
            );

            $elecToken->setTransaction($transaction);

            $this->em->persist($transaction);
            $this->em->persist($elecToken);

            $conn = $this->em->getConnection();
            $conn->beginTransaction();

            try {
                $this->em->flush();
                $conn->commit();

                $this->logger->info('Generated electricity token', [
                    'transID' => $transID,
                    'meterNumber' => $meterNumber,
                    'preview' => $preview,
                    'attempt' => $attempt,
                ]);

                return [
                    'transaction' => $transaction,
                    'plain_token' => $plain,
                    'tokenEntity' => $elecToken,
                ];
            } catch (UniqueConstraintViolationException $ex) {
                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }
                if ($this->em->contains($elecToken)) {
                    $this->em->detach($elecToken);
                }
                if ($this->em->contains($transaction)) {
                    $this->em->detach($transaction);
                }

                $this->logger->warning('Token or transaction collision, retrying', [
                    'attempt' => $attempt,
                    'exception' => $ex->getMessage(),
                ]);

                usleep(100000); // 100ms
                continue;
            } catch (\Throwable $e) {
                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }
                if ($this->em->contains($elecToken)) {
                    $this->em->detach($elecToken);
                }
                if ($this->em->contains($transaction)) {
                    $this->em->detach($transaction);
                }

                $this->logger->error('Failed to create verification token', [
                    'exception_class' => get_class($e),
                    'exception_message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'transID' => $transID,
                    'meterNumber' => $meterNumber,
                ]);

                throw $e;
            }
        }

        throw new \RuntimeException('Failed to generate unique token after multiple attempts');
    }


//    /**
//     * Create a verification transaction and electricity token.
//     *
//     * Returns:
//     *  - 'transaction' => ElectricityTransaction (managed)
//     *  - 'plain_token' => string (the plain token — returned once)
//     *  - 'tokenEntity' => ElectricityToken (managed)
//     *
//     * @throws \Throwable on unrecoverable errors
//     */
//    public function createVerificationAndToken(
//        string $transID,
//        string $meterNumber,
//        float $amount,
//        ?UserInterface $user = null
//    ): array {
//        $this->ensureDefaults();
//
//        // Return existing transaction + token if transID already exists
//        if ($transID) {
//            $existing = $this->em->getRepository(ElectricityTransaction::class)
//                ->findOneBy(['transID' => $transID]);
//
//            if ($existing) {
//                $plainToken = $existing->getToken() ?? $this->generatePlain16Digit();
//                return [
//                    'transaction' => $existing,
//                    'plain_token' => $plainToken,
//                    'tokenEntity' => $plainToken, // backward compatible
//                ];
//            }
//        }
//
//        $amountNormalized = (float) number_format($amount, 2, '.', '');
//        $attempt = 0;
//
//        while ($attempt < $this->tokenRetryLimit) {
//            $attempt++;
//
//            $transaction = new ElectricityTransaction();
//            $transaction->setTransID($transID)
//                ->setMeterNumber($meterNumber)
//                ->setAmount($amountNormalized)
//                ->setStatus('pending')
//                ->setUser($user)
//                ->setUpdatedAt(new \DateTimeImmutable());
//
//            $plain = $this->generateUnique16Digit();
//            $hash = $this->hashToken($plain);
//            $preview = substr(str_replace('-', '', $plain), -4);
//            $expiresAt = (new \DateTimeImmutable())->modify("+{$this->tokenExpiryMinutes} minutes");
//
//            $elecToken = new ElectricityToken(
//                $hash,
//                $meterNumber,
//                $amountNormalized,
//                $expiresAt,
//                $preview
//            );
//
//            $elecToken->setTransaction($transaction);
//
//            $this->em->persist($transaction);
//            $this->em->persist($elecToken);
//
//            $conn = $this->em->getConnection();
//            $conn->beginTransaction();
//
//            try {
//                $this->em->flush();
//                $conn->commit();
//
//                $this->logger->info('Generated electricity token', [
//                    'transID' => $transID,
//                    'meterNumber' => $meterNumber,
//                    'preview' => $preview,
//                    'attempt' => $attempt,
//                ]);
//
//                return [
//                    'transaction' => $transaction,
//                    'plain_token' => $plain,
//                    'tokenEntity' => $elecToken,
//                ];
//            } catch (UniqueConstraintViolationException $ex) {
//                if ($conn->isTransactionActive()) {
//                    $conn->rollBack();
//                }
//
//                // Detach entities to clean EM state
//                if ($this->em->contains($elecToken)) {
//                    $this->em->detach($elecToken);
//                }
//                if ($this->em->contains($transaction)) {
//                    $this->em->detach($transaction);
//                }
//
//                $this->logger->warning('Token or transaction collision, retrying', [
//                    'attempt' => $attempt,
//                    'exception' => $ex->getMessage(),
//                ]);
//
//                usleep(100000); // 100ms
//                continue;
//            } catch (\Throwable $e) {
//                if ($conn->isTransactionActive()) {
//                    $conn->rollBack();
//                }
//                if ($this->em->contains($elecToken)) {
//                    $this->em->detach($elecToken);
//                }
//                if ($this->em->contains($transaction)) {
//                    $this->em->detach($transaction);
//                }
//
//                $this->logger->error('Failed to create verification token', [
//                    'exception_class' => get_class($e),
//                    'exception_message' => $e->getMessage(),
//                    'trace' => $e->getTraceAsString(),
//                    'transID' => $transID,
//                    'meterNumber' => $meterNumber,
//                ]);
//
//                throw $e;
//            }
//        }
//
//        throw new \RuntimeException('Failed to generate unique token after multiple attempts');
//    }


    /**
     * Perform purchase using a plain token.
     *
     * Returns:
     *  - 'transaction' => ElectricityTransaction
     *  - 'receipt' => array (receipt details)
     *
     * @throws \RuntimeException on validation errors
     * @throws \Throwable on DB errors
     */
    public function purchaseWithToken(
        string $plainToken,
        string $transID,
        string $meterNumber,
        float $amount,
        ?UserInterface $user = null
    ): array {
        $this->ensureDefaults();

        try {
            //  Fetch candidate tokens
            $qb = $this->em->createQueryBuilder();
            $qb->select('t')
                ->from(ElectricityToken::class, 't')
                ->where('t.used = false')
                ->andWhere('t.meterNumber = :meterNumber')
                ->andWhere('(t.expiresAt IS NULL OR t.expiresAt > :now)')
                ->orderBy('t.createdAt', 'DESC')
                ->setParameter('meterNumber', $meterNumber)
                ->setParameter('now', new \DateTimeImmutable());

            $candidates = $qb->getQuery()->getResult();

            $found = null;

            //  Try to match token
            foreach ($candidates as $candidate) {
                if ($this->verifyTokenHash($plainToken, $candidate->getTokenHash())) {
                    $found = $candidate;
                    break;
                }
            }

            // Auto-create token if none found or token = 'auto'
            if (!$found || $plainToken === 'auto') {
                $this->logger->warning('No matching existing token — auto-creating token', [
                    'meterNumber' => $meterNumber,
                    'amount' => $amount,
                ]);

                // Use createVerificationAndToken to generate token
                $result = $this->createVerificationAndToken(
                    $transID,
                    $meterNumber,
                    $amount,
                    $user
                );

                if (!isset($result['tokenEntity']) || !$result['tokenEntity'] instanceof ElectricityToken) {
                    throw new \RuntimeException('Failed to generate electricity token');
                }

                $found = $result['tokenEntity'];
                $plainToken = $result['plain_token'];
            }


            //  Validate token info
            if ($found->isUsed()) {
                throw new \RuntimeException("Token already used");
            }

            if (bccomp($found->getAmount(), number_format($amount, 2, '.', ''), 2) !== 0) {
                throw new \RuntimeException("Token amount mismatch");
            }

            //  Load tariff
            $tariffs = $this->em->getRepository(ElectricityTariff::class)->findBy([], ['rate' => 'ASC']);
            if (empty($tariffs)) {
                throw new \RuntimeException('No tariff configured');
            }

            $tariff = $tariffs[0];
            $rate = (float)$tariff->getRate();
            $units = (int) floor($amount / $rate);

            $providerName = $tariff->getProvider()?->getName()
                ?? $this->em->getRepository(ElectricityProvider::class)->findOneBy([])?->getName()
                ?? 'Unknown';

            //  Begin DB transaction
            $conn = $this->em->getConnection();
            $conn->beginTransaction();

            try {
                $transaction = $found->getTransaction() ?? new ElectricityTransaction();

                if (!$transaction->getId()) {
                    $transaction->setTransID($transID)
                        ->setMeterNumber($meterNumber)
                        ->setAmount(number_format($amount, 2, '.', ''))
                        ->setUser($user)
                        ->setStatus('pending')
                        ->setUpdatedAt(new \DateTimeImmutable());
                    $this->em->persist($transaction);
                }

                $receiptNo = strtoupper(bin2hex(random_bytes(6)));

                $transaction->setReceiptNo($receiptNo)
                    ->setUnits($units)
                    ->setProvider($providerName)
                    ->setToken(substr($plainToken, -6))
                    ->setStatus('success')
                    ->setDetails([
                        'tariff_rate' => $tariff->getRate(),
                        'tariff_band' => $tariff->getBand(),
                    ])
                    ->setUpdatedAt(new \DateTimeImmutable());

                // Mark token as used
                $found->setUsed(true);
                $found->setTransaction($transaction);

                $this->em->flush();
                $conn->commit();

                return [
                    'transaction' => $transaction,
                    'receipt' => [
                        'receiptNo' => $receiptNo,
                        'units' => $units,
                        'provider' => $providerName,
                        'tariff' => (float)$tariff->getRate(),
                    ],
                    'token' => $plainToken,  // send plain token back to client
                ];
            } catch (\Throwable $e) {
                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }
                $this->em->clear();
                throw $e;
            }
        } catch (\Throwable $e) {
            $this->logger->error('PurchaseWithToken failed', [
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'transID' => $transID,
                'meterNumber' => $meterNumber,
                'plainToken' => $plainToken,
            ]);

            throw $e;
        }
    }



}

