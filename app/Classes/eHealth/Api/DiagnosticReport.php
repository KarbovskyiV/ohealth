<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest as Request;
use App\Classes\eHealth\EHealthResponse;
use App\Enums\Person\DiagnosticReportStatus;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DiagnosticReport extends Request
{
    protected const string URL = '/api/patients';

    /**
     * Create the diagnostic report for patient.
     *
     * @param  string  $uuid  Person UUID
     * @param  array  $data
     * @return EHealthResponse|PromiseInterface
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/diagnostic-report-data-package/submit-diagnostic-report-package
     */
    public function create(string $uuid, array $data = []): PromiseInterface|EHealthResponse
    {
        return $this->post(self::URL . "/$uuid/diagnostic_report_package", $data);
    }

    /**
     * Get a diagnostic report by ID.
     *
     * @param  string  $patientId
     * @param  string  $diagnosticReportId
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/diagnostic-report/get-diagnostic-report-by-id
     */
    public function getById(string $patientId, string $diagnosticReportId): PromiseInterface|EHealthResponse
    {
        return $this->get(self::URL . "/$patientId/diagnostic_reports/$diagnosticReportId");
    }

    /**
     * Get a list of info filtered by search params.
     *
     * @param  string  $patientId
     * @param  array{
     *     code?: string,
     *     encounter_id?: string,
     *     context_episode_id?: string,
     *     origin_episode_id?: string,
     *     issued_from?: string,
     *     issued_to?: string,
     *     based_on?: string,
     *     managing_organization_id?: string,
     *     specimen_id?: string,
     *     page?: int,
     *     page_size?: int
     *     }  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/diagnostic-report/get-diagnostic-report-by-search-params
     */
    public function getBySearchParams(string $patientId, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateDiagnosticReports(...));
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$patientId/diagnostic_reports", $mergedQuery);
    }

    /**
     * Get a list of summary info about diagnostic reports.
     *
     * @param  string  $patientId
     * @param  array{code?: string, issued_from?: string, issued_to?: string, page?: int, page_size?: int}  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/patient-summary/get-diagnostic-report-by-search-params
     */
    public function getSummary(string $patientId, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$patientId/summary/diagnostic_reports", $mergedQuery);
    }

    /**
     * Validate diagnostic reports response from eHealth API.
     *
     * @param  EHealthResponse  $response
     * @return array
     */
    protected function validateDiagnosticReports(EHealthResponse $response): array
    {
        $replaced = [];
        foreach ($response->getData() as $data) {
            $replaced[] = self::replaceEHealthPropNames($data);
        }

        $rules = collect($this->diagnosticReportValidationRules())
            ->mapWithKeys(static fn ($rule, $key) => ["*.$key" => $rule])
            ->toArray();

        $validator = Validator::make($replaced, $rules);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error(
                'Diagnostic report validation failed: ' . implode(', ', $validator->errors()->all())
            );
        }

        return $validator->validate();
    }

    /**
     * Validation rules for diagnostic report data.
     *
     * @return array
     */
    protected function diagnosticReportValidationRules(): array
    {
        return [
            'uuid' => ['required', 'uuid'],
            'status' => ['required', Rule::in(DiagnosticReportStatus::values())],

            'based_on' => ['nullable', 'array'],
            'based_on.identifier' => ['nullable', 'array'],
            'based_on.identifier.type' => ['nullable', 'array'],
            'based_on.identifier.type.coding' => ['nullable', 'array'],
            'based_on.identifier.type.coding.*.code' => ['nullable', 'string'],
            'based_on.identifier.type.coding.*.system' => ['nullable', 'string'],
            'based_on.identifier.value' => ['nullable', 'uuid'],

            'paper_referral' => ['nullable', 'array'],
            'paper_referral.requisition' => ['nullable', 'string'],
            'paper_referral.requester_legal_entity_name' => ['nullable', 'string'],
            'paper_referral.requester_legal_entity_edrpou' => ['required_with:paper_referral', 'string'],
            'paper_referral.requester_employee_name' => ['required_with:paper_referral', 'string'],
            'paper_referral.service_request_date' => ['required_with:paper_referral', 'date'],
            'paper_referral.note' => ['nullable', 'string'],

            'category' => ['required', 'array'],
            'category.*.coding' => ['required', 'array'],
            'category.*.coding.*.code' => ['required', 'string'],
            'category.*.coding.*.system' => ['required', 'string'],
            'category.*.text' => ['nullable', 'string'],

            'division' => ['nullable', 'array'],
            'division.identifier' => ['nullable', 'array'],
            'division.identifier.type' => ['nullable', 'array'],
            'division.identifier.type.coding' => ['nullable', 'array'],
            'division.identifier.type.coding.*.code' => ['nullable', 'string'],
            'division.identifier.type.coding.*.system' => ['nullable', 'string'],
            'division.identifier.value' => ['nullable', 'uuid'],

            'code' => ['required', 'array'],
            'code.identifier' => ['required', 'array'],
            'code.identifier.type' => ['required', 'array'],
            'code.identifier.type.coding' => ['required', 'array'],
            'code.identifier.type.coding.*.code' => ['required', 'string'],
            'code.identifier.type.coding.*.system' => ['required', 'string'],
            'code.identifier.value' => ['nullable', 'uuid'],

            'encounter' => ['nullable', 'array'],
            'encounter.identifier' => ['nullable', 'array'],
            'encounter.identifier.type' => ['nullable', 'array'],
            'encounter.identifier.type.coding' => ['nullable', 'array'],
            'encounter.identifier.type.coding.*.code' => ['nullable', 'string'],
            'encounter.identifier.type.coding.*.system' => ['nullable', 'string'],
            'encounter.identifier.value' => ['nullable', 'uuid'],

            'origin_episode' => ['nullable', 'array'],
            'origin_episode.identifier' => ['nullable', 'array'],
            'origin_episode.identifier.type' => ['nullable', 'array'],
            'origin_episode.identifier.type.coding' => ['nullable', 'array'],
            'origin_episode.identifier.type.coding.*.code' => ['nullable', 'string'],
            'origin_episode.identifier.type.coding.*.system' => ['nullable', 'string'],
            'origin_episode.identifier.value' => ['nullable', 'uuid'],

            'effective_date_time' => ['nullable', 'date'],
            'effective_period' => ['nullable', 'array'],
            'effective_period.start' => ['nullable', 'date'],
            'effective_period.end' => ['nullable', 'date'],

            'issued' => ['required', 'date'],
            'primary_source' => ['required', 'boolean'],

            'performer' => ['nullable', 'array'],
            'performer.reference' => ['nullable', 'array'],
            'performer.text' => ['nullable', 'string'],
            'performer.reference.display_value' => ['nullable', 'string'],
            'performer.reference.identifier' => ['nullable', 'array'],
            'performer.reference.identifier.type' => ['nullable', 'array'],
            'performer.reference.identifier.type.coding' => ['nullable', 'array'],
            'performer.reference.identifier.type.coding.*.code' => ['nullable', 'string'],
            'performer.reference.identifier.type.coding.*.system' => ['nullable', 'string'],
            'performer.reference.identifier.type.text' => ['nullable', 'string'],
            'performer.reference.identifier.value' => ['nullable', 'uuid'],

            'report_origin' => ['nullable', 'array'],
            'report_origin.coding' => ['nullable', 'array'],
            'report_origin.coding.*.code' => ['nullable', 'string'],
            'report_origin.coding.*.system' => ['nullable', 'string'],
            'report_origin.text' => ['nullable', 'string'],

            'recorded_by' => ['required', 'array'],
            'recorded_by.identifier' => ['required', 'array'],
            'recorded_by.identifier.type' => ['required', 'array'],
            'recorded_by.identifier.type.coding' => ['required', 'array'],
            'recorded_by.identifier.type.text' => ['nullable', 'string'],
            'recorded_by.identifier.type.coding.*.code' => ['required', 'string'],
            'recorded_by.identifier.type.coding.*.system' => ['required', 'string'],
            'recorded_by.identifier.value' => ['required', 'uuid'],

            'results_interpreter' => ['nullable', 'array'],
            'results_interpreter.reference' => ['nullable', 'array'],
            'results_interpreter.text' => ['nullable', 'string'],
            'results_interpreter.reference.display_value' => ['nullable', 'string'],
            'results_interpreter.reference.identifier' => ['nullable', 'array'],
            'results_interpreter.reference.identifier.type' => ['nullable', 'array'],
            'results_interpreter.reference.identifier.type.coding' => ['nullable', 'array'],
            'results_interpreter.reference.identifier.type.coding.*.code' => ['nullable', 'string'],
            'results_interpreter.reference.identifier.type.coding.*.system' => ['nullable', 'string'],
            'results_interpreter.reference.identifier.type.text' => ['nullable', 'string'],
            'results_interpreter.reference.identifier.value' => ['nullable', 'uuid'],

            'managing_organization' => ['nullable', 'array'],
            'managing_organization.identifier' => ['nullable', 'array'],
            'managing_organization.identifier.type' => ['nullable', 'array'],
            'managing_organization.identifier.type.coding' => ['nullable', 'array'],
            'managing_organization.identifier.type.coding.*.code' => ['nullable', 'string'],
            'managing_organization.identifier.type.coding.*.system' => ['nullable', 'string'],
            'managing_organization.identifier.value' => ['nullable', 'uuid'],

            'specimens' => ['nullable', 'array'],
            'specimens.*.identifier' => ['nullable', 'array'],
            'specimens.*.identifier.type' => ['nullable', 'array'],
            'specimens.*.identifier.type.coding' => ['nullable', 'array'],
            'specimens.*.identifier.type.coding.*.code' => ['nullable', 'string'],
            'specimens.*.identifier.type.coding.*.system' => ['nullable', 'string'],
            'specimens.*.identifier.value' => ['nullable', 'uuid'],

            'conclusion' => ['nullable', 'string'],

            'conclusion_code' => ['nullable', 'array'],
            'conclusion_code.coding' => ['nullable', 'array'],
            'conclusion_code.coding.*.code' => ['nullable', 'string'],
            'conclusion_code.coding.*.system' => ['nullable', 'string'],
            'conclusion_code.text' => ['nullable', 'string'],

            'explanatory_letter' => ['nullable', 'string'],

            'cancellation_reason' => ['nullable', 'array'],
            'cancellation_reason.coding' => ['nullable', 'array'],
            'cancellation_reason.coding.*.code' => ['nullable', 'string'],
            'cancellation_reason.coding.*.system' => ['nullable', 'string'],
            'cancellation_reason.text' => ['nullable', 'string'],

            'used_references' => ['nullable', 'array'],
            'used_references.*.identifier' => ['nullable', 'array'],
            'used_references.*.identifier.type' => ['nullable', 'array'],
            'used_references.*.identifier.type.coding' => ['nullable', 'array'],
            'used_references.*.identifier.type.coding.*.code' => ['nullable', 'string'],
            'used_references.*.identifier.type.coding.*.system' => ['nullable', 'string'],
            'used_references.*.identifier.value' => ['nullable', 'uuid'],

            'ehealth_inserted_at' => ['required', 'date'],
            'ehealth_updated_at' => ['required', 'date']
        ];
    }

    /**
     * Replace eHealth property names with the ones used in the application.
     * E.g., id => uuid, inserted_at => ehealth_inserted_at.
     */
    protected static function replaceEHealthPropNames(array $properties): array
    {
        $replaced = [];

        foreach ($properties as $name => $value) {
            $newName = match ($name) {
                'id' => 'uuid',
                'inserted_at' => 'ehealth_inserted_at',
                'updated_at' => 'ehealth_updated_at',
                default => $name
            };

            $replaced[$newName] = $value;
        }

        return $replaced;
    }
}
