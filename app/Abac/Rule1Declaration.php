<?php

declare(strict_types=1);

namespace App\Abac;

use App\Models\Declaration;
use App\Models\LegalEntity;
use App\Models\Person\Person;
use App\Models\Preperson;
use Illuminate\Database\Eloquent\Collection;

/**
 * Employee with an active declaration with the patient in the same legal entity can read all the patient's data.
 * see: https://e-health-ua.atlassian.net/wiki/spaces/ESOZ/pages/19244122467/DRAFT+rule_1
 */
class Rule1Declaration
{
    /**
     * Determine whether one of the user's employees in the legal entity has an active declaration with the patient.
     * A preperson never has a declaration.
     *
     * @param  Collection  $employees  User's employees in the legal entity
     * @param  Person|Preperson  $patient
     * @param  LegalEntity  $legalEntity
     * @return bool
     */
    public function allows(Collection $employees, Person|Preperson $patient, LegalEntity $legalEntity): bool
    {
        if ($patient instanceof Preperson) {
            return false;
        }

        return Declaration::active()
            ->wherePersonId($patient->id)
            ->filterByLegalEntityId($legalEntity->id)
            ->forEmployees($employees->pluck('id')->all())
            ->exists();
    }
}
