<?php

declare(strict_types=1);

namespace App\Services\Dictionary;

use App\Enums\User\Role;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class MedicationProgramPicker
{
    public const string DEFAULT_ID = '1318eabc-1a1a-42f6-8450-61e11c19eede';

    public static function resolve(?string $programId): string
    {
        return filled($programId) ? $programId : self::DEFAULT_ID;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $programs
     * @return Collection<int, array<string, mixed>>
     */
    public static function forCurrentUser(Collection $programs): Collection
    {
        $user = Auth::user();
        if (!$user) {
            return $programs->where('is_active', '=', true);
        }

        $mainSpeciality = $user->getMainSpeciality(legalEntity());
        $filtered = $programs->where('is_active', '=', true);

        if ($user->hasAllowedRole(Role::SPECIALIST) || $user->hasAllowedRole(Role::DOCTOR)) {
            $filtered = $filtered->filter(function (array $program) use ($mainSpeciality): bool {
                $allowedSpecialities = Arr::get($program, 'medical_program_settings.speciality_types_allowed', []);

                return $mainSpeciality->intersect($allowedSpecialities)->isNotEmpty();
            });
        }

        return $filtered;
    }
}
