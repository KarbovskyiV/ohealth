<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Kept apart from the prepersons create migration because the employees table is created after it.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('prepersons', static function (Blueprint $table): void {
            if (!Schema::hasColumn('prepersons', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('uuid')
                    ->constrained('employees');
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
        Schema::table('prepersons', static function (Blueprint $table): void {
            if (Schema::hasColumn('prepersons', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};
