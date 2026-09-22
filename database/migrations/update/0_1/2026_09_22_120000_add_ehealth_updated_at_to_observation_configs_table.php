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
        Schema::table('observation_configs', static function (Blueprint $table) {
            if (!Schema::hasColumn('observation_configs', 'ehealth_updated_at')) {
                $table->string('ehealth_updated_at')
                    ->nullable()
                    ->after('value_range')
                    ->comment('raw updated_at from eHealth, used as the incremental sync watermark');
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
        Schema::table('observation_configs', static function (Blueprint $table) {
            if (Schema::hasColumn('observation_configs', 'ehealth_updated_at')) {
                $table->dropColumn('ehealth_updated_at');
            }
        });
    }
};
