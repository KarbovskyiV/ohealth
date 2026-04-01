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
        Schema::table('diagnostic_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('diagnostic_reports', 'person_id')) {
                $table->foreignId('person_id')->after('uuid')->constrained('persons');
            }
            if (!Schema::hasColumn('diagnostic_reports', 'origin_episode_id')) {
                $table->foreignId('origin_episode_id')->after('encounter_id')->nullable()->constrained('identifiers');
            }
            if (!Schema::hasColumn('diagnostic_reports', 'explanatory_letter')) {
                $table->string('explanatory_letter')->after('report_origin_id')->nullable();
            }
            if (!Schema::hasColumn('diagnostic_reports', 'cancellation_reason_id')) {
                $table->foreignId('cancellation_reason_id')->after('explanatory_letter')->nullable()->constrained('codeable_concepts');
            }
            if (!Schema::hasColumn('diagnostic_reports', 'ehealth_inserted_at')) {
                $table->timestamp('ehealth_inserted_at')->nullable()->after('cancellation_reason_id');
            }
            if (!Schema::hasColumn('diagnostic_reports', 'ehealth_updated_at')) {
                $table->timestamp('ehealth_updated_at')->nullable()->after('ehealth_inserted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnostic_reports', function (Blueprint $table) {
            if (Schema::hasColumn('diagnostic_reports', 'person_id')) {
                $table->dropForeign(['person_id']);
                $table->dropColumn('person_id');
            }
            if (Schema::hasColumn('diagnostic_reports', 'origin_episode_id')) {
                $table->dropForeign(['origin_episode_id']);
                $table->dropColumn('origin_episode_id');
            }
            if (Schema::hasColumn('diagnostic_reports', 'explanatory_letter')) {
                $table->dropColumn('explanatory_letter');
            }
            if (Schema::hasColumn('diagnostic_reports', 'cancellation_reason_id')) {
                $table->dropForeign(['cancellation_reason_id']);
                $table->dropColumn('cancellation_reason_id');
            }
            if (Schema::hasColumn('diagnostic_reports', 'ehealth_inserted_at')) {
                $table->dropColumn('ehealth_inserted_at');
            }
            if (Schema::hasColumn('diagnostic_reports', 'ehealth_updated_at')) {
                $table->dropColumn('ehealth_updated_at');
            }
        });
    }
};
