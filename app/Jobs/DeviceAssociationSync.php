<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Classes\eHealth\EHealth;
use App\Classes\eHealth\EHealthResponse;
use App\Core\EHealthJob;
use App\Models\LegalEntity;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Repositories\MedicalEvents\Repository;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Queue\Middleware\RateLimited;
use Throwable;

class DeviceAssociationSync extends EHealthJob
{
    public const string BATCH_NAME = 'DeviceAssociationSync';

    public const string SCOPE_REQUIRED = 'device_association:read';

    public const string ENTITY = LegalEntity::ENTITY_DEVICE_ASSOCIATION;

    protected ?string $patientUuid = null;

    protected ?int $personId = null;

    protected ?int $prepersonId = null;

    /**
     * Read the patient the batch was started for, then process the page.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->patientUuid = $this->batch()->options['patient_uuid'] ?? null;
        $this->personId = $this->batch()->options['person_id'] ?? null;
        $this->prepersonId = $this->batch()->options['preperson_id'] ?? null;

        parent::handle();
    }

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

        $patient = $this->prepersonId !== null
            ? Preperson::findOrFail($this->prepersonId)
            : Person::findOrFail($this->personId);

        Repository::deviceAssociation()->sync($patient, $validatedData);
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
