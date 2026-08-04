@php
    $diagnosticReportErrorPath = $diagnosticReportErrorPath
        ?? (($context ?? null) === 'diagnostic-report'
            ? 'form.diagnosticReport'
            : 'form.diagnosticReports.*');
@endphp
<fieldset class="fieldset">
    <legend class="legend">
        {{ __('forms.additional_info') }}
    </legend>

    @if($isEncounterContext ?? false)
        {{-- Information source (doctor or patient) --}}
        <div class="flex gap-20 mb-8">
            <h2 class="default-p font-bold">{{ __('patients.information_source') }}</h2>
            {{-- Doctor --}}
            <div class="flex items-center">
                <input
                    x-model.boolean="modalDiagnosticReport.primarySource"
                    id="performer"
                    type="radio"
                    value="true"
                    name="primarySource"
                    class="default-radio"
                    :checked="modalDiagnosticReport.primarySource === true"
                >
                <label for="performer" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                    {{ __('patients.performer') }}
                </label>
            </div>

            {{-- Patient --}}
            <div class="flex items-center">
                <input
                    x-model.boolean="modalDiagnosticReport.primarySource"
                    id="patient"
                    type="radio"
                    value="false"
                    name="primarySource"
                    class="default-radio"
                    :checked="modalDiagnosticReport.primarySource === false"
                >
                <label for="patient" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                    {{ __('forms.patient') }}
                </label>
            </div>
        </div>

        {{-- When patient selected --}}
        <div x-show="modalDiagnosticReport.primarySource === false" x-transition>
            <div class="form-row-3">
                <div>
                    <label for="reportOrigin" class="label-modal">
                        {{ __('patients.source_link') }}
                    </label>
                    <select
                        x-model="modalDiagnosticReport.reportOriginCode"
                        class="input-select peer"
                        id="reportOrigin"
                        type="text"
                        required
                    >
                        <option value="" selected>{{ __('forms.select') }}</option>
                        @foreach($this->dictionaries['eHealth/report_origins'] as $key => $reportOrigin)
                            <option value="{{ $key }}">{{ $reportOrigin }}</option>
                        @endforeach
                    </select>

                    <p
                        class="text-error text-xs"
                        x-show="!Object.keys($wire.dictionaries['eHealth/report_origins']).includes(modalDiagnosticReport.reportOriginCode)"
                    >
                        {{ __('forms.field_empty') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if($context === 'diagnostic-report')
        <div class="form-row-2">
            <div class="form-group group">
                <select
                    x-model="modalDiagnosticReport.divisionId"
                    @change="
                        modalDiagnosticReport.performerEmployeeId = '';
                        modalDiagnosticReport.usedReferences = [];
                    "
                    @if($isEncounterContext ?? false)
                        x-effect="
                            const encounterDivisionId =
                                $wire.form.encounter.divisionId || '';

                            if (
                                modalDiagnosticReport.divisionId
                                    !== encounterDivisionId
                            ) {
                                modalDiagnosticReport.divisionId =
                                    encounterDivisionId;

                                modalDiagnosticReport.performerEmployeeId = '';
                                modalDiagnosticReport.usedReferences = [];
                            }
                        "
                        disabled
                    @elseif(count($divisions) === 1)
                        {{-- Set division by default if only one exists --}}
                        x-init="
                            modalDiagnosticReport.divisionId =
                                '{{ $divisions[0]['uuid'] }}';
                        "
                    @endif
                    id="divisionNames"
                    class="input-select peer"
                    type="text"
                >
                    <option value="" selected>
                        {{ __('forms.select') }} {{ mb_strtolower(__('forms.division_name')) }}
                    </option>
                    @foreach($divisions as $key => $division)
                        <option value="{{ $division['uuid'] }}">{{ $division['name'] }}</option>
                    @endforeach
                </select>

                @error($diagnosticReportErrorPath . '.divisionId')
                <p class="text-error">{{ $message }}</p>
                @enderror

                @if($isEncounterContext ?? false)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('patients.division_filled_from_encounter') }}
                    </p>
                @endif
            </div>
        </div>
    @endif

    {{-- Performer --}}
    <div
        class="form-row-2"
        x-show="modalDiagnosticReport.primarySource === true"
        x-cloak
    >
        <div class="form-group group">
            <select
                x-model="modalDiagnosticReport.performerEmployeeId"
                id="diagnosticReportPerformer"
                class="input-select peer"
                :required="modalDiagnosticReport.primarySource === true"
            >
                <option value="">
                    {{ __('forms.select') }}
                    {{ mb_strtolower(__('patients.performer')) }}
                    *
                </option>

                <template
                     x-for="
                        employee in diagnosticReportEmployees.filter(
                            employee =>
                                !modalDiagnosticReport.divisionId
                                || employee.divisionUuid
                                    === modalDiagnosticReport.divisionId
                        )
                    "
                    :key="employee.uuid"
                >
                    <option
                        :value="employee.uuid"
                        :selected="
                            String(modalDiagnosticReport.performerEmployeeId)
                                === String(employee.uuid)
                        "
                        x-text="
                            `${employee.name} — ${
                                $wire.dictionaries['POSITION'][employee.position]
                                ?? employee.position
                            }`
                        "
                    ></option>
                </template>
            </select>

            @error($diagnosticReportErrorPath . '.performerEmployeeId')
                <p class="text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Result interpreter --}}
    <div class="form-row-2">
        <div class="form-group group">
            <select
                x-model="modalDiagnosticReport.resultsInterpreterEmployeeId"
                id="resultsInterpreter"
                class="input-select peer"
                type="text"
                :required="['diagnostic_procedure', 'imaging'].includes(modalDiagnosticReport.categoryCode)"
            >
                <option value="" selected>
                    {{ __('forms.select') }} {{ mb_strtolower(__('patients.the_doctor_who_interpreted_the_results')) }}
                </option>
                <template
                    x-for="
                        employee in diagnosticReportEmployees.filter(
                            employee => [
                                'DOCTOR',
                                'SPECIALIST'
                            ].includes(employee.employeeType)
                        )
                    "
                    :key="employee.uuid"
                >
                    <option
                        :value="employee.uuid"
                        :selected="
                            String(modalDiagnosticReport.resultsInterpreterEmployeeId)
                                === String(employee.uuid)
                        "
                        x-text="
                            `${employee.name} — ${
                                $wire.dictionaries['POSITION'][
                                    employee.position
                                ] ?? employee.position
                            }`
                        "
                    ></option>
                </template>
            </select>

            @error($diagnosticReportErrorPath . '.resultsInterpreterEmployeeId')
            <p class="text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Issued datetime --}}
    <div class="form-row-3">
        <div class="form-group group">
            <div class="datepicker-wrapper">
                <input
                    x-model="modalDiagnosticReport.issuedDate"
                    @input="validateIssuedDateTime()"
                    datepicker-max-date="{{ now()->format(config('app.date_format')) }}"
                    type="text"
                    name="issuedDate"
                    id="issuedDate"
                    class="datepicker-input with-leading-icon input peer"
                    placeholder=" "
                    required
                    autocomplete="off"
                >
                <label for="issuedDate" class="wrapped-label">
                    {{ __('patients.date_time_entered') }}
                </label>

                @error($diagnosticReportErrorPath . '.issuedDate')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="form-group group !w-1/2" onclick="document.getElementById('issuedTime').showPicker()">
            <div class="relative flex items-center">
                @icon('mingcute-time-fill', 'svg-input left-2.5')
                <input
                    x-model="modalDiagnosticReport.issuedTime"
                    @input="$event.target.blur(); validateIssuedDateTime()"
                    :min="encounterPeriodStart"
                    :max="encounterPeriodEnd"
                    datepicker-max-date="{{ now()->format(config('app.date_format')) }}"
                    type="time"
                    name="issuedTime"
                    id="issuedTime"
                    class="input peer !pl-10"
                    autocomplete="off"
                    required
                >
            </div>

            @error($diagnosticReportErrorPath . '.issuedTime')
            <p class="text-error">{{ $message }}</p>
            @enderror
            <p x-show="issuedDateTimeInvalid" x-cloak class="text-error">
                {{ __('patients.diagnostic_report_issued_outside_encounter_period') }}
            </p>
        </div>
    </div>

    {{-- Effective type --}}
    <div class="form-row-2">
        <div class="form-group group">
            <select
                x-model="modalDiagnosticReport.effectiveType"
                id="diagnosticReportEffectiveType"
                class="input-select peer"
                @change="setEffectiveType($event.target.value)"
            >
                <option value="">
                    {{ __('patients.do_not_specify') }}
                </option>

                <option value="date_time">
                    {{ __('patients.effective_date_time') }}
                </option>

                <option value="period">
                    {{ __('patients.effective_period') }}
                </option>
            </select>

            @error($diagnosticReportErrorPath . '.effectiveType')
                <p class="text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Effective date and time --}}
    <div
        class="form-row-3"
        x-show="
            modalDiagnosticReport.effectiveType
            === 'date_time'
        "
        x-cloak
    >
        <div class="form-group group">
            <div class="datepicker-wrapper">
                <input
                    x-model="modalDiagnosticReport.effectiveDate"
                    datepicker-max-date="{{
                        now()->format(config('app.date_format'))
                    }}"
                    type="text"
                    name="effectiveDate"
                    id="diagnosticReportEffectiveDate"
                    class="datepicker-input with-leading-icon input peer"
                    placeholder=" "
                    autocomplete="off"
                    :required="
                        modalDiagnosticReport.effectiveType
                        === 'date_time'
                    "
                >

                <label
                    for="diagnosticReportEffectiveDate"
                    class="wrapped-label"
                >
                    {{ __('patients.effective_date_time') }}
                </label>

                @error($diagnosticReportErrorPath . '.effectiveDate')
                    <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div
            class="form-group group !w-1/2"
            onclick="
                document
                    .getElementById(
                        'diagnosticReportEffectiveTime'
                    )
                    .showPicker()
            "
        >
            <div class="relative flex items-center">
                @icon(
                    'mingcute-time-fill',
                    'svg-input left-2.5'
                )

                <input
                    x-model="modalDiagnosticReport.effectiveTime"
                    @input="$event.target.blur()"
                    type="time"
                    name="effectiveTime"
                    id="diagnosticReportEffectiveTime"
                    class="input peer !pl-10"
                    autocomplete="off"
                    :required="
                        modalDiagnosticReport.effectiveType
                        === 'date_time'
                    "
                >
            </div>

            @error($diagnosticReportErrorPath . '.effectiveTime')
                <p class="text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    @unless($isEncounterContext ?? false)
    {{-- Effective period --}}
    <div
        x-show="
            modalDiagnosticReport.effectiveType
            === 'period'
        "
        x-cloak
    >
        <div class="form-row-3">
            <div class="form-group group">
                <div class="datepicker-wrapper">
                    <input
                        x-model="
                            modalDiagnosticReport
                                .effectivePeriodStartDate
                        "
                        datepicker-max-date="{{
                            now()->format(config('app.date_format'))
                        }}"
                        type="text"
                        name="effectivePeriodStartDate"
                        id="effectivePeriodStartDate"
                        class="datepicker-input with-leading-icon input peer"
                        placeholder=" "
                        autocomplete="off"
                        :required="
                            modalDiagnosticReport.effectiveType
                            === 'period'
                        "
                    >

                    <label
                        for="effectivePeriodStartDate"
                        class="wrapped-label"
                    >
                        {{ __('patients.effective_period_start') }}
                    </label>

                    @error(
                        $diagnosticReportErrorPath
                        . '.effectivePeriodStartDate'
                    )
                        <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div
                class="form-group group !w-1/2"
                onclick="
                    document
                        .getElementById(
                            'effectivePeriodStartTime'
                        )
                        .showPicker()
                "
            >
                <div class="relative flex items-center">
                    @icon(
                        'mingcute-time-fill',
                        'svg-input left-2.5'
                    )

                    <input
                        x-model="
                            modalDiagnosticReport
                                .effectivePeriodStartTime
                        "
                        @input="$event.target.blur()"
                        type="time"
                        name="effectivePeriodStartTime"
                        id="effectivePeriodStartTime"
                        class="input peer !pl-10"
                        autocomplete="off"
                        :required="
                            modalDiagnosticReport.effectiveType
                            === 'period'
                        "
                    >
                </div>

                @error(
                    $diagnosticReportErrorPath
                    . '.effectivePeriodStartTime'
                )
                    <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="form-row-3">
            <div class="form-group group">
                <div class="datepicker-wrapper">
                    <input
                        x-model="
                            modalDiagnosticReport
                                .effectivePeriodEndDate
                        "
                        datepicker-max-date="{{
                            now()->format(config('app.date_format'))
                        }}"
                        type="text"
                        name="effectivePeriodEndDate"
                        id="effectivePeriodEndDate"
                        class="datepicker-input with-leading-icon input peer"
                        placeholder=" "
                        autocomplete="off"
                        :required="
                            Boolean(
                                modalDiagnosticReport
                                    .effectivePeriodEndTime
                            )
                        "
                    >

                    <label
                        for="effectivePeriodEndDate"
                        class="wrapped-label"
                    >
                        {{ __('patients.effective_period_end') }}
                    </label>

                    @error(
                        $diagnosticReportErrorPath
                        . '.effectivePeriodEndDate'
                    )
                        <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div
                class="form-group group !w-1/2"
                onclick="
                    document
                        .getElementById(
                            'effectivePeriodEndTime'
                        )
                        .showPicker()
                "
            >
                <div class="relative flex items-center">
                    @icon(
                        'mingcute-time-fill',
                        'svg-input left-2.5'
                    )

                    <input
                        x-model="
                            modalDiagnosticReport
                                .effectivePeriodEndTime
                        "
                        @input="$event.target.blur()"
                        type="time"
                        name="effectivePeriodEndTime"
                        id="effectivePeriodEndTime"
                        class="input peer !pl-10"
                        autocomplete="off"
                        :required="
                            Boolean(
                                modalDiagnosticReport
                                    .effectivePeriodEndDate
                            )
                        "
                    >
                </div>

                @error(
                    $diagnosticReportErrorPath
                    . '.effectivePeriodEndTime'
                )
                    <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
    @endunless

    {{-- Used references / Equipment --}}
    @if($context === 'diagnostic-report')
        <div class="form-row-2">
            <div class="w-full max-w-107.5">
                <p class="label-modal mb-2 block text-sm">
                    {{ __('equipments.label') }}
                </p>

                <div class="space-y-4">
                    <template
                        x-for="
                            (usedReference, index)
                            in modalDiagnosticReport.usedReferences
                        "
                        :key="index"
                    >
                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <template
                                    x-if="!modalDiagnosticReport.divisionId"
                                >
                                    <div class="form-group group">
                                        <input
                                            type="text"
                                            class="input peer"
                                            placeholder=" "
                                            disabled
                                        >

                                        <label class="label">
                                            {{ __('equipments.search') }}
                                        </label>
                                    </div>
                                </template>

                                @foreach(
                                    $equipmentOptionsByDivision
                                    as $divisionUuid => $options
                                )
                                    <div
                                        x-show="
                                            modalDiagnosticReport.divisionId
                                                === @js($divisionUuid)
                                        "
                                        x-cloak
                                    >
                                        <x-forms.combobox
                                            class="w-full"
                                            model="usedReference"
                                            modelKey="id"
                                            :options="$options"
                                            bindValue="uuid"
                                            bindParam="name"
                                            :label="__('equipments.search')"
                                        />
                                    </div>
                                @endforeach

                                <template
                                    x-if="
                                        modalDiagnosticReport.divisionId
                                        && !Object.keys(
                                            @js($equipmentOptionsByDivision)
                                        ).includes(
                                            modalDiagnosticReport.divisionId
                                        )
                                    "
                                >
                                    <p class="text-xs text-gray-500 mt-1">
                                        Немає доступного обладнання для
                                        обраного місця надання послуг
                                    </p>
                                </template>
                            </div>

                            <button
                                type="button"
                                @click.prevent="
                                    removeUsedReference(index)
                                "
                                class="
                                    shrink-0 text-error
                                    hover:opacity-80
                                "
                            >
                                @icon('delete', 'w-5 h-5')
                            </button>
                        </div>
                    </template>
                </div>

                @error($diagnosticReportErrorPath . '.usedReferences.*.id')
                <p class="text-error mt-2">{{ $message }}</p>
                @enderror

                <button type="button" @click.prevent="addUsedReference()" class="item-add mt-4">
                    {{ __('equipments.add') }}
                </button>
            </div>
        </div>
    @endif
</fieldset>
