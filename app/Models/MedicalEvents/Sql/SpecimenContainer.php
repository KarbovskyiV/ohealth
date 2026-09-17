<?php

declare(strict_types=1);

namespace App\Models\MedicalEvents\Sql;

use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecimenContainer extends Model
{
    use HasCamelCasing;

    protected $fillable = [
        'specimen_id',
        'identifier',
        'description',
        'type_id',
        'capacity_id',
        'specimen_quantity_id',
        'additive_codeable_concept_id'
    ];

    protected $hidden = [
        'id',
        'specimen_id',
        'type_id',
        'capacity_id',
        'specimen_quantity_id',
        'additive_codeable_concept_id',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the specimen the container belongs to.
     *
     * @return BelongsTo
     */
    public function specimen(): BelongsTo
    {
        return $this->belongsTo(Specimen::class);
    }

    /**
     * Get the container type.
     *
     * @return BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'type_id');
    }

    /**
     * Get the container volume or size.
     *
     * @return BelongsTo
     */
    public function capacity(): BelongsTo
    {
        return $this->belongsTo(Quantity::class, 'capacity_id');
    }

    /**
     * Get the quantity of specimen within the container.
     *
     * @return BelongsTo
     */
    public function specimenQuantity(): BelongsTo
    {
        return $this->belongsTo(Quantity::class, 'specimen_quantity_id');
    }

    /**
     * Get the additive associated with the container.
     *
     * @return BelongsTo
     */
    public function additiveCodeableConcept(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'additive_codeable_concept_id');
    }
}
