<?php
//
//namespace App\Dto\V1;
//
//use Symfony\Component\Validator\Constraints as Assert;
//
//class PurchaseElectricityRequest
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
//    public $amount = null;
//
//    #[Assert\NotBlank]
//    #[Assert\Length(min: 8, max: 255)]
//    public ?string $elec_token = null;
//}


// src/Dto/V1/PurchaseElectricityRequest.php
namespace App\Dto\V1;

use Symfony\Component\Validator\Constraints as Assert;

class PurchaseElectricityRequest
{
    /**
     * @Assert\NotBlank(groups={"create"})
     */
    public ?string $transID = null;

    /**
     * @Assert\NotBlank(groups={"create"})
     */
    public ?string $meterNumber = null;

    /**
     * @Assert\NotBlank(groups={"create"})
     * @Assert\Type(type="numeric")
     * @Assert\GreaterThanOrEqual(10)
     */
    public ?float $amount = null;

    /**
     * @Assert\NotBlank(groups={"create"})
     */
    public ?string $elec_token = null;
}
