<?php

declare(strict_types=1);

namespace App\Dto\Division;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use App\Livewire\Division\Forms\DivisionForm;
use Brick\Math\Exception\NumberFormatException;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(source: DivisionForm::class)]
class Location
{
    private const int DECIMAL_PLACES = 14;

    #[Map(source: 'division[location?][latitude?]', transform: [self::class, 'normalizeCoordinate'])]
    public mixed $latitude = null;

    #[Map(source: 'division[location?][longitude?]', transform: [self::class, 'normalizeCoordinate'])]
    public mixed $longitude = null;

    public static function normalizeCoordinate(mixed $value): mixed
    {
        $normalized = \is_string($value) ? str_replace(',', '.', $value) : $value;

        if (!\is_numeric($normalized) || !is_finite((float) $normalized)) {
            return $value;
        }

        $number = \is_float($normalized)
            ? json_encode($normalized, JSON_THROW_ON_ERROR)
            : trim((string) $normalized);

        try {
            return (string) BigDecimal::of($number)
                ->toScale(self::DECIMAL_PLACES, RoundingMode::Down)
                ->strippedOfTrailingZeros();
        } catch (NumberFormatException) {
            return $value;
        }
    }

    /** @return array{latitude: mixed, longitude: mixed} */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
