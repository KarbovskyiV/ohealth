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
        Schema::table('client_connections', static function (Blueprint $table): void {
            if (Schema::hasColumn('client_connections', 'client_uuid') && static::getForeignKey('client_uuid') === null) {
                $table->foreign('client_uuid')->references('uuid')->on('clients')->cascadeOnDelete();
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
        Schema::table('client_connections', static function (Blueprint $table): void {
            $foreignKey = static::getForeignKey('client_uuid');

            if ($foreignKey !== null) {
                $table->dropForeign($foreignKey['name']);
            }
        });
    }

    private static function getForeignKey(string $column): ?array
    {
        return collect(Schema::getForeignKeys('client_connections'))
            ->first(fn (array $foreignKey) => in_array($column, $foreignKey['columns'], true));
    }
};
