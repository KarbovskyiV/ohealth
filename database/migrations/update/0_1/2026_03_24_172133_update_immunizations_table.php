<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('immunizations', function (Blueprint $table) {
            $table->foreignId('person_id')->after('uuid')->constrained('persons');
            $table->foreignId('encounter_id')->nullable()->change();
            $table->string('explanatory_letter')->after('expiration_date')->nullable();
            $table->timestamp('ehealth_inserted_at')->nullable()->after('route_id');
            $table->timestamp('ehealth_updated_at')->nullable()->after('ehealth_inserted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immunizations', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropColumn(['person_id', 'explanatory_letter', 'ehealth_inserted_at', 'ehealth_updated_at']);
            $table->foreignId('encounter_id')->nullable(false)->change();
        });
    }
};
