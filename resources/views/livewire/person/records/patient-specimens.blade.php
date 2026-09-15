<x-layouts.patient :personId="$personId" :prepersonId="$prepersonId" :patientFullName="$patientFullName" activeTab="specimens">
    <x-slot name="headerActions">
        <a href="#" class="button-primary">
            {{ __('specimens.new_specimen') }}
        </a>
        <button type="button" class="button-primary-outline px-5 py-2 text-sm whitespace-nowrap">
            {{ __('patients.data_access') }}
        </button>
        <button type="button" class="button-sync flex items-center gap-2 px-5 py-2 text-sm shadow-sm transition-colors whitespace-nowrap">
            @icon('refresh', 'w-4 h-4')
            {{ __('forms.synchronise_with_eHealth') }}
        </button>
    </x-slot>

    <div class="breadcrumb-form shift-content p-4">
        <div class="mt-6 w-full" x-data="{ showAdditionalParams: $wire.entangle('showAdditionalParams') }">
            <div class="mb-4 flex items-center gap-1 font-semibold text-gray-900 dark:text-gray-100">
                @icon('search-outline', 'w-4.5 h-4.5')
                <p>{{ __('specimens.search_specimens') }}</p>
            </div>

            <div class="form-row-3 mb-6">
                <div class="form-group group">
                    <select id="filterStatus" wire:model="filterStatus" class="input-select peer w-full">
                        <option value="">{{ __('specimens.available') }}</option>
                    </select>
                    <label class="label" for="filterStatus">{{ __('specimens.status') }}</label>
                </div>
                <div class="form-group group">
                    <select id="filterSpecimenType" wire:model="filterSpecimenType" class="input-select peer w-full">
                        <option value="">Кров</option>
                    </select>
                    <label class="label" for="filterSpecimenType">{{ __('specimens.specimen_type') }}</label>
                </div>
                <div class="form-group group">
                    <div class="datepicker-wrapper">
                        <input id="filterDateRange" type="text" class="daterangepicker-uk with-leading-icon input peer w-full" placeholder=" " autocomplete="off" wire:model="filterDateRange" value="02.02.2026 - 10.02.2026" />
                        <label class="wrapped-label" for="filterDateRange">{{ __('specimens.collection_date_range') }}</label>
                    </div>
                </div>
            </div>

            <div class="mb-9 flex flex-wrap justify-between gap-4">
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="button-primary flex items-center gap-2 px-5 py-2.5 text-sm shadow-sm">
                        @icon('search', 'w-4 h-4')
                        <span>{{ __('forms.search') }}</span>
                    </button>
                    <button type="button" class="button-primary-outline-red px-5 py-2.5 text-sm">
                        {{ __('patients.reset_filters') }}
                    </button>
                    <button type="button" class="button-minor flex items-center gap-2 px-5 py-2.5 text-sm whitespace-nowrap" @click.prevent="showAdditionalParams = !showAdditionalParams">
                        @icon('adjustments', 'w-4 h-4 text-gray-500')
                        <span>{{ __('forms.additional_search_parameters') }}</span>
                    </button>
                </div>
                <div class="relative" x-data="{ openGroupActions: false }"
                     @keydown.escape.prevent.stop="openGroupActions = false"
                     @click.outside="openGroupActions = false">
                    <button type="button"
                            @click="openGroupActions = !openGroupActions"
                            class="button-primary-outline px-5 py-2.5 text-sm">
                        {{ __('specimens.group_actions') }}
                    </button>

                    <div x-show="openGroupActions"
                         x-transition
                         x-cloak
                         class="absolute right-0 top-full mt-2 z-10 w-60 bg-white rounded-lg shadow-lg border border-gray-200 dark:bg-gray-700 dark:border-gray-600 overflow-hidden"
                    >
                        <div class="py-1">
                            <button type="button"
                                    @click="openGroupActions = false"
                                    class="dropdown-button !flex items-center gap-2.5 w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors text-left"
                            >
                            <span class="text-gray-500">
                                @icon('close', 'w-4 h-4')
                            </span>
                                {{ __('patients.revoke_access') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="showAdditionalParams" x-transition x-cloak>
                <div class="form-row-3 mb-6">
                    <div class="form-group group">
                        <input id="filterEmployee" type="text" class="input peer" wire:model="filterEmployee" placeholder=" " autocomplete="off" value="Шевченко" />
                        <label class="label" for="filterEmployee">{{ __('specimens.created_by_employee') }}</label>
                    </div>
                    <div class="form-group group">
                        <input id="filterContainerId" type="text" class="input peer" wire:model="filterContainerId" placeholder=" " autocomplete="off" value="1231-adsadas-aqeqe-casdda" />
                        <label class="label" for="filterContainerId">{{ __('specimens.container_id') }}</label>
                    </div>
                    <div class="form-group group">
                        <select id="filterContainerType" wire:model="filterContainerType" class="input-select peer w-full">
                            <option value="">Пробірка</option>
                        </select>
                        <label class="label" for="filterContainerType">{{ __('specimens.container_type') }}</label>
                    </div>
                </div>
                
                <div class="form-row-3 mb-6">
                    <div class="form-group group">
                        <input id="filterParentSpecimen" type="text" class="input peer" wire:model="filterParentSpecimen" placeholder=" " autocomplete="off" value="1231-adsadas-aqeqe-casdda" />
                        <label class="label" for="filterParentSpecimen">{{ __('specimens.parent_specimen') }}</label>
                    </div>
                    <div class="form-group group">
                        <input id="filterElectronicReferral" type="text" class="input peer" wire:model="filterElectronicReferral" placeholder=" " autocomplete="off" value="1231-adsadas-aqeqe-casdda" />
                        <label class="label" for="filterElectronicReferral">{{ __('specimens.electronic_referral') }}</label>
                    </div>
                    <div class="form-group group">
                        <input id="filterEncounter" type="text" class="input peer" wire:model="filterEncounter" placeholder=" " autocomplete="off" value="1231-adsadas-aqeqe-casdda" />
                        <label class="label" for="filterEncounter">{{ __('specimens.encounter') }}</label>
                    </div>
                </div>
            </div>

            <div class="space-y-4 mt-6">
                <div class="record-inner-card">
                    <div class="record-inner-header">
                        <div class="record-inner-checkbox-col">
                            <input type="checkbox" class="default-checkbox h-5 w-5" />
                        </div>
                        <div class="record-inner-column flex-1">
                            <div class="record-inner-label">{{ __('specimens.specimen_number') }} 1332-1421-1321-6123-1235</div>
                            <div class="record-inner-value text-[17px] font-semibold text-gray-900 dark:text-gray-100">
                                Кров венозна
                            </div>
                        </div>

                        <div class="record-inner-column-bordered w-full shrink-0 md:w-48">
                            <div class="record-inner-label">{{ __('specimens.status') }}</div>
                            <div>
                                <span class="badge-green">{{ __('specimens.available') }}</span>
                            </div>
                        </div>

                        <div class="record-inner-action-col">
                            <div
                                x-data="{
                                    open: false,
                                    toggle() {
                                        if (this.open) {
                                            return this.close();
                                        }
                                        this.$refs.button.focus();
                                        this.open = true;
                                    },
                                    close(focusAfter) {
                                        if (! this.open) return;
                                        this.open = false;
                                        focusAfter && focusAfter.focus();
                                    },
                                }"
                                @keydown.escape.prevent.stop="close($refs.button)"
                                @focusin.window="! $refs.panel.contains($event.target) && close()"
                                x-id="['dropdown-button']"
                                class="relative"
                            >
                                <button
                                    @click="toggle()"
                                    x-ref="button"
                                    :aria-expanded="open"
                                    :aria-controls="$id('dropdown-button')"
                                    type="button"
                                    class="record-inner-action-btn cursor-pointer rounded-lg p-2 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                >
                                    @icon('edit-user-outline', 'w-6 h-6 text-gray-700 dark:text-gray-300')
                                </button>

                                <div
                                    x-show="open"
                                    x-cloak
                                    x-ref="panel"
                                    x-transition.origin.top.right
                                    @click.outside="close($refs.button)"
                                    :id="$id('dropdown-button')"
                                    class="absolute right-0 z-50 mt-2 w-56 rounded-md border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-600 dark:bg-gray-700"
                                >
                                    <button
                                        @click="close($refs.button)"
                                        class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-600"
                                    >
                                        @icon('eye', 'w-5 h-5 text-gray-500')
                                        {{ __('specimens.view_details') }}
                                    </button>

                                    <button
                                        @click="close($refs.button)"
                                        class="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-600"
                                    >
                                        @icon('alert-circle', 'w-5 h-5 text-gray-500')
                                        {{ __('conditions.status.entered_in_error') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="record-inner-body">
                        <div class="record-inner-grid-container">
                            <div class="grid grid-cols-2 gap-x-4 gap-y-3 md:grid-cols-3">
                                <div>
                                    <div class="record-inner-label text-[10px] uppercase">{{ __('specimens.collection') }}</div>
                                    <div class="record-inner-value font-semibold">09.09.2026-<br>03.05.2026</div>
                                </div>
                                <div>
                                    <div class="record-inner-label text-[10px] uppercase">{{ __('specimens.parent_specimen') }}</div>
                                    <div class="record-inner-value font-semibold">2313-1245-1235</div>
                                </div>
                                <div>
                                    <div class="record-inner-label text-[10px] uppercase">{{ __('specimens.containers_and_type') }}</div>
                                    <div class="record-inner-value font-semibold">VAC-001, пробірка<br>VAC-001, пробірка</div>
                                </div>
                                <div>
                                    <div class="record-inner-label text-[10px] uppercase">{{ __('specimens.created_by') }}</div>
                                    <div class="record-inner-value font-semibold">Сидоренко І.В.</div>
                                </div>
                                <div>
                                    <div class="record-inner-label text-[10px] uppercase">{{ __('specimens.electronic_referral') }}</div>
                                    <div class="record-inner-value font-semibold">2313-1245-1235</div>
                                </div>
                            </div>
                        </div>

                        <div class="record-inner-id-col md:!w-64">
                            <div class="min-w-0">
                                <div class="record-inner-label">{{ __('specimens.encounter') }}</div>
                                <div class="record-inner-id-value font-semibold whitespace-nowrap">1231-adsadas-aqeqe-casdda</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.patient>
