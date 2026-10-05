<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $uuidColumn = collect(Schema::getColumns('addresses'))->where('name', 'settlement_id')->first();

        if (!$uuidColumn['nullable']) {
            // Change the uuid column to be nullable
            Schema::table('addresses', static function (Blueprint $table): void {
                $table->string('settlement_id')
                    ->nullable()
                    ->comment('settlement identification from uaadresses')
                    ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Foreign addresses may contain a null settlement_id. Backfill those rows before restoring NOT NULL so
     * rollback does not fail with SQLSTATE[23502].
     */
    public function down(): void
    {
        $uuidColumn = collect(Schema::getColumns('addresses'))->where('name', 'settlement_id')->first();

        if ($uuidColumn['nullable']) {
            DB::table('addresses')
                ->whereNull('settlement_id')
                ->update(['settlement_id' => '']);

            Schema::table('addresses', static function (Blueprint $table): void {
                $table->string('settlement_id')
                    ->nullable(false)
                    ->comment('settlement identification from uaadresses')
                    ->change();
            });
        }
    }
};
