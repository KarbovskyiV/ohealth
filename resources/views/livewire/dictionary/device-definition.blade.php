<div
    x-data="{
        allPrograms: @js($programs),
        dictionaries: @js($dictionaries),
        roleLabels: @js(__('users.role')),
        selectedProgramId: @entangle('filterMedicalProgramId'),
        get selectedProgram() {
            return this.allPrograms.find(program => program.id === this.selectedProgramId) || null;
        },
        translateRoles(roles) {
            return roles?.map(role => this.roleLabels[role] || role).join(', ') || '-';
        },
        translateSpecialities(specialities) {
            return specialities?.map(speciality => this.dictionaries.SPECIALITY_TYPE[speciality] || speciality).join(', ') || '-';
        },
        translateClassificationType(code, system) {
            // Use the system field to determine which dictionary to use
            if (system === 'eHealth/assistive_devices') {
                return this.dictionaries['eHealth/assistive_devices']?.[code] || code;
            } else if (system === 'device_definition_classification_type') {
                return this.dictionaries['device_definition_classification_type']?.[code] || code;
            }

            return code;
        }
    }"
>
    <x-header-navigation class="breadcrumb-form">
        <x-slot name="title">{{ __('dictionaries.medical_device.page_title') }}</x-slot>

        <x-slot name="navigation">
            <div class="flex flex-col gap-4" x-data="{ showFilter: false }">
                <div class="flex max-w-sm flex-col gap-4">
                    {{-- Program --}}
                    <div class="form-group group">
                        <label for="programSelect" class="default-label mb-2">
                            {{ __('dictionaries.program_label') }}*
                        </label>

                        <select id="programSelect" class="input-select" wire:model="filterMedicalProgramId">
                            <option value="" selected>{{ __('forms.select') }}</option>
                            <template x-for="program in allPrograms" :key="program.id">
                                <option :value="program.id" x-text="program.name"></option>
                            </template>
                        </select>

                        @error('filterMedicalProgramId')
                            <p class="text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Search medical devices --}}
                    <div class="form-group group">
                        <label for="deviceSearch" class="default-label mb-2">
                            {{ __('dictionaries.medical_device.search') }}
                        </label>

                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                                @icon('search-outline', 'w-4 h-4 text-gray-500 dark:text-gray-400')
                            </div>
                            <input
                                type="text"
                                id="deviceSearch"
                                class="input w-full ps-9"
                                placeholder=" "
                                autocomplete="off"
                                wire:model="filterName"
                            />
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="button-primary flex items-center gap-2" wire:click="search">
                        @icon('search', 'w-4 h-4')
                        <span>{{ __('forms.search') }}</span>
                    </button>

                    <button type="button" class="button-primary-outline-red" wire:click="resetFilters">
                        {{ __('forms.reset_all_filters') }}
                    </button>

                    <button
                        type="button"
                        class="button-minor flex items-center gap-2"
                        @click="showFilter = ! showFilter"
                    >
                        @icon('adjustments', 'w-4 h-4')
                        <span>{{ __('forms.additional_search_parameters') }}</span>
                    </button>
                </div>

                {{-- Additional filters --}}
                <div x-cloak x-show="showFilter" x-transition class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="form-group group">
                        <select id="deviceType" class="input-select" wire:model="filterClassificationTypeCode">
                            <option value="">{{ __('forms.select') }}</option>
                            @foreach ($classificationTypes as $classificationType)
                                <option value="{{ $classificationType['code'] }}">
                                    {{ $classificationType['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <label for="deviceType" class="label">
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
                            wire:model="filterModelNumber"
                        />
                        <label for="deviceModelNumber" class="label">
                            {{ __('dictionaries.medical_device.device_model_number') }}
                        </label>
                    </div>
                </div>
            </div>
        </x-slot>
    </x-header-navigation>

    <template x-if="selectedProgram">
        <section class="shift-content mt-6 max-w-[1280px] pl-3.5">
            <div>
                {{-- Program info --}}
                <fieldset class="fieldset p-6 sm:p-8">
                    <legend class="legend">
                        {{ __('dictionaries.medical_device.prescription_medication_details') }}
                    </legend>

                    <div class="space-y-2 text-gray-900 dark:text-gray-100">
                        <p>
                            <span class="font-semibold">{{ __('dictionaries.medical_device.funding_source') }}:</span>
                            <span x-text="dictionaries.FUNDING_SOURCE[selectedProgram.funding_source]"></span>
                        </p>
                        <p>
                            <span class="font-semibold">{{ __('dictionaries.medical_device.employee_types_to_create_request') }}:</span>
                            <span
                                x-text="
                                    translateRoles(
                                        selectedProgram.medical_program_settings?.employee_types_to_create_request,
                                    )
                                "
                            ></span>
                        </p>
                        <p>
                            <span class="font-semibold">
                                {{ __('dictionaries.medical_device.speciality_types_request_allowed') }}:
                            </span>
                            <span
                                x-text="
                                    translateSpecialities(
                                        selectedProgram.medical_program_settings.speciality_types_request_allowed,
                                    )
                                "
                            ></span>
                        </p>
                        <p>
                            <span class="font-semibold">
                                {{ __('dictionaries.medical_device.speciality_types_care_plan_activity_allowed') }}:
                            </span>
                            <span
                                x-text="
                                    translateSpecialities(
                                        selectedProgram.medical_program_settings
                                            .speciality_types_care_plan_activity_allowed,
                                    )
                                "
                            ></span>
                        </p>
                        <p>
                            <span class="font-semibold">
                                {{ __('dictionaries.medical_device.skip_treatment_period') }}:
                            </span>
                            <span x-text="selectedProgram.medical_program_settings?.skip_treatment_period ? '{{ __('forms.yes') }}' : '{{ __('forms.no') }}'"></span>
                        </p>
                        <p>
                            <span class="font-semibold">{{ __('dictionaries.medical_device.request_max_period_day') }}:</span>
                            <span x-text="selectedProgram.medical_program_settings?.request_max_period_day || '-'"></span>
                        </p>
                        <p>
                            <span class="font-semibold">
                                {{ __('dictionaries.medical_device.skip_request_employee_declaration_verify') }}:
                            </span>
                            <span x-text="selectedProgram.medical_program_settings.skip_request_employee_declaration_verify ? '{{ __('forms.yes') }}' : '{{ __('forms.no') }}'"></span>
                        </p>
                        <p>
                            <span class="font-semibold">
                                {{ __('dictionaries.medical_device.skip_request_legal_entity_declaration_verify') }}:
                            </span>
                            <span x-text="selectedProgram.medical_program_settings.skip_request_legal_entity_declaration_verify ? '{{ __('forms.yes') }}' : '{{ __('forms.no') }}'"></span>
                        </p>
                    </div>
                </fieldset>

                {{-- Device Definition info --}}
                @if ($this->deviceDefinitions->count() > 0)
                    <div
                        class="mt-8 flow-root"
                        wire:key="medical-devices-table"
                        x-data="{
                         openDetails: {},
                         deviceDefinitions: @js($this->deviceDefinitions->items()),
                         toggleDetails(deviceId) {
                             this.openDetails[deviceId] = ! this.openDetails[deviceId];
                         },
                         isDetailsOpen(deviceId) {
                             return this.openDetails[deviceId] || false;
                         },
                         getDeviceById(deviceId) {
                             return this.deviceDefinitions.find(device => device.id === deviceId);
                         }
                     }"
                    >
                        <div class="max-w-screen-xl">
                            <div class="index-table-wrapper">
                                <table class="index-table">
                                    <thead class="index-table-thead">
                                        <tr>
                                            <th class="index-table-th w-[26%]">{{ __('forms.name') }}</th>
                                            <th class="index-table-th w-[26%]">{{ __('forms.type') }}</th>
                                            <th class="index-table-th w-[20%]">
                                                {{ __('dictionaries.medical_device.table.package') }}
                                            </th>
                                            <th class="index-table-th w-[22%]">
                                                {{ __('dictionaries.medical_device.table.program_participants') }}
                                            </th>
                                            <th class="index-table-th w-[6%]">{{ __('forms.action') }}</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($this->deviceDefinitions as $deviceDefinition)
                                            <tr class="index-table-tr">
                                                <td class="index-table-td-primary">
                                                    <div class="flex flex-col gap-1">
                                                        @foreach ($deviceDefinition['device_names'] as $deviceName)
                                                            <span>{{ $deviceName['name'] }}</span>
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td class="index-table-td">
                                                    <div class="flex flex-col gap-1">
                                                        {{-- The same type may come more than once --}}
                                                        @foreach (collect($deviceDefinition['classification_types'])->unique(static fn (array $type): string => $type['system'] . '|' . $type['code']) as $classificationType)
                                                            <span x-text="translateClassificationType('{{ $classificationType['code'] }}', '{{ $classificationType['system'] }}')"></span>
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td class="index-table-td">
                                                    {{ $this->dictionaries['device_definition_packaging_type'][$deviceDefinition['packaging']['packaging_type']] }} {{ $deviceDefinition['packaging']['packaging_count'] }} {{ $this->dictionaries['device_unit'][$deviceDefinition['packaging']['packaging_unit']] }}
                                                </td>
                                                <td class="index-table-td">
                                                    {{ count($deviceDefinition['program_devices']) }}
                                                </td>
                                                <td class="index-table-td-actions">
                                                    <button
                                                        type="button"
                                                        class="text-primary hover:text-primary/80 flex cursor-pointer items-center justify-center"
                                                        @click="toggleDetails('{{ $deviceDefinition['id'] }}')"
                                                    >
                                                        @icon('plus-circle', 'w-4 h-4')
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination --}}
                            <div class="pagination">{{ $this->deviceDefinitions->links() }}</div>

                            {{-- Details for each device --}}
                            @foreach ($this->deviceDefinitions as $deviceDefinition)
                                <div
                                    x-show="isDetailsOpen('{{ $deviceDefinition['id'] }}')"
                                    x-cloak
                                    class="mt-4"
                                    wire:key="details-{{ $deviceDefinition['id'] }}"
                                >
                                    <fieldset class="fieldset p-6 sm:p-8">
                                        <legend class="legend">
                                            {{ __('dictionaries.medical_device.table.program_participants') }}
                                        </legend>

                                        @foreach ($deviceDefinition['program_devices'] as $programDevice)
                                            <div class="mb-4 space-y-2 text-gray-900 last:mb-0 dark:text-gray-100">
                                                <p>
                                                    <span class="font-semibold">
                                                        {{ __('dictionaries.medical_device.participant_details.period') }}:
                                                    </span>
                                                    <span>
                                                        {{ convertToAppDateFormat($programDevice['start_date']) }} - {{ ($programDevice['end_date'] ?? null) ? convertToAppDateFormat($programDevice['end_date']) : __('dictionaries.medical_device.participant_details.end_date_not_set') }}
                                                    </span>
                                                </p>
                                                <p>
                                                    <span class="font-semibold">
                                                        {{ __('dictionaries.medical_device.participant_details.max_daily_dose') }}:
                                                    </span>
                                                    <span>{{ $programDevice['max_daily_count'] }} {{ $this->dictionaries['device_unit'][$deviceDefinition['packaging']['packaging_unit']] }}</span>
                                                </p>
                                            </div>
                                        @endforeach
                                    </fieldset>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    @if (!empty($this->filterMedicalProgramId))
                        <div class="mt-8 py-8 text-center">
                            <p class="text-gray-500 dark:text-gray-400">
                                {{ __('dictionaries.medical_device.no_results') }}
                            </p>
                        </div>
                    @endif
                @endif
            </div>
        </section>
    </template>

    <x-forms.loading />
    <livewire:components.x-message :key="time()" />
</div>
