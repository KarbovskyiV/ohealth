<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\DocumentIssuedAfterBirthDate;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentIssuedAfterBirthDateTest extends TestCase
{
    #[Test]
    #[DataProvider('dates')]
    public function comparesDatesWithoutRequiringEmployeeFormFields(mixed $birthDate, mixed $issuedDate, bool $fails): void
    {
        config(['app.date_format' => 'd.m.Y']);
        $validator = Validator::make(
            ['issuedAt' => $issuedDate],
            ['issuedAt' => [new DocumentIssuedAfterBirthDate($birthDate)]]
        );

        $this->assertSame($fails, $validator->fails());

        if ($fails) {
            $this->assertSame(
                __('validation.custom.employee.document_issued_before_birth'),
                $validator->errors()->first('issuedAt')
            );
        }
    }

    public static function dates(): array
    {
        return [
            'before birth' => ['23.02.2001', '22.02.2001', true],
            'same day' => ['23.02.2001', '23.02.2001', false],
            'after birth' => ['23.02.2001', '01.03.2019', false],
            'leap day before birth' => ['01.03.2000', '29.02.2000', true],
            'missing birth date' => [null, '01.03.2019', false],
            'empty birth date' => ['', '01.03.2019', false],
            'invalid birth date' => ['invalid', '01.03.2019', false],
            'non-string birth date' => [[], '01.03.2019', false],
            'invalid issue date' => ['23.02.2001', 'invalid', false],
            'nonexistent issue date' => ['01.03.2001', '30.02.2001', false],
            'non-string issue date' => ['23.02.2001', [], false],
        ];
    }
}
