<?php

declare(strict_types=1);

namespace App\Exceptions\MedicalEvents;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ClinicalStorageException extends RuntimeException
{
    public static function fromQueryException(QueryException $exception, string $operation): self
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        // QueryException messages contain bindings, including patient data. Log only diagnostic identifiers.
        $driverMessage = (string) ($exception->errorInfo[2] ?? '');
        preg_match('/(?:column|relation|table|constraint) [\"`\']([a-zA-Z_][a-zA-Z0-9_.]*)[\"`\']/', $driverMessage, $object);
        Log::error('Clinical storage operation failed', [
            'operation' => $operation,
            'exceptionType' => $exception::class,
            'connectionName' => $exception->getConnectionName(),
            'sqlState' => $sqlState,
            'driverCode' => $exception->errorInfo[1] ?? null,
            'databaseObject' => $object[1] ?? null,
            'sourceFile' => $exception->getFile(),
            'sourceLine' => $exception->getLine(),
            'applicationTrace' => array_values(array_map(
                static fn (array $frame): array => array_intersect_key($frame, array_flip(['file', 'line', 'class', 'function'])),
                array_filter($exception->getTrace(), static fn (array $frame): bool => isset($frame['file']) && str_starts_with($frame['file'], app_path().DIRECTORY_SEPARATOR))
            )),
        ]);

        return new self(__(in_array($sqlState, ['42703', '42P01', '42S22', '42S02'], true)
            ? 'clinical-storage.update_required' : 'clinical-storage.write_failed'));
    }

    /** The repository already recorded safe diagnostics, without the original SQL exception chain. */
    public function report(): void
    {
    }
}
