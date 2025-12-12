<?php
//
//namespace App\Dto\V1;
//
//use Symfony\Component\Validator\Constraints as Assert;
//
//class VerifyCustomerRequest
//{
//    #[Assert\NotBlank]
//    #[Assert\Length(min: 5, max: 50)]
//    public ?string $transID = null;
//
//    #[Assert\NotBlank]
//    #[Assert\Length(min: 10, max: 20)]
//    public ?string $meterNumber = null;
//
//    #[Assert\NotNull]
//    #[Assert\Type(type: 'numeric')]
//    #[Assert\Positive]
//    #[Assert\GreaterThanOrEqual(value: 10)]
////    public $amount = null;
//
//    #[Assert\NotBlank]
//    #[Assert\Email]
//    public ?string $email = null;
//
//    #[Assert\NotBlank]
//    #[Assert\Length(min: 6, max: 128)]
//    public ?string $password = null;
//}

// src/Dto/V1/VerifyCustomerRequest.php
namespace App\Dto\V1;

use Symfony\Component\Validator\Constraints as Assert;

class VerifyCustomerRequest
{
    /**
     * @Assert\NotBlank(groups={"create"})
     * @Assert\Length(min=5, max=50)
     */
    public ?string $transID = null;

    /**
     * @Assert\NotBlank(groups={"create"})
     * @Assert\Length(min=6, max=20)
     */
    public ?string $meterNumber = null;

    /**
     * Numeric amount
     * @Assert\NotBlank(groups={"create"})
     * @Assert\Type(type="numeric")
     * @Assert\GreaterThanOrEqual(10)
     */
    public ?float $amount = null;

    /**
     * @Assert\Email
     */
    public ?string $email = null;

    /**
     * @Assert\NotBlank(groups={"create"})
     * @Assert\Length(min=6, max=128)
     */
    public ?string $password = null;
}
