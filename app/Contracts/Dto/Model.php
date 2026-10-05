<?php

declare(strict_types=1);

namespace App\Contracts\Dto;

use Illuminate\Database\Eloquent\Model as EloquentModel;

interface Model
{
    public function toModel(): EloquentModel;
}
