<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest as Request;
use App\Classes\eHealth\EHealthResponse;
use App\Enums\Person\ClinicalImpressionStatus;
use App\Enums\Person\EncounterStatus;
use App\Enums\Person\ImmunizationStatus;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Patient extends Request
{
    protected const string URL = '/api/patients';

    /**
     * @param  string  $id  Person ID
     * @param  array  $data
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/encounter-data-package/submit-encounter-package
     */
    public function submitEncounter(string $id, array $data): PromiseInterface|EHealthResponse
    {
        return $this->post(self::URL . "/$id/encounter_package", $data);
    }

    /**
     * Get a list of short Encounter info filtered by search params.
     *
     * @param  string  $patientId
     * @param  array{
     *     period_start_from?: string,
     *     period_start_to?: string,
     *     period_end_from?: string,
     *     period_end_to?: string,
     *     episode_id?: string,
     *     status?: string,
     *     type?: string,
     *     class?: string,
     *     performer_speciality?: string,
     *     page?: int,
     *     page_size?: int
     *     }  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/patient-summary/get-short-encounters-by-search-params
     */
    public function getShortEncounters(string $patientId, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateEncounters(...));
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$patientId/summary/encounters", $mergedQuery);
    }

    /**
     * Get data about Encounter by ID.
     *
     * @param  string  $patientId
     * @param  string  $encounterId
     * @param  array  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/immunization/get-encounter-by-id
     */
    public function getEncounterById(
        string $patientId,
        string $encounterId,
        array $query = []
    ): PromiseInterface|EHealthResponse {
        return $this->get(self::URL . "/$patientId/encounters/$encounterId", $query);
    }

    /**
     * Get a list of observations.
     *
     * @param  string  $patientId
     * @param  array{
     *     period_start_from?: string,
     *     period_start_to?: string,
     *     period_end_from?: string,
     *     period_end_to?: string,
     *     episode_id?: string,
     *     incoming_referral_id?: string,
     *     origin_episode_id?: string,
     *     managing_organization_id?: string,
     *     page?: int,
     *     page_size?: int
     * }  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/encounter/get-encounters-by-search-params
     */
    public function getEncounterBySearchParams(string $patientId, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$patientId/encounters", $mergedQuery);
    }

    /**
     * Get a list of summary info about clinical impressions.
     *
     * @param  string  $patientId
     * @param  array{encounter_id?: string, episode_id?: string, code?: string, status?: string, page?: int, page_size?: int}  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/patient-summary/get-clinical-impressions
     */
    public function getClinicalImpressions(string $patientId, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateClinicalImpressions(...));
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$patientId/summary/clinical_impressions", $mergedQuery);
    }

    /**
     * Get a list of summary info about immunizations.
     *
     * @param  string  $patientId
     * @param  array{vaccine_code?: string, date_from?: string, date_to?: string, page?: int, page_size?: int}  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/patient-summary/get-immunizations
     */
    public function getImmunizations(string $patientId, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateImmunizations(...));
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$patientId/summary/immunizations", $mergedQuery);
    }

    /**
     * Get the current diagnoses related only to active episodes.
     *
     * @param  string  $patientId
     * @param  array{code?:string, page?: int, page_size?: int}  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/patient-summary/get-active-diagnoses
     */
    public function getActiveDiagnoses(string $patientId, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$patientId/summary/diagnoses", $mergedQuery);
    }

    /**
     * Get a list of summary info about conditions.
     *
     * @param  string  $patientId
     * @param  array{code?: string, onset_date_from?: string, onset_date_to?: string, page?: int, page_size?: int}  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/patient-summary/get-conditions
     */
    public function getConditions(string $patientId, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$patientId/summary/conditions", $mergedQuery);
    }

    /**
     * Get a list of summary info about allergy intolerances.
     *
     * @param  string  $patientId
     * @param  array{code?: string, onset_date_time_from?: string, onset_date_time_to?: string, page?: int, page_size?: int}  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/patient-summary/get-allergy-intolerances
     */
    public function getAllergyIntolerances(string $patientId, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$patientId/summary/allergy_intolerances", $mergedQuery);
    }

    /**
     * Get a list of summary info about risk assessments.
     *
     * @param  string  $patientId
     * @param  array{code?: string, asserted_date_from?: string, asserted_date_to?: string, page?: int, page_size?: int}  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/patient-summary/get-risk-assessments-by-search-params
     */
    public function getRiskAssessments(string $patientId, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$patientId/summary/risk_assessments", $mergedQuery);
    }

    /**
     * Get a list of summary info about devices.
     *
     * @param  string  $patientId
     * @param  array{type?: string, asserted_date_from?: string, asserted_date_to?: string, page?: int, page_size?: int}  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/patient-summary/get-devices-by-search-params
     */
    public function getDevices(string $patientId, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$patientId/summary/devices", $mergedQuery);
    }

    /**
     * Get a list of summary info about medication statements.
     *
     * @param  string  $patientId
     * @param  array{medication_code?: string, asserted_date_from?: string, asserted_date_to?: string, page?: int, page_size?: int}  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/patient-summary/get-medication-statement-by-search-params
     */
    public function getMedicationStatements(string $patientId, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$patientId/summary/medication_statements", $mergedQuery);
    }

    /**
     * Validate encounters data from eHealth API.
     *
     * @param  EHealthResponse  $response
     * @return array
     */
    protected function validateEncounters(EHealthResponse $response): array
    {
        $replaced = [];
        foreach ($response->getData() as $data) {
            $replaced[] = self::replaceEHealthPropNames($data);
        }

        $rules = collect($this->encounterValidationRules())
            ->mapWithKeys(static fn ($rule, $key) => ["*.$key" => $rule])
            ->toArray();

        $validator = Validator::make($replaced, $rules);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error(
                'Encounter validation failed: ' . implode(', ', $validator->errors()->all())
            );
        }

        return $validator->validate();
    }

    /**
     * Validate clinical impressions data from eHealth API.
     *
     * @param  EHealthResponse  $response
     * @return array
     */
    protected function validateClinicalImpressions(EHealthResponse $response): array
    {
        $replaced = [];
        foreach ($response->getData() as $data) {
            $replaced[] = self::replaceEHealthPropNames($data);
        }

        $rules = collect($this->clinicalImpressionValidationRules())
            ->mapWithKeys(static fn ($rule, $key) => ["*.$key" => $rule])
            ->toArray();

        $validator = Validator::make($replaced, $rules);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error(
                'Clinical impression validation failed: ' . implode(', ', $validator->errors()->all())
            );
        }

        return $validator->validate();
    }

    /**
     * Validate immunizations data from eHealth API.
     *
     * @param  EHealthResponse  $response
     * @return array
     */
    protected function validateImmunizations(EHealthResponse $response): array
    {
        $replaced = [];
        foreach ($response->getData() as $data) {
            $replaced[] = self::replaceEHealthPropNames($data);
        }

        $rules = collect($this->immunizationValidationRules())
            ->mapWithKeys(static fn ($rule, $key) => ["*.$key" => $rule])
            ->toArray();

        $validator = Validator::make($replaced, $rules);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error(
                'Immunization validation failed: ' . implode(', ', $validator->errors()->all())
            );
        }

        return $validator->validate();
    }

    /**
     * List of validation rules for encounters from eHealth.
     *
     * @return array
     */
    protected function encounterValidationRules(): array
    {
        return [
            'uuid' => ['required', 'uuid'],
            'status' => ['required', Rule::in(EncounterStatus::values())],

            'class' => ['required', 'array'],
            'class.code' => ['required', 'string'],
            'class.system' => ['required', 'string'],

            'type' => ['required', 'array'],
            'type.coding' => ['required', 'array'],
            'type.coding.*.code' => ['required', 'string'],
            'type.coding.*.system' => ['required', 'string'],
            'type.text' => ['nullable', 'string'],

            'episode' => ['required', 'array'],
            'episode.identifier' => ['required', 'array'],
            'episode.identifier.type' => ['required', 'array'],
            'episode.identifier.type.coding' => ['required', 'array'],
            'episode.identifier.type.coding.*.code' => ['required', 'string'],
            'episode.identifier.type.coding.*.system' => ['required', 'string'],
            'episode.identifier.type.text' => ['nullable', 'string'],
            'episode.identifier.value' => ['required', 'uuid'],

            'performer_speciality' => ['required', 'array'],
            'performer_speciality.coding' => ['required', 'array'],
            'performer_speciality.coding.*.code' => ['required', 'string'],
            'performer_speciality.coding.*.system' => ['required', 'string'],
            'performer_speciality.text' => ['nullable', 'string'],

            'period' => ['required', 'array'],
            'period.start' => ['required', 'date'],
            'period.end' => ['required', 'date']
        ];
    }

    /**
     * List of validation rules for clinical impressions from eHealth.
     *
     * @return array
     */
    protected function clinicalImpressionValidationRules(): array
    {
        return [
            'uuid' => ['required', 'uuid'],
            'status' => ['required', Rule::in(ClinicalImpressionStatus::values())],
            'description' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'summary' => ['nullable', 'string'],
            'explanatory_letter' => ['nullable', 'string'],
            'ehealth_inserted_at' => ['required', 'date'],
            'ehealth_updated_at' => ['required', 'date'],

            'assessor' => ['required', 'array'],
            'assessor.identifier' => ['required', 'array'],
            'assessor.identifier.type' => ['required', 'array'],
            'assessor.identifier.type.coding' => ['required', 'array'],
            'assessor.identifier.type.coding.*.code' => ['required', 'string'],
            'assessor.identifier.type.coding.*.system' => ['required', 'string'],
            'assessor.identifier.type.text' => ['nullable', 'string'],
            'assessor.identifier.value' => ['required', 'uuid'],

            'code' => ['required', 'array'],
            'code.coding' => ['required', 'array'],
            'code.coding.*.code' => ['required', 'string'],
            'code.coding.*.system' => ['required', 'string'],
            'code.text' => ['nullable', 'string'],

            'effective_period' => ['nullable', 'array'],
            'effective_period.start' => ['nullable', 'date'],
            'effective_period.end' => ['nullable', 'date'],
            'effective_date_time' => ['nullable', 'date'],

            'encounter' => ['required', 'array'],
            'encounter.identifier' => ['required', 'array'],
            'encounter.identifier.type' => ['required', 'array'],
            'encounter.identifier.type.coding' => ['required', 'array'],
            'encounter.identifier.type.coding.*.code' => ['required', 'string'],
            'encounter.identifier.type.coding.*.system' => ['required', 'string'],
            'encounter.identifier.type.text' => ['nullable', 'string'],
            'encounter.identifier.value' => ['required', 'uuid'],

            'findings' => ['nullable', 'array'],
            'findings.*.basis' => ['nullable', 'string'],
            'findings.*.item_reference' => ['required', 'array'],
            'findings.*.item_reference.identifier' => ['required', 'array'],
            'findings.*.item_reference.identifier.type' => ['required', 'array'],
            'findings.*.item_reference.identifier.type.coding' => ['required', 'array'],
            'findings.*.item_reference.identifier.type.coding.*.code' => ['required', 'string'],
            'findings.*.item_reference.identifier.type.coding.*.system' => ['required', 'string'],
            'findings.*.item_reference.identifier.type.text' => ['nullable', 'string'],
            'findings.*.item_reference.identifier.value' => ['required', 'uuid'],

            'previous' => ['nullable', 'array'],
            'previous.identifier' => ['nullable', 'array'],
            'previous.identifier.type' => ['nullable', 'array'],
            'previous.identifier.type.coding' => ['nullable', 'array'],
            'previous.identifier.type.coding.*.code' => ['nullable', 'string'],
            'previous.identifier.type.coding.*.system' => ['nullable', 'string'],
            'previous.identifier.type.text' => ['nullable', 'string'],
            'previous.identifier.value' => ['nullable', 'uuid'],

            'problems' => ['nullable', 'array'],
            'problems.*.identifier' => ['required', 'array'],
            'problems.*.identifier.type' => ['required', 'array'],
            'problems.*.identifier.type.coding' => ['required', 'array'],
            'problems.*.identifier.type.coding.*.code' => ['required', 'string'],
            'problems.*.identifier.type.coding.*.system' => ['required', 'string'],
            'problems.*.identifier.type.text' => ['nullable', 'string'],
            'problems.*.identifier.value' => ['required', 'uuid'],

            'supporting_info' => ['nullable', 'array'],
            'supporting_info.*.identifier' => ['required', 'array'],
            'supporting_info.*.identifier.type' => ['required', 'array'],
            'supporting_info.*.identifier.type.coding' => ['required', 'array'],
            'supporting_info.*.identifier.type.coding.*.code' => ['required', 'string'],
            'supporting_info.*.identifier.type.coding.*.system' => ['required', 'string'],
            'supporting_info.*.identifier.type.text' => ['nullable', 'string'],
            'supporting_info.*.identifier.value' => ['required', 'uuid'],
        ];
    }

    /**
     * List of validation rules for immunizations from eHealth.
     *
     * @return array
     */
    protected function immunizationValidationRules(): array
    {
        return [
            'uuid' => ['required', 'uuid'],
            'status' => ['required', Rule::in(ImmunizationStatus::values())],
            'not_given' => ['required', 'boolean'],
            'primary_source' => ['required', 'boolean'],
            'date' => ['required', 'date'],
            'ehealth_inserted_at' => ['required', 'date'],
            'ehealth_updated_at' => ['required', 'date'],
            'manufacturer' => ['nullable', 'string'],
            'lot_number' => ['nullable', 'string'],
            'expiration_date' => ['nullable', 'date'],
            'explanatory_letter' => ['nullable', 'string'],

            'vaccine_code' => ['required', 'array'],
            'vaccine_code.coding' => ['required', 'array'],
            'vaccine_code.coding.*.code' => ['required', 'string'],
            'vaccine_code.coding.*.system' => ['required', 'string'],
            'vaccine_code.text' => ['nullable', 'string'],

            'context' => ['required', 'array'],
            'context.identifier' => ['required', 'array'],
            'context.identifier.type' => ['required', 'array'],
            'context.identifier.type.coding' => ['required', 'array'],
            'context.identifier.type.coding.*.code' => ['required', 'string'],
            'context.identifier.type.coding.*.system' => ['required', 'string'],
            'context.identifier.type.text' => ['nullable', 'string'],
            'context.identifier.value' => ['required', 'uuid'],

            'performer' => ['nullable', 'array'],
            'performer.identifier' => ['nullable', 'array'],
            'performer.identifier.type' => ['nullable', 'array'],
            'performer.identifier.type.coding' => ['nullable', 'array'],
            'performer.identifier.type.coding.*.code' => ['nullable', 'string'],
            'performer.identifier.type.coding.*.system' => ['nullable', 'string'],
            'performer.identifier.type.text' => ['nullable', 'string'],
            'performer.identifier.value' => ['nullable', 'uuid'],

            'report_origin' => ['nullable', 'array'],
            'report_origin.coding' => ['nullable', 'array'],
            'report_origin.coding.*.code' => ['nullable', 'string'],
            'report_origin.coding.*.system' => ['nullable', 'string'],
            'report_origin.text' => ['nullable', 'string'],

            'site' => ['nullable', 'array'],
            'site.coding' => ['nullable', 'array'],
            'site.coding.*.code' => ['nullable', 'string'],
            'site.coding.*.system' => ['nullable', 'string'],
            'site.text' => ['nullable', 'string'],

            'route' => ['nullable', 'array'],
            'route.coding' => ['nullable', 'array'],
            'route.coding.*.code' => ['nullable', 'string'],
            'route.coding.*.system' => ['nullable', 'string'],
            'route.text' => ['nullable', 'string'],

            'dose_quantity' => ['nullable', 'array'],
            'dose_quantity.value' => ['nullable', 'numeric'],
            'dose_quantity.comparator' => ['nullable', 'string'],
            'dose_quantity.unit' => ['nullable', 'string'],
            'dose_quantity.system' => ['nullable', 'string'],
            'dose_quantity.code' => ['nullable', 'string'],

            'explanation' => ['nullable', 'array'],
            'explanation.reasons' => ['nullable', 'array'],
            'explanation.reasons.*.coding' => ['nullable', 'array'],
            'explanation.reasons.*.coding.*.code' => ['nullable', 'string'],
            'explanation.reasons.*.coding.*.system' => ['nullable', 'string'],
            'explanation.reasons.*.text' => ['nullable', 'string'],

            'explanation.reasons_not_given' => ['nullable', 'array'],
            'explanation.reasons_not_given.*.coding' => ['nullable', 'array'],
            'explanation.reasons_not_given.*.coding.*.code' => ['nullable', 'string'],
            'explanation.reasons_not_given.*.coding.*.system' => ['nullable', 'string'],
            'explanation.reasons_not_given.*.text' => ['nullable', 'string'],

            'reactions' => ['nullable', 'array'],
            'reactions.*.detail' => ['required', 'array'],
            'reactions.*.detail.identifier' => ['required', 'array'],
            'reactions.*.detail.identifier.type' => ['required', 'array'],
            'reactions.*.detail.identifier.type.coding' => ['required', 'array'],
            'reactions.*.detail.identifier.type.coding.*.code' => ['required', 'string'],
            'reactions.*.detail.identifier.type.coding.*.system' => ['required', 'string'],
            'reactions.*.detail.identifier.value' => ['required', 'uuid'],
            'reactions.*.detail.display_value' => ['nullable', 'string'],

            'vaccination_protocols' => ['nullable', 'array'],
            'vaccination_protocols.*.dose_sequence' => ['nullable', 'integer'],
            'vaccination_protocols.*.description' => ['nullable', 'string'],
            'vaccination_protocols.*.authority' => ['nullable', 'array'],
            'vaccination_protocols.*.authority.coding' => ['nullable', 'array'],
            'vaccination_protocols.*.authority.coding.*.code' => ['nullable', 'string'],
            'vaccination_protocols.*.authority.coding.*.system' => ['nullable', 'string'],
            'vaccination_protocols.*.series' => ['nullable', 'string'],
            'vaccination_protocols.*.series_doses' => ['nullable', 'integer'],
            'vaccination_protocols.*.target_diseases' => ['required', 'array'],
            'vaccination_protocols.*.target_diseases.*.coding' => ['nullable', 'array'],
            'vaccination_protocols.*.target_diseases.*.text' => ['nullable', 'string'],
            'vaccination_protocols.*.target_diseases.*.coding.*.code' => ['nullable', 'string'],
            'vaccination_protocols.*.target_diseases.*.coding.*.system' => ['nullable', 'string']
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
