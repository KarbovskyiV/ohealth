<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\MedicalEvents\Sql\Coding;
use PHPUnit\Framework\TestCase;

class CodingSerializationTest extends TestCase
{
    public function test_coding_serializes_system_and_code(): void
    {
        $coding = new Coding(['system' => 'synthetic/codes', 'code' => 'A']);

        $this->assertSame(['system' => 'synthetic/codes', 'code' => 'A'], $coding->toArray());
    }

    public function test_coding_mass_assignment_remains_limited_to_system_and_code(): void
    {
        $this->assertSame(['system', 'code'], (new Coding())->getFillable());
    }
}
