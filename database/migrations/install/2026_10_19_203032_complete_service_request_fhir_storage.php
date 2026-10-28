<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_request_requests', static function (Blueprint $table): void {
            $table->foreignId('employee_id')->nullable()->change();
            $table->decimal('quantity', 24, 10)->nullable()->change();
            $table->timestamp('started_at', 6)->nullable()->change();
            $table->timestamp('ended_at', 6)->nullable()->change();
            foreach (['code_identifier_id', 'program_identifier_id', 'requester_employee_id', 'requester_legal_entity_id', 'subject_identifier_id'] as $column) {
                $table->foreignId($column)->nullable()->constrained('identifiers');
            }
            foreach (['quantity_id', 'remaining_quantity_id'] as $column) {
                $table->foreignId($column)->nullable()->constrained('quantities');
            }
            $table->timestamp('occurrence_date_time', 6)->nullable();
            $table->string('clinical_status')->nullable();
            $table->string('program_processing_status')->nullable()->index();
            $table->timestampTz('authored_on', 6)->nullable();
            $table->text('explanatory_letter')->nullable();
            $table->foreignId('status_reason_id')->nullable()->constrained('codeable_concepts');

            $table->timestamp('details_synced_at')->nullable();
        });
        Schema::create('service_request_references', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_request_id')->constrained('service_request_requests')->cascadeOnDelete();
            $table->foreignId('identifier_id')->constrained('identifiers');
            $table->string('collection');
            $table->unsignedInteger('position');
            $table->unique(['service_request_id', 'collection', 'position'], 'service_request_reference_position_unique');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Service Request FHIR recovery requires a forward fix or restoration of the matching pre-cutover backup.');
    }
};
