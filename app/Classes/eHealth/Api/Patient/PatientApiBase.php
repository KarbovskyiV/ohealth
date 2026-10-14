<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api\Patient;

use App\Classes\eHealth\EHealthRequest as Request;
use App\Classes\eHealth\EHealthResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PatientApiBase extends Request
{
    protected const string URL = '/api/patients';

    /**
     * Validate the response to a submitted package, which links to the job processing it.
     *
     * @param  EHealthResponse  $response
     * @return array
     * @throws ValidationException
     */
    protected function validateSubmittedJob(EHealthResponse $response): array
    {
        $validator = Validator::make($response->getData(), [
            'status' => ['required', 'string'],
            'eta' => ['nullable', 'string'],
            'links' => ['required', 'array'],
            'links.*.entity' => ['required', 'string'],
            'links.*.href' => ['required', 'string']
        ]);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error('Submitted job validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        return $validator->validate();
    }

    /**
     * Replace recursively eHealth property names with the ones used in the application.
     * E.g., id => uuid, inserted_at => ehealth_inserted_at.
     */
    protected function replaceEHealthPropNames(array $properties): array
    {
        $mapping = $this->propertyMapping();
        $replaced = [];

        foreach ($properties as $name => $value) {
            $newName = $mapping[$name] ?? $name;
            $replaced[$newName] = is_array($value) ? $this->replaceEHealthPropNames($value) : $value;
        }

        return $replaced;
    }

    protected function propertyMapping(): array
    {
        return [
            'id' => 'uuid',
            'inserted_at' => 'ehealth_inserted_at',
            'inserted_by' => 'ehealth_inserted_by',
            'updated_at' => 'ehealth_updated_at',
            'updated_by' => 'ehealth_updated_by'
        ];
    }
}
