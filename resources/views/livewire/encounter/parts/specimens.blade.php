<div
    class="p-4 sm:p-8"
    id="specimens-section"
    x-data="{
        specimens: $wire.entangle('specimenForm.specimens'),
        procedures: $wire.entangle('procedureForm.procedures'),
        selectedRecords: $wire.entangle('selectedRecords.specimens'),
        cancelledRecords: $wire.cancelledRecords.specimens,
        canCancelRecords: {{ ($canCancelRecords ?? false) ? 'true' : 'false' }},
        employees: @js($employees),
        currentEmployee: @js($deviceDispenseEmployee),
        specimenTypesDictionary: $wire.dictionaries['specimen_types'],
        containerTypesDictionary: $wire.dictionaries['specimen_container_types'],
        modalSpecimen: new Specimen(),
        newSpecimen: false,
        openSpecimenDrawer: false,
        item: 0,

        employeeName(id) {
            return this.employees.find((employee) => employee.uuid === id)?.name || this.currentEmployee.name || '-';
        },

        procedureLabel(procedure) {
            const service = Object.values($wire.dictionaries['custom/services'] ?? {}).find((service) => service.id === procedure.codeValue);

            return service ? `${service.code} / ${service.name}` : procedure.codeValue || procedure.uuid || '-';
        },

        isReferenced(specimenId) {
            const observations = $wire.observationForm.observations ?? [];
            const diagnosticReports = $wire.diagnosticReportForm.diagnosticReports ?? [];

            return observations.some((observation) => observation.specimenId === specimenId) ||
                diagnosticReports.some((diagnosticReport) => (diagnosticReport.specimenIds ?? []).includes(specimenId));
        },

        statusName(specimenId) {
            return this.isReferenced(specimenId)
                ? '{{ __('specimens.statuses.unavailable') }}'
                : '{{ __('specimens.statuses.available') }}';
        },

        collectedAt(specimen) {
            if (specimen.collectedType === 'period') {
                return `${specimen.collectedPeriodRange || '-'} ${specimen.collectedPeriodStartTime || ''}`;
            }

            return `${specimen.collectedDate || '-'} ${specimen.collectedTime || ''}`;
        },

        containersLabel(specimen) {
            return specimen.containers
                .map((container) => [container.identifier, this.containerTypesDictionary[container.typeCode]].filter(Boolean).join(' — '))
                .join(', ') || '-';
        },

        parentOptions() {
            const packageSpecimenIds = this.specimens.map((specimen) => specimen.uuid);

            return [
                ...this.specimens.filter((specimen) => specimen.uuid !== this.modalSpecimen.uuid),
                ...$wire.patientSpecimens.filter(
                    (specimen) => specimen.status === 'available' && ! packageSpecimenIds.includes(specimen.uuid)
                )
            ];
        },

        parentsLabel(specimen) {
            const parents = [...this.specimens, ...$wire.patientSpecimens];

            return specimen.parentIds
                .map((parentId) => this.specimenTypesDictionary[parents.find((parent) => parent.uuid === parentId)?.typeCode])
                .filter(Boolean)
                .join(', ') || '-';
        },

        changeCollectorType() {
            const collectorIds = {
                current: this.currentEmployee.uuid,
                patient: $wire.patientUuid
            };

            this.modalSpecimen.collectorId = collectorIds[this.modalSpecimen.collectorType] || '';
        },

        createSpecimen() {
            this.newSpecimen = true;
            this.modalSpecimen = new Specimen();
            this.modalSpecimen.collectorId = this.currentEmployee.uuid || '';
            this.modalSpecimen.collectedDate = $wire.form.encounter.periodDate || '';
            this.modalSpecimen.collectedTime = $wire.form.encounter.periodStart || '';
            this.openSpecimenDrawer = true;
        },

        editSpecimen(index) {
            this.item = index;
            this.modalSpecimen = new Specimen(this.specimens[index]);
            this.newSpecimen = false;
            this.openSpecimenDrawer = true;
        },

        saveSpecimen() {
            const specimen = JSON.parse(JSON.stringify(this.modalSpecimen));

            specimen.parentIds = specimen.parentIds.filter(Boolean);

            if (this.newSpecimen) {
                this.specimens.push(specimen);
            } else {
                this.specimens.splice(this.item, 1, specimen);
            }

            this.openSpecimenDrawer = false;
        },

        removeSpecimen(index) {
            const removedId = this.specimens[index].uuid;

            this.specimens.splice(index, 1);
            this.specimens.forEach((specimen) => {
                specimen.parentIds = specimen.parentIds.filter((parentId) => parentId !== removedId);
            });
        },

        canSaveSpecimen() {
            const collectedAtFilled = this.modalSpecimen.collectedType === 'period'
                ? this.modalSpecimen.collectedPeriodRange && this.modalSpecimen.collectedPeriodStartTime
                : this.modalSpecimen.collectedDate && this.modalSpecimen.collectedTime;

            return this.modalSpecimen.typeCode &&
                this.modalSpecimen.collectorId &&
                collectedAtFilled &&
                this.modalSpecimen.containers.length > 0 &&
                this.modalSpecimen.containers.every((container) => container.identifier.trim());
        }
    }"
>
    <div class="space-y-4">
        <template x-for="(specimen, index) in specimens" :key="specimen.uuid || index">
            <div class="record-inner-card">
                <div class="record-inner-header">
                    <div class="record-inner-checkbox-col">
                        <label :for="`specimenRecord${index}`" class="sr-only">{{ __('forms.select') }}</label>
                        <input
                            type="checkbox"
                            :id="`specimenRecord${index}`"
                            class="default-checkbox h-5 w-5"
                            :value="specimen.uuid"
                            x-model="selectedRecords"
                            :disabled="! canCancelRecords || ! specimen.uuid || cancelledRecords.includes(specimen.uuid)"
                        />
                    </div>

                    <template x-if="cancelledRecords.includes(specimen.uuid)">
                        <span class="record-inner-badge-error"> {{ __('specimens.statuses.entered_in_error') }} </span>
                    </template>

                    <div class="record-inner-column flex-1">
                        <div
                            class="record-inner-label"
                            x-text="`{{ __('specimens.specimen_number') }} ${index + 1}`"
                        ></div>
                        <div
                            class="record-inner-value text-[16px]"
                            x-text="specimenTypesDictionary[specimen.typeCode] || '-'"
                        ></div>
                    </div>

                    <div class="record-inner-action-col">
                        <div
                            x-data="{
                                openDropdown: false,
                                toggle() {
                                    if (this.openDropdown) {
                                        return this.close();
                                    }
                                    this.$refs.button.focus();
                                    this.openDropdown = true;
                                },
                                close(focusAfter) {
                                    if (! this.openDropdown) return;
                                    this.openDropdown = false;
                                    focusAfter && focusAfter.focus();
                                },
                            }"
                            @keydown.escape.prevent.stop="close($refs.button)"
                            @focusin.window="$refs.panel && ! $refs.panel.contains($event.target) && close()"
                            x-id="['dropdown-button']"
                            class="relative"
                        >
                            @if ($isReadonly ?? false)
                                <a
                                    href="#"
                                    @click.prevent="editSpecimen(index)"
                                    class="record-inner-action-btn cursor-pointer"
                                    title="{{ __('forms.view') }}"
                                >
                                    @icon('eye', 'w-6 h-6')
                                    <span class="sr-only">{{ __('forms.view') }}</span>
                                </a>
                            @else
                                <button
                                    x-ref="button"
                                    @click="toggle()"
                                    :aria-expanded="openDropdown"
                                    :aria-controls="$id('dropdown-button')"
                                    type="button"
                                    class="record-inner-action-btn cursor-pointer"
                                >
                                    <svg class="h-6 w-6 text-gray-800 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="square" stroke-linejoin="round" stroke-width="2" d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z" />
                                    </svg>
                                </button>

                                <div class="absolute right-0 z-50">
                                    <div
                                        x-ref="panel"
                                        x-show="openDropdown"
                                        x-transition.origin.top.left
                                        @click.outside="close($refs.button)"
                                        :id="$id('dropdown-button')"
                                        x-cloak
                                        class="dropdown-panel relative"
                                    >
                                        <button
                                            type="button"
                                            @click.prevent="
                                                editSpecimen(index);
                                                close($refs.button);
                                            "
                                        >
                                            {{ __('forms.edit') }}
                                        </button>

                                        <button
                                            type="button"
                                            class="dropdown-delete"
                                            @click.prevent="
                                                removeSpecimen(index);
                                                close($refs.button);
                                            "
                                        >
                                            {{ __('forms.delete') }}
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="record-inner-body">
                    <div class="record-inner-grid-container">
                        <div class="grid w-full grid-cols-2 gap-x-4 gap-y-4 xl:grid-cols-4">
                            <div>
                                <div class="record-inner-label uppercase">{{ __('forms.status.label') }}</div>
                                <div class="record-inner-subvalue" x-text="statusName(specimen.uuid)"></div>
                            </div>
                            <div>
                                <div class="record-inner-label uppercase">{{ __('specimens.collection') }}</div>
                                <div class="record-inner-subvalue" x-text="collectedAt(specimen)"></div>
                            </div>
                            <div>
                                <div class="record-inner-label uppercase">
                                    {{ __('specimens.containers_and_type') }}
                                </div>
                                <div class="record-inner-subvalue" x-text="containersLabel(specimen)"></div>
                            </div>
                            <div>
                                <div class="record-inner-label uppercase">{{ __('specimens.collector') }}</div>
                                <div
                                    class="record-inner-subvalue"
                                    x-text="specimen.collectorType === 'patient' ? '{{ __('forms.patient') }}' : employeeName(specimen.collectorId)"
                                ></div>
                            </div>
                            <div>
                                <div class="record-inner-label uppercase">{{ __('specimens.parent_specimen') }}</div>
                                <div class="record-inner-subvalue" x-text="parentsLabel(specimen)"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @unless ($isReadonly ?? false)
        <button type="button" @click.prevent="createSpecimen()" class="item-add my-5">
            {{ __('specimens.add_specimen') }}
        </button>
    @endunless

    <x-dialog-drawer x-model="openSpecimenDrawer" maxWidth="4/5" wire:ignore>
        <x-slot name="title">
            <span x-text="newSpecimen ? '{{ __('specimens.new_specimen') }}' : '{{ __('specimens.edit_specimen') }}'"></span>
        </x-slot>

        <form>
            <fieldset @disabled($isReadonly ?? false) @class(['pointer-event-none' => $isReadonly ?? false])>
                <fieldset class="fieldset-card mb-6 p-4 sm:p-8 sm:pb-10">
                    <legend class="legend">{{ __('specimens.general_info') }}</legend>

                    <div class="form-row-2">
                        <div class="form-group group">
                            <select
                                x-model="modalSpecimen.typeCode"
                                id="specimenType"
                                class="input-select peer"
                                required
                            >
                                <option value="" selected>{{ __('forms.select') }}</option>
                                @foreach ($this->dictionaries['specimen_types'] as $code => $specimenType)
                                    <option value="{{ $code }}">{{ $specimenType }}</option>
                                @endforeach
                            </select>
                            <label for="specimenType" class="label">{{ __('specimens.specimen_type') }}</label>
                        </div>

                        <div class="form-group group">
                            <select
                                x-model="modalSpecimen.conditionCode"
                                id="specimenCondition"
                                class="input-select peer"
                            >
                                <option value="" selected>{{ __('forms.select') }}</option>
                                @foreach ($this->dictionaries['specimen_conditions'] as $code => $specimenCondition)
                                    <option value="{{ $code }}">{{ $specimenCondition }}</option>
                                @endforeach
                            </select>
                            <label
                                for="specimenCondition"
                                class="label"
                            >{{ __('specimens.specimen_condition') }}</label>
                        </div>
                    </div>

                    <div class="form-row-2 mt-6">
                        <div class="form-group group">
                            <input
                                type="text"
                                id="specimenStatus"
                                class="input-select peer cursor-not-allowed! text-gray-500! dark:text-gray-400!"
                                :value="statusName(modalSpecimen.uuid)"
                                disabled
                            />
                            <label for="specimenStatus" class="label">{{ __('forms.status.label') }}</label>
                        </div>

                        <div
                            class="form-group group relative flex justify-between"
                            x-show="isReferenced(modalSpecimen.uuid)"
                            x-cloak
                        >
                            <div class="datepicker-wrapper flex-1">
                                <input
                                    x-model="modalSpecimen.receivedDate"
                                    type="text"
                                    id="specimenReceivedDate"
                                    autocomplete="off"
                                    class="datepicker-input with-leading-icon input peer rounded-r-none border-r-0"
                                    placeholder=" "
                                />
                                <label
                                    for="specimenReceivedDate"
                                    class="wrapped-label"
                                >{{ __('specimens.date_time_received') }}</label>
                            </div>
                            <div class="relative -ml-px w-32">
                                <label for="specimenReceivedTime" class="sr-only">{{ __('forms.time') }}</label>
                                <input
                                    x-model="modalSpecimen.receivedTime"
                                    type="time"
                                    id="specimenReceivedTime"
                                    class="input peer rounded-l-none pl-10"
                                    placeholder=" "
                                />
                                @icon('clock', 'svg-input left-2.5 text-gray-400')
                            </div>
                        </div>
                    </div>

                    <div class="form-row-2 mt-6" x-show="isReferenced(modalSpecimen.uuid)" x-cloak>
                        <div class="form-group group">
                            <input
                                type="text"
                                id="specimenStatusReason"
                                class="input-select peer cursor-not-allowed! text-gray-500! dark:text-gray-400!"
                                :value="$wire.dictionaries['specimen_invalidate_reasons']['used']"
                                disabled
                            />
                            <label
                                for="specimenStatusReason"
                                class="label"
                            >{{ __('specimens.unavailability_reason') }}</label>
                        </div>
                    </div>

                    <div class="form-row-2 mt-6">
                        <div class="form-group group">
                            <p class="label-modal mb-2 block">{{ __('specimens.parent_specimen') }}</p>

                            <template x-for="(parentId, parentIndex) in modalSpecimen.parentIds" :key="parentIndex">
                                <div class="mt-4 flex items-center gap-2">
                                    <div class="form-group group relative flex-1">
                                        <label :for="`specimenParent${parentIndex}`" class="sr-only">
                                            {{ __('specimens.parent_specimen') }}
                                        </label>
                                        <select
                                            x-model="modalSpecimen.parentIds[parentIndex]"
                                            :id="`specimenParent${parentIndex}`"
                                            class="input-select peer"
                                        >
                                            <option value="" selected>{{ __('forms.select') }}</option>
                                            <template x-for="parent in parentOptions()" :key="parent.uuid">
                                                <option
                                                    :value="parent.uuid"
                                                    x-text="specimenTypesDictionary[parent.typeCode] || parent.uuid"
                                                ></option>
                                            </template>
                                        </select>
                                    </div>
                                    <button
                                        type="button"
                                        @click="modalSpecimen.parentIds.splice(parentIndex, 1)"
                                        class="mt-2 text-gray-400 transition-colors hover:text-red-500 dark:text-gray-500 dark:hover:text-red-500"
                                    >
                                        @icon('delete', 'w-5 h-5')
                                    </button>
                                </div>
                            </template>

                            <div class="mt-2">
                                <button
                                    type="button"
                                    @click="modalSpecimen.parentIds.push('')"
                                    class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                >
                                    {{ __('specimens.add_parent_specimen') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-row-1 mt-6">
                        <div class="form-group group">
                            <label for="specimenNote" class="label-modal mb-2 block">{{ __('specimens.note') }}</label>
                            <textarea
                                x-model="modalSpecimen.note"
                                id="specimenNote"
                                class="textarea"
                                rows="3"
                                placeholder="{{ __('forms.text_for_input') }}"
                            ></textarea>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="fieldset-card mb-6 p-4 sm:p-8 sm:pb-10">
                    <legend class="legend">{{ __('specimens.material_collection') }}</legend>

                    <div class="form-row-2">
                        <div class="form-group group">
                            <select
                                x-model="modalSpecimen.collectorType"
                                @change="changeCollectorType()"
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

                        <div class="form-group group" x-show="modalSpecimen.collectorType === 'current'" x-cloak>
                            <input
                                type="text"
                                id="collectionCurrentCollector"
                                class="input-select peer cursor-not-allowed! text-gray-500! dark:text-gray-400!"
                                :value="currentEmployee.name"
                                disabled
                            />
                            <label
                                for="collectionCurrentCollector"
                                class="label"
                            >{{ __('specimens.current_employee') }}</label>
                        </div>

                        <div class="form-group group" x-show="modalSpecimen.collectorType === 'other'" x-cloak>
                            <select
                                x-model="modalSpecimen.collectorId"
                                id="collectionOtherCollector"
                                class="input-select peer"
                            >
                                <option value="" selected>{{ __('forms.select') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee['uuid'] }}">{{ $employee['name'] }}</option>
                                @endforeach
                            </select>
                            <label
                                for="collectionOtherCollector"
                                class="label"
                            >{{ __('specimens.select_other_employee') }}</label>
                        </div>
                    </div>

                    <div class="mt-6 mb-4 flex flex-wrap items-center gap-8">
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('specimens.when_done') }}</span>

                        <div class="flex items-center">
                            <input
                                x-model="modalSpecimen.collectedType"
                                id="specimenCollectedTypeDateTime"
                                type="radio"
                                value="date_time"
                                name="specimenCollectedType"
                                class="default-radio"
                            />
                            <label
                                for="specimenCollectedTypeDateTime"
                                class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                            >
                                {{ __('specimens.exact_date_and_time') }}
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input
                                x-model="modalSpecimen.collectedType"
                                id="specimenCollectedTypePeriod"
                                type="radio"
                                value="period"
                                name="specimenCollectedType"
                                class="default-radio"
                            />
                            <label
                                for="specimenCollectedTypePeriod"
                                class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                            >
                                {{ __('specimens.period') }}
                            </label>
                        </div>
                    </div>

                    <div class="form-row-2" x-show="modalSpecimen.collectedType === 'date_time'" x-cloak>
                        <div class="form-group group relative flex justify-between">
                            <div class="datepicker-wrapper flex-1">
                                <input
                                    x-model="modalSpecimen.collectedDate"
                                    :datepicker-max-date="$wire.form.encounter.periodDate"
                                    type="text"
                                    id="specimenCollectedDate"
                                    autocomplete="off"
                                    class="datepicker-input with-leading-icon input peer rounded-r-none border-r-0"
                                    placeholder=" "
                                    :required="modalSpecimen.collectedType === 'date_time'"
                                />
                                <label
                                    for="specimenCollectedDate"
                                    class="wrapped-label"
                                >{{ __('specimens.date_time') }}</label>
                            </div>
                            <div class="relative -ml-px w-32">
                                <label for="specimenCollectedTime" class="sr-only">{{ __('forms.time') }}</label>
                                <input
                                    x-model="modalSpecimen.collectedTime"
                                    type="time"
                                    id="specimenCollectedTime"
                                    class="input peer rounded-l-none pl-10"
                                    placeholder=" "
                                    :required="modalSpecimen.collectedType === 'date_time'"
                                />
                                @icon('clock', 'svg-input left-2.5 text-gray-400')
                            </div>
                        </div>
                    </div>

                    <div class="form-row-3" x-show="modalSpecimen.collectedType === 'period'" x-cloak>
                        <div class="form-group group relative">
                            <input
                                x-model="modalSpecimen.collectedPeriodRange"
                                type="text"
                                id="specimenCollectedPeriodRange"
                                class="daterangepicker-uk input peer"
                                autocomplete="off"
                                placeholder=" "
                                :required="modalSpecimen.collectedType === 'period'"
                            />
                            <label for="specimenCollectedPeriodRange" class="label">{{ __('specimens.period') }}</label>
                        </div>

                        <div class="form-group group relative">
                            <input
                                x-model="modalSpecimen.collectedPeriodStartTime"
                                type="time"
                                id="specimenCollectedPeriodStartTime"
                                class="input peer"
                                placeholder=" "
                                :required="modalSpecimen.collectedType === 'period'"
                            />
                            <label
                                for="specimenCollectedPeriodStartTime"
                                class="label"
                            >{{ __('specimens.period_start') }}</label>
                        </div>

                        <div class="form-group group relative">
                            <input
                                x-model="modalSpecimen.collectedPeriodEndTime"
                                type="time"
                                id="specimenCollectedPeriodEndTime"
                                class="input peer"
                                placeholder=" "
                            />
                            <label
                                for="specimenCollectedPeriodEndTime"
                                class="label"
                            >{{ __('specimens.period_end') }}</label>
                        </div>
                    </div>

                    <div class="form-row-2 mt-6">
                        <div class="form-group group flex items-start gap-2">
                            <div class="relative flex-1">
                                <input
                                    x-model="modalSpecimen.durationValue"
                                    type="number"
                                    min="0"
                                    step="any"
                                    id="specimenDurationValue"
                                    class="input peer"
                                    placeholder=" "
                                />
                                <label
                                    for="specimenDurationValue"
                                    class="label"
                                >{{ __('specimens.collection_duration') }}</label>
                            </div>
                            <div class="w-40">
                                <label for="specimenDurationCode" class="sr-only">{{ __('specimens.unit') }}</label>
                                <select
                                    x-model="modalSpecimen.durationCode"
                                    id="specimenDurationCode"
                                    class="input-select peer"
                                >
                                    <option value="" selected>{{ __('forms.select') }}</option>
                                    @foreach (config('ehealth.specimen_duration_allowed_codes') as $code)
                                        <option value="{{ $code }}">
                                            {{ $this->dictionaries['eHealth/ucum/units'][$code] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group group flex items-start gap-2">
                            <div class="relative flex-1">
                                <input
                                    x-model="modalSpecimen.quantityValue"
                                    type="number"
                                    min="0"
                                    step="any"
                                    id="specimenQuantityValue"
                                    class="input peer"
                                    placeholder=" "
                                />
                                <label
                                    for="specimenQuantityValue"
                                    class="label"
                                >{{ __('specimens.material_amount') }}</label>
                            </div>
                            <div class="w-40">
                                <label for="specimenQuantityCode" class="sr-only">{{ __('specimens.unit') }}</label>
                                <select
                                    x-model="modalSpecimen.quantityCode"
                                    id="specimenQuantityCode"
                                    class="input-select peer"
                                >
                                    <option value="" selected>{{ __('forms.select') }}</option>
                                    @foreach ($this->dictionaries['eHealth/ucum/units'] as $code => $unit)
                                        <option value="{{ $code }}">{{ $unit }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row-2 mt-6">
                        <div class="form-group group">
                            <select x-model="modalSpecimen.methodCode" id="collectionMethod" class="input-select peer">
                                <option value="" selected>{{ __('forms.select') }}</option>
                                @foreach ($this->dictionaries['specimen_collection_methods'] as $code => $collectionMethod)
                                    <option value="{{ $code }}">{{ $collectionMethod }}</option>
                                @endforeach
                            </select>
                            <label for="collectionMethod" class="label">{{ __('specimens.collection_method') }}</label>
                        </div>

                        <div class="form-group group">
                            <select
                                x-model="modalSpecimen.bodySiteCode"
                                id="collectionBodySite"
                                class="input-select peer"
                            >
                                <option value="" selected>{{ __('forms.select') }}</option>
                                @foreach ($this->dictionaries['eHealth/body_sites'] as $code => $bodySite)
                                    <option value="{{ $code }}">{{ $bodySite }}</option>
                                @endforeach
                            </select>
                            <label for="collectionBodySite" class="label">{{ __('specimens.body_site') }}</label>
                        </div>
                    </div>

                    <div class="form-row-2 mt-6">
                        <div class="form-group group">
                            <select
                                x-model="modalSpecimen.fastingStatusCode"
                                id="collectionFastingStatus"
                                class="input-select peer"
                            >
                                <option value="" selected>{{ __('forms.select') }}</option>
                                @foreach ($this->dictionaries['fasting_statuses'] as $code => $fastingStatus)
                                    <option value="{{ $code }}">{{ $fastingStatus }}</option>
                                @endforeach
                            </select>
                            <label
                                for="collectionFastingStatus"
                                class="label"
                            >{{ __('specimens.fasting_status') }}</label>
                        </div>

                        <div class="form-group group">
                            <select
                                x-model="modalSpecimen.procedureId"
                                id="collectionProcedure"
                                class="input-select peer"
                            >
                                <option value="" selected>{{ __('forms.select') }}</option>
                                <template x-for="procedure in procedures" :key="procedure.uuid">
                                    <option :value="procedure.uuid" x-text="procedureLabel(procedure)"></option>
                                </template>
                            </select>
                            <label
                                for="collectionProcedure"
                                class="label"
                            >{{ __('specimens.procedure_during_collection') }}</label>
                        </div>
                    </div>
                </fieldset>

                <template x-for="(container, containerIndex) in modalSpecimen.containers" :key="containerIndex">
                    <fieldset class="fieldset-card relative mb-6 p-4 sm:p-8 sm:pb-10">
                        <legend
                            class="legend"
                            x-text="`{{ __('specimens.container_number') }}${containerIndex + 1}`"
                        ></legend>

                        <template x-if="containerIndex > 0">
                            <button
                                type="button"
                                @click="modalSpecimen.containers.splice(containerIndex, 1)"
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
                                    :id="`containerIdentifier${containerIndex}`"
                                    class="input peer"
                                    placeholder=" "
                                    required
                                />
                                <label
                                    :for="`containerIdentifier${containerIndex}`"
                                    class="label"
                                >{{ __('specimens.identifier') }}</label>
                            </div>

                            <div class="form-group group relative">
                                <input
                                    x-model="container.description"
                                    type="text"
                                    :id="`containerDescription${containerIndex}`"
                                    class="input peer"
                                    placeholder=" "
                                />
                                <label
                                    :for="`containerDescription${containerIndex}`"
                                    class="label"
                                >{{ __('specimens.container_description') }}</label>
                            </div>
                        </div>

                        <div class="form-row-2 mt-6">
                            <div class="form-group group">
                                <select
                                    x-model="container.typeCode"
                                    :id="`containerType${containerIndex}`"
                                    class="input-select peer"
                                >
                                    <option value="" selected>{{ __('forms.select') }}</option>
                                    @foreach ($this->dictionaries['specimen_container_types'] as $code => $containerType)
                                        <option value="{{ $code }}">{{ $containerType }}</option>
                                    @endforeach
                                </select>
                                <label
                                    :for="`containerType${containerIndex}`"
                                    class="label"
                                >{{ __('specimens.container_type') }}</label>
                            </div>

                            <div class="form-group group">
                                <select
                                    x-model="container.additiveCode"
                                    :id="`containerAdditive${containerIndex}`"
                                    class="input-select peer"
                                >
                                    <option value="" selected>{{ __('forms.select') }}</option>
                                    @foreach ($this->dictionaries['specimen_container_additives'] as $code => $containerAdditive)
                                        <option value="{{ $code }}">{{ $containerAdditive }}</option>
                                    @endforeach
                                </select>
                                <label
                                    :for="`containerAdditive${containerIndex}`"
                                    class="label"
                                >{{ __('specimens.additive') }}</label>
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
                                        :id="`containerCapacityValue${containerIndex}`"
                                        class="input peer"
                                        placeholder=" "
                                    />
                                    <label
                                        :for="`containerCapacityValue${containerIndex}`"
                                        class="label"
                                    >{{ __('specimens.container_volume') }}</label>
                                </div>
                                <div class="w-40">
                                    <label :for="`containerCapacityCode${containerIndex}`" class="sr-only">
                                        {{ __('specimens.unit') }}
                                    </label>
                                    <select
                                        x-model="container.capacityCode"
                                        :id="`containerCapacityCode${containerIndex}`"
                                        class="input-select peer"
                                    >
                                        <option value="" selected>{{ __('forms.select') }}</option>
                                        @foreach ($this->dictionaries['eHealth/ucum/units'] as $code => $unit)
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
                                        :id="`containerSpecimenQuantityValue${containerIndex}`"
                                        class="input peer"
                                        placeholder=" "
                                    />
                                    <label
                                        :for="`containerSpecimenQuantityValue${containerIndex}`"
                                        class="label"
                                    >{{ __('specimens.biomaterial_amount_in_container') }}</label>
                                </div>
                                <div class="w-40">
                                    <label :for="`containerSpecimenQuantityCode${containerIndex}`" class="sr-only">
                                        {{ __('specimens.unit') }}
                                    </label>
                                    <select
                                        x-model="container.specimenQuantityCode"
                                        :id="`containerSpecimenQuantityCode${containerIndex}`"
                                        class="input-select peer"
                                    >
                                        <option value="" selected>{{ __('forms.select') }}</option>
                                        @foreach ($this->dictionaries['eHealth/ucum/units'] as $code => $unit)
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
                        @click="modalSpecimen.containers.push(new SpecimenContainer())"
                        class="text-sm font-medium text-blue-600 hover:text-blue-800"
                    >
                        {{ __('specimens.add_container') }}
                    </button>
                </div>

                <div class="mt-8 flex w-full justify-start space-x-4">
                    <button type="button" @click="openSpecimenDrawer = false" class="button-minor">
                        {{ __('forms.cancel') }}
                    </button>
                    @unless ($isReadonly ?? false)
                        <button
                            type="button"
                            @click="saveSpecimen()"
                            class="button-primary"
                            :disabled="! canSaveSpecimen()"
                        >
                            <span x-text="newSpecimen ? '{{ __('specimens.add_specimen_btn') }}' : '{{ __('forms.save') }}'"></span>
                        </button>
                    @endunless
                </div>
            </fieldset>
        </form>
    </x-dialog-drawer>
</div>

<script>
    class SpecimenContainer {
        constructor(obj = null) {
            this.identifier = '';
            this.description = '';
            this.typeCode = '';
            this.capacityValue = '';
            this.capacityCode = '';
            this.specimenQuantityValue = '';
            this.specimenQuantityCode = '';
            this.additiveCode = '';

            if (obj) {
                Object.assign(this, JSON.parse(JSON.stringify(obj)));
            }
        }
    }

    class Specimen {
        constructor(obj = null) {
            this.uuid = crypto.randomUUID();
            this.typeCode = '';
            this.conditionCode = '';
            this.receivedDate = '';
            this.receivedTime = '';
            this.note = '';
            this.parentIds = [];
            this.collectorType = 'current';
            this.collectorId = '';
            this.collectedType = 'date_time';
            this.collectedDate = '';
            this.collectedTime = '';
            this.collectedPeriodRange = '';
            this.collectedPeriodStartTime = '';
            this.collectedPeriodEndTime = '';
            this.durationValue = '';
            this.durationCode = '';
            this.quantityValue = '';
            this.quantityCode = '';
            this.methodCode = '';
            this.bodySiteCode = '';
            this.fastingStatusCode = '';
            this.procedureId = '';
            this.containers = [new SpecimenContainer()];

            if (obj) {
                Object.assign(this, JSON.parse(JSON.stringify(obj)));
            }
        }
    }
</script>
