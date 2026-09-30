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
            if (!Schema::hasColumn('legal_entities', 'device_association_sync_status')) {
                $table->enum('device_association_sync_status', JobStatus::values())
                    ->nullable()
                    ->after('detected_issue_sync_status');
            }
        });

        if (!Schema::hasTable('device_associations')) {
            return;
        }

        Schema::table('device_associations', static function (Blueprint $table) {
            if (!Schema::hasColumn('device_associations', 'status_reason_id')) {
                $table->foreignId('status_reason_id')
                    ->nullable()
                    ->after('explanatory_letter')
                    ->constrained('codeable_concepts');
            }

            if (!Schema::hasColumn('device_associations', 'ehealth_inserted_at')) {
                $table->timestamp('ehealth_inserted_at')->nullable()->after('recorder_id');
            }

            if (!Schema::hasColumn('device_associations', 'ehealth_updated_at')) {
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
            if (Schema::hasColumn('legal_entities', 'device_association_sync_status')) {
                $table->dropColumn('device_association_sync_status');
            }
        });

        if (!Schema::hasTable('device_associations')) {
            return;
        }

        Schema::table('device_associations', static function (Blueprint $table) {
            if (Schema::hasColumn('device_associations', 'status_reason_id')) {
                $table->dropConstrainedForeignId('status_reason_id');
            }

            if (Schema::hasColumn('device_associations', 'ehealth_inserted_at')) {
                $table->dropColumn('ehealth_inserted_at');
            }

            if (Schema::hasColumn('device_associations', 'ehealth_updated_at')) {
                $table->dropColumn('ehealth_updated_at');
            }
        });
    }
};
