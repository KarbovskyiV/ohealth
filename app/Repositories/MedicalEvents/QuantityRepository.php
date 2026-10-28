<?php

declare(strict_types=1);

namespace App\Repositories\MedicalEvents;

use App\Models\MedicalEvents\Sql\Quantity;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\MathException;
use InvalidArgumentException;

class QuantityRepository extends BaseRepository
{
    public function __construct(Quantity $model)
    {
        parent::__construct($model);
    }

    public function replaceComplete(?Quantity $quantity, ?array $data): ?Quantity
    {
        if ($data === null) {
            return null;
        }
        $value = $data['value'] ?? null;
        if (!is_numeric($value) || !is_finite((float) $value)) {
            throw new InvalidArgumentException('Quantity must be a finite number.');
        }
        $numeric = is_float($value) ? json_encode($value, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR) : (string) $value;
        try {
            $decimal = BigDecimal::of($numeric)->toScale(10, RoundingMode::UNNECESSARY);
        } catch (MathException $exception) {
            throw new InvalidArgumentException('Quantity exceeds the supported decimal scale; rounding is not permitted.', previous: $exception);
        }
        if ($decimal->abs()->isGreaterThanOrEqualTo('100000000000000')) {
            throw new InvalidArgumentException('Quantity is outside the supported numeric range.');
        }
        if (!$decimal->isEqualTo(json_encode((float) $value, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR))) {
            throw new InvalidArgumentException('Quantity cannot be represented without loss in the JSON numeric contract.');
        }
        $quantity ??= new Quantity();
        $quantity->fill([
            'value' => (string) $decimal->stripTrailingZeros(),
            'comparator' => $data['comparator'] ?? null,
            'unit' => $data['unit'] ?? null,
            'system' => $data['system'] ?? null,
            'code' => $data['code'] ?? null,
        ])->save();

        return $quantity;
    }

    public function toResource(?Quantity $quantity): ?array
    {
        if ($quantity === null) {
            return null;
        }
        $data = ['value' => $quantity->value];
        foreach (['comparator', 'unit', 'system', 'code'] as $field) {
            if ($quantity->{$field} !== null) {
                $data[$field] = $quantity->{$field};
            }
        }

        return $data;
    }
}
