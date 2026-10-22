<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Classes\eHealth\EHealth;
use App\Classes\eHealth\EHealthResponse;
use App\Core\EHealthJob;
use App\Core\PatientEHealthJob;
use App\Models\LegalEntity;
use App\Repositories\MedicalEvents\Repository;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Queue\Middleware\RateLimited;
use Throwable;

class DeviceDispenseSync extends PatientEHealthJob
{
    public const string BATCH_NAME = 'DeviceDispenseSync';

    public const string SCOPE_REQUIRED = 'device_dispense:read';

    public const string ENTITY = LegalEntity::ENTITY_DEVICE_DISPENSE;

    /**
     * {@inheritDoc}
     */
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse
    {
        return EHealth::deviceDispense()
            ->withToken($token)
            ->getBySearchParams($this->patientUuid, array_filter([
                'performer_legal_entity' => $this->hasPatientAccess ? null : $this->legalEntity->uuid,
                'page' => $this->page
            ]));
    }

    /**
     * {@inheritDoc}
     *
     * @throws Throwable
     */
    protected function processResponse(?EHealthResponse $response): void
    {
        $validatedData = $response?->validate();

        if (empty($validatedData)) {
            return;
        }

        Repository::deviceDispense()->sync($this->patient(), $validatedData);
    }

    /**
     * {@inheritDoc}
     */
    protected function getNextEntityJob(): ?EHealthJob
    {
        return $this->standalone || !$this->nextEntity ? new CompleteSync(legalEntity: $this->legalEntity, isFirstLogin: $this->isFirstLogin) : $this->nextEntity;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAdditionalMiddleware(): array
    {
        return [ new RateLimited('ehealth-device-dispense-get') ];
    }
}
