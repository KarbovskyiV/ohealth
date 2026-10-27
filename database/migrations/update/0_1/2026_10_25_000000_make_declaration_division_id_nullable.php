<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $divisionColumn = collect(Schema::getColumns('declaration_requests'))->firstWhere('name', 'division_id');

        if ($divisionColumn !== null && !$divisionColumn['nullable']) {
            Schema::table('declaration_requests', static function (Blueprint $table): void {
                $table->foreignId('division_id')
                    ->nullable()
                    ->comment('Registered Medical Service Provider Division identifier.')
                    ->change();
            });
        }

        $divisionColumn = collect(Schema::getColumns('declarations'))->firstWhere('name', 'division_id');

        if ($divisionColumn !== null && !$divisionColumn['nullable']) {
            Schema::table('declarations', static function (Blueprint $table): void {
                $table->foreignId('division_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        $divisionColumn = collect(Schema::getColumns('declarations'))->firstWhere('name', 'division_id');

        if ($divisionColumn !== null && $divisionColumn['nullable']) {
            Schema::table('declarations', static function (Blueprint $table): void {
                $table->foreignId('division_id')->nullable(false)->change();
            });
        }

        $divisionColumn = collect(Schema::getColumns('declaration_requests'))->firstWhere('name', 'division_id');

        if ($divisionColumn !== null && $divisionColumn['nullable']) {
            Schema::table('declaration_requests', static function (Blueprint $table): void {
                $table->foreignId('division_id')
                    ->nullable(false)
                    ->comment('Registered Medical Service Provider Division identifier.')
                    ->change();
            });
        }
    }
};
