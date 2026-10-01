<?php

declare(strict_types=1);

namespace App\Abac;

use App\Enums\Person\ApprovalStatus;
use App\Models\LegalEntity;
use App\Models\MedicalEvents\Sql\Approval;
use App\Models\MedicalEvents\Sql\Episode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Employee with an active approval, or an employee of the legal entity with an active approval, can read the episode
 * the approval is given on.
 * see: https://e-health-ua.atlassian.net/wiki/spaces/ESOZ/pages/19244123079/DRAFT+rule_5
 */
class Rule5EpisodeApproval
{
    /**
     * Determine whether there is an active approval on the episode granted to one of the user's employees
     * in the legal entity, or to the legal entity itself.
     *
     * @param  Collection  $employees  User's employees in the legal entity
     * @param  Episode  $episode
     * @param  LegalEntity  $legalEntity
     * @return bool
     */
    public function allows(Collection $employees, Episode $episode, LegalEntity $legalEntity): bool
    {
        $employeeIds = $employees->pluck('uuid')->all();

        return Approval::isAlive($episode)
            ->isVerified()
            ->whereStatus(ApprovalStatus::ACTIVE)
            ->where(
                static fn (Builder $approval): Builder => $approval
                    ->where(
                        static fn (Builder $toEmployee): Builder => $toEmployee->whereGrantedToType('employee')
                            ->whereHas(
                                'grantedTo',
                                static fn (Builder $identifier): Builder => $identifier->whereIn('value', $employeeIds)
                            )
                    )
                    ->orWhere(
                        static fn (Builder $toLegalEntity): Builder => $toLegalEntity->whereGrantedToType('legal_entity')
                            ->whereHas(
                                'grantedTo',
                                static fn (Builder $identifier): Builder => $identifier->whereValue($legalEntity->uuid)
                            )
                    )
            )
            ->exists();
    }
}
