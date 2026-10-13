<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->references() as $columnName => $targetTable) {
            if (Schema::hasColumn('service_request_requests', $columnName)) {
                continue;
            }

            $foreignKeyName = $this->foreignKeyName($columnName);
            Schema::table('service_request_requests', static function (Blueprint $table) use ($columnName, $targetTable, $foreignKeyName): void {
                $table->foreignId($columnName)->nullable();
                $table->foreign($columnName, $foreignKeyName)->references('id')->on($targetTable);
            });
        }
    }

    public function down(): void
    {
        // Existing installations may already own these columns; undo only this migration's additions.
        $foreignKeyNames = array_column(Schema::getForeignKeys('service_request_requests'), 'name');

        foreach (array_keys($this->references()) as $columnName) {
            $foreignKeyName = $this->foreignKeyName($columnName);
            if (!in_array($foreignKeyName, $foreignKeyNames, true)) {
                continue;
            }

            Schema::table('service_request_requests', static function (Blueprint $table) use ($columnName, $foreignKeyName): void {
                $table->dropForeign($foreignKeyName);
                $table->dropColumn($columnName);
            });
        }
    }

    /** @return array<string, string> */
    private function references(): array
    {
        return [
            'performer_id' => 'identifiers',
            'location_reference_id' => 'identifiers',
            'performer_type_id' => 'codeable_concepts',
        ];
    }

    private function foreignKeyName(string $columnName): string
    {
        return 'service_request_requests_'.$columnName.'_upgrade_fk';
    }
};
