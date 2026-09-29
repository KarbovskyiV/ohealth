<?php

declare(strict_types=1);

use App\Enums\JobStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('legal_entities', static function (Blueprint $table) {
            if (!Schema::hasColumn('legal_entities', 'detected_issue_sync_status')) {
                $table->enum('detected_issue_sync_status', JobStatus::values())
                    ->nullable()
                    ->after('specimen_sync_status');
            }
        });

        if (!Schema::hasTable('detected_issues')) {
            return;
        }

        Schema::table('detected_issues', static function (Blueprint $table) {
            if (!Schema::hasColumn('detected_issues', 'status_reason_id')) {
                $table->foreignId('status_reason_id')
                    ->nullable()
                    ->after('explanatory_letter')
                    ->constrained('codeable_concepts');
            }

            if (!Schema::hasColumn('detected_issues', 'ehealth_inserted_at')) {
                $table->timestamp('ehealth_inserted_at')->nullable()->after('recorder_id');
            }

            if (!Schema::hasColumn('detected_issues', 'ehealth_updated_at')) {
                $table->timestamp('ehealth_updated_at')->nullable()->after('ehealth_inserted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('legal_entities', static function (Blueprint $table) {
            if (Schema::hasColumn('legal_entities', 'detected_issue_sync_status')) {
                $table->dropColumn('detected_issue_sync_status');
            }
        });

        if (!Schema::hasTable('detected_issues')) {
            return;
        }

        Schema::table('detected_issues', static function (Blueprint $table) {
            if (Schema::hasColumn('detected_issues', 'status_reason_id')) {
                $table->dropConstrainedForeignId('status_reason_id');
            }

            if (Schema::hasColumn('detected_issues', 'ehealth_inserted_at')) {
                $table->dropColumn('ehealth_inserted_at');
            }

            if (Schema::hasColumn('detected_issues', 'ehealth_updated_at')) {
                $table->dropColumn('ehealth_updated_at');
            }
        });
    }
};
