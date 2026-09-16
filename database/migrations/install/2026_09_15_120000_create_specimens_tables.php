<?php

declare(strict_types=1);

use App\Enums\Specimen\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('specimens', static function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('accession_identifier')->nullable();
            $table->foreignId('person_id')->nullable()->constrained('persons');
            $table->foreignId('preperson_id')->nullable()->constrained('prepersons');
            $table->enum('status', Status::values());
            $table->foreignId('type_id')->constrained('codeable_concepts');
            $table->foreignId('condition_id')->nullable()->constrained('codeable_concepts');
            $table->text('note')->nullable();
            $table->foreignId('managing_organization_id')->constrained('identifiers');
            $table->foreignId('registered_by_id')->constrained('identifiers');
            $table->foreignId('context_id')->constrained('identifiers');
            $table->timestamp('received_time')->nullable();
            $table->foreignId('status_reason_id')->nullable()->constrained('codeable_concepts');
            $table->string('explanatory_letter')->nullable();
            $table->timestamp('ehealth_inserted_at')->nullable();
            $table->uuid('ehealth_inserted_by')->nullable();
            $table->timestamp('ehealth_updated_at')->nullable();
            $table->uuid('ehealth_updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('specimen_parents', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('specimen_id')->constrained('specimens')->cascadeOnDelete();
            $table->foreignId('identifier_id')->constrained('identifiers')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('specimen_requests', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('specimen_id')->constrained('specimens')->cascadeOnDelete();
            $table->foreignId('identifier_id')->constrained('identifiers')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('specimen_containers', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('specimen_id')->constrained('specimens')->cascadeOnDelete();
            $table->string('identifier');
            $table->string('description')->nullable();
            $table->foreignId('type_id')->nullable()->constrained('codeable_concepts');
            $table->foreignId('capacity_id')->nullable()->constrained('quantities');
            $table->foreignId('specimen_quantity_id')->nullable()->constrained('quantities');
            $table->foreignId('additive_codeable_concept_id')->nullable()->constrained('codeable_concepts');
            $table->timestamps();
        });

        Schema::create('specimen_collections', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('specimen_id')->unique()->constrained('specimens')->cascadeOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('identifiers');
            $table->foreignId('collector_id')->constrained('identifiers');
            $table->timestamp('collected_date_time')->nullable();
            $table->foreignId('duration_id')->nullable()->constrained('quantities');
            $table->foreignId('quantity_id')->nullable()->constrained('quantities');
            $table->foreignId('method_id')->nullable()->constrained('codeable_concepts');
            $table->foreignId('body_site_id')->nullable()->constrained('codeable_concepts');
            $table->foreignId('fasting_status_codeable_concept_id')->nullable()->constrained('codeable_concepts');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('specimen_collections');
        Schema::dropIfExists('specimen_containers');
        Schema::dropIfExists('specimen_requests');
        Schema::dropIfExists('specimen_parents');
        Schema::dropIfExists('specimens');
    }
};
