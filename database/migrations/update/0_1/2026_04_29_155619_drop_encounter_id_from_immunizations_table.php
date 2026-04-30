<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('immunizations', static function (Blueprint $table) {
            if (Schema::hasColumn('immunizations', 'encounter_id')) {
                $table->dropForeign(['encounter_id']);
                $table->dropColumn('encounter_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immunizations', static function (Blueprint $table) {
            if (!Schema::hasColumn('immunizations', 'encounter_id')) {
                $table->foreignId('encounter_id')->nullable()->after('person_id')->constrained('encounters');
            }
        });
    }
};
