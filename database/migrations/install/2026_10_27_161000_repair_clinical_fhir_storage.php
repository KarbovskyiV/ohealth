<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Repair databases which ran an earlier definition under the same migration filename. */
    public function up(): void
    {
        $references = [
            'care_plans' => ['subject_reference_id' => 'identifiers', 'status_reason_id' => 'codeable_concepts'],
            'care_plan_activities' => ['care_plan_reference_id' => 'identifiers'],
            'service_request_requests' => ['status_reason_id' => 'codeable_concepts'],
            'medication_request_requests' => ['quantity_id' => 'quantities', 'container_quantity_id' => 'quantities'],
        ];
        foreach ($references as $name => $columns) {
            foreach ($columns as $column => $target) {
                if (!Schema::hasColumn($name, $column)) {
                    Schema::table($name, static fn (Blueprint $table) => $table->foreignId($column)->nullable()->constrained($target));
                }
            }
        }
        foreach (['care_plans', 'care_plan_activities', 'service_request_requests', 'medication_request_requests'] as $name) {
            if (!Schema::hasColumn($name, 'details_synced_at')) {
                Schema::table($name, static fn (Blueprint $table) => $table->timestamp('details_synced_at')->nullable());
            }
        }
        $columns = [
            'service_request_requests' => [
                'program_processing_status' => 'string', 'authored_on' => 'timestampTz', 'explanatory_letter' => 'text',
            ],
            'medication_request_requests' => [
                'authored_on' => 'string', 'dispense_valid_from' => 'date', 'dispense_valid_to' => 'date',
                'rejected_at' => 'timestampTz', 'rejected_by_uuid' => 'uuid', 'blocked_to' => 'timestampTz',
                'reject_reason' => 'text', 'reject_reason_code' => 'string', 'block_reason' => 'text', 'block_reason_code' => 'string',
            ],
            'dosage_instructions' => [
                'position' => 'unsignedInteger', 'additional_instruction' => 'json', 'as_needed_codeable_concept' => 'json', 'site' => 'json',
            ],
            'periods' => [
                'start_precision' => 'string', 'end_precision' => 'string', 'start_timezone' => 'string', 'end_timezone' => 'string',
            ],
        ];
        foreach ($columns as $name => $definitions) {
            foreach ($definitions as $column => $type) {
                if (!Schema::hasColumn($name, $column)) {
                    Schema::table($name, static function (Blueprint $table) use ($column, $type): void {
                        $definition = $type === 'timestampTz' ? $table->timestampTz($column, 6) : $table->{$type}($column);
                        $definition->nullable();
                        if ($column === 'program_processing_status') {
                            $definition->index();
                        }
                    });
                }
            }
        }
        if (!Schema::hasColumn('medication_request_requests', 'signed_prescription_pending')) {
            Schema::table('medication_request_requests', static fn (Blueprint $table) => $table->boolean('signed_prescription_pending')->default(false)->index());
        }
        // These JSON structures can exceed the old varchar length.
        Schema::table('dosage_instructions', static function (Blueprint $table): void {
            foreach (['route', 'method', 'max_dose_per_period', 'max_dose_per_administration', 'max_dose_per_lifetime'] as $column) {
                $table->text($column)->nullable()->change();
            }
        });
        foreach ([
            'care_plan_contributors' => ['care_plan_id', 'care_plans', 'identifier_id', 'identifiers'],
            'care_plan_activity_authors' => ['activity_id', 'care_plan_activities', 'identifier_id', 'identifiers'],
            'care_plan_activity_reason_codes' => ['activity_id', 'care_plan_activities', 'concept_id', 'codeable_concepts'],
            'care_plan_activity_goal_concepts' => ['activity_id', 'care_plan_activities', 'concept_id', 'codeable_concepts'],
            'care_plan_activity_outcome_concepts' => ['activity_id', 'care_plan_activities', 'concept_id', 'codeable_concepts'],
        ] as $name => [$ownerColumn, $ownerTable, $valueColumn, $valueTable]) {
            if (!Schema::hasTable($name)) {
                Schema::create($name, static function (Blueprint $table) use ($ownerColumn, $ownerTable, $valueColumn, $valueTable): void {
                    $table->id();
                    $table->foreignId($ownerColumn)->constrained($ownerTable)->cascadeOnDelete();
                    $table->foreignId($valueColumn)->constrained($valueTable);
                    $table->unsignedInteger('position');
                    $table->unique([$ownerColumn, $valueColumn]);
                });
            }
        }
        // Earlier applied definitions used these names with the same reference columns.
        // Rename instead of recreating them, preserving identifiers and their ownership.
        foreach ([
            'service_request_fhir_references' => 'service_request_references',
            'medication_request_fhir_references' => 'medication_request_references',
        ] as $legacyName => $name) {
            if (!Schema::hasTable($name) && Schema::hasTable($legacyName)) {
                Schema::rename($legacyName, $name);
            }
        }
        foreach ([
            'service_request_references' => ['service_request_id', 'service_request_requests', 'collection', 'service_request_reference_position_unique'],
            'medication_request_references' => ['medication_request_id', 'medication_request_requests', 'field', 'medication_request_reference_position_unique'],
        ] as $name => [$ownerColumn, $ownerTable, $field, $indexName]) {
            if (!Schema::hasTable($name)) {
                Schema::create($name, static function (Blueprint $table) use ($ownerColumn, $ownerTable, $field, $indexName, $name): void {
                    $table->id();
                    $table->foreignId($ownerColumn)->constrained($ownerTable)->cascadeOnDelete();
                    $table->foreignId('identifier_id')->constrained('identifiers');
                    $table->string($field);
                    $table->unsignedInteger('position');
                    $table->unique([$ownerColumn, $field, 'position'], $indexName);
                    if ($name === 'medication_request_references') {
                        $table->unique('identifier_id');
                    }
                });
            }
        }
        Schema::table('compositions', static function (Blueprint $table): void {
            $table->timestampTz('date', 6)->nullable()->change();
            foreach (['is_accident', 'is_intoxicated', 'is_foreign_treatment', 'is_force_renew'] as $column) {
                $table->boolean($column)->nullable()->default(null)->change();
            }
        });
        if (!Schema::hasTable('composition_events')) {
            Schema::create('composition_events', static function (Blueprint $table): void {
                $table->id();
                $table->foreignId('composition_id')->constrained()->cascadeOnDelete();
                $table->foreignId('code_id')->nullable()->constrained('codeable_concepts');
                $table->unsignedInteger('position');
                $table->unique(['composition_id', 'position']);
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Clinical FHIR repair requires a forward fix or restoration of the backup.');
    }
};
