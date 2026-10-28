<?php

declare(strict_types=1);

namespace App\Repositories\MedicalEvents;

use Illuminate\Database\Eloquent\Model;
use App\Models\MedicalEvents\Sql\Period;
use Carbon\CarbonImmutable;

class PeriodRepository extends BaseRepository
{
    /** Shared periods keep UTC instants; prescriptions also retain the source precision and offset. */
    public function toResource(Period $period, bool $preservePrecision = false): array
    {
        $data = [];
        foreach (['start', 'end'] as $boundary) {
            $value = $period->getRawOriginal($boundary);
            $instant = $value === null ? null : CarbonImmutable::parse($value, 'UTC')->utc();
            if ($instant === null) {
                $data[$boundary] = null;
                continue;
            }
            $precision = $preservePrecision ? $period->getRawOriginal($boundary.'_precision') : null;
            $timezone = $preservePrecision ? $period->getRawOriginal($boundary.'_timezone') : null;
            if ($precision === null) {
                $data[$boundary] = rtrim(rtrim($instant->format('Y-m-d\TH:i:s.u'), '0'), '.').'Z';
                continue;
            }
            $local = $instant->setTimezone($timezone === 'Z' || $timezone === null ? 'UTC' : $timezone);
            $data[$boundary] = match ($precision) {
                'year' => $local->format('Y'),
                'month' => $local->format('Y-m'),
                'day' => $local->format('Y-m-d'),
                'minute' => $local->format('Y-m-d\TH:i').$timezone,
                'second' => $local->format('Y-m-d\TH:i:s').$timezone,
                default => $local->format('Y-m-d\TH:i:s').'.'.substr($local->format('u'), 0, (int) substr($precision, 9)).$timezone,
            };
        }

        return $data;
    }

    public function sync(Model $periodable, ?array $periodData, string $relation = 'period', bool $preservePrecision = false): void
    {
        $relationship = $periodable->{$relation}();
        if (empty($periodData)) {
            $relationship->delete();
            $periodable->unsetRelation($relation);

            return;
        }

        $period = $relationship->first() ?? $relationship->make();
        foreach (['start', 'end'] as $boundary) {
            if ($boundary === 'start' && !array_key_exists($boundary, $periodData)) {
                continue;
            }
            $value = $periodData[$boundary] ?? null;
            $parseValue = match (true) {
                is_string($value) && preg_match('/^\d{4}$/D', $value) === 1 => $value.'-01-01',
                is_string($value) && preg_match('/^\d{4}-\d{2}$/D', $value) === 1 => $value.'-01',
                default => $value,
            };
            $attributes = [$boundary => $parseValue === null ? null : CarbonImmutable::parse($parseValue, 'UTC')->utc()->format('Y-m-d H:i:s.u')];
            if ($preservePrecision) {
                [$precision, $timezone] = $this->precision($value);
                $attributes[$boundary.'_precision'] = $precision;
                $attributes[$boundary.'_timezone'] = $timezone;
            }
            // Bypass the presentation cast, which truncates seconds and converts to localized text.
            $period->setRawAttributes(array_replace($period->getAttributes(), $attributes));
        }
        $period->save();
        $periodable->setRelation($relation, $period);
    }

    private function precision(?string $value): array
    {
        if ($value === null) {
            return [null, null];
        }
        if (preg_match('/^\d{4}(?:-\d{2})?(?:-\d{2})?$/D', $value)) {
            return [match (strlen($value)) { 4 => 'year', 7 => 'month', default => 'day' }, null];
        }
        if (preg_match('/T\d{2}:\d{2}(?::\d{2}(?:\.(\d{1,6}))?)?(Z|[+-]\d{2}:\d{2})$/D', $value, $matches)) {
            $precision = !empty($matches[1]) ? 'fraction:'.strlen($matches[1]) : (substr_count($value, ':') - substr_count($matches[2], ':') === 2 ? 'second' : 'minute');

            return [$precision, $matches[2]];
        }

        return [null, null];
    }
}
