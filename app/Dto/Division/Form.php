<?php

declare(strict_types=1);

namespace App\Dto\Division;

use App\Models\Division;
use App\Models\Relations\Address;
use Illuminate\Support\Collection;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(source: Division::class)]
class Form
{
    /** @var array<string, mixed> */
    #[Map(source: 'attributes', transform: [self::class, 'divisionAttributes'])]
    public array $division = [];

    /** @var list<array<string, mixed>> */
    #[Map(source: '[addresses]', transform: [self::class, 'relationAttributes'])]
    public array $addresses = [];

    /** @var list<array<string, mixed>> */
    #[Map(source: '[phones]', transform: [self::class, 'relationAttributes'])]
    public array $phones = [];

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    public static function divisionAttributes(array $value, Division $source): array
    {
        return $source->attributesToArray();
    }

    /**
     * @param  Collection<int, \Illuminate\Database\Eloquent\Model>  $value
     * @return list<array<string, mixed>>
     */
    public static function relationAttributes(Collection $value): array
    {
        return $value->values()->toArray();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_replace($this->division, [
            'id' => $this->division['id'] ?? '',
            'uuid' => $this->division['uuid'] ?? '',
            'addresses' => $this->addresses,
            'phones' => $this->phones,
        ]);
    }

    /** @return array<string, mixed> */
    public function address(string $type): array
    {
        $address = ['country' => Address::DEFAULT_COUNTRY, 'type' => $type];

        foreach ($this->addresses as $item) {
            if (strtoupper($item['type'] ?? '') === $type) {
                $address = $item;
            }
        }

        return $address;
    }

    public function hasReceptionAddress(): bool
    {
        return array_any(
            $this->addresses,
            fn (array $address): bool => strtoupper($address['type'] ?? '') === Address::RECEPTION_TYPE
        );
    }
}
