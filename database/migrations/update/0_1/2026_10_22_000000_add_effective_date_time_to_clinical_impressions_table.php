<?php

declare(strict_types=1);

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
        if (Schema::hasTable('clinical_impressions') && !Schema::hasColumn('clinical_impressions', 'effective_date_time')) {
            Schema::table('clinical_impressions', function (Blueprint $table) {
                $table->timestamp('effective_date_time')->nullable()->after('previous_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if (Schema::hasTable('clinical_impressions') && Schema::hasColumn('clinical_impressions', 'effective_date_time')) {
            Schema::table('clinical_impressions', function (Blueprint $table) {
                $table->dropColumn('effective_date_time');
            });
        }
    }
};
