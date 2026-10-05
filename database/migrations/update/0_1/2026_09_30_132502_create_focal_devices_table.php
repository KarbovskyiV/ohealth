<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('focal_devices')) {
            return;
        }

        Schema::create('focal_devices', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete();
            $table->foreignId('action_id')->nullable()->constrained('codeable_concepts')->cascadeOnDelete();
            $table->foreignId('manipulated_id')->constrained('identifiers')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (DB::table('migrations')
            ->where('migration', '2025_12_10_000080_create_procedures_table')
            ->exists())
        {
            return;
        }

        Schema::dropIfExists('focal_devices');
    }
};
