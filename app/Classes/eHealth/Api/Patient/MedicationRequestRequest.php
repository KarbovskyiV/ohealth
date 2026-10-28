<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api\Patient;

use App\Classes\eHealth\EHealthResponse;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MedicationRequestRequest extends PatientApiBase
{
    /** Read the unsigned request, including the snapshot required for signing. */
    public function getById(string $patientId, string $id, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateDetails(...));

        return $this->get("/api/persons/{$patientId}/medication_request_requests/{$id}", $query);
    }

    protected function validateDetails(EHealthResponse $response): array
    {
        $native = $response->getData();
        $data = $this->replaceEHealthPropNames($native);
        $toValidate = isset($data[0]) && is_array($data[0]) ? $data[0] : $data;

        $validator = Validator::make($toValidate, [
            'uuid' => 'required|string',
            'status' => 'required|string',
            'request_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error(
                'MedicationRequestRequest details validation failed: ' . implode(', ', $validator->errors()->all())
            );
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // KEP signs this native snapshot, including its id, nested ids, seed and snake_case fields.
        return $native;
    }

}
