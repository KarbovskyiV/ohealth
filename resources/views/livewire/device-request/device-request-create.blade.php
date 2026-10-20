@php
    $carePlans = $deviceRequestCarePlans ?? [];
    $encounters = $deviceRequestEncounters ?? [];
    $programs = $deviceRequestPrograms ?? [];
    $deviceTypes = $deviceRequestDeviceTypes ?? [];
    $units = $deviceRequestUnits ?? [];
    $authenticationMethods = $deviceRequestAuthenticationMethods ?? [];
    $availableRecords = $deviceRequestAvailableRecords ?? [];
    $devices = $deviceRequestDevices ?? [];
@endphp

<div
    x-data="{
        deviceSearch: false,
        recordSearch: false,
        recordGroup: 'reason',
        searchQuery: '',
        selectedDeviceName: '',
        availableRecords: @js($availableRecords),
        requestForm: {
            createCarePlan: false, carePlanUuid: '', encounterId: '', programId: '',
            deviceMode: 'type', deviceId: '', canFix: '', patientHeight: '',
            quantity: '', unit: '', startDate: '', startTime: '', endDate: '', endTime: '',
            priority: 'routine', reason: [], supportingInfo: [], authenticationMethod: '',
        },
        isMedicalRecordAdded(recordId) {
            return this.requestForm[this.recordGroup].includes(recordId);
        },
    }"
    @device-request-medical-record-selected.window="
        if (!isMedicalRecordAdded($event.detail.record.uuid)) {
            requestForm[recordGroup].push($event.detail.record.uuid);
        }
        recordSearch = false;
    "
    x-init="
        $watch('requestForm.deviceMode', () => {
            requestForm.deviceId = '';
            selectedDeviceName = '';
        })
    "
>
    <form @submit.prevent class="w-full max-w-screen-xl space-y-8 pb-10">
        <fieldset class="fieldset-card p-6 sm:p-8">
            <legend class="legend">
                {{ __('device-requests.care_plan') }}
            </legend>
            <label
                class="flex items-center gap-3 text-sm font-medium text-gray-900 dark:text-white"
            >
                <input
                    type="checkbox"
                    x-model="requestForm.createCarePlan"
                    class="default-checkbox"
                />
                {{ __('device-requests.create_care_plan') }}
            </label>
            <template x-if="requestForm.createCarePlan">
                <div class="form-group mt-6 max-w-md">
                    <select
                        id="deviceRequestCarePlan"
                        x-model="requestForm.carePlanUuid"
                        class="input-select peer"
                    >
                        <option value="">
                            {{ __('device-requests.choose_care_plan') }}
                        </option>
                        @foreach ($carePlans as $uuid => $title)
                            <option value="{{ $uuid }}">{{ $title }}</option>
                        @endforeach
                    </select>
                    <label
                        for="deviceRequestCarePlan"
                        class="label"
                        >{{ __('device-requests.care_plan') }}</label
                    >
                </div>
            </template>
        </fieldset>

        <fieldset class="fieldset-card p-6 sm:p-8">
            <legend class="legend">
                {{ __('device-requests.encounter') }}
            </legend>
            <div class="form-group max-w-md">
                <select
                    id="deviceRequestEncounter"
                    x-model="requestForm.encounterId"
                    class="input-select peer"
                >
                    <option value="">{{ __('forms.select') }}</option>
                    @foreach ($encounters as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
                <label
                    for="deviceRequestEncounter"
                    class="label"
                    >{{ __('device-requests.encounter') }}</label
                >
            </div>
        </fieldset>

        <fieldset class="fieldset-card p-6 sm:p-8">
            <legend class="legend">{{ __('device-requests.program') }}</legend>
            <div class="form-group max-w-md">
                <select
                    id="deviceRequestProgram"
                    x-model="requestForm.programId"
                    class="input-select peer"
                >
                    <option value="">
                        {{ __('device-requests.without_program') }}
                    </option>
                    @foreach ($programs as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <label
                    for="deviceRequestProgram"
                    class="label"
                    >{{ __('device-requests.medical_program') }}</label
                >
            </div>
        </fieldset>

        <fieldset class="fieldset-card p-6 sm:p-8">
            <legend class="legend">{{ __('device-requests.device') }}</legend>
            <div class="mb-8 flex flex-wrap gap-x-8 gap-y-3">
                @foreach (['type' => 'choose_type', 'model' => 'choose_model'] as $mode => $label)
                    <label
                        class="flex items-center gap-2 text-sm font-medium text-gray-900 dark:text-white"
                    >
                        <input
                            type="radio"
                            x-model="requestForm.deviceMode"
                            value="{{ $mode }}"
                            class="default-radio"
                        />
                        {{ __('device-requests.'.$label) }}
                    </label>
                @endforeach
            </div>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-10"
            >
                <div class="form-group mb-0 max-w-md">
                    <template x-if="requestForm.deviceMode === 'type'">
                        <select
                            id="deviceRequestDevice"
                            x-model="requestForm.deviceId"
                            class="input-select peer"
                        >
                            <option value="">
                                {{ __('device-requests.choose_device') }}
                            </option>
                            @foreach ($deviceTypes as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select> </template
                    ><template x-if="requestForm.deviceMode === 'model'">
                        <button
                            id="deviceRequestDevice"
                            type="button"
                            @click="deviceSearch = true"
                            class="input-select peer text-left"
                        >
                            <span
                                x-text="selectedDeviceName || @js(__('device-requests.choose_device'))"
                            ></span>
                        </button>
                    </template>
                    <label for="deviceRequestDevice" class="label"
                        >{{ __('device-requests.device') }}*</label
                    >
                </div>
                <button
                    type="button"
                    @click="deviceSearch = true"
                    class="flex shrink-0 items-center gap-2 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400"
                >
                    <span
                        class="inline-flex h-4 w-4 shrink-0 [&>svg]:h-full [&>svg]:w-full"
                    >
                        @icon ('book-open')
                    </span>
                    <span
                        class="whitespace-nowrap"
                        >{{ __('device-requests.dictionary') }}</span
                    >
                </button>
            </div>
        </fieldset>

        <fieldset class="fieldset-card p-6 sm:p-8">
            <legend class="legend">
                {{ __('device-requests.characteristics') }}
            </legend>
            <div class="max-w-md space-y-6">
                <div class="form-group">
                    <select
                        id="deviceRequestCanFix"
                        x-model="requestForm.canFix"
                        class="input-select peer"
                    >
                        <option value="">{{ __('forms.select') }}</option>
                        <option value="true">{{ __('forms.yes') }}</option>
                        <option value="false">{{ __('forms.no') }}</option>
                    </select>
                    <label
                        for="deviceRequestCanFix"
                        class="label"
                        >{{ __('device-requests.mobility') }}</label
                    >
                </div>
                <div class="form-group">
                    <select
                        id="deviceRequestHeight"
                        x-model="requestForm.patientHeight"
                        class="input-select peer"
                    >
                        <option value="">{{ __('forms.select') }}</option>
                        @foreach (['under180' => __('device-requests.height_under180'), 'over180' => __('device-requests.height_over180')] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <label
                        for="deviceRequestHeight"
                        class="label"
                        >{{ __('device-requests.height') }}</label
                    >
                </div>
            </div>
        </fieldset>

        <fieldset class="fieldset-card p-6 sm:p-8">
            <legend class="legend">
                {{ __('device-requests.quantity_period') }}
            </legend>
            <div class="max-w-md space-y-6">
                <div class="flex gap-6">
                    <div class="form-group w-28">
                        <input
                            id="deviceRequestQuantity"
                            type="number"
                            min="1"
                            step="1"
                            x-model="requestForm.quantity"
                            class="input peer"
                            placeholder=" "
                        />
                        <label for="deviceRequestQuantity" class="label"
                            >{{ __('device-requests.quantity') }}*</label
                        >
                    </div>
                    <div class="form-group w-28">
                        <select
                            id="deviceRequestUnit"
                            x-model="requestForm.unit"
                            class="input-select peer"
                            aria-label="{{ __('device-requests.quantity') }}"
                        >
                            @foreach ($units as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @foreach (['start' => 'start_date', 'end' => 'end_date'] as $period => $label)
                    <div class="grid grid-cols-2 gap-6">
                        <div class="form-group datepicker-wrapper">
                            <input
                                id="deviceRequest{{ ucfirst($period) }}Date"
                                type="text"
                                x-model="requestForm.{{ $period }}Date"
                                class="datepicker-input with-leading-icon input peer"
                                datepicker-format="{{ frontendDateFormat() }}"
                                placeholder=" "
                                autocomplete="off"
                            />
                            <label
                                for="deviceRequest{{ ucfirst($period) }}Date"
                                class="wrapped-label whitespace-nowrap"
                                >{{ __('device-requests.'.$label) }}*</label
                            >
                        </div>
                        <div class="form-group">
                            <div
                                class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3"
                            >
                                @icon ('mingcute-time-fill', 'w-4 h-4 text-gray-400')
                            </div>
                            <input
                                type="text"
                                x-model="requestForm.{{ $period }}Time"
                                class="timepicker-uk with-leading-icon input peer"
                                autocomplete="off"
                                aria-label="{{ __('device-requests.'.$period.'_time') }}"
                            />
                        </div>
                    </div>
                @endforeach
                <div class="form-group">
                    <select
                        id="deviceRequestPriority"
                        x-model="requestForm.priority"
                        class="input-select peer"
                    >
                        @foreach (['routine', 'urgent'] as $priority)
                            <option value="{{ $priority }}">
                                {{ __('device-requests.'.$priority) }}
                            </option>
                        @endforeach
                    </select>
                    <label
                        for="deviceRequestPriority"
                        class="label"
                        >{{ __('device-requests.priority') }}</label
                    >
                </div>
            </div>
        </fieldset>

        @foreach (['reason' => 'reason', 'supportingInfo' => 'supporting'] as $group => $title)
            <fieldset class="fieldset-card p-6 sm:p-8">
                <legend class="legend">
                    {{ __('device-requests.'.$title) }}
                </legend>
                <div
                    class="mb-6 overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700"
                >
                    <table class="table-base">
                        <thead class="table-header">
                            <tr>
                                @foreach (['date', 'type', 'record', 'action'] as $column)
                                    <th scope="col" class="px-4 py-4">
                                        {{ __('device-requests.'.$column) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <template
                                x-for="record in Object.values(availableRecords).filter(record => requestForm[@js($group)].includes(record.uuid))"
                                :key="record.uuid"
                            >
                                <tr
                                    class="border-t border-gray-100 dark:border-gray-700"
                                >
                                    <td
                                        class="px-4 py-4"
                                        x-text="record.date"
                                    ></td>
                                    <td
                                        class="px-4 py-4"
                                        x-text="record.typeLabel"
                                    ></td>
                                    <td
                                        class="px-4 py-4"
                                        x-text="record.label"
                                    ></td>
                                    <td class="px-4 py-4">
                                        <button
                                            type="button"
                                            @click="requestForm[@js($group)] = requestForm[@js($group)].filter(uuid => uuid !== record.uuid)"
                                            class="text-gray-900 hover:text-red-600 dark:text-white"
                                            aria-label="{{ __('forms.delete') }}"
                                        >
                                            @icon ('delete', 'w-4 h-4')
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="requestForm[@js($group)].length === 0">
                                <td colspan="4" class="px-4 py-4">
                                    {{ __('device-requests.no_records') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button
                    type="button"
                    @click="recordGroup = @js($group); recordSearch = true"
                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400"
                >
                    {{ __('device-requests.add_record') }}
                </button>
            </fieldset>
        @endforeach

        <fieldset class="fieldset-card p-6 sm:p-8">
            <legend class="legend">
                {{ __('device-requests.authentication') }}
            </legend>
            <div class="form-group max-w-md">
                <select
                    id="deviceRequestAuthentication"
                    x-model="requestForm.authenticationMethod"
                    class="input-select peer"
                    aria-label="{{ __('device-requests.authentication') }}"
                >
                    <option value="">{{ __('forms.select') }}</option>
                    @foreach ($authenticationMethods as $uuid => $label)
                        <option value="{{ $uuid }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </fieldset>

        <div class="flex flex-wrap items-center gap-4 pt-4">
            <button
                type="button"
                disabled
                class="button-primary-outline-red disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ __('forms.delete') }}
            </button>
            <button
                type="button"
                disabled
                class="button-primary-outline flex items-center gap-2 disabled:cursor-not-allowed disabled:opacity-50"
            >
                @icon ('archive', 'w-4 h-4')
                {{ __('forms.save') }}
            </button>
            <button
                type="button"
                class="button-primary disabled:cursor-not-allowed disabled:opacity-50"
                disabled
            >
                {{ __('device-requests.create_sign') }}
            </button>
        </div>
    </form>
    <x-dialog-drawer
        x-model="deviceSearch"
        maxWidth="4/5"
        zIndex="60"
        onCloseClick="deviceSearch = false"
        title="{{ __('device-requests.search') }}"
    >
        <div class="form-group mb-6">
            <input
                x-model="searchQuery"
                type="search"
                class="input peer"
                placeholder="{{ __('device-requests.search_placeholder') }}"
                aria-label="{{ __('device-requests.search') }}"
            />
        </div>
        <div class="space-y-3">
            @forelse ($devices as $device)
                <button
                    type="button"
                    x-show="!searchQuery || @js($device['name']).toLowerCase().includes(searchQuery.toLowerCase())"
                    @click="requestForm.deviceId = @js($device['id']); selectedDeviceName = @js($device['name']); deviceSearch = false"
                    class="fieldset-card flex w-full items-center justify-between gap-4 p-4 text-left text-sm hover:border-blue-400"
                >
                    <span>{{ $device['name'] }}</span>
                    @icon ('plus', 'w-4 h-4 text-blue-600')
                </button>
            @empty
                <p class="text-sm text-gray-500">{{ __('device-requests.no_devices') }}</p>
            @endforelse
        </div>
        <button
            type="button"
            @click="deviceSearch = false"
            class="button-minor mt-6"
        >
            {{ __('forms.close') }}
        </button>
    </x-dialog-drawer>

    <x-dialog-drawer
        x-model="recordSearch"
        maxWidth="4/5"
        zIndex="60"
        onCloseClick="recordSearch = false"
        title="{{ __('encounters.search_medical_records') }}"
    >
        <template x-if="recordGroup === 'reason'">
            <div>
                <x-encounter.medical-record-picker
                    :records="array_filter($availableRecords, static fn (array $record): bool => $record['canBeReason'] ?? false)"
                    selection-event="device-request-medical-record-selected"
                    is-added-check="isMedicalRecordAdded"
                />
            </div>
        </template>
        <template x-if="recordGroup === 'supportingInfo'">
            <div>
                <x-encounter.medical-record-picker
                    :records="$availableRecords"
                    selection-event="device-request-medical-record-selected"
                    is-added-check="isMedicalRecordAdded"
                />
            </div>
        </template>
        <button
            type="button"
            @click="recordSearch = false"
            class="button-minor mt-6"
        >
            {{ __('forms.close') }}
        </button>
    </x-dialog-drawer>
</div>
