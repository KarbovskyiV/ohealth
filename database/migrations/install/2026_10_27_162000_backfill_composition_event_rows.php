<?php

declare(strict_types=1);

use App\Models\MedicalEvents\Sql\Composition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Existing periods stay attached to their conclusion. No clinical code is fabricated.
        DB::table('periods')->where('periodable_type', Composition::class)
            ->whereIn('periodable_id', DB::table('compositions')->select('id'))
            ->select('periodable_id')->distinct()->orderBy('periodable_id')
            ->each(static function (object $period): void {
                DB::table('composition_events')->insertOrIgnore(['composition_id' => $period->periodable_id, 'position' => 0]);
            });
    }

    public function down(): void
    {
        throw new RuntimeException('Existing clinical event rows must be preserved; use a forward fix.');
    }
};
