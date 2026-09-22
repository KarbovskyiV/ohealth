@php
    $patientName = $patientFullName ?? __('forms.patient');
    $title = __('specimens.add_specimen') . ' - ' . $patientName;
@endphp

<x-layouts.patient
    :personId="$personId"
    :prepersonId="$prepersonId"
    :patientFullName="$patientName"
    :hideNavigation="is_null($personId) && is_null($prepersonId)"
    :title="$title"
    activeTab="specimens"
>
    <x-slot name="headerActions">
    </x-slot>

    <div class="breadcrumb-form shift-content p-4">
        <form wire:submit.prevent="save">
            <fieldset class="fieldset-card mb-6 p-4 sm:p-8 sm:pb-10">
                <legend class="legend">{{ __('specimens.general_info') }}</legend>

                <div class="form-row-2">
                    <div class="form-group group">
                        <select
                            wire:model="form.typeCode"
                            id="specimenType"
                            class="input-select peer"
                            required
                        >
                            <option value="" selected>{{ __('forms.select') }}</option>
                            @foreach ($this->dictionaries['specimen_types'] ?? [] as $code => $specimenType)
                                <option value="{{ $code }}">{{ $specimenType }}</option>
                            @endforeach
                        </select>
                        <label for="specimenType" class="label">{{ __('specimens.specimen_type') }}</label>
                    </div>

                    <div class="form-group group">
                        <select
                            wire:model="form.conditionCode"
                            id="specimenCondition"
                            class="input-select peer"
                        >
                            <option value="" selected>{{ __('forms.select') }}</option>
                            @foreach ($this->dictionaries['specimen_conditions'] ?? [] as $code => $specimenCondition)
                                <option value="{{ $code }}">{{ $specimenCondition }}</option>
                            @endforeach
                        </select>
                        <label for="specimenCondition" class="label">{{ __('specimens.specimen_condition') }}</label>
                    </div>
                </div>

                <div class="form-row-2 mt-6">
                    <div class="form-group group">
                        <input
                            type="text"
                            id="specimenStatus"
                            class="input-select peer cursor-not-allowed! text-gray-500! dark:text-gray-400!"
                            value="{{ __('specimens.statuses.available') }}"
                            disabled
                        />
                        <label for="specimenStatus" class="label">{{ __('forms.status.label') ?? 'Ð¡Ñ‚Ð°Ñ‚ÑƒÑ' }}</label>
                    </div>
                </div>

                <div class="form-row-2 mt-6">
                    <div class="form-group group">
                        <p class="label-modal mb-2 block">{{ __('specimens.parent_specimen') }}</p>

                        <div x-data="{ parentIds: $wire.entangle('form.parentIds') }">
                            <template x-for="(parentId, parentIndex) in parentIds" :key="parentIndex">
                                <div class="mt-4 flex items-center gap-2">
                                    <div class="form-group group relative flex-1">
                                        <label :for="'specimenParent' + parentIndex" class="sr-only">
                                            {{ __('specimens.parent_specimen') }}
                                        </label>
                                        <select
                                            x-model="parentIds[parentIndex]"
                                            :id="'specimenParent' + parentIndex"
                                            class="input-select peer"
                                        >
                                            <option value="" selected>{{ __('forms.select') }}</option>
                                            @foreach ($specimens as $parentOption)
                                                <option value="{{ $parentOption['uuid'] }}">
                                                    {{ $this->dictionaries['specimen_types'][$parentOption['typeCode']] ?? $parentOption['uuid'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button
                                        type="button"
                                        @click="parentIds.splice(parentIndex, 1)"
                                        class="mt-2 text-gray-400 transition-colors hover:text-red-500 dark:text-gray-500 dark:hover:text-red-500"
                                    >
                                        @icon('delete', 'w-5 h-5')
                                    </button>
                                </div>
                            </template>

                            <div class="mt-2">
                                <button
                                    type="button"
                                    @click="parentIds.push('')"
                                    class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                >
                                    {{ __('specimens.add_parent_specimen') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-row-1 mt-6">
                    <div class="form-group group">
                        <label for="specimenNote" class="label-modal mb-2 block">{{ __('specimens.note') }}</label>
                        <textarea
                            wire:model="form.note"
                            id="specimenNote"
                            class="textarea"
                            rows="3"
                            placeholder="{{ __('forms.text_for_input') }}"
                        ></textarea>
                    </div>
                </div>
            </fieldset>

            <fieldset class="fieldset-card mb-6 p-4 sm:p-8 sm:pb-10" x-data="{ collectorType: $wire.entangle('form.collectorType'), collectedType: $wire.entangle('form.collectedType') }">
                <legend class="legend">{{ __('specimens.material_collection') }}</legend>

                <div class="form-row-2">
                    <div class="form-group group">
                        <select
                            x-model="collectorType"
                            id="collectionCollectorType"
                            class="input-select peer"
                            required
                        >
                            <option value="current">{{ __('specimens.current_employee') }}</option>
                            <option value="other">{{ __('specimens.other_employee') }}</option>
                            <option value="patient">{{ __('forms.patient') }}</option>
                        </select>
                        <label for="collectionCollectorType" class="label">{{ __('specimens.collector') }}</label>
                    </div>

                    <template x-if="collectorType === 'current'">
                        <div class="form-group group">
                            <input
                                type="text"
                                id="collectionCurrentCollector"
                                class="input-select peer cursor-not-allowed! text-gray-500! dark:text-gray-400!"
                                value="{{ auth()->user()->employee?->name ?? '' }}"
                                disabled
                            />
                            <label for="collectionCurrentCollector" class="label">{{ __('specimens.current_employee') }}</label>
                        </div>
                    </template>

                    <template x-if="collectorType === 'other'">
                        <div class="form-group group">
                            <select
                                wire:model="form.collectorId"
                                id="collectionOtherCollector"
                                class="input-select peer"
                            >
                                <option value="" selected>{{ __('forms.select') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee['uuid'] }}">{{ $employee['name'] }}</option>
                                @endforeach
                            </select>
                            <label for="collectionOtherCollector" class="label">{{ __('specimens.select_other_employee') }}</label>
                        </div>
                    </template>
                </div>

                <div class="mt-6 mb-4 flex flex-wrap items-center gap-8">
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('specimens.when_done') }}</span>

                    <div class="flex items-center">
                        <input
                            x-model="collectedType"
                            id="specimenCollectedTypeDateTime"
                            type="radio"
                            value="date_time"
                            class="default-radio"
                        />
                        <label for="specimenCollectedTypeDateTime" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                            {{ __('specimens.exact_date_and_time') }}
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input
                            x-model="collectedType"
                            id="specimenCollectedTypePeriod"
                            type="radio"
                            value="period"
                            class="default-radio"
                        />
                        <label for="specimenCollectedTypePeriod" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                            {{ __('specimens.period') }}
                        </label>
                    </div>
                </div>

                <div class="form-row-2" x-show="collectedType === 'date_time'" x-cloak>
                    <div class="form-group group relative flex justify-between items-stretch">
                        <div class="datepicker-wrapper flex-1 h-full">
                            <input
                                wire:model="form.collectedDate"
                                type="text"
                                id="specimenCollectedDate"
                                autocomplete="off"
                                class="datepicker-input with-leading-icon input peer rounded-r-none border-r-0 h-full"
                                placeholder=" "
                                x-bind:required="collectedType === 'date_time'"
                            />
                            <label for="specimenCollectedDate" class="wrapped-label">{{ __('specimens.date_time') }}</label>
                        </div>
                        <div class="relative w-32 h-full">
                            <label for="specimenCollectedTime" class="sr-only">{{ __('specimens.time') }}</label>
                            <input
                                wire:model="form.collectedTime"
                                type="time"
                                id="specimenCollectedTime"
                                class="input peer rounded-l-none pl-10 h-full"
                                placeholder=" "
                                x-bind:required="collectedType === 'date_time'"
                            />
                            @icon('clock', 'svg-input left-2.5 text-gray-400 !w-4.5 !h-4.5')
                        </div>
                    </div>
                </div>

                <div class="form-row-3" x-show="collectedType === 'period'" x-cloak>
                    <div class="form-group group relative">
                        <input
                            wire:model="form.collectedPeriodRange"
                            type="text"
                            id="specimenCollectedPeriodRange"
                            class="daterangepicker-uk input peer"
                            autocomplete="off"
                            placeholder=" "
                            x-bind:required="collectedType === 'period'"
                        />
                        <label for="specimenCollectedPeriodRange" class="label">{{ __('specimens.period') }}</label>
                    </div>
                    <div class="form-group group relative">
                        <input
                            wire:model="form.collectedPeriodStartTime"
                            type="time"
                            id="specimenCollectedPeriodStartTime"
                            class="input peer pl-10"
                            placeholder=" "
                            x-bind:required="collectedType === 'period'"
                        />
                        <label for="specimenCollectedPeriodStartTime" class="label left-7">{{ __('specimens.period_start') }}</label>
                        @icon('clock', 'svg-input left-2.5 text-gray-400 !w-4.5 !h-4.5')
                    </div>
                    <div class="form-group group relative">
                        <input
                            wire:model="form.collectedPeriodEndTime"
                            type="time"
                            id="specimenCollectedPeriodEndTime"
                            class="input peer pl-10"
                            placeholder=" "
                        />
                        <label for="specimenCollectedPeriodEndTime" class="label left-7">{{ __('specimens.period_end') }}</label>
                        @icon('clock', 'svg-input left-2.5 text-gray-400 !w-4.5 !h-4.5')
                    </div>
                </div>

        <div class="form-row-2 mt-6">
            <div class="form-group group flex items-start gap-2">
                <div class="relative flex-1">
                    <input
                        wire:model="form.durationValue"
                        type="number"
                        min="0"
                        step="any"
                        id="specimenDurationValue"
                        class="input peer"
                        placeholder=" "
                    />
                    <label for="specimenDurationValue" class="label">{{ __('specimens.collection_duration') }}</label>
                </div>
                <div class="w-40">
                    <label for="specimenDurationCode" class="sr-only">{{ __('specimens.unit') }}</label>
                    <select
                        wire:model="form.durationCode"
                        id="specimenDurationCode"
                        class="input-select peer"
                    >
                        <option value="" selected>{{ __('forms.select') }}</option>
                        @foreach (config('ehealth.specimen_duration_allowed_codes', []) as $code)
                            <option value="{{ $code }}">
                                {{ $this->dictionaries['eHealth/ucum/units'][$code] ?? $code }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group group flex items-start gap-2">
                <div class="relative flex-1">
                    <input
                        wire:model="form.quantityValue"
                        type="number"
                        min="0"
                        step="any"
                        id="specimenQuantityValue"
                        class="input peer"
                        placeholder=" "
                    />
                    <label for="specimenQuantityValue" class="label">{{ __('specimens.material_amount') }}</label>
                </div>
                <div class="w-40">
                    <label for="specimenQuantityCode" class="sr-only">{{ __('specimens.unit') }}</label>
                    <select
                        wire:model="form.quantityCode"
                        id="specimenQuantityCode"
                        class="input-select peer"
                    >
                        <option value="" selected>{{ __('forms.select') }}</option>
                        @foreach ($this->dictionaries['eHealth/ucum/units'] ?? [] as $code => $unit)
                            <option value="{{ $code }}">{{ $unit }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="form-row-2 mt-6">
            <div class="form-group group">
                <select wire:model="form.methodCode" id="collectionMethod" class="input-select peer">
                    <option value="" selected>{{ __('forms.select') }}</option>
                    @foreach ($this->dictionaries['specimen_collection_methods'] ?? [] as $code => $collectionMethod)
                        <option value="{{ $code }}">{{ $collectionMethod }}</option>
                    @endforeach
                </select>
                <label for="collectionMethod" class="label">{{ __('specimens.collection_method') }}</label>
            </div>

            <div class="form-group group">
                <select
                    wire:model="form.bodySiteCode"
                    id="collectionBodySite"
                    class="input-select peer"
                >
                    <option value="" selected>{{ __('forms.select') }}</option>
                    @foreach ($this->dictionaries['eHealth/body_sites'] ?? [] as $code => $bodySite)
                        <option value="{{ $code }}">{{ $bodySite }}</option>
                    @endforeach
                </select>
                <label for="collectionBodySite" class="label">{{ __('specimens.body_site') }}</label>
            </div>
        </div>

        <div class="form-row-2 mt-6">
            <div class="form-group group">
                <select
                    wire:model="form.fastingStatusCode"
                    id="collectionFastingStatus"
                    class="input-select peer"
                >
                    <option value="" selected>{{ __('forms.select') }}</option>
                    @foreach ($this->dictionaries['fasting_statuses'] ?? [] as $code => $fastingStatus)
                        <option value="{{ $code }}">{{ $fastingStatus }}</option>
                    @endforeach
                </select>
                <label for="collectionFastingStatus" class="label">{{ __('specimens.fasting_status') }}</label>
            </div>

            <div class="form-group group">
                <select
                    wire:model="form.procedureId"
                    id="collectionProcedure"
                    class="input-select peer"
                >
                    <option value="" selected>{{ __('forms.select') }}</option>
                    @foreach ($procedures ?? [] as $procedure)
                        <option value="{{ $procedure['uuid'] }}">{{ $procedure['name'] ?? $procedure['uuid'] }}</option>
                    @endforeach
                </select>
                <label for="collectionProcedure" class="label">{{ __('specimens.procedure_during_collection') }}</label>
            </div>
        </div>
    </fieldset>

    <div x-data="{ containers: $wire.entangle('form.containers') }">
        <template x-for="(container, containerIndex) in containers" :key="containerIndex">
            <fieldset class="fieldset-card relative mb-6 p-4 sm:p-8 sm:pb-10">
                <legend class="legend" x-text="'{{ __('specimens.container_number') }}' + (containerIndex + 1)"></legend>

                <template x-if="containerIndex > 0">
                    <button
                        type="button"
                        @click="containers.splice(containerIndex, 1)"
                        class="absolute -top-5 right-4 bg-white px-2 text-gray-400 transition-colors hover:text-red-500 sm:right-8 dark:bg-slate-900 dark:text-gray-500 dark:hover:text-red-500"
                    >
                        @icon('delete', 'w-6 h-6')
                    </button>
                </template>

                <div class="form-row-2">
                    <div class="form-group group relative">
                        <input
                            x-model="container.identifier"
                            type="text"
                            :id="'containerIdentifier' + containerIndex"
                            class="input peer"
                            placeholder=" "
                            required
                        />
                        <label :for="'containerIdentifier' + containerIndex" class="label">{{ __('specimens.identifier') }}</label>
                    </div>

                    <div class="form-group group relative">
                        <input
                            x-model="container.description"
                            type="text"
                            :id="'containerDescription' + containerIndex"
                            class="input peer"
                            placeholder=" "
                        />
                        <label :for="'containerDescription' + containerIndex" class="label">{{ __('specimens.container_description') }}</label>
                    </div>
                </div>

                <div class="form-row-2 mt-6">
                    <div class="form-group group">
                        <select
                            x-model="container.typeCode"
                            :id="'containerType' + containerIndex"
                            class="input-select peer"
                        >
                            <option value="" selected>{{ __('forms.select') }}</option>
                            @foreach ($this->dictionaries['specimen_container_types'] ?? [] as $code => $containerType)
                                <option value="{{ $code }}">{{ $containerType }}</option>
                            @endforeach
                        </select>
                        <label :for="'containerType' + containerIndex" class="label">{{ __('specimens.container_type') }}</label>
                    </div>

                    <div class="form-group group">
                        <select
                            x-model="container.additiveCode"
                            :id="'containerAdditive' + containerIndex"
                            class="input-select peer"
                        >
                            <option value="" selected>{{ __('forms.select') }}</option>
                            @foreach ($this->dictionaries['specimen_container_additives'] ?? [] as $code => $containerAdditive)
                                <option value="{{ $code }}">{{ $containerAdditive }}</option>
                            @endforeach
                        </select>
                        <label :for="'containerAdditive' + containerIndex" class="label">{{ __('specimens.additive') }}</label>
                    </div>
                </div>

                <div class="form-row-2 mt-6">
                    <div class="form-group group flex items-start gap-2">
                        <div class="relative flex-1">
                            <input
                                x-model="container.capacityValue"
                                type="number"
                                min="0"
                                step="any"
                                :id="'containerCapacityValue' + containerIndex"
                                class="input peer"
                                placeholder=" "
                            />
                            <label :for="'containerCapacityValue' + containerIndex" class="label">{{ __('specimens.container_volume') }}</label>
                        </div>
                        <div class="w-40">
                            <label :for="'containerCapacityCode' + containerIndex" class="sr-only">{{ __('specimens.unit') }}</label>
                            <select
                                x-model="container.capacityCode"
                                :id="'containerCapacityCode' + containerIndex"
                                class="input-select peer"
                            >
                                <option value="" selected>{{ __('forms.select') }}</option>
                                @foreach ($this->dictionaries['eHealth/ucum/units'] ?? [] as $code => $unit)
                                    <option value="{{ $code }}">{{ $unit }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group group flex items-start gap-2">
                        <div class="relative flex-1">
                            <input
                                x-model="container.specimenQuantityValue"
                                type="number"
                                min="0"
                                step="any"
                                :id="'containerSpecimenQuantityValue' + containerIndex"
                                class="input peer"
                                placeholder=" "
                            />
                            <label :for="'containerSpecimenQuantityValue' + containerIndex" class="label">{{ __('specimens.biomaterial_amount_in_container') }}</label>
                        </div>
                        <div class="w-40">
                            <label :for="'containerSpecimenQuantityCode' + containerIndex" class="sr-only">{{ __('specimens.unit') }}</label>
                            <select
                                x-model="container.specimenQuantityCode"
                                :id="'containerSpecimenQuantityCode' + containerIndex"
                                class="input-select peer"
                            >
                                <option value="" selected>{{ __('forms.select') }}</option>
                                @foreach ($this->dictionaries['eHealth/ucum/units'] ?? [] as $code => $unit)
                                    <option value="{{ $code }}">{{ $unit }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </fieldset>
        </template>

        <div class="mb-6">
            <button
                type="button"
                @click="containers.push({ identifier: '', description: '', typeCode: '', additiveCode: '', capacityValue: null, capacityCode: '', specimenQuantityValue: null, specimenQuantityCode: '' })"
                class="text-sm font-medium text-blue-600 hover:text-blue-800"
            >
                {{ __('specimens.add_container') }}
            </button>
        </div>
    </div>

    <div class="mt-6 flex justify-start">
        <button type="submit" class="button-primary-outline flex items-center gap-2">
            @icon('archive', 'w-4 h-4')
            {{ __('forms.save') }}
        </button>
    </div>
</form>
    </div>
</x-layouts.patient>
