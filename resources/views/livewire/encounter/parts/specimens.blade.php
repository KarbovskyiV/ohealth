<div
    class="p-4 sm:p-8"
    id="specimens-section"
    x-data="{
        openSpecimenDrawer: false,
    }"
>
    <div class="space-y-4">
        <div class="record-inner-card">
            <div class="record-inner-header">
                <div class="record-inner-checkbox-col">
                    <label for="specimenRecord" class="sr-only">{{ __('forms.select') }}</label>
                    <input type="checkbox" id="specimenRecord" class="default-checkbox h-5 w-5" />
                </div>

                <div class="record-inner-column flex-1">
                    <div class="record-inner-label">{{ __('specimens.specimen_number') }}</div>
                    <div class="record-inner-value text-[16px]"></div>
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
                                @click.prevent="openSpecimenDrawer = true"
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
                                            openSpecimenDrawer = true;
                                            close($refs.button);
                                        "
                                    >
                                        {{ __('forms.edit') }}
                                    </button>

                                    <button type="button" class="dropdown-delete" @click.prevent="close($refs.button)">
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
                            <div class="record-inner-label uppercase">{{ __('specimens.status') }}</div>
                            <div class="record-inner-subvalue">{{ __('specimens.available') }}</div>
                        </div>
                        <div>
                            <div class="record-inner-label uppercase">{{ __('specimens.collection') }}</div>
                            <div class="record-inner-subvalue"></div>
                        </div>
                        <div>
                            <div class="record-inner-label uppercase">{{ __('specimens.electronic_referral') }}</div>
                            <div class="record-inner-subvalue"></div>
                        </div>
                        <div>
                            <div class="record-inner-label uppercase">{{ __('specimens.containers_and_type') }}</div>
                            <div class="record-inner-subvalue"></div>
                        </div>
                        <div>
                            <div class="record-inner-label uppercase">{{ __('specimens.created_by') }}</div>
                            <div class="record-inner-subvalue"></div>
                        </div>
                        <div>
                            <div class="record-inner-label uppercase">{{ __('specimens.parent_specimen') }}</div>
                            <div class="record-inner-subvalue"></div>
                        </div>
                        <div>
                            <div class="record-inner-label uppercase">{{ __('specimens.encounter') }}</div>
                            <div class="record-inner-subvalue"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div>
        @unless ($isReadonly ?? false)
            <button
                type="button"
                @click.prevent="openSpecimenDrawer = true"
                class="item-add my-5 mt-5 flex cursor-pointer items-center gap-1.5 text-sm font-medium text-blue-600 transition-colors hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
            >
                {{ __('specimens.add_specimen') }}
            </button>
        @endunless
    </div>

    <x-dialog-drawer x-model="openSpecimenDrawer" maxWidth="4/5" wire:ignore>
        <x-slot name="title">{{ __('specimens.new_specimen') }}</x-slot>

        <form>
            <fieldset @disabled($isReadonly ?? false) @class(['pointer-event-none' => $isReadonly ?? false])>

                <fieldset class="fieldset-card mb-6 p-4 sm:p-8 sm:pb-10">
                    <legend class="legend">{{ __('specimens.general_info') }}</legend>

                    <div class="form-row-2">
                        <div class="form-group group" x-data="{ fields: [] }">
                            <select id="specimenType" class="input-select peer" required>
                                <option value="" disabled selected hidden></option>
                                <option value="" selected>specimen_types</option>
                            </select>
                            <label for="specimenType" class="label">{{ __('specimens.specimen_type') }}</label>

                            <template x-for="(field, index) in fields" :key="index">
                                <div class="mt-4 flex items-center gap-2">
                                    <div class="form-group group relative flex-1">
                                        <input type="text" class="input peer" placeholder=" ">
                                        <label class="label">{{ __('specimens.clarification') }}</label>
                                    </div>
                                    <button type="button" @click="fields.splice(index, 1)" class="text-gray-400 transition-colors hover:text-red-500 dark:text-gray-500 dark:hover:text-red-500 mt-2">
                                        @icon('delete', 'w-5 h-5')
                                    </button>
                                </div>
                            </template>

                            <div class="mt-2" x-show="fields.length === 0">
                                <button type="button" @click="fields.push('')" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('specimens.add_clarification') }}</button>
                            </div>
                        </div>
                        <div class="form-group group" x-data="{ fields: [] }">
                            <select id="specimenCondition" class="input-select peer">
                                <option value="" disabled selected hidden></option>
                                <option value="" selected>condition</option>
                            </select>
                            <label for="specimenCondition" class="label">{{ __('specimens.specimen_condition') }}</label>

                            <template x-for="(field, index) in fields" :key="index">
                                <div class="mt-4 flex items-center gap-2">
                                    <div class="form-group group relative flex-1">
                                        <input type="text" class="input peer" x-model="field.val" placeholder=" ">
                                        <label class="label">{{ __('specimens.clarification') }}</label>
                                    </div>
                                    <button type="button" @click="fields.splice(index, 1)" class="text-gray-400 transition-colors hover:text-red-500 dark:text-gray-500 dark:hover:text-red-500 mt-2">
                                        @icon('delete', 'w-5 h-5')
                                    </button>
                                </div>
                            </template>

                            <div class="mt-2" x-show="fields.length === 0">
                                <button type="button" @click="fields.push({val: ''})" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('specimens.add_clarification') }}</button>
                            </div>
                        </div>
                    </div>

                    <div class="form-row-2 mt-6">
                        <div class="form-group group relative">
                            <input type="text" id="specimenId" class="input peer" value="" placeholder=" " required />
                            <label for="specimenId" class="label">{{ __('specimens.identifier') }}</label>
                        </div>
                        <div class="form-group group relative flex justify-between">
                            <div class="datepicker-wrapper flex-1">
                                <input type="text" id="specimenDate" class="datepicker-input with-leading-icon input peer rounded-r-none border-r-0" placeholder=" " value="" />
                                <label for="specimenDate" class="wrapped-label">{{ __('specimens.date_time_received') }}</label>
                            </div>
                            <div class="relative -ml-px w-32">
                                <label for="specimenTime" class="sr-only">{{ __('specimens.time') }}</label>
                                <input type="text" id="specimenTime" class="input peer rounded-l-none pl-10" placeholder=" " value="" />
                                @icon('clock', 'svg-input left-2.5 text-gray-400')
                            </div>
                        </div>
                    </div>

                    <div class="form-row-2 mt-6">
                        <div class="form-group group">
                            <select id="specimenStatus" class="input-select peer" required>
                                <option value="" disabled selected hidden></option>
                                <option value="1" selected>{{ __('specimens.available') }}</option>
                            </select>
                            <label for="specimenStatus" class="label">{{ __('specimens.status') }}</label>
                        </div>
                        <div class="form-group group">
                            <select id="specimenInvalidReason" class="input-select peer" required>
                                <option value="" disabled selected hidden></option>
                                <option value="1" selected>specimen_invalidate_reasons</option>
                            </select>
                            <label for="specimenInvalidReason" class="label">{{ __('specimens.unavailability_reason') }}</label>
                        </div>
                    </div>

                    <div class="form-row-2 mt-6">
                        <div class="form-group group" x-data="{ fields: [] }">
                            <select id="specimenRequest" class="input-select peer">
                                <option value="" disabled selected hidden></option>
                                <option value="" selected>request</option>
                            </select>
                            <label for="specimenRequest" class="label">{{ __('specimens.electronic_referral') }}</label>

                            <template x-for="(field, index) in fields" :key="index">
                                <div class="mt-4 flex items-center gap-2">
                                    <div class="form-group group relative flex-1">
                                        <select class="input-select peer">
                                            <option value="" disabled selected hidden></option>
                                            <option value="">request</option>
                                        </select>
                                        <label class="label">{{ __('specimens.electronic_referral') }}</label>
                                    </div>
                                    <button type="button" @click="fields.splice(index, 1)" class="text-gray-400 transition-colors hover:text-red-500 dark:text-gray-500 dark:hover:text-red-500 mt-2">
                                        @icon('delete', 'w-5 h-5')
                                    </button>
                                </div>
                            </template>

                            <div class="mt-2" x-show="fields.length === 0">
                                <button type="button" @click="fields.push('')" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('specimens.add_electronic_referral') }}</button>
                            </div>
                        </div>
                        <div class="form-group group" x-data="{ fields: [] }">
                            <select id="specimenParent" class="input-select peer">
                                <option value="" disabled selected hidden></option>
                                <option value="" selected>parent</option>
                            </select>
                            <label for="specimenParent" class="label">{{ __('specimens.parent_specimen') }}</label>

                            <template x-for="(field, index) in fields" :key="index">
                                <div class="mt-4 flex items-center gap-2">
                                    <div class="form-group group relative flex-1">
                                        <select class="input-select peer">
                                            <option value="" disabled selected hidden></option>
                                            <option value="">parent</option>
                                        </select>
                                        <label class="label">{{ __('specimens.parent_specimen') }}</label>
                                    </div>
                                    <button type="button" @click="fields.splice(index, 1)" class="text-gray-400 transition-colors hover:text-red-500 dark:text-gray-500 dark:hover:text-red-500 mt-2">
                                        @icon('delete', 'w-5 h-5')
                                    </button>
                                </div>
                            </template>

                            <div class="mt-2" x-show="fields.length === 0">
                                <button type="button" @click="fields.push('')" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('specimens.add_parent_specimen') }}</button>
                            </div>
                        </div>
                    </div>

                    <div class="form-row-1 mt-6">
                        <div class="form-group group">
                            <label for="specimenNote" class="label-modal mb-2 block">{{ __('specimens.note') }}</label>
                            <textarea id="specimenNote" class="textarea" rows="3" placeholder=""></textarea>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="fieldset-card mb-6 p-4 sm:p-8 sm:pb-10" x-data="{ timeType: 'period' }">
                    <legend class="legend">{{ __('specimens.material_collection') }}</legend>

                    <div class="form-row-2">
                        <div class="form-group group">
                            <select id="collectionCollector" class="input-select peer" required>
                                <option value="" disabled selected hidden></option>
                                <option value="current" selected>{{ __('specimens.current_employee') }}</option>
                                <option value="other">{{ __('specimens.other_employee') }}</option>
                                <option value="patient">{{ __('specimens.patient') }}</option>
                            </select>
                            <label for="collectionCollector" class="label">{{ __('specimens.collector') }}</label>
                        </div>
                        <div class="form-group group">
                            <select id="collectionOtherCollector" class="input-select peer">
                                <option value="" disabled selected hidden></option>
                                <option value="" selected>collector</option>
                            </select>
                            <label for="collectionOtherCollector" class="label">{{ __('specimens.select_other_employee') }}</label>
                        </div>
                    </div>

                    <div class="form-row-2 mt-6">
                        <div class="form-group group">
                            <div class="mb-4 flex gap-6">
                                <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('specimens.when_done') }}</span>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" x-model="timeType" value="exact" name="when_done" class="default-radio h-4 w-4" />
                                    {{ __('specimens.exact_date_and_time') }}
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" x-model="timeType" value="period" name="when_done" class="default-radio h-4 w-4" />
                                    {{ __('specimens.period') }}
                                </label>
                            </div>
                            <div class="relative" x-show="timeType === 'period'" x-cloak>
                                <div class="datepicker-wrapper">
                                    <input type="text" id="specimenPeriodDate" class="datepicker-input with-leading-icon input peer" value="" placeholder=" " />
                                    <label for="specimenPeriodDate" class="wrapped-label">{{ __('specimens.date_time') }}</label>
                                </div>
                            </div>
                            <div class="relative flex justify-between" x-show="timeType === 'exact'" x-cloak>
                                <div class="datepicker-wrapper flex-1">
                                    <input type="text" id="specimenExactDate" class="datepicker-input with-leading-icon input peer rounded-r-none border-r-0" placeholder=" " value="" />
                                    <label for="specimenExactDate" class="wrapped-label">{{ __('specimens.date_time') }}</label>
                                </div>
                                <div class="relative -ml-px w-32">
                                    <label class="sr-only">{{ __('specimens.time') }}</label>
                                    <input type="text" class="input peer rounded-l-none pl-10" placeholder=" " value="" />
                                    @icon('clock', 'svg-input left-2.5 text-gray-400')
                                </div>
                            </div>
                        </div>
                        <div class="form-group group flex items-end gap-2" x-show="timeType === 'period'" x-cloak>
                            <div class="relative flex-1">
                                <input type="text" class="input peer" value="" placeholder=" " />
                                <label class="label">{{ __('specimens.collection_duration') }}</label>
                            </div>
                            <div class="w-32">
                                <select class="input-select peer">
                                    <option value="min" selected>{{ __('specimens.minutes') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div x-show="timeType === 'period'" x-cloak>
                        <div class="form-row-2 mt-6">
                            <div class="form-group group">
                                <select id="collectionMethod" class="input-select peer">
                                    <option value="" disabled selected hidden></option>
                                    <option value="1" selected>specimen_collection_methods</option>
                                </select>
                                <label for="collectionMethod" class="label">{{ __('specimens.collection_method') }}</label>
                            </div>
                            <div class="form-group group flex items-start gap-2">
                                <div class="relative flex-1">
                                    <input type="text" class="input peer" value="" placeholder=" " />
                                    <label class="label">{{ __('specimens.material_amount') }}</label>
                                </div>
                                <div class="w-32">
                                    <select class="input-select peer">
                                        <option value="ml" selected>{{ __('specimens.ml') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-row-2 mt-6">
                            <div class="form-group group">
                                <select id="collectionBodySite" class="input-select peer">
                                    <option value="" disabled selected hidden></option>
                                    <option value="1" selected>body_site</option>
                                </select>
                                <label for="collectionBodySite" class="label">{{ __('specimens.body_site') }}</label>
                            </div>
                            <div class="form-group group">
                                <select id="collectionFastingStatus" class="input-select peer">
                                    <option value="" disabled selected hidden></option>
                                    <option value="1" selected>fasting_statuses</option>
                                </select>
                                <label for="collectionFastingStatus" class="label">{{ __('specimens.fasting_status') }}</label>
                            </div>
                        </div>

                        <div class="form-row-2 mt-6">
                            <div class="form-group group">
                                <select id="collectionProcedure" class="input-select peer">
                                    <option value="" disabled selected hidden></option>
                                    <option value="1" selected>collection.procedure</option>
                                </select>
                                <label for="collectionProcedure" class="label">{{ __('specimens.procedure_during_collection') }}</label>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <div x-data="{ containers: [{ id: Date.now() }] }">
                    <template x-for="(container, index) in containers" :key="container.id">
                        <fieldset class="fieldset-card mb-6 p-4 sm:p-8 sm:pb-10 relative">
                            <legend class="legend" x-text="`{{ __('specimens.container_number') }}${index + 1}`"></legend>
                            <template x-if="index > 0">
                                <button type="button" @click="containers.splice(index, 1)" class="absolute -top-5 right-4 sm:right-8 bg-white px-2 text-gray-400 transition-colors hover:text-red-500 dark:bg-slate-900 dark:text-gray-500 dark:hover:text-red-500">
                                    @icon('delete', 'w-6 h-6')
                                </button>
                            </template>

                            <div class="form-row-2">
                                <div class="form-group group relative">
                                    <input type="text" class="input peer" value="" placeholder=" " required />
                                    <label class="label">{{ __('specimens.identifier') }}</label>
                                </div>
                                <div class="form-group group" x-data="{ fields: [] }">
                                    <select class="input-select peer" required>
                                        <option value="" disabled selected hidden></option>
                                        <option value="1" selected>container.type.coding</option>
                                    </select>
                                    <label class="label">{{ __('specimens.container_type') }}</label>

                                    <template x-for="(field, fieldIndex) in fields" :key="fieldIndex">
                                        <div class="mt-4 flex items-center gap-2">
                                            <div class="form-group group relative flex-1">
                                                <input type="text" class="input peer" placeholder=" ">
                                                <label class="label">{{ __('specimens.clarification') }}</label>
                                            </div>
                                            <button type="button" @click="fields.splice(fieldIndex, 1)" class="text-gray-400 transition-colors hover:text-red-500 dark:text-gray-500 dark:hover:text-red-500 mt-2">
                                                @icon('delete', 'w-5 h-5')
                                            </button>
                                        </div>
                                    </template>

                                    <div class="mt-2" x-show="fields.length === 0">
                                        <button type="button" @click="fields.push('')" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('specimens.add_clarification') }}</button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row-2 mt-6">
                                <div class="form-group group relative">
                                    <input type="text" class="input peer" value="" placeholder=" " />
                                    <label class="label">{{ __('specimens.container_description') }}</label>
                                </div>
                                <div class="form-group group flex items-start gap-2">
                                    <div class="relative flex-1">
                                        <input type="text" class="input peer" value="" placeholder=" " required />
                                        <label class="label">{{ __('specimens.container_volume') }}</label>
                                    </div>
                                    <div class="w-32">
                                        <select class="input-select peer">
                                            <option value="ml" selected>{{ __('specimens.ml') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row-2 mt-6">
                                <div class="form-group group" x-data="{ fields: [] }">
                                    <select class="input-select peer">
                                        <option value="" disabled selected hidden></option>
                                        <option value="1" selected>container.additive_codeable_concept.coding</option>
                                    </select>
                                    <label class="label">{{ __('specimens.additive') }}</label>

                                    <template x-for="(field, fieldIndex) in fields" :key="fieldIndex">
                                        <div class="mt-4 flex items-center gap-2">
                                            <div class="form-group group relative flex-1">
                                                <input type="text" class="input peer" placeholder=" ">
                                                <label class="label">{{ __('specimens.clarification') }}</label>
                                            </div>
                                            <button type="button" @click="fields.splice(fieldIndex, 1)" class="text-gray-400 transition-colors hover:text-red-500 dark:text-gray-500 dark:hover:text-red-500 mt-2">
                                                @icon('delete', 'w-5 h-5')
                                            </button>
                                        </div>
                                    </template>

                                    <div class="mt-2" x-show="fields.length === 0">
                                        <button type="button" @click="fields.push('')" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('specimens.add_clarification') }}</button>
                                    </div>
                                </div>
                                <div class="form-group group flex items-start gap-2">
                                    <div class="relative flex-1">
                                        <input type="text" class="input peer" value="" placeholder=" " required />
                                        <label class="label">{{ __('specimens.biomaterial_amount_in_container') }}</label>
                                    </div>
                                    <div class="w-32">
                                        <select class="input-select peer">
                                            <option value="ml" selected>{{ __('specimens.ml') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </template>

                    <div class="mb-6">
                        <button type="button" @click="containers.push({ id: Date.now() })" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('specimens.add_container') }}</button>
                    </div>
                </div>

                <div class="mt-8 flex w-full justify-start space-x-4">
                    <button type="button" @click="openSpecimenDrawer = false" class="button-minor">
                        {{ __('specimens.cancel') }}
                    </button>
                    @unless ($isReadonly ?? false)
                        <button type="button" @click="openSpecimenDrawer = false" class="button-primary">
                            {{ __('specimens.add_specimen_btn') }}
                        </button>
                    @endunless
                </div>
            </fieldset>
        </form>
    </x-dialog-drawer>
</div>

