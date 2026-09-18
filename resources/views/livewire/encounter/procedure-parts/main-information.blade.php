@php
    use App\Enums\Person\ProcedureStatus;

    $procedureErrorPath = $context === 'encounter' ? 'procedureForm.procedures.*' : 'form.procedure';
@endphp

<fieldset class="fieldset">
    <legend class="legend">{{ __('forms.main_information') }}</legend>

    <div>
        {{-- Is referral available, show only in encounter. For single procedure referral is neccessary. --}}
        @if ($context === 'encounter')
            <div class="form-row-2">
                <div class="form-group group">
                    <input
                        x-model="modalProcedure.isReferralAvailable"
                        @click="modalProcedure.isReferralAvailable = ! modalProcedure.isReferralAvailable"
                        type="checkbox"
                        name="isDiagnosticReferralAvailable"
                        id="procedureReferralAvailable"
                        class="default-checkbox mb-1"
                        tabindex="-1"
                    />
                    <label class="default-p" for="procedureReferralAvailable">
                        {{ __('medical-events.referral.available') }}
                    </label>
                </div>
            </div>
        @endif

        {{-- When referral available --}}
        <template x-if="modalProcedure.isReferralAvailable">
            <div class="form-group group">
                <div class="form-row-2" x-cloak>
                    <div>
                        <label
                            for="procedureReferralType"
                            class="sr-only"
                        >{{ __('medical-events.referral.requisition_type') }}</label>
                        <select
                            x-model="modalProcedure.referralType"
                            id="procedureReferralType"
                            class="input-select peer"
                            type="text"
                            required
                        >
                            <option selected value="">
                                {{ __('forms.select') }} {{ mb_strtolower(__('medical-events.referral.requisition_type')) }} *
                            </option>
                            <option value="electronic">{{ __('medical-events.referral.electronic') }}</option>
                            <option value="paper">{{ __('medical-events.referral.paper') }}</option>
                        </select>

                        @error('procedureForm.procedures.*.referralType')
                            <p class="text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Electronic referral --}}
                    <template x-if="modalProcedure.referralType === 'electronic'" x-transition>
                        <div class="form-group group">
                            <input
                                x-model="modalProcedure.basedOnIdentifier"
                                type="text"
                                name="basedOnIdentifier"
                                id="procedureBasedOnIdentifier"
                                class="input-select peer"
                                placeholder=" "
                                required
                                autocomplete="off"
                            />
                            <label for="procedureBasedOnIdentifier" class="label">
                                {{ __('medical-events.referral.number') }}
                            </label>
                        </div>
                    </template>
                </div>

                {{-- Paper referral --}}
                <template x-if="modalProcedure.referralType === 'paper'" x-transition>
                    <div>
                        <div class="form-row-2">
                            <div class="form-group group">
                                <input
                                    x-model="modalProcedure.paperReferralRequisition"
                                    type="text"
                                    name="requisition"
                                    id="procedureRequisition"
                                    class="input peer"
                                    placeholder=" "
                                    autocomplete="off"
                                />
                                <label for="procedureRequisition" class="label">
                                    {{ __('medical-events.referral.number') }}
                                </label>

                                @error($procedureErrorPath . '.paperReferralRequisition')
                                    <p class="text-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-group group">
                                <input
                                    x-model="modalProcedure.paperReferralRequesterEmployeeName"
                                    type="text"
                                    name="requesterEmployeeName"
                                    id="procedureRequesterEmployeeName"
                                    class="input peer"
                                    placeholder=" "
                                    autocomplete="off"
                                />
                                <label for="procedureRequesterEmployeeName" class="label">
                                    {{ __('medical-events.referral.author') }}
                                </label>

                                @error($procedureErrorPath . '.paperReferralRequesterEmployeeName')
                                    <p class="text-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group group">
                                <input
                                    x-model="modalProcedure.paperReferralRequesterLegalEntityEdrpou"
                                    type="text"
                                    name="requesterLegalEntityEdrpou"
                                    id="procedureRequesterLegalEntityEdrpou"
                                    class="input peer"
                                    placeholder=" "
                                    autocomplete="off"
                                    maxlength="10"
                                    required
                                />
                                <label for="procedureRequesterLegalEntityEdrpou" class="label">
                                    {{ __('medical-events.referral.edrpou_of_the_issuing_institution') }}
                                </label>

                                @error($procedureErrorPath . '.paperReferralRequesterLegalEntityEdrpou')
                                    <p class="text-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-group group">
                                <input
                                    x-model="modalProcedure.paperReferralRequesterLegalEntityName"
                                    type="text"
                                    name="requesterLegalEntityName"
                                    id="procedureRequesterLegalEntityName"
                                    class="input peer"
                                    placeholder=" "
                                    autocomplete="off"
                                />
                                <label for="procedureRequesterLegalEntityName" class="label">
                                    {{ __('medical-events.referral.name_of_the_institution_that_issued_it') }}
                                </label>

                                @error($procedureErrorPath . '.paperReferralRequesterLegalEntityName')
                                    <p class="text-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row-modal">
                            <div class="form-group group">
                                <div class="datepicker-wrapper">
                                    <input
                                        x-model="modalProcedure.paperReferralServiceRequestDate"
                                        type="text"
                                        name="serviceRequestDate"
                                        id="procedureServiceRequestDate"
                                        class="datepicker-input with-leading-icon input peer"
                                        placeholder=" "
                                        required
                                        autocomplete="off"
                                    />
                                    <label for="procedureServiceRequestDate" class="wrapped-label">
                                        {{ __('medical-events.referral.date') }}
                                    </label>

                                    @error($procedureErrorPath . '.paperReferralServiceRequestDate')
                                        <p class="text-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group group">
                                <input
                                    x-model="modalProcedure.paperReferralNote"
                                    type="text"
                                    name="paperNote"
                                    id="paperNote"
                                    class="input peer"
                                    placeholder=" "
                                    autocomplete="off"
                                />
                                <label for="paperNote" class="label"> {{ __('medical-events.referral.notes') }} </label>

                                @error($procedureErrorPath . '.paperReferralNote')
                                    <p class="text-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        {{-- Category --}}
        <div class="form-row-2">
            <div class="form-group group">
                <label for="category" class="sr-only">{{ __('procedures.category') }}</label>
                <select
                    x-model="modalProcedure.categoryCode"
                    id="category"
                    class="input-select peer"
                    type="text"
                    required
                >
                    <option selected value="">
                        {{ __('forms.select') }} {{ mb_strtolower(__('procedures.category')) }} *
                    </option>
                    @foreach ($this->dictionaries['eHealth/procedure_categories'] as $key => $category)
                        <option value="{{ $key }}">{{ $category }}</option>
                    @endforeach
                </select>

                @error($procedureErrorPath . '.categoryCode')
                    <p class="text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group group">
                <label for="procedureStatus" class="sr-only">{{ __('forms.status.label') }}</label>
                <select
                    x-model="modalProcedure.status"
                    @change="
                        modalProcedure.status === 'completed'
                            ? setPerformedType(modalProcedure.performedType || 'period')
                            : setPerformedType('')
                    "
                    id="procedureStatus"
                    class="input-select peer"
                    required
                >
                    <option value="">{{ __('forms.select') }} {{ mb_strtolower(__('forms.status.label')) }} *</option>
                    <option value="completed">{{ __('procedures.status.completed') }}</option>

                    @if (in_array(($context ?? null), ['encounter', 'procedure'], true))
                        <option value="{{ ProcedureStatus::NOT_DONE->value }}">
                            {{ __('procedures.status.not_done') }}
                        </option>
                    @endif

                    @if (data_get($this->form, 'procedure.status') === ProcedureStatus::ENTERED_IN_ERROR->value)
                        <option value="{{ ProcedureStatus::ENTERED_IN_ERROR->value }}">
                            {{ __('procedures.status.entered_in_error') }}
                        </option>
                    @endif
                </select>

                @error($procedureErrorPath . '.status')
                    <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Services --}}
        <div class="form-row-2 relative z-1">
            <div class="form-group group">
                <x-select2
                    modelPath="modalProcedure.codeValue"
                    dictionaryName="custom/services"
                    id="serviceCode"
                    class="input peer"
                />
                <label for="serviceCode" class="label">
                    {{ __('forms.select') }} {{ mb_strtolower(__('procedures.code')) }} *
                </label>

                @error($procedureErrorPath . '.codeValue')
                    <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Divisions --}}
        <div class="form-row-2">
            <div class="form-group group">
                <label for="procedureDivision" class="sr-only">{{ __('procedures.division') }}</label>
                <select
                    x-model="modalProcedure.divisionId"
                    @change="modalProcedure.usedReferences = []"
                    @if (count($divisions) === 1)
                        x-init="if (! modalProcedure.divisionId) modalProcedure.divisionId = '{{ $divisions[0]['uuid'] }}';"
                    @endif
                    id="procedureDivision"
                    class="input-select peer"
                >
                    <option selected value="">
                        {{ __('forms.select') }} {{ mb_strtolower(__('procedures.division')) }}
                    </option>
                    @foreach ($divisions as $key => $division)
                        <option value="{{ $division['uuid'] }}">{{ $division['name'] }}</option>
                    @endforeach
                </select>

                @error($procedureErrorPath . '.divisionId')
                    <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Outcome --}}
        <div class="form-row-modal">
            <div class="form-group group">
                <label for="outcome" class="sr-only">{{ __('procedures.outcome_result') }}</label>
                <select x-model="modalProcedure.outcomeCode" id="outcome" class="input-select peer" type="text">
                    <option selected value="">
                        {{ __('forms.select') }} {{ mb_strtolower(__('procedures.outcome_result')) }}
                    </option>
                    @foreach ($this->dictionaries['eHealth/procedure_outcomes'] as $key => $outcome)
                        <option value="{{ $key }}">{{ $outcome }}</option>
                    @endforeach
                </select>

                @error($procedureErrorPath . '.outcomeCode')
                    <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</fieldset>
