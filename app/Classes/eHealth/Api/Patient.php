<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest as Request;
use App\Classes\eHealth\EHealthResponse;
use App\Enums\Person\EpisodeStatus;
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
     * Get brief information about episodes, in order not to disclose confidential and sensitive data.
     *
     * @param  string  $id
     * @param  array{
     *     period_start_from?: string,
     *     period_start_to?: string,
     *     period_end_from?: string,
     *     period_end_to?: string,
     *     page?: int,
     *     page_size?: int
     *     }  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     *
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/patient-summary/get-short-episodes-by-search-params
     */
    public function getShortEpisodes(string $id, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateEpisodes(...));
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$id/summary/episodes", $mergedQuery);
    }

    /**
     * Get a list of short Encounter info filtered by search params.
     *
     * @param  string  $id
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
     * @see https://medicaleventsmisapi.docs.apiary.io/#reference/medical-events/patient-summary/get-short-episodes-by-search-params
     */
    public function getShortEncounters(string $id, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateEncounters(...));
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$id/summary/encounters", $mergedQuery);
    }

    /**
     * Get the current diagnoses related only to active episodes.
     *
     * @param  string  $id
     * @param  array  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function getActiveDiagnoses(string $id, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$id/summary/diagnoses", $mergedQuery);
    }

    /**
     * Get the current diagnoses related only to active episodes.
     *
     * @param  string  $id
     * @param  array  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function getObservations(string $id, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return $this->get(self::URL . "/$id/summary/observations", $mergedQuery);
    }

    /**
     * Validate episodes data from eHealth API.
     *
     * @param  EHealthResponse  $response
     * @return array
     */
    protected function validateEpisodes(EHealthResponse $response): array
    {
        $replaced = [];
        foreach ($response->getData() as $data) {
            $replaced[] = self::replaceEHealthPropNames($data);
        }

        $rules = collect($this->episodeValidationRules())
            ->mapWithKeys(static fn ($rule, $key) => ["*.$key" => $rule])
            ->toArray();

        $validator = Validator::make($replaced, $rules);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error('Episode validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        return $validator->validate();
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
            Log::channel('e_health_errors')->error('Encounter validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        return $validator->validate();
    }

    /**
     * List of validation rules for episodes from eHealth.
     *
     * @return array
     */
    protected function episodeValidationRules(): array
    {
        return [
            'uuid' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(EpisodeStatus::values())],
            'ehealth_inserted_at' => ['required', 'date'],
            'ehealth_updated_at' => ['required', 'date'],
            'period' => ['required', 'array'],
            'period.start' => ['required', 'date'],
            'period.end' => ['nullable', 'date']
        ];
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
            'status' => ['required', 'string'],
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
