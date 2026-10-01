<?php

declare(strict_types=1);

namespace App\Models\MedicalEvents\Sql;

use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FocalDevice extends Model
{
    use HasCamelCasing;

    protected $fillable = [
        'procedure_id',
        'action_id',
        'manipulated_id'
    ];

    protected $hidden = [
        'id',
        'procedure_id',
        'action_id',
        'manipulated_id',
        'created_at',
        'updated_at'
    ];

    public function action(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'action_id');
    }

    public function manipulated(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'manipulated_id');
    }
}