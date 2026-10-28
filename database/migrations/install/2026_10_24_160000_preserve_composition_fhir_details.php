<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compositions', static function (Blueprint $table): void {
            $table->timestampTz('date', 6)->nullable()->change();
            foreach (['is_accident', 'is_intoxicated', 'is_foreign_treatment', 'is_force_renew'] as $field) {
                $table->boolean($field)->nullable()->default(null)->change();
            }
        });
        if (!Schema::hasTable('composition_events')) {
            Schema::create('composition_events', static function (Blueprint $table): void {
                $table->id();
                $table->foreignId('composition_id')->constrained()->cascadeOnDelete();
                $table->foreignId('code_id')->nullable()->constrained('codeable_concepts');
                $table->unsignedInteger('position');
                $table->unique(['composition_id', 'position']);
            });
        }
        // Existing conclusions already own their first event period through this morph.
        DB::table('periods')->where('periodable_type', App\Models\MedicalEvents\Sql\Composition::class)
            ->whereIn('periodable_id', DB::table('compositions')->select('id'))
            ->select('periodable_id')->distinct()->orderBy('periodable_id')
            ->each(static function (object $period): void {
                DB::table('composition_events')->updateOrInsert(
                    ['composition_id' => $period->periodable_id, 'position' => 0],
                    []
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('composition_events');
    }
};
