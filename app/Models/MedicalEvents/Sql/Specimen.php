<?php

declare(strict_types=1);

namespace App\Models\MedicalEvents\Sql;

use App\Casts\EHealthTimestampCast;
use App\Enums\Specimen\Status;
use App\Models\Person\Person;
use App\Models\Preperson;
use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Specimen extends Model
{
    use HasCamelCasing;

    protected $fillable = [
        'uuid',
        'accession_identifier',
        'person_id',
        'preperson_id',
        'status',
        'type_id',
        'condition_id',
        'note',
        'managing_organization_id',
        'registered_by_id',
        'context_id',
        'received_time',
        'status_reason_id',
        'explanatory_letter',
        'ehealth_inserted_at',
        'ehealth_inserted_by',
        'ehealth_updated_at',
        'ehealth_updated_by'
    ];

    protected $casts = [
        'status' => Status::class,
        'received_time' => EHealthTimestampCast::class,
        'ehealth_inserted_at' => EHealthTimestampCast::class,
        'ehealth_updated_at' => EHealthTimestampCast::class
    ];

    protected $hidden = [
        'id',
        'person_id',
        'preperson_id',
        'type_id',
        'condition_id',
        'managing_organization_id',
        'registered_by_id',
        'context_id',
        'status_reason_id',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the preperson the specimen belongs to.
     *
     * @return BelongsTo
     */
    public function preperson(): BelongsTo
    {
        return $this->belongsTo(Preperson::class);
    }

    /**
     * Get the specimen type.
     *
     * @return BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'type_id');
    }

    /**
     * Get the specimen condition.
     *
     * @return BelongsTo
     */
    public function condition(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'condition_id');
    }

    /**
     * Get the legal entity where the specimen was created.
     *
     * @return BelongsTo
     */
    public function managingOrganization(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'managing_organization_id');
    }

    /**
     * Get the employee who registered the specimen.
     *
     * @return BelongsTo
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'registered_by_id');
    }

    /**
     * Get the encounter within which the specimen was created.
     *
     * @return BelongsTo
     */
    public function context(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'context_id');
    }

    /**
     * Get the reason the specimen became unavailable.
     *
     * @return BelongsTo
     */
    public function statusReason(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'status_reason_id');
    }

    /**
     * Get the specimens from which this specimen originated.
     *
     * @return BelongsToMany
     */
    public function parent(): BelongsToMany
    {
        return $this->belongsToMany(Identifier::class, 'specimen_parents')->withTimestamps();
    }

    /**
     * Get the service requests the specimen was collected for.
     *
     * @return BelongsToMany
     */
    public function request(): BelongsToMany
    {
        return $this->belongsToMany(Identifier::class, 'specimen_requests')->withTimestamps();
    }

    /**
     * Get the direct containers of the specimen.
     *
     * @return HasMany
     */
    public function container(): HasMany
    {
        return $this->hasMany(SpecimenContainer::class);
    }

    /**
     * Get the collection details of the specimen.
     *
     * @return HasOne
     */
    public function collection(): HasOne
    {
        return $this->hasOne(SpecimenCollection::class);
    }

    /**
     * Eager load all specimen relations.
     *
     * @param  Builder  $query
     * @return Builder
     */
    #[Scope]
    protected function withAllRelations(Builder $query): Builder
    {
        return $query->with([
            'type.coding',
            'condition.coding',
            'managingOrganization.type.coding',
            'registeredBy.type.coding',
            'context.type.coding',
            'statusReason.coding',
            'parent.type.coding',
            'request.type.coding',
            'container.type.coding',
            'container.capacity',
            'container.specimenQuantity',
            'container.additiveCodeableConcept.coding',
            'collection.procedure.type.coding',
            'collection.collector.type.coding',
            'collection.collectedPeriod',
            'collection.duration',
            'collection.quantity',
            'collection.method.coding',
            'collection.bodySite.coding',
            'collection.fastingStatusCodeableConcept.coding'
        ]);
    }

    /**
     * Scope specimens to the given patient.
     *
     * @param  Builder  $query
     * @param  Person|Preperson  $patient
     * @return Builder
     */
    #[Scope]
    protected function forPatient(Builder $query, Person|Preperson $patient): Builder
    {
        return $patient instanceof Preperson ? $query->wherePrepersonId($patient->id) : $query->wherePersonId($patient->id);
    }

    /**
     * Leave out the specimens marked as entered in error.
     *
     * @param  Builder  $query
     * @return Builder
     */
    #[Scope]
    protected function notEnteredInError(Builder $query): Builder
    {
        return $query->whereNot('status', Status::ENTERED_IN_ERROR);
    }

    /**
     * Scope specimens to the given encounter.
     *
     * @param  Builder  $query
     * @param  string  $encounterId
     * @return Builder
     */
    #[Scope]
    protected function forEncounter(Builder $query, string $encounterId): Builder
    {
        return $query->whereHas('context', static fn (Builder $identifier): Builder => $identifier->whereValue($encounterId));
    }
}
