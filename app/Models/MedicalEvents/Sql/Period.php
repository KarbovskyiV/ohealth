<?php

declare(strict_types=1);

namespace App\Models\MedicalEvents\Sql;

use App\Casts\EHealthTimestampCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Period extends Model
{
    protected $fillable = [
        'start',
        'end'
    ];

    protected $hidden = [
        'id',
        'periodable_type',
        'periodable_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'start' => EHealthTimestampCast::class,
        'end' => EHealthTimestampCast::class
    ];

    public function periodable(): MorphTo
    {
        return $this->morphTo();
    }
}
