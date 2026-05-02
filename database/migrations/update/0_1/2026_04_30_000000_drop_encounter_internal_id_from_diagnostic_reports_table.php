<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_reports', static function (Blueprint $table) {
            if (Schema::hasColumn('diagnostic_reports', 'encounter_internal_id')) {
                $table->dropForeign(['encounter_internal_id']);
                $table->dropColumn('encounter_internal_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_reports', static function (Blueprint $table) {
            if (!Schema::hasColumn('diagnostic_reports', 'encounter_internal_id')) {
                $table->foreignId('encounter_internal_id')->nullable()->after('uuid')->constrained('encounters');
            }
        });
    }
};
