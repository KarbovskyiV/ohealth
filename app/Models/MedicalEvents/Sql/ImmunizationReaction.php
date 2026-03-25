<?php

declare(strict_types=1);

namespace App\Models\MedicalEvents\Sql;

use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImmunizationReaction extends Model
{
    use HasCamelCasing;

    protected $fillable = [
        'immunization_id',
        'detail_id',
        'display_value'
    ];

    protected $hidden = [
        'id',
        'immunization_id',
        'detail_id',
        'created_at',
        'updated_at'
    ];

    public function immunization(): BelongsTo
    {
        return $this->belongsTo(Immunization::class);
    }

    public function detail(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'detail_id');
    }
}
