<?php

declare(strict_types=1);

namespace App\Dto\Address;

use App\Contracts\Dto\Model as ModelContract;
use App\Models\Relations\Address as AddressModel;
use App\Classes\eHealth\Api\Responses\Collections\AddressCreate;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Condition\SourceClass;
use Illuminate\Support\Collection;

#[Map(source: AddressCreate::class)]
#[Map(source: Collection::class)]
class Model implements ModelContract
{
    #[Map(source: '[type]')]
    public ?string $type = null;

    #[Map(source: '[country]')]
    public ?string $country = null;

    #[Map(source: '[area]')]
    public ?string $area = null;

    #[Map(source: '[region]')]
    public ?string $region = null;

    #[Map(source: '[settlement]')]
    public ?string $settlement = null;

    #[Map(source: '[settlement_id]', if: new SourceClass(AddressCreate::class))]
    #[Map(source: '[settlementId]', if: [self::class, 'isFormAddress'])]
    public ?string $settlementId = null;

    #[Map(source: '[settlement_type]', if: new SourceClass(AddressCreate::class))]
    #[Map(source: '[settlementType]', if: [self::class, 'isFormAddress'])]
    public ?string $settlementType = null;

    #[Map(source: '[street_type]', if: new SourceClass(AddressCreate::class))]
    #[Map(source: '[streetType]', if: [self::class, 'isFormAddress'])]
    public ?string $streetType = null;

    #[Map(source: '[street]')]
    public ?string $street = null;

    #[Map(source: '[building]')]
    public ?string $building = null;

    #[Map(source: '[apartment]')]
    public ?string $apartment = null;

    #[Map(source: '[zip]')]
    public ?string $zip = null;

    public static function isFormAddress(mixed $value, object $source): bool
    {
        return $source instanceof Collection && !$source instanceof AddressCreate;
    }

    public function toModel(): AddressModel
    {
        return new AddressModel(get_object_vars($this));
    }
}
