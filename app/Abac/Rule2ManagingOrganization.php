<?php

declare(strict_types=1);

namespace App\Abac;

use App\Models\LegalEntity;
use Illuminate\Database\Eloquent\Builder;

/**
 * Employee can read the records created in the employee's legal entity.
 * see: https://e-health-ua.atlassian.net/wiki/spaces/ESOZ/pages/19244122467/DRAFT+rule_2
 */
class Rule2ManagingOrganization
{
    /**
     * Limit the query to the records managed by the legal entity. A record without a managing organization came from
     * a short (summary) response that does not return one, so it is kept: there is nothing to tell it apart.
     *
     * @param  Builder  $query  Query of a model with the managingOrganization relation
     * @param  LegalEntity  $legalEntity
     * @return Builder
     */
    public function apply(Builder $query, LegalEntity $legalEntity): Builder
    {
        return $query->where(
            static fn (Builder $record): Builder => $record
                ->whereNull('managing_organization_id')
                ->orWhereHas(
                    'managingOrganization',
                    static fn (Builder $identifier): Builder => $identifier->whereValue($legalEntity->uuid)
                )
        );
    }
}
