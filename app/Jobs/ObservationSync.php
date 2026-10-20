<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Core\EHealthJob;
use App\Core\PatientEHealthJob;
use App\Models\LegalEntity;
use App\Classes\eHealth\EHealth;
use App\Repositories\MedicalEvents\Repository;
use GuzzleHttp\Promise\PromiseInterface;
use App\Classes\eHealth\EHealthResponse;
use Illuminate\Queue\Middleware\RateLimited;
use Throwable;

class ObservationSync extends PatientEHealthJob
{
    public const string BATCH_NAME = 'ObservationSync';

    public const string SCOPE_REQUIRED = 'observation:read';

    public const string ENTITY = LegalEntity::ENTITY_OBSERVATION;

    /**
     * {@inheritDoc}
     */
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse
    {
        return EHealth::observation()
            ->withToken($token)
            ->getBySearchParams($this->patientUuid, array_filter([
                'managing_organization_id' => $this->hasPatientAccess ? null : $this->legalEntity->uuid,
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

        Repository::observation()->sync($this->patient(), $validatedData);
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
            new RateLimited('ehealth-observation-get')
        ];
    }
}
