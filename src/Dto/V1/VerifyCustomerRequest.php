<?php

namespace App\Dto\V1;

use Symfony\Component\Validator\Constraints as Assert;

class VerifyCustomerRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 50)]
    public ?string $transID = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 20)]
    public ?string $meterNumber = null;

    #[Assert\NotNull]
    #[Assert\Type(type: 'numeric')]
    #[Assert\Positive]
    #[Assert\GreaterThanOrEqual(value: 10)]
    public $amount = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6, max: 128)]
    public ?string $password = null;
}
