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
            if (!Schema::hasColumn('legal_entities', 'specimen_sync_status')) {
                $table->enum('specimen_sync_status', JobStatus::values())->nullable()->after('device_sync_status');
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
            if (Schema::hasColumn('legal_entities', 'specimen_sync_status')) {
                $table->dropColumn('specimen_sync_status');
            }
        });
    }
};
