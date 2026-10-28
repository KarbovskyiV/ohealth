<?php

declare(strict_types=1);

namespace App\Livewire\CarePlan\Forms;

use App\Core\Arr;
use App\Enums\CarePlanActivityKind;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class CarePlanActivityForm extends Form
{
    public static function buildCreateContent(array $resource): array
    {
        Validator::make($resource, [
            'id' => ['required', 'uuid'], 'author' => ['required', 'array', 'min:1'],
            'author.*.identifier.value' => ['required', 'uuid'],
            'carePlan.identifier.value' => ['required', 'uuid'],
            'detail.kind' => ['required', 'in:'.implode(',', array_column(CarePlanActivityKind::cases(), 'value'))],
        ])->validate();
        $detail = $resource['detail'];
        if ($detail['kind'] === CarePlanActivityKind::DEVICE_REQUEST->value) {
            Validator::make($detail, [
                'quantity.value' => ['sometimes', 'integer', 'min:1'],
            ], [], ['quantity.value' => __('care-plan.ehealth_fields.quantity_value')])->validate();
            if (isset($detail['quantity']['value'])) {
                $detail['quantity']['value'] = (int) $detail['quantity']['value'];
            }
        }
        if (isset($detail['productReference']) === isset($detail['productCodeableConcept'])) {
            throw ValidationException::withMessages(['product' => 'Exactly one product variant is required.']);
        }
        $content = array_intersect_key($resource, array_flip(['id', 'author', 'carePlan']));
        $content['detail'] = array_intersect_key($detail, array_flip(['kind', 'productReference', 'productCodeableConcept', 'reasonCode', 'reasonReference', 'goal', 'quantity', 'location', 'performer', 'dailyAmount', 'scheduledPeriod', 'scheduledTiming', 'scheduledString', 'description', 'program', 'doNotPerform']));
        $content['detail']['status'] = 'scheduled';

        return self::resourcePayload($content);
    }

    public static function buildCancelContent(array $snapshot, array $reason): array
    {
        Validator::make($snapshot, ['id' => ['required', 'uuid'], 'detail' => ['required', 'array']])->validate();
        $snapshot['detail']['status_reason'] = $reason;

        return $snapshot;
    }

    public static function buildCompleteContent(array $reason, array $outcomes): array
    {
        Validator::make(['reason' => $reason, 'outcomes' => $outcomes], [
            'reason.coding' => ['required', 'array', 'min:1'],
            'outcomes' => ['required', 'array', 'min:1'],
            'outcomes.*.coding' => ['required', 'array', 'min:1'],
        ])->validate();

        return ['detail' => ['status_reason' => $reason], 'outcome_codeable_concept' => $outcomes];
    }

    private static function resourcePayload(array $data): array
    {
        $result = [];
        foreach (Arr::toSnakeCase($data) as $key => $value) {
            if (is_array($value)) {
                $value = self::resourcePayload($value);
            }
            if ($value !== null && $value !== '' && $value !== []) {
                $result[$key] = $value;
            }
        }

        return array_is_list($data) ? array_values($result) : $result;
    }
}
