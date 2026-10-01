<?php

declare(strict_types=1);

namespace App\Abac;

use App\Enums\Person\ApprovalStatus;
use App\Models\MedicalEvents\Sql\Approval;
use App\Models\Person\Person;
use App\Models\Preperson;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Employee with an active approval on the patient's data can read all the patient's data.
 * see: https://e-health-ua.atlassian.net/wiki/spaces/ESOZ/pages/19244122467/DRAFT+rule_4
 */
class Rule4Approval
{
    /**
     * Determine whether the patient granted an active approval on their data to one of the user's employees
     * in the legal entity.
     *
     * @param  Collection  $employees  User's employees in the legal entity
     * @param  Person|Preperson  $patient
     * @return bool
     */
    public function allows(Collection $employees, Person|Preperson $patient): bool
    {
        $employeeIds = $employees->pluck('uuid')->all();

        return Approval::isAlive($patient)
            ->isVerified()
            ->whereStatus(ApprovalStatus::ACTIVE)
            ->whereGrantedToType('employee')
            ->whereHas(
                'grantedTo',
                static fn (Builder $identifier): Builder => $identifier->whereIn('value', $employeeIds)
            )
            ->exists();
    }
}
