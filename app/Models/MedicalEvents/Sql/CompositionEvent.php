<?php

declare(strict_types=1);

namespace App\Models\MedicalEvents\Sql;

use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CompositionEvent extends Model
{
    use HasCamelCasing;

    protected $fillable = ['composition_id', 'code_id', 'position'];

    protected $casts = ['position' => 'integer'];

    public $timestamps = false;

    public function code(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class);
    }

    public function period(): MorphOne
    {
        return $this->morphOne(Period::class, 'periodable');
    }
}
