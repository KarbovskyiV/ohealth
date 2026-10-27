<?php

declare(strict_types=1);

namespace App\Models\MedicalEvents\Sql;

use App\Enums\Person\EncounterStatus;
use App\Casts\EHealthTimestampCast;
use App\Enums\DeviceDispense\Status;
use App\Models\LegalEntity;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Models\User;
use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DeviceDispense extends Model
{
    use HasCamelCasing;

    protected $fillable = [
        'uuid',
        'person_id',
        'preperson_id',
        'based_on_id',
        'status',
        'performer_id',
        'location_id',
        'when_handed_over',
        'note',
        'performer_legal_entity_id',
        'program_id',
        'part_of_id',
        'encounter_id',
        'context_episode_id',
        'origin_episode_id',
        'status_reason_id',
        'explanatory_letter',
        'ehealth_inserted_at',
        'ehealth_updated_at'
    ];

    protected $casts = [
        'status' => Status::class,
        'when_handed_over' => EHealthTimestampCast::class,
        'ehealth_inserted_at' => EHealthTimestampCast::class,
        'ehealth_updated_at' => EHealthTimestampCast::class
    ];

    protected $hidden = [
        'id',
        'person_id',
        'preperson_id',
        'based_on_id',
        'performer_id',
        'location_id',
        'performer_legal_entity_id',
        'program_id',
        'part_of_id',
        'encounter_id',
        'status_reason_id',
        'created_at',
        'updated_at'
    ];

    protected function ehealthInsertedDate(): Attribute
    {
        return Attribute::make(
            get: fn (): string => Str::before((string) $this->ehealthInsertedAt, ' ')
        );
    }

    public function preperson(): BelongsTo
    {
        return $this->belongsTo(Preperson::class);
    }

    public function basedOn(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'based_on_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'performer_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'location_id');
    }

    public function performerLegalEntity(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'performer_legal_entity_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'program_id');
    }

    public function partOf(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'part_of_id');
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'encounter_id');
    }

    public function statusReason(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'status_reason_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DeviceDispenseDetail::class);
    }

    public function supportingInfo(): BelongsToMany
    {
        return $this->belongsToMany(Identifier::class, 'device_dispense_supporting_info')->withTimestamps();
    }

    #[Scope]
    protected function withAllRelations(Builder $query): Builder
    {
        return $query->with([
            'basedOn.type.coding',
            'performer.type.coding',
            'location.type.coding',
            'performerLegalEntity.type.coding',
            'program.type.coding',
            'partOf.type.coding',
            'encounter.type.coding',
            'statusReason.coding',
            'details.device.type.coding',
            'details.deviceCode.coding',
            'details.programDevice.type.coding',
            'details.quantity',
            'supportingInfo.type.coding'
        ]);
    }

    #[Scope]
    protected function forPatient(Builder $query, Person|Preperson $patient): Builder
    {
        return $patient instanceof Preperson ? $query->wherePrepersonId($patient->id) : $query->wherePersonId($patient->id);
    }

    /**
     * Filter the patient's device dispenses performed by the current legal entity.
     *
     * ABAC rule 2.
     *
     * @param  Builder  $query
     * @param  Person|Preperson  $patient
     * @return Builder
     */
    #[Scope]
    protected function forLegalEntity(Builder $query, Person|Preperson $patient): Builder
    {
        return $query->forPatient($patient)->whereHas(
            'performerLegalEntity',
            static fn (Builder $identifier): Builder => $identifier->whereValue(legalEntity()->uuid)
        );
    }

    /**
     * Filter the patient's device dispenses made in the context of an episode managed by the current legal entity.
     *
     * ABAC rule 3.
     *
     * @param  Builder  $query
     * @param  Person|Preperson  $patient
     * @return Builder
     */
    #[Scope]
    protected function inEpisodeOfLegalEntity(Builder $query, Person|Preperson $patient): Builder
    {
        return $query->forPatient($patient)->whereIn(
            'context_episode_id',
            Episode::forPatient($patient)->managedByLegalEntity()->pluck('uuid')
        );
    }

    /**
     * Filter the patient's device dispenses originated by an episode managed by the current legal entity.
     *
     * ABAC rule 6.
     *
     * @param  Builder  $query
     * @param  Person|Preperson  $patient
     * @return Builder
     */
    #[Scope]
    protected function originatedInLegalEntity(Builder $query, Person|Preperson $patient): Builder
    {
        return $query->forPatient($patient)->whereIn(
            'origin_episode_id',
            Episode::forPatient($patient)->managedByLegalEntity()->pluck('uuid')
        );
    }

    /**
     * Filter the patient's device dispenses based on a device request issued under a care plan one of the user's
     * employees has an active read approval on.
     *
     * ABAC rule 14.
     *
     * @param  Builder  $query
     * @param  Person|Preperson  $patient
     * @param  User  $user
     * @param  LegalEntity  $legalEntity
     * @return Builder
     */
    #[Scope]
    protected function basedOnApprovedCarePlan(
        Builder $query,
        Person|Preperson $patient,
        User $user,
        LegalEntity $legalEntity
    ): Builder {
        return $query->forPatient($patient)->whereHas(
            'basedOn',
            static fn (Builder $identifier): Builder => $identifier->whereIn(
                'value',
                DeviceRequestRequest::basedOnApprovedCarePlan($patient, $user, $legalEntity)->pluck('uuid')
            )
        );
    }

    /**
     * Filter the patient's device dispenses based on a device request issued under a care plan managed by the current
     * legal entity.
     *
     * ABAC rule 17.
     *
     * @param  Builder  $query
     * @param  Person|Preperson  $patient
     * @return Builder
     */
    #[Scope]
    protected function basedOnCarePlanOfLegalEntity(Builder $query, Person|Preperson $patient): Builder
    {
        return $query->forPatient($patient)->whereHas(
            'basedOn',
            static fn (Builder $identifier): Builder => $identifier->whereIn(
                'value',
                DeviceRequestRequest::basedOnCarePlanOfLegalEntity($patient)->pluck('uuid')
            )
        );
    }

    /**
     * Device dispenses of the patient the user may read: with access to all the patient's data (an active declaration
     * or approval) all of them, including the ones of the persons and prepersons merged into a person, otherwise only
     * the patient's own ones performed by the current legal entity, made in the context of its episode or originated by
     * its episode.
     *
     * @param  Builder  $query
     * @param  Person|Preperson  $patient
     * @param  bool  $hasPatientAccess
     * @return Builder
     */
    #[Scope]
    protected function readableFor(Builder $query, Person|Preperson $patient, bool $hasPatientAccess): Builder
    {
        if ($hasPatientAccess) {
            return $patient instanceof Person
                ? $patient->filterRecordsWithMerged($query)
                : $query->forPatient($patient);
        }

        return $query->where(
            static fn (Builder $dispense): Builder => $dispense->forLegalEntity($patient)
                ->orWhere(static fn (Builder $inEpisode): Builder => $inEpisode->inEpisodeOfLegalEntity($patient))
                ->orWhere(static fn (Builder $originated): Builder => $originated->originatedInLegalEntity($patient))
        );
    }

    /**
     * Leave out the records of an encounter package eHealth has not accepted yet, which is still a draft, including the
     * draft encounters of the persons and prepersons merged into a person.
     *
     * @param  Builder  $query
     * @param  Person|Preperson  $patient
     * @return Builder
     */
    #[Scope]
    protected function notInDraftEncounter(Builder $query, Person|Preperson $patient): Builder
    {
        return $query->whereDoesntHave(
            'encounter',
            static fn (Builder $identifier): Builder => $identifier->whereIn(
                'value',
                Encounter::readableFor($patient, true)->whereStatus(EncounterStatus::DRAFT)->pluck('uuid')
            )
        );
    }

    #[Scope]
    protected function forEncounter(Builder $query, string $encounterId): Builder
    {
        return $query->whereHas('encounter', static fn (Builder $identifier): Builder => $identifier->whereValue($encounterId));
    }
}
