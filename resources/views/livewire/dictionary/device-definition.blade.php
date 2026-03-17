<div>
    <x-header-navigation class="breadcrumb-form">
        <x-slot name="title">
            {{ __('dictionaries.medical_device.page_title') }}
        </x-slot>

        <x-slot name="navigation">
            <div class="flex flex-col gap-4" x-data="{ showFilter: false }">
                <div class="flex flex-col gap-4 max-w-sm">
                    {{-- Program --}}
                    <div class="form-group group">
                        <label for="programSelect" class="default-label mb-2">
                            {{ __('dictionaries.program_label') }}*
                        </label>

                        <select id="programSelect"
                                class="input-select"
                        >
                            <option value="" selected>{{ __('forms.select') }}</option>
                            <option value="gdp">{{ __('dictionaries.medical_device.medical_guarantees') }}</option>
                            <option value="other">{{ __('dictionaries.medical_device.other_program') }}</option>
                        </select>
                    </div>

                    {{-- Search medical devices --}}
                    <div class="form-group group">
                        <label for="deviceSearch" class="default-label mb-2">
                            {{ __('dictionaries.medical_device.search') }}
                        </label>

                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                @icon('search-outline', 'w-4 h-4 text-gray-500 dark:text-gray-400')
                            </div>
                            <input type="text"
                                   id="deviceSearch"
                                   class="input w-full ps-9"
                                   placeholder=" "
                                   autocomplete="off"
                            />
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-wrap gap-2">
                    <button type="button"
                            class="button-primary flex items-center gap-2"
                    >
                        @icon('search', 'w-4 h-4')
                        <span>{{ __('forms.search') }}</span>
                    </button>

                    <button type="button"
                            class="button-primary-outline-red"
                    >
                        {{ __('forms.reset_all_filters') }}
                    </button>

                    <button type="button"
                            class="button-minor flex items-center gap-2"
                            @click="showFilter = !showFilter"
                    >
                        @icon('adjustments', 'w-4 h-4')
                        <span>{{ __('forms.additional_search_parameters') }}</span>
                    </button>
                </div>

                {{-- Additional filters --}}
                <div x-cloak x-show="showFilter" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group group">
                        <select
                            id="deviceType"
                            class="peer input-select w-full"
                        >
                            <option value="" selected>{{ __('forms.select') }}</option>
                        </select>
                        <label for="deviceType" class="label peer-focus:text-blue-600 peer-valid:text-blue-600">
                            {{ __('dictionaries.medical_device.device_type') }}
                        </label>
                    </div>

                    <div class="form-group group">
                        <input
                            type="text"
                            id="deviceModelNumber"
                            class="input peer"
                            placeholder=" "
                            autocomplete="off"
                        />
                        <label for="deviceModelNumber" class="label peer-focus:text-blue-600 peer-valid:text-blue-600">
                            {{ __('dictionaries.medical_device.device_model_number') }}
                        </label>
                    </div>
                </div>
            </div>
        </x-slot>
    </x-header-navigation>

    <section class="shift-content pl-3.5 mt-6 max-w-[1280px]">
        <fieldset class="fieldset p-6 sm:p-8">
            <legend class="legend">
                {{ __('dictionaries.medical_device.prescription_medication_details') }}
            </legend>

            <div class="space-y-2 text-gray-900 dark:text-gray-100">
                <p class="font-semibold">
                    {{ __('dictionaries.medical_device.funding_source') }}:
                </p>
                <p class="font-semibold">
                    {{ __('dictionaries.medical_device.employee_types_to_create_request') }}:
                </p>
                <p class="font-semibold">
                    {{ __('dictionaries.medical_device.speciality_types_allowed') }}:
                </p>
                <p class="font-semibold">
                    {{ __('dictionaries.medical_device.skip_treatment_period') }}:
                </p>
                <p class="font-semibold">
                    {{ __('dictionaries.medical_device.request_max_period_day') }}:
                </p>
                <p class="font-semibold">
                    {{ __('dictionaries.medical_device.skip_request_employee_declaration_verify') }}:
                </p>
                <p class="font-semibold">
                    {{ __('dictionaries.medical_device.skip_request_legal_entity_declaration_verify') }}:
                </p>
            </div>
        </fieldset>

        <div
            class="flow-root mt-8"
            wire:key="medical-devices-table"
            x-data="{ openDetails: false }"
        >
            <div class="max-w-screen-xl">
                <div class="index-table-wrapper">
                    <table class="index-table">
                        <thead class="index-table-thead">
                        <tr>
                            <th class="index-table-th w-[26%]">
                                {{ __('dictionaries.medical_device.table.name') }}
                            </th>
                            <th class="index-table-th w-[26%]">
                                {{ __('dictionaries.medical_device.table.type') }}
                            </th>
                            <th class="index-table-th w-[20%]">
                                {{ __('dictionaries.medical_device.table.package') }}
                            </th>
                            <th class="index-table-th w-[22%]">
                                {{ __('dictionaries.medical_device.table.program_participants') }}
                            </th>
                            <th class="index-table-th w-[6%]">
                                {{ __('dictionaries.medical_device.table.for') }}
                            </th>
                        </tr>
                        </thead>

                        <tbody>
                        <tr class="index-table-tr">
                            <td class="index-table-td-primary">
                                Реагент швидкого тестування на глюкозу
                            </td>
                            <td class="index-table-td">
                                Медичні вироби для визначення рівня глюкози в крові
                            </td>
                            <td class="index-table-td">
                                Коробка 50 штук
                            </td>
                            <td class="index-table-td">
                                <div class="flex flex-col gap-1 text-gray-700 dark:text-gray-200">
                                    <div class="flex items-center gap-1">
                                        <span>DM</span>
                                        @icon('question-mark-circle', 'w-4 h-4 text-gray-400')
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span>RightTest ELSA</span>
                                        @icon('question-mark-circle', 'w-4 h-4 text-gray-400')
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span>RightTest Ultra</span>
                                        @icon('question-mark-circle', 'w-4 h-4 text-gray-400')
                                    </div>
                                </div>
                            </td>
                            <td class="index-table-td-actions">
                                <button
                                    type="button"
                                    class="flex items-center justify-center cursor-pointer text-primary hover:text-primary/80"
                                    @click="openDetails = !openDetails"
                                >
                                    @icon('plus-circle', 'w-4 h-4')
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div x-show="openDetails" x-cloak class="mt-4">
                    <fieldset class="fieldset p-6 sm:p-8">
                        <legend class="sr-only">
                            {{ __('dictionaries.medical_device.table.program_participants') }}
                        </legend>

                        <div class="space-y-2 text-gray-900 dark:text-gray-100">
                            <p>
                                {{ __('dictionaries.medical_device.participant_details.period') }}: 31.08.2023 - Дату не визначено
                            </p>
                            <p>
                                {{ __('dictionaries.medical_device.participant_details.max_daily_dose') }}: 5 шт
                            </p>
                        </div>
                    </fieldset>
                </div>
            </div>
        </div>
    </section>

</div>
