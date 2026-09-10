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
            $table->foreignId('legal_entity_type_id')->nullable()->change();
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
            $table->foreignId('legal_entity_type_id')->nullable(false)->change();
        });
    }
};
