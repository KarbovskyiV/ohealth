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
        Schema::table('medication_request_requests', static function (Blueprint $table): void {
            $table->decimal('medication_qty', 24, 10)->nullable()->change();
            $table->timestamp('details_synced_at')->nullable();
            $table->boolean('signed_prescription_pending')->default(false)->index();
            $table->string('authored_on', 40)->nullable();
            $table->foreignId('quantity_id')->nullable()->constrained('quantities');
            $table->foreignId('container_quantity_id')->nullable()->constrained('quantities');
            $table->date('dispense_valid_from')->nullable();
            $table->date('dispense_valid_to')->nullable();
            $table->timestampTz('rejected_at', 6)->nullable();
            $table->uuid('rejected_by_uuid')->nullable();
            $table->timestampTz('blocked_to', 6)->nullable();
            $table->text('reject_reason')->nullable();
            $table->string('reject_reason_code')->nullable();
            $table->text('block_reason')->nullable();
            $table->string('block_reason_code')->nullable();

        });
        Schema::table('dosage_instructions', static function (Blueprint $table): void {
            $table->unsignedInteger('position')->nullable();
            $table->json('additional_instruction')->nullable();
            $table->json('as_needed_codeable_concept')->nullable();
            $table->json('site')->nullable();
            foreach (['route', 'method', 'max_dose_per_period', 'max_dose_per_administration', 'max_dose_per_lifetime'] as $column) {
                $table->text($column)->nullable()->change();
            }
        });
        Schema::create('medication_request_references', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medication_request_id')->constrained('medication_request_requests')->cascadeOnDelete();
            $table->foreignId('identifier_id')->constrained('identifiers');
            $table->string('field');
            $table->unsignedInteger('position');
            $table->unique(['medication_request_id', 'field', 'position'], 'medication_request_reference_position_unique');
            $table->unique('identifier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException('Medication FHIR storage requires a forward fix or restoration of the pre-cutover backup.');
    }
};
