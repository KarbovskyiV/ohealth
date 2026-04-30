<?php

declare(strict_types=1);

namespace App\Livewire\Encounter\Forms;

use App\Core\BaseForm;
use App\Rules\Cyrillic;
use App\Rules\InDictionary;
use App\Rules\OnlyOnePrimaryDiagnosis;
use App\Rules\PastDateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

class EncounterForm extends BaseForm
{
    public array $encounter = ['diagnoses' => [], 'reasons' => [], 'actions' => []];

    public array $episode = ['id' => ''];

    public array $conditions;

    public array $immunizations;

    public array $observations;

    public array $diagnosticReports;

    public array $procedures;

    public array $clinicalImpressions;

    protected function rules(): array
    {
        $rules = [
            'encounter.periodDate' => ['required', 'date', 'before_or_equal:today'],
            'encounter.periodStart' => ['required', 'date_format:H:i'],
            'encounter.periodEnd' => [
                'required',
                'date_format:H:i',
                'after:encounter.periodStart',
                new PastDateTime($this->encounter['periodDate']),
            ],
            'encounter.classCode' => ['required', 'string', new InDictionary('eHealth/encounter_classes')],
            'encounter.typeCode' => ['required', 'string', new InDictionary('eHealth/encounter_types')],
            'encounter.priorityCode' => [
                'required_if:encounter.classCode,INPATIENT',
                'string',
                new InDictionary('eHealth/encounter_priority')
            ],
            'encounter.reasons' => ['required_if:encounter.classCode,PHC', 'array'],
            'encounter.reasons.*.code' => ['required', 'string', new InDictionary('eHealth/ICPC2/reasons')],
            'encounter.reasons.*.text' => ['nullable', 'string', new Cyrillic()],
            'encounter.diagnoses' => [
                'required_unless:encounter.typeCode,intervention',
                new OnlyOnePrimaryDiagnosis(),
                'array'
            ],
            'encounter.*.diagnoses.*.roleCode' => [
                'required_with:conditions',
                'string',
                new InDictionary('eHealth/diagnosis_roles')
            ],
            'encounter.*.diagnoses.*.diagnosisRank' => ['nullable', 'integer', 'min:1', 'max:10'],
            'encounter.actions' => [
                'required_if:encounter.classCode,PHC',
                'prohibited_unless:encounter.classCode,PHC',
                'array'
            ],
            'encounter.actions.*.code' => ['required', 'string', new InDictionary('eHealth/ICPC2/actions')],
            'encounter.actions.*.text' => ['nullable', 'string', new Cyrillic()],
            'encounter.divisionId' => [
                'nullable',
                'uuid',
                Rule::prohibitedIf(in_array($this->encounter['typeCode'], ['field', 'home']))
            ],

            'episode.id' => ['nullable', 'uuid'],
            'episode.typeCode' => ['nullable', 'string', new InDictionary('eHealth/episode_types')],
            'episode.name' => ['nullable', 'string', new Cyrillic()],

            'conditions' => ['nullable', 'array'],
            'conditions.*.uuid' => ['nullable', 'uuid'], // for edit page
            'conditions.*.primarySource' => ['required_with:conditions', 'boolean'],
            'conditions.*.reportOriginCode' => ['nullable', 'string', 'required_if:conditions.*.primarySource,false'],
            'conditions.*.codeCode' => [
                'required_with:conditions',
                'string',
                new InDictionary(['eHealth/ICPC2/condition_codes', 'eHealth/ICD10_AM/condition_codes'])
            ],
            'conditions.*.codeSystem' => [
                'required_with:conditions',
                'string',
                'in:eHealth/ICPC2/condition_codes,eHealth/ICD10_AM/condition_codes'
            ],
            'conditions.*.clinicalStatus' => [
                'required_with:conditions',
                'string',
                new InDictionary('eHealth/condition_clinical_statuses')
            ],
            'conditions.*.verificationStatus' => [
                'required_with:conditions',
                'string',
                new InDictionary('eHealth/condition_verification_statuses')
            ],
            'conditions.*.severityCode' => [
                'nullable',
                'string',
                new InDictionary('eHealth/condition_severities')
            ],
            // absent on frontend
            'conditions.*.bodySites.*.code' => ['nullable', 'string', new InDictionary('eHealth/body_sites')],
            'conditions.*.onsetDate' => ['required_with:conditions', 'before:tomorrow', 'date'],
            'conditions.*.onsetTime' => ['required_with:conditions', 'date_format:H:i'],
            'conditions.*.assertedDate' => ['nullable', 'before:tomorrow', 'date'],
            'conditions.*.assertedTime' => ['nullable', 'date_format:H:i'],
            'conditions.*.asserterText' => ['nullable', 'string'],
            'conditions.*.stageCode' => [
                'nullable',
                'string',
                new InDictionary('eHealth/condition_stages')
            ],
            'conditions.*.evidenceCodes.*.code' => [
                'nullable',
                'string',
                new InDictionary('eHealth/ICPC2/reasons')
            ],
            'conditions.*.evidenceDetails.*.id' => ['nullable', 'uuid'],
            'conditions.*.evidenceDetails.*.type' => ['nullable', 'string', 'in:observation,condition'],

            'immunizations' => ['nullable', 'array'],
            'immunizations.*.uuid' => ['nullable', 'uuid'], // for edit page
            'immunizations.*.primarySource' => ['required_with:immunizations', 'boolean'],
            'immunizations.*.notGiven' => ['required_with:immunizations', 'boolean'],
            'immunizations.*.vaccineCode' => [
                'required_with:immunizations',
                'string',
                new InDictionary('eHealth/vaccine_codes')
            ],
            'immunizations.*.date' => ['required_with:immunizations', 'before:tomorrow', 'date'],
            'immunizations.*.time' => Rule::forEach(fn ($value, $attribute) => [
                'required_with:immunizations',
                'date_format:H:i',
                new PastDateTime($this->immunizations[explode('.', $attribute)[1]]['date']) //test is it works
            ]),
            'immunizations.*.reasons' => [
                'required_if:immunizations.*.notGiven,false',
                'prohibited_if:immunizations.*.notGiven,true',
                'array'
            ],
            'immunizations.*.reasons.*' => [
                'required',
                'string',
                new InDictionary('eHealth/reason_explanations')
            ],
            'immunizations.*.reasonNotGivenCode' => [
                'required_if:immunizations.*.notGiven,true',
                'prohibited_if:immunizations.*.notGiven,false',
                'string',
                new InDictionary('eHealth/reason_not_given_explanations')
            ],
            'immunizations.*.reportOriginCode' => [
                'required_if:immunizations.*.primarySource,false',
                'prohibited_if:immunizations.*.primarySource,true',
                'string',
                new InDictionary('eHealth/immunization_report_origins')
            ],
            'immunizations.*.reportOriginText' => ['nullable', 'string', 'max:255'],
            'immunizations.*.manufacturer' => ['nullable', 'string', 'max:255'],
            'immunizations.*.lotNumber' => ['nullable', 'string', 'max:255'],
            'immunizations.*.expirationDate' => ['nullable', 'date'],
            'immunizations.*.siteCode' => ['nullable', 'string', new InDictionary('eHealth/immunization_body_sites')],
            'immunizations.*.routeCode' => ['nullable', 'string', new InDictionary('eHealth/vaccination_routes')],
            'immunizations.*.doseQuantityValue' => ['nullable', 'numeric', 'min:0'],
            'immunizations.*.doseQuantityCode' => ['nullable', 'string', new InDictionary('eHealth/immunization_dosage_units')],
            'immunizations.*.doseQuantityUnit' => ['nullable', 'string'],
            'immunizations.*.vaccinationProtocols' => Rule::forEach(function ($value, $attribute) {
                $index = (int) explode('.', $attribute)[1];
                $immunization = $this->immunizations[$index];

                return [
                    Rule::when($immunization['primarySource'] && $immunization['notGiven'], 'required'),
                    'nullable',
                    'array',
                ];
            }),
            'immunizations.*.vaccinationProtocols.*.authorityCode' => [
                'required_with:immunizations.*.vaccinationProtocols',
                'string',
                new InDictionary('eHealth/vaccination_authorities')
            ],
            'immunizations.*.vaccinationProtocols.*.doseSequence' => ['nullable', 'integer', 'min:1', $this->requiredIfHasMoHAuthority()],
            'immunizations.*.vaccinationProtocols.*.series' => ['nullable', 'string', $this->requiredIfHasMoHAuthority()],
            'immunizations.*.vaccinationProtocols.*.seriesDoses' => ['nullable', 'integer', 'min:1', $this->requiredIfHasMoHAuthority()],
            'immunizations.*.vaccinationProtocols.*.description' => ['nullable', 'string'],
            'immunizations.*.vaccinationProtocols.*.targetDiseaseCodes' => [
                'required_with:immunizations.*.vaccinationProtocols',
                'array'
            ],
            'immunizations.*.vaccinationProtocols.*.targetDiseaseCodes.*' => [
                'required',
                'string',
                new InDictionary('eHealth/vaccination_target_diseases')
            ],

            //            'observations' => ['nullable', 'array'],
            //            'observations.*.primarySource' => ['required_with:observations', 'boolean'],
            //            'observations.*.performer' => [
            //                'required_if:observations.*.primarySource,true',
            //                'prohibited_if:observations.*.primarySource,false',
            //                'array'
            //            ],
            //            'observations.*.reportOrigin' => [
            //                'required_if:observations.*.primarySource,false',
            //                'array'
            //            ],
            //            'observations.*.reportOrigin.coding.*.code' => [
            //                'required_if:observations.*.primarySource,false',
            //                'prohibited_if:observations.*.primarySource,true',
            //                'string'
            //            ],
            //            'observations.*.categories' => ['required_with:observations', 'array'],
            //            'observations.*.categories.coding.*.code' => [
            //                'required',
            //                'string',
            //                new InDictionary(['eHealth/observation_categories', 'eHealth/ICF/observation_categories'])
            //            ],
            //            'observations.*.code' => ['required_with:observations', 'array'],
            //            'observations.*.code.coding.*.code' => [
            //                'required',
            //                'string',
            //                new InDictionary(['eHealth/LOINC/observation_codes', 'eHealth/ICF/classifiers'])
            //            ],
            //            'observations.*.issuedDate' => ['required_with:observations', 'date', 'before_or_equal:now'],
            //            'observations.*.issuedTime' => ['required_with:observations', 'date_format:H:i'],
            //            'observations.*.effectiveDate' => ['nullable', 'date', 'before_or_equal:now'],
            //            'observations.*.effectiveTime' => ['nullable', 'date_format:H:i'],
            //
            //            'diagnosticReports' => ['nullable', 'array'],
            //            'diagnosticReports.*.category.*.coding.*.code' => [
            //                'required_with:diagnosticReports',
            //                'string',
            //                new InDictionary('eHealth/diagnostic_report_categories')
            //            ],
            //            'diagnosticReports.*.resultsInterpreter.text' => ['required_with:diagnosticReports', 'string', 'max:255'],
            //            'diagnosticReports.*.issued' => ['required_with:diagnosticReports', 'date', 'before_or_equal:now'],
            //            'diagnosticReports.*.effectivePeriod.start' => [
            //                'required_with:diagnosticReports',
            //                'date',
            //                'before_or_equal:now'
            //            ],
            //            'diagnosticReports.*.effectivePeriod.end' => [
            //                'required_with:diagnosticReports',
            //                'date',
            //                'after:diagnosticReports.*.effectivePeriod.start'
            //            ],
            //
            //            'procedures' => ['nullable', 'array'],
            //            'procedures.*.code.identifier.value' => ['required_with:procedures', 'uuid', 'max:255'],
            //            'procedures.*.category.coding.*.code' => [
            //                'required_with:procedures',
            //                'string',
            //                new InDictionary('eHealth/procedure_categories')
            //            ],
            //            'procedures.*.performedPeriod.start' => ['required_with:procedures', 'date', 'before_or_equal:now'],
            //            'procedures.*.performedPeriod.end' => [
            //                'required_with:procedures',
            //                'date',
            //                'before_or_equal:now',
            //                'after:procedures.*.performedPeriod.start'
            //            ],
            //
            //            'clinicalImpressions' => ['nullable', 'array'],
            //            'clinicalImpressions.*.code.coding.*.code' => [
            //                'required_with:clinicalImpressions',
            //                'string',
            //                'max:255',
            //                new InDictionary('eHealth/clinical_impression_patient_categories')
            //            ],
            //            'clinicalImpressions.*.description' => ['nullable', 'string', 'max:1000'],
            //            'clinicalImpressions.*.effectivePeriod.start' => [
            //                'required_with:clinicalImpressions',
            //                'date',
            //                'before_or_equal:now'
            //            ],
            //            'clinicalImpressions.*.effectivePeriod.end' => [
            //                'required_with:clinicalImpressions',
            //                'date',
            //                'before_or_equal:now',
            //                'after:clinicalImpressions.*.effectivePeriod.start'
            //            ]
        ];

        $this->addAllowedEncounterClasses($rules);
        $this->addAllowedEncounterTypes($rules);
        $this->addAllowedEpisodeCareManagerEmployeeTypes($rules);

        return $rules;
    }

    /**
     * @return array
     */
    protected function messages(): array
    {
        return [
            'encounter.divisionId.prohibited' => __('validation.custom.encounter.divisionId.prohibited')
        ];
    }

    /**
     * Add allowed values for episode type code.
     *
     * @param  array  $rules
     * @return void
     */
    private function addAllowedEpisodeCareManagerEmployeeTypes(array &$rules): void
    {
        $allowedValues = $this->getAllowedValues(
            'ehealth.legal_entity_episode_types',
            'ehealth.employee_episode_types'
        );
        $this->addAllowedRule($rules, 'episode.typeCode', $allowedValues);
    }

    /**
     * Add allowed values for encounter classes.
     *
     * @param  array  $rules
     * @return void
     */
    private function addAllowedEncounterClasses(array &$rules): void
    {
        $allowedValues = $this->getAllowedValues(
            'ehealth.legal_entity_encounter_classes',
            'ehealth.employee_encounter_classes'
        );
        $this->addAllowedRule($rules, 'encounter.classCode', $allowedValues);
    }

    /**
     * Add allowed values for encounter types.
     *
     * @param  array  $rules
     * @return void
     */
    private function addAllowedEncounterTypes(array &$rules): void
    {
        $allowedValues = config('ehealth.encounter_class_encounter_types')[key(
            $this->component->dictionaries['eHealth/encounter_classes']
        )];
        $this->addAllowedRule($rules, 'encounter.typeCode', $allowedValues);
    }

    /**
     * Get allowed values by config keys.
     *
     * @param  string  $configKey
     * @param  string|null  $additionalConfigKey
     * @return array
     */
    private function getAllowedValues(string $configKey, ?string $additionalConfigKey = null): array
    {
        $allowedValues = config($configKey);

        if ($additionalConfigKey) {
            $additionalValues = config($additionalConfigKey);
            $allowedValues = array_intersect(
                $allowedValues[legalEntity()->type->name],
                $additionalValues[Auth::user()?->getEncounterWriterEmployee()->employeeType]
            );
        }

        return $allowedValues;
    }

    /**
     * Add 'in' rule by key and with allowed values.
     *
     * @param  array  $rules
     * @param  string  $ruleKey
     * @param  array  $allowedValues
     * @return void
     */
    private function addAllowedRule(array &$rules, string $ruleKey, array $allowedValues): void
    {
        $rules[$ruleKey][] = 'in:' . implode(',', $allowedValues);
    }

    /**
     * Required if vaccinationProtocols.authority.coding.*.code === MoH
     *
     * @return RequiredIf
     */
    private function requiredIfHasMoHAuthority(): RequiredIf
    {
        return Rule::requiredIf(function () {
            return collect($this->immunizations)
                ->flatMap(static fn (array $immunization) => $immunization['vaccinationProtocols'])
                ->contains(static fn (array $protocol) => $protocol['authorityCode'] === 'MoH');
        });
    }
}
