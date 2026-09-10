<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('clients', static function (Blueprint $table): void {
            if (!Schema::hasColumn('clients', 'uuid')) {
                $table->uuid()->nullable()->comment('Client UUID at the eHealth side');
            }

            if (!Schema::hasIndex('clients', 'clients_unique')) {
                $table->unique('uuid', 'clients_unique');
            }
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('clients', static function (Blueprint $table): void {
            if (Schema::hasIndex('clients', 'clients_unique')) {
                $table->dropUnique('clients_unique');
            }

            if (Schema::hasColumn('clients', 'uuid')) {
                $table->dropColumn('uuid');
            }
        });
    }
};
