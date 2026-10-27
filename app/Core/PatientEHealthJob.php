<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Person\Person;
use App\Models\Preperson;

/**
 * A sync job for the records of one patient, who is passed in the batch options.
 */
abstract class PatientEHealthJob extends EHealthJob
{
    /**
     * eHealth ID of the patient whose records are synced.
     *
     * @var string|null
     */
    protected ?string $patientUuid = null;

    protected ?int $personId = null;

    protected ?int $prepersonId = null;

    /**
     * Whether the user has access to all the patient's records, so the request is not narrowed to the legal entity.
     *
     * @var bool
     */
    protected bool $hasPatientAccess = false;

    /**
     * Read the patient from the batch options before the job runs.
     *
     * @return void
     */
    public function handle(): void
    {
        $options = $this->batch()->options;

        $this->patientUuid = $options['patient_uuid'] ?? null;
        $this->personId = $options['person_id'] ?? null;
        $this->prepersonId = $options['preperson_id'] ?? null;
        $this->hasPatientAccess = $options['has_patient_access'] ?? false;

        parent::handle();
    }

    /**
     * The patient whose records are synced.
     *
     * @return Person|Preperson
     */
    protected function patient(): Person|Preperson
    {
        return $this->prepersonId !== null
            ? Preperson::findOrFail($this->prepersonId)
            : Person::findOrFail($this->personId);
    }
}
