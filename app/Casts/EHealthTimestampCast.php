<?php

declare(strict_types=1);

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class EHealthTimestampCast implements CastsAttributes
{
    /**
     * {@inheritDoc}
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->format(config('app.date_format'));
    }

    /**
     * {@inheritDoc}
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
