<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Core\Arr;

use App\Classes\eHealth\EHealthRequest as Request;
use App\Classes\eHealth\EHealthResponse;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CarePlanActivity extends Request
{
    protected const string URL = '/api/care_plans';

    /**
     * Create a new Care Plan Activity in eHealth.
     *
     * @param  string  $patientId
     * @param  string  $carePlanId
     * @param  array  $payload
     * @return PromiseInterface|EHealthResponse
     * @throws EHealthConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function create(string $patientId, string $carePlanId, array $payload): PromiseInterface|EHealthResponse
    {
        $this->setValidator(\App\Classes\eHealth\Api\Job::validateSubmissionResponse(...));

        return $this->post("/api/patients/{$patientId}/care_plans/{$carePlanId}/activities", $payload);
    }

    /**
     * Cancel a Care Plan Activity (API-007-006-0005).
     * Requires Digital Signature (DS). Request body is signed_data only; the signed PKCS#7
     * content must be the activity from DB with $.detail.status_reason set.
     *
     * @param  string  $personId
     * @param  string  $carePlanId
     * @param  string  $activityId
     * @param  array  $payload
     * @return PromiseInterface|EHealthResponse
     * @throws EHealthConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function cancel(string $personId, string $carePlanId, string $activityId, array $payload): PromiseInterface|EHealthResponse
    {
        $this->setValidator(\App\Classes\eHealth\Api\Job::validateSubmissionResponse(...));

        return $this->patch("/api/patients/$personId/care_plans/$carePlanId/activities/$activityId/actions/cancel", $payload);
    }

    /**
     * Complete a Care Plan Activity.
     * Note: This does NOT require a Digital Signature (DS).
     * The payload must contain 'outcome_codeable_concept'.
     *
     * @param  string  $personId
     * @param  string  $carePlanId
     * @param  string  $activityId
     * @param  array  $payload
     * @return PromiseInterface|EHealthResponse
     * @throws EHealthConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function complete(string $personId, string $carePlanId, string $activityId, array $payload): PromiseInterface|EHealthResponse
    {
        $this->setValidator(\App\Classes\eHealth\Api\Job::validateSubmissionResponse(...));

        return $this->patch("/api/patients/$personId/care_plans/$carePlanId/activities/$activityId/actions/complete", $payload);
    }

    /**
     * Fetch a summary of Care Plan Activities.
     *
     * @param  string  $personId
     * @param  string  $carePlanId
     * @param  array  $query
     * @return PromiseInterface|EHealthResponse
     */
    public function getSummary(string $personId, string $carePlanId, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateMany(...));

        return $this->get("/api/patients/$personId/care_plans/$carePlanId/activities", $query);
    }

    /**
     * Fetch details of a single Care Plan Activity.
     *
     * @param  string  $personId
     * @param  string  $carePlanId
     * @param  string  $activityId
     * @return PromiseInterface|EHealthResponse
     */
    public function getDetails(string $personId, string $carePlanId, string $activityId, bool $preserveApiKeys = false): PromiseInterface|EHealthResponse
    {
        $this->setValidator(fn (EHealthResponse $response): array => $this->validateDetails($response, $preserveApiKeys));

        return $this->get("/api/patients/$personId/care_plans/$carePlanId/activities/$activityId");
    }

    protected function validateDetails(EHealthResponse $response, bool $preserveApiKeys = false): array
    {
        $native = $response->getData();
        if (array_is_list($native) && count($native) === 1) {
            $native = $native[0];
        }
        $data = Arr::toCamelCase($native);
        if (isset($data['author']['identifier'])) {
            $data['author'] = [$data['author']];
        }
        if (isset($data['status']) && !isset($data['detail']['status'])) {
            $data['detail']['status'] = $data['status'];
        }

        $validator = Validator::make($data, [
            'id' => 'required|uuid',
            'detail.status' => 'required|string',
            'detail.kind' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error(
                'CarePlanActivity details validation failed: ' . implode(', ', $validator->errors()->all())
            );
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // Signed snapshots retain the API's keys; both forms pass the same validator.
        return $preserveApiKeys ? $native : $data;
    }

    protected function validateMany(EHealthResponse $response): array
    {
        $transformedData = [];
        $items = $response->getData();
        if (isset($items['data']) && is_array($items['data'])) {
            $items = $items['data'];
        }
        if (is_array($items)) {
            foreach ($items as $item) {
                if (is_array($item)) {
                    $transformedData[] = Arr::toCamelCase($item);
                }
            }
        }

        $validator = Validator::make($transformedData, [
            '*' => 'array',
            '*.id' => 'required|uuid',
            '*.status' => 'nullable|string',
            '*.kind' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error(
                'CarePlanActivity many validation failed: ' . implode(', ', $validator->errors()->all())
            );
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $transformedData;
    }

}
