<?php

declare(strict_types=1);

namespace App\Models\MedicalEvents\Sql;

use App\Casts\EHealthTimestampCast;
use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SpecimenCollection extends Model
{
    use HasCamelCasing;

    protected $fillable = [
        'specimen_id',
        'procedure_id',
        'collector_id',
        'collected_date_time',
        'duration_id',
        'quantity_id',
        'method_id',
        'body_site_id',
        'fasting_status_codeable_concept_id'
    ];

    protected $casts = [
        'collected_date_time' => EHealthTimestampCast::class
    ];

    protected $hidden = [
        'id',
        'specimen_id',
        'procedure_id',
        'collector_id',
        'duration_id',
        'quantity_id',
        'method_id',
        'body_site_id',
        'fasting_status_codeable_concept_id',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the specimen the collection belongs to.
     *
     * @return BelongsTo
     */
    public function specimen(): BelongsTo
    {
        return $this->belongsTo(Specimen::class);
    }

    /**
     * Get the procedure that collected the specimen.
     *
     * @return BelongsTo
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'procedure_id');
    }

    /**
     * Get the employee who collected the specimen.
     *
     * @return BelongsTo
     */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'collector_id');
    }

    /**
     * Get the period during which the specimen was collected.
     *
     * @return MorphOne
     */
    public function collectedPeriod(): MorphOne
    {
        return $this->morphOne(Period::class, 'periodable');
    }

    /**
     * Get how long it took to collect the specimen.
     *
     * @return BelongsTo
     */
    public function duration(): BelongsTo
    {
        return $this->belongsTo(Quantity::class, 'duration_id');
    }

    /**
     * Get the quantity of specimen collected.
     *
     * @return BelongsTo
     */
    public function quantity(): BelongsTo
    {
        return $this->belongsTo(Quantity::class, 'quantity_id');
    }

    /**
     * Get the technique used to perform the collection.
     *
     * @return BelongsTo
     */
    public function method(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'method_id');
    }

    /**
     * Get the anatomical collection site.
     *
     * @return BelongsTo
     */
    public function bodySite(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'body_site_id');
    }

    /**
     * Get whether or how long the patient abstained from food and drink.
     *
     * @return BelongsTo
     */
    public function fastingStatusCodeableConcept(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'fasting_status_codeable_concept_id');
    }
}
