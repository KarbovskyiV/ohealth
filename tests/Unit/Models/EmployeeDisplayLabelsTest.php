<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Employee\Employee;
use App\Models\Relations\Speciality;
use App\Services\Dictionary\Dictionaries\BasicDictionary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmployeeDisplayLabelsTest extends TestCase
{
    public static function dictionaryPrefixes(): array
    {
        return [[''], ['eHealth/']];
    }

    #[DataProvider('dictionaryPrefixes')]
    public function test_position_and_stored_specialities_use_dictionary_descriptions(string $prefix): void
    {
        app()->setLocale('uk');
        Cache::forever(BasicDictionary::KEY, [
            ['name' => $prefix.'POSITION', 'values' => [['code' => 'P6', 'description' => 'Лікар-акушер-гінеколог', 'is_active' => false]]],
            ['name' => $prefix.'SPECIALITY_TYPE', 'values' => [
                ['code' => 'FAMILY_DOCTOR', 'description' => 'Загальна практика — сімейна медицина', 'is_active' => false],
                ['code' => 'OBSTETRICS_AND_GYNECOLOGY', 'description' => 'Акушерство та гінекологія'],
            ]],
        ]);
        Cache::forever(BasicDictionary::KEY.':fresh', true);
        $employee = new Employee(['position' => 'P6', 'employee_type' => 'DOCTOR']);
        $employee->setRelation('specialities', new Collection([
            new Speciality(['speciality' => 'FAMILY_DOCTOR']),
            new Speciality(['speciality' => 'OBSTETRICS_AND_GYNECOLOGY']),
            new Speciality(['speciality' => 'UNKNOWN_CODE']),
        ]));

        $this->assertSame('Лікар-акушер-гінеколог', $employee->positionLabel());
        $this->assertSame(['Загальна практика — сімейна медицина', 'Акушерство та гінекологія'], $employee->specialityLabels());
    }
}
