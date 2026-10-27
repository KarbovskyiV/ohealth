@if ($showEncounterEPrescriptionDrawer)
    <div
        x-data="{
            open: $wire.entangle('showEncounterEPrescriptionDrawer').live,
        }"
    >
        <x-dialog-drawer
            x-model="open"
            maxWidth="4/5"
            overlayWidth="100%"
            :stopClickPropagation="true"
            zIndex="46"
            onCloseClick="$wire.closeEncounterEPrescriptionDrawer()"
        >
            <x-slot name="title">
                {{ __('encounters.eprescription.title') }}
            </x-slot>
            <p class="mb-6 text-sm text-gray-600 dark:text-gray-300">
                {{ __('encounters.eprescription.encounter_number', ['number' => $encounterUuid ?? $encounterId]) }}
            </p>

            <form
                wire:submit.prevent="validateEncounterEPrescription"
                class="space-y-6"
            >
                <fieldset class="fieldset-card p-6 sm:p-8">
                    <legend class="legend">{{ __('care-plan.medication') }}</legend>
                    <div class="form-row-2 mt-6">
                        <div class="form-group group md:col-span-2">
                            <label
                                for="encounterEPrescriptionProgram"
                                class="label"
                            >
                                {{ __('device-requests.medical_program') }} *
                            </label>
                            <select
                                id="encounterEPrescriptionProgram"
                                class="input-select peer w-full"
                                wire:model.live="encounterEPrescriptionForm.program_id"
                            >
                                <option value="">{{ __('encounters.eprescription.select_program') }}</option>
                                @foreach ($encounterEPrescriptionPrograms as $program)
                                    <option value="{{ $program['id'] }}">
                                        {{ $program['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error ('encounterEPrescriptionForm.program_id')
                                <p class="text-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="form-group group md:col-span-2">
                            <label
                                for="encounterEPrescriptionSearch"
                                class="label"
                            >
                                {{ __('encounters.eprescription.medication_search') }} *
                            </label>
                            <div class="flex items-center gap-4">
                                <input
                                    type="text"
                                    id="encounterEPrescriptionSearch"
                                    class="input peer w-full"
                                    placeholder="{{ __('encounters.eprescription.medication_search_placeholder') }}"
                                    wire:model="encounterEPrescriptionSearchQuery"
                                    wire:keydown.enter.prevent="searchEncounterEPrescriptionMedications"
                                />
                                <button
                                    type="button"
                                    class="button-primary shrink-0"
                                    wire:click="searchEncounterEPrescriptionMedications"
                                    @disabled (($encounterEPrescriptionForm['program_id'] ?? '') === '')
                                >
                                    {{ __('forms.search') }}
                                </button>
                            </div>
                            @if (($encounterEPrescriptionForm['program_id'] ?? '') === '')
                                <p class="mt-1 text-xs text-gray-500">{{ __('encounters.eprescription.program_required') }}</p>
                            @endif
                        </div>
                        @if ($encounterEPrescriptionSearchResults !== [])
                            <div
                                class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3 md:col-span-2 dark:border-gray-600 dark:bg-gray-900/40"
                            >
                                @foreach ($encounterEPrescriptionSearchResults as $drug)
                                    <button
                                        type="button"
                                        wire:click="selectEncounterEPrescriptionMedication('{{ $drug['id'] }}')"
                                        class="w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-left text-sm transition-colors hover:border-blue-300 hover:bg-blue-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:border-blue-400 dark:hover:bg-gray-700"
                                    >
                                        <div
                                            class="font-medium text-gray-900 dark:text-gray-100"
                                        >
                                            {{ $drug['name'] ?: __('care-plan.medication') }}
                                        </div>
                                        <div
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            {{ __('encounters.eprescription.innm') }}: {{ $drug['innm_name'] ?: '-' }} ·
                                            {{ __('encounters.eprescription.dosage_form') }}: {{ $drug['innm_dosage_form'] ?: '-' }}
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        <div class="form-group group md:col-span-2">
                            <label
                                for="encounterEPrescriptionMedication"
                                class="label"
                            >
                                {{ __('encounters.eprescription.selected_medication') }}
                            </label>
                            <input
                                type="text"
                                id="encounterEPrescriptionMedication"
                                class="input peer w-full"
                                value="{{ $encounterEPrescriptionSelectedMedication['name'] ?? '' }}"
                                placeholder="{{ __('encounters.eprescription.medication_not_selected') }}"
                                readonly
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                UUID: {{ $encounterEPrescriptionForm['medication_id'] ?? '-' }}
                            </p>
                            @error ('encounterEPrescriptionForm.medication_id')
                                <p class="text-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="form-group group">
                            <label
                                for="encounterEPrescriptionCategory"
                                class="label"
                                >{{ __('forms.category') }}</label
                            >
                            <select
                                id="encounterEPrescriptionCategory"
                                class="input-select peer w-full"
                                wire:model="encounterEPrescriptionForm.category"
                            >
                                <option value="community">{{ __('encounters.eprescription.community') }}</option>
                            </select>
                        </div>
                        <div class="form-group group">
                            <label
                                for="encounterEPrescriptionQuantity"
                                class="label"
                                >{{ __('care-plan.quantity') }} *</label
                            >
                            <input
                                type="number"
                                id="encounterEPrescriptionQuantity"
                                min="0.01"
                                step="any"
                                class="input peer w-full"
                                wire:model="encounterEPrescriptionForm.medication_qty"
                            />
                            @if (!empty($encounterEPrescriptionSelectedMedication))
                                <p class="mt-1 text-xs text-gray-500">{{ __('encounters.eprescription.quantity_packaging_hint') }}</p>
                            @endif
                        </div>
                        <div class="form-group group">
                            <label
                                for="encounterEPrescriptionUnit"
                                class="label"
                                >{{ __('encounters.eprescription.unit') }}</label
                            >
                            <input
                                type="text"
                                id="encounterEPrescriptionUnit"
                                class="input peer w-full"
                                wire:model="encounterEPrescriptionForm.medication_unit"
                            />
                        </div>
                    </div>
                </fieldset>

                <fieldset class="fieldset-card p-6 sm:p-8">
                    <legend class="legend">{{ __('encounters.eprescription.dosage') }}</legend>
                    <div class="form-row-2 mt-6">
                        <div class="form-group group md:col-span-2">
                            <label
                                for="encounterEPrescriptionSignature"
                                class="label"
                                >{{ __('medication-requests.signatura') }} *</label
                            >
                            <input
                                type="text"
                                id="encounterEPrescriptionSignature"
                                class="input peer w-full"
                                wire:model="encounterEPrescriptionForm.signature_text"
                            />
                            @error ('encounterEPrescriptionForm.signature_text')
                                <p class="text-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="form-group group">
                            <label
                                for="encounterEPrescriptionMaxDosePerAdministration"
                                class="label"
                            >
                                {{ __('encounters.eprescription.max_dose_per_administration') }} *
                            </label>
                            <input
                                type="number"
                                id="encounterEPrescriptionMaxDosePerAdministration"
                                min="0.01"
                                step="any"
                                class="input peer w-full"
                                wire:model="encounterEPrescriptionForm.max_dose_per_administration"
                            />
                        </div>
                        <div class="form-group group">
                            <label
                                for="encounterEPrescriptionMaxDosePerPeriod"
                                class="label"
                            >
                                {{ __('encounters.eprescription.max_dose_per_period') }} *
                            </label>
                            <input
                                type="number"
                                id="encounterEPrescriptionMaxDosePerPeriod"
                                min="0.01"
                                step="any"
                                class="input peer w-full"
                                wire:model="encounterEPrescriptionForm.max_dose_per_period"
                            />
                        </div>
                        <div class="form-group group">
                            <div class="datepicker-wrapper">
                                <input
                                    type="date"
                                    id="encounterEPrescriptionStartedAt"
                                    class="input peer w-full"
                                    placeholder=" "
                                    autocomplete="off"
                                    wire:model="encounterEPrescriptionForm.started_at"
                                />
                                <label
                                    for="encounterEPrescriptionStartedAt"
                                    class="wrapped-label"
                                    >{{ __('encounters.eprescription.course_start') }}</label
                                >
                            </div>
                            @error ('encounterEPrescriptionForm.started_at')
                                <p class="text-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="form-group group">
                            <div class="datepicker-wrapper">
                                <input
                                    type="date"
                                    id="encounterEPrescriptionEndedAt"
                                    class="input peer w-full"
                                    placeholder=" "
                                    autocomplete="off"
                                    wire:model="encounterEPrescriptionForm.ended_at"
                                />
                                <label
                                    for="encounterEPrescriptionEndedAt"
                                    class="wrapped-label"
                                    >{{ __('encounters.eprescription.course_end') }}</label
                                >
                            </div>
                            @error ('encounterEPrescriptionForm.ended_at')
                                <p class="text-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </fieldset>

                <fieldset class="fieldset-card p-6 sm:p-8">
                    <legend class="legend">{{ __('forms.authentication') }}</legend>
                    <div class="form-group group">
                        <label
                            for="encounterEPrescriptionInformWith"
                            class="label"
                        >
                            {{ __('encounters.eprescription.patient_authentication') }} *
                        </label>
                        <select
                            id="encounterEPrescriptionInformWith"
                            class="input-select peer w-full"
                            wire:model="encounterEPrescriptionForm.inform_with"
                        >
                            <option value="">{{ __('encounters.eprescription.select_authentication') }}</option>
                            @foreach ($encounterEPrescriptionAuthMethods as $method)
                                <option
                                    value="{{ $method['value'] ?? $method['uuid'] }}"
                                >
                                    {{ $method['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @error ('encounterEPrescriptionForm.inform_with')
                            <p class="text-error">{{ $message }}</p>
                        @enderror
                    </div>
                </fieldset>

                @if ($encounterEPrescriptionWarningMessage !== '')
                    <div
                        class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800"
                        role="alert"
                    >
                        {{ $encounterEPrescriptionWarningMessage }}
                    </div>
                @endif

                <div class="mt-8 flex w-full justify-start gap-4">
                    <button
                        type="button"
                        class="button-minor"
                        wire:click="closeEncounterEPrescriptionDrawer"
                    >
                        {{ __('forms.cancel') }}
                    </button>
                    <button type="submit" class="button-primary">
                        {{ __('forms.create_and_sign') }}
                    </button>
                </div>
            </form>
        </x-dialog-drawer>
    </div>
@endif
