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

class DeviceAssociationSync extends PatientEHealthJob
{
    public const string BATCH_NAME = 'DeviceAssociationSync';

    public const string SCOPE_REQUIRED = 'device_association:read';

    public const string ENTITY = LegalEntity::ENTITY_DEVICE_ASSOCIATION;

    /**
     * Read the patient the batch was started for, then process the page.
     *
     * @return void
     */
    /**
     * {@inheritDoc}
     */
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse
    {
        return EHealth::deviceAssociation()
            ->withToken($token)
            ->getBySearchParams($this->patientUuid, [
                'recorder_legal_entity_id' => $this->legalEntity->uuid,
                'page' => $this->page
            ]);
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

        Repository::deviceAssociation()->sync($this->patient(), $validatedData);
    }

    /**
     * {@inheritDoc}
     */
    protected function getNextEntityJob(): ?EHealthJob
    {
        return $this->standalone || !$this->nextEntity
            ? new CompleteSync(legalEntity: $this->legalEntity, isFirstLogin: $this->isFirstLogin)
            : $this->nextEntity;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAdditionalMiddleware(): array
    {
        return [
            new RateLimited('ehealth-device-association-get')
        ];
    }
}
