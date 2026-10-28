<?php

declare(strict_types=1);

namespace App\Repositories\MedicalEvents;

use App\Models\MedicalEvents\Sql\Identifier as SqlIdentifier;

/** @property SqlIdentifier $model  */
class IdentifierRepository extends BaseRepository
{
    public function update(SqlIdentifier $identifier, array $data): SqlIdentifier
    {
        $identifier->fill([
            'value' => $data['identifier']['value'],
            'display_value' => $data['displayValue'] ?? $data['display_value'] ?? null,
        ])->save();
        if (isset($data['identifier']['type'])) {
            Repository::codeableConcept()->attach($identifier, $data);
        } else {
            foreach ($identifier->type()->get() as $concept) {
                Repository::codeableConcept()->delete($concept);
            }
        }
        $identifier->unsetRelation('type');

        return $identifier;
    }

    /** The caller must eager-load type.coding. */
    public function toReference(?SqlIdentifier $identifier): ?array
    {
        if ($identifier === null) {
            return null;
        }

        $data = ['identifier' => ['value' => $identifier->value]];
        $type = $identifier->getRelation('type')->first();
        if ($type) {
            $data['identifier']['type'] = Repository::codeableConcept()->toResource($type);
        }
        if ($identifier->displayValue !== null) {
            $data['displayValue'] = $identifier->displayValue;
        }

        return $data;
    }

    /**
     * Create identifier in DB.
     *
     * @param  string  $value
     * @param  string|null  $displayValue
     * @return SqlIdentifier
     */
    public function store(string $value, ?string $displayValue = null): SqlIdentifier
    {
        return $this->model->create([
            'value' => $value,
            'display_value' => $displayValue
        ]);
    }

}
