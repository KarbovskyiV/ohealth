<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MedicalEvents;

use App\Enums\DeviceDispense\Status;
use App\Services\MedicalEvents\EncounterPackageBuilder;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EncounterPackageBuilderDeviceDispenseTest extends TestCase
{
    #[Test]
    public function to_fhir_maps_based_on_device_request_and_quantity_code(): void
    {
        $basedOnId = (string) Str::uuid();
        $encounterUuid = (string) Str::uuid();

        $payload = $this->builder()->toFhir($this->package([
            'basedOnId' => $basedOnId,
            'performerId' => (string) Str::uuid(),
            'locationId' => (string) Str::uuid(),
            'whenHandedOverDate' => '15.09.2026',
            'whenHandedOverTime' => '10:15',
            'quantity' => 2,
            'quantityCode' => 'piece',
            'deviceSelectionType' => 'type',
            'deviceCode' => '30221',
            'status' => Status::COMPLETED->value,
        ]), [
            'encounter' => $encounterUuid,
            'visit' => (string) Str::uuid(),
            'episode' => (string) Str::uuid(),
        ]);

        $dispense = $payload['deviceDispenses'][0];

        $this->assertSame($basedOnId, data_get($dispense, 'basedOn.identifier.value'));
        $this->assertSame('device_request', data_get($dispense, 'basedOn.identifier.type.coding.0.code'));
        $this->assertSame($encounterUuid, data_get($dispense, 'encounter.identifier.value'));
        $this->assertSame(2, data_get($dispense, 'details.0.quantity.value'));
        $this->assertSame('piece', data_get($dispense, 'details.0.quantity.code'));
        $this->assertSame('30221', data_get($dispense, 'details.0.deviceCode.coding.0.code'));
    }

    #[Test]
    public function to_fhir_maps_model_without_based_on(): void
    {
        $deviceDefinitionId = (string) Str::uuid();

        $payload = $this->builder()->toFhir($this->package([
            'performerId' => (string) Str::uuid(),
            'locationId' => (string) Str::uuid(),
            'whenHandedOverDate' => '15.09.2026',
            'whenHandedOverTime' => '11:00',
            'quantity' => 1,
            'deviceSelectionType' => 'model',
            'deviceDefinitionId' => $deviceDefinitionId,
        ]), [
            'encounter' => (string) Str::uuid(),
            'visit' => (string) Str::uuid(),
            'episode' => (string) Str::uuid(),
        ]);

        $dispense = $payload['deviceDispenses'][0];

        $this->assertArrayNotHasKey('basedOn', $dispense);
        $this->assertSame($deviceDefinitionId, data_get($dispense, 'details.0.device.identifier.value'));
    }

    private function builder(): EncounterPackageBuilder
    {
        return new EncounterPackageBuilder();
    }

    /**
     * @param  array<string, mixed>  $deviceDispense
     * @return array<string, mixed>
     */
    private function package(array $deviceDispense): array
    {
        return [
            'encounter' => [
                'periodDate' => '15.09.2026',
                'periodStart' => '10:00',
                'periodEnd' => '10:30',
                'classCode' => 'AMB',
                'typeCode' => 'service_delivery_location',
                'performerId' => (string) Str::uuid(),
                'referralType' => '',
                'diagnoses' => [],
            ],
            'deviceDispenses' => [$deviceDispense],
        ];
    }
}
