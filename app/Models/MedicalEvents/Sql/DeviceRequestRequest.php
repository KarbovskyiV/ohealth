<?php

declare(strict_types=1);

namespace App\Models\MedicalEvents\Sql;

use App\Models\CarePlan;
use App\Models\CarePlanActivity;
use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Models\User;
use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DeviceRequestRequest extends Model
{
    use HasCamelCasing;

    public function __construct(array $attributes = [])
    {
        $this->mergeFillable(array_map(Str::camel(...), $this->getFillable()));
        parent::__construct($attributes);
    }

    protected $table = 'device_request_requests';

    protected $fillable = [
        'uuid',
        'employee_id',
        'person_id',
        'division_id',
        'status',
        'request_number',
        'started_at',
        'ended_at',
        'device_id',
        'quantity',
        'program_id',
        'intent_id',
        'category_id',
        'based_on_id',
        'context_id',
        'priority_id',
        'note',
        'supporting_info',
        'request_payload',
        'remote_details',
        'sms_resent_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'quantity' => 'decimal:2',
        'supporting_info' => 'array',
        'request_payload' => 'array',
        'remote_details' => 'array',
        'sms_resent_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function basedOn(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'based_on_id');
    }

    public function context(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'context_id');
    }

    public function intent(): BelongsTo
    {
        return $this->belongsTo(Coding::class, 'intent_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'category_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'priority_id');
    }

    /**
     * Filter device requests issued under an activity of a care plan of the patient one of the user's employees has an
     * active read approval on.
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
        if ($patient instanceof Preperson) {
            return $query->whereRaw('1 = 0');
        }

        $activityIds = CarePlanActivity::whereIn(
            'care_plan_id',
            CarePlan::wherePersonId($patient->id)
                ->whereKey(Approval::carePlanAccessGrantedTo($user, $legalEntity)->pluck('approvable_id'))
                ->select('id')
        )
            ->pluck('uuid');

        return $query->whereHas(
            'basedOn',
            static fn (Builder $identifier): Builder => $identifier->whereIn('value', $activityIds)
        );
    }

    /**
     * Filter device requests issued under an activity of a care plan of the patient managed by the current legal entity.
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
        if ($patient instanceof Preperson) {
            return $query->whereRaw('1 = 0');
        }

        $activityIds = CarePlanActivity::whereIn(
            'care_plan_id',
            CarePlan::wherePersonId($patient->id)
                ->whereHas(
                    'managingOrganization',
                    static fn (Builder $identifier): Builder => $identifier->whereValue(legalEntity()->uuid)
                )
                ->select('id')
        )
            ->pluck('uuid');

        return $query->whereHas(
            'basedOn',
            static fn (Builder $identifier): Builder => $identifier->whereIn('value', $activityIds)
        );
    }
}
