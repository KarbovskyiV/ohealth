<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['ehealth_inserted_at', 'ehealth_updated_at'] as $column) {
            if (Schema::getColumnType('client_connections', $column) === 'timestamp') {
                continue;
            }

            Schema::table('client_connections', static function (Blueprint $table) use ($column): void {
                $table->timestamp($column)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('client_connections', static function (Blueprint $table): void {
            $table->date('ehealth_inserted_at')->nullable()->change();
            $table->date('ehealth_updated_at')->nullable()->change();
        });
    }
};
