<?php

declare(strict_types=1);

use App\Core\Arr;
use App\Models\MedicalEvents\Sql\Identifier;
use App\Models\MedicalEvents\Sql\Medications\MedicationRequestRequest;
use App\Repositories\MedicalEvents\Repository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeCarePlanReferences();
        $this->normalizeMedicationPeriods();
    }

    private function normalizeCarePlanReferences(): void
    {
        $legacyColumns = [];
        foreach (['based_on' => 'based_on_id', 'part_of' => 'part_of_id'] as $legacyColumn => $foreignKey) {
            if (!Schema::hasColumn('care_plans', $foreignKey)) {
                Schema::table('care_plans', static function (Blueprint $table) use ($foreignKey): void {
                    $table->foreignId($foreignKey)->nullable()->constrained('identifiers');
                });
            }
            if (!Schema::hasColumn('care_plans', $legacyColumn)) {
                continue;
            }
            $legacyColumns[] = $legacyColumn;
            DB::table('care_plans')->select('id', $legacyColumn, $foreignKey)
                ->whereNotNull($legacyColumn)->orderBy('id')->chunkById(100, static function ($rows) use ($legacyColumn, $foreignKey): void {
                    foreach ($rows as $row) {
                        DB::transaction(static function () use ($row, $legacyColumn, $foreignKey): void {
                            $reference = json_decode($row->{$legacyColumn}, true, flags: JSON_THROW_ON_ERROR);
                            if ($reference === null || $reference === []) {
                                return;
                            }
                            if (is_array($reference) && array_is_list($reference) && count($reference) === 1) {
                                $reference = $reference[0];
                            }
                            if (!is_array($reference) || !is_string(data_get($reference, 'identifier.value')) || data_get($reference, 'identifier.value') === '') {
                                throw new RuntimeException("Care Plan {$row->id}: {$legacyColumn} must contain one Reference before migration.");
                            }
                            $reference = Arr::toCamelCase($reference);
                            $repository = Repository::identifier();
                            $identifier = $row->{$foreignKey} === null
                                ? $repository->store($reference['identifier']['value'])
                                : Identifier::findOrFail($row->{$foreignKey});
                            $repository->update($identifier, $reference);
                            DB::table('care_plans')->where('id', $row->id)->update([$foreignKey => $identifier->id]);
                        });
                    }
                });
        }
        if ($legacyColumns !== []) {
            Schema::table('care_plans', static fn (Blueprint $table) => $table->dropColumn($legacyColumns));
        }
    }

    private function normalizeMedicationPeriods(): void
    {
        $legacyColumns = array_values(array_filter(['period_start', 'period_end'], static fn (string $column): bool => Schema::hasColumn('medication_request_requests', $column)));
        $columns = ['id', 'started_at', 'ended_at', ...$legacyColumns];
        MedicationRequestRequest::query()->select($columns)->with('period')->chunkById(100, static function ($requests) use ($legacyColumns): void {
            foreach ($requests as $request) {
                $period = $request->period === null ? [] : Repository::period()->toResource($request->period);
                foreach (['start' => 'started_at', 'end' => 'ended_at'] as $boundary => $fallbackColumn) {
                    $legacyColumn = 'period_'.$boundary;
                    $value = in_array($legacyColumn, $legacyColumns, true) ? $request->getRawOriginal($legacyColumn) : null;
                    $existing = $period[$boundary] ?? null;
                    if ($existing !== null && $value !== null
                        && !\Carbon\CarbonImmutable::parse($existing, 'UTC')->equalTo(\Carbon\CarbonImmutable::parse($value, 'UTC'))) {
                        throw new RuntimeException("Medication Request {$request->id}: conflicting {$boundary} boundaries must be reconciled before migration.");
                    }
                    if ($existing === null) {
                        $value ??= $request->period === null ? $request->getRawOriginal($fallbackColumn) : null;
                        if ($value !== null) {
                            $period[$boundary] = $value;
                        }
                    }
                }
                if ($period !== []) {
                    DB::transaction(static fn () => Repository::period()->sync($request, $period));
                }
            }
        });
        if ($legacyColumns !== []) {
            Schema::table('medication_request_requests', static fn (Blueprint $table) => $table->dropColumn($legacyColumns));
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Restoring JSON references and duplicate medication period columns requires the matching backup.');
    }
};
