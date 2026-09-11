@use('App\Livewire\Specimen\SpecimenIndex')

<div>
    <livewire:components.x-message :listen-async="true" :key="time()" />
    <x-header-navigation class="items-start" x-data="{ showFilter: false }">
        <x-slot name="title">{{ __('specimens.title') }}</x-slot>

        <div class="mt-3 ml-0 flex flex-col gap-2 self-start sm:flex-row sm:flex-wrap">
            <a href="#" class="button-primary">
                {{ __('specimens.new_specimen') }}
            </a>

            <button
                wire:click.prevent="sync"
                type="button"
                class="button-sync flex items-center gap-2 px-5 py-2 text-sm whitespace-nowrap shadow-sm"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="sync">
                    @icon('refresh', 'w-4 h-4')
                </span>
                <span wire:loading wire:target="sync" class="animate-spin">
                    @icon('refresh', 'w-4 h-4')
                </span>
                <span>{{ __('forms.synchronise_with_eHealth') }}</span>
            </button>
        </div>
    </x-header-navigation>

    <section class="section-form mt-4">
        <div class="shift-content w-full max-w-7xl px-4 py-6 lg:py-10">
            <div class="mb-6 w-full" x-data="{ showAdditionalParams: false }">
                <div class="mb-4 flex items-center gap-1 font-semibold text-gray-900 dark:text-gray-100">
                    @icon('search-outline', 'w-4.5 h-4.5')
                    <p>{{ __('specimens.search_specimens') }}</p>
                </div>

                <div class="form-row-4 mb-6">
                    <div class="form-group group">
                        <div class="relative">
                            <input
                                wire:model="searchId"
                                wire:keydown.enter="search"
                                type="text"
                                name="searchId"
                                id="searchId"
                                class="input peer w-full"
                                placeholder=" "
                                autocomplete="off"
                            />
                            <label for="searchId" class="label">{{ __('specimens.specimen_id') }}</label>
                            <button
                                type="button"
                                wire:click="$set('searchId', '')"
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                x-show="$wire.searchId"
                            >
                                @icon('close', 'w-4 h-4')
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mb-9 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            wire:click="search"
                            class="button-primary flex items-center gap-2 px-5 py-2.5 text-sm shadow-sm"
                        >
                            @icon('search', 'w-4 h-4')
                            <span>{{ __('forms.search') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="record-inner-card">
                    <div class="record-inner-header">
                        <div class="record-inner-checkbox-col">
                           <input type="checkbox" class="default-checkbox h-5 w-5" />
                        </div>
                        <div class="record-inner-column flex-1">
                            <div class="record-inner-label">{{ __('specimens.specimen_number') }} 1332-1421-1321-6123-1235</div>
                            <div class="record-inner-value text-[17px] font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('specimens.venous_blood') }}
                            </div>
                        </div>

                        <div class="record-inner-column-bordered w-full shrink-0 md:w-48">
                            <div class="record-inner-label">{{ __('specimens.status') }}</div>
                            <div>
                                <span class="badge-green">{{ __('specimens.available') }}</span>
                            </div>
                        </div>

                        <div class="record-inner-action-col">
                            <div class="relative flex justify-center" x-data="{
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
                                }
                            }" @keydown.escape.prevent.stop="close($refs.button)" @focusin.window="! $refs.panel.contains($event.target) && close()">
                                <button x-ref="button" @click="toggle()" :aria-expanded="open" type="button" class="inline-flex items-center p-2 text-gray-500 hover:text-gray-800 rounded-lg">
                                    @icon('edit-user-outline', 'w-6 h-6 text-gray-800 dark:text-white')
                                </button>

                                <div x-ref="panel" x-show="open" x-cloak x-transition class="absolute top-full right-0 z-50 w-72 bg-white rounded shadow-lg dark:bg-gray-700 mt-2" style="display: none;">
                                    <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                        <li>
                                            <a href="#" class="flex items-center gap-2 py-2 px-5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors whitespace-nowrap">
                                                @icon('eye', 'w-5 h-5') {{ __('specimens.view_details') }}
                                            </a>
                                        </li>
                                        <li>
                                            <button type="button" class="flex w-full items-center gap-2 py-2 px-5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors whitespace-nowrap">
                                                @icon('check-circle', 'w-5 h-5') {{ __('specimens.received_for_research') }}
                                            </button>
                                        </li>
                                        <li class="border-t border-gray-100 dark:border-gray-600 mt-1 pt-1">
                                            <button type="button" class="flex items-center gap-2 w-full py-2 px-5 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 text-left transition-colors whitespace-nowrap">
                                                @icon('close-circle', 'w-5 h-5')
                                                <span>{{ __('specimens.mark_unavailable') }}</span>
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="flex items-center gap-2 w-full py-2 px-5 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 text-left transition-colors whitespace-nowrap">
                                                @icon('close-circle', 'w-5 h-5')
                                                <span>{{ __('specimens.mark_unsatisfactory') }}</span>
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="flex items-center gap-2 w-full py-2 px-5 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 text-left transition-colors whitespace-nowrap">
                                                @icon('close-circle', 'w-5 h-5')
                                                <span>{{ __('specimens.mark_entered_in_error') }}</span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="record-inner-body">
                        <div class="record-inner-grid-container">
                            <div class="grid grid-cols-2 gap-x-4 gap-y-3 md:grid-cols-3">
                                <div>
                                    <div class="record-inner-label text-[10px] uppercase">{{ __('specimens.collection') }}</div>
                                    <div class="record-inner-value font-semibold">{!! __('specimens.mock_date') !!}</div>
                                </div>
                                <div>
                                    <div class="record-inner-label text-[10px] uppercase">{{ __('specimens.parent_specimen') }}</div>
                                    <div class="record-inner-value font-semibold">{{ __('specimens.mock_parent_specimen') }}</div>
                                </div>
                                <div>
                                    <div class="record-inner-label text-[10px] uppercase">{{ __('specimens.containers_and_type') }}</div>
                                    <div class="record-inner-value font-semibold">{!! __('specimens.mock_containers') !!}</div>
                                </div>
                                <div>
                                    <div class="record-inner-label text-[10px] uppercase">{{ __('specimens.created_by') }}</div>
                                    <div class="record-inner-value font-semibold">{{ __('specimens.mock_author') }}</div>
                                </div>
                                <div>
                                    <div class="record-inner-label text-[10px] uppercase">{{ __('specimens.electronic_referral') }}</div>
                                    <div class="record-inner-value font-semibold">{{ __('specimens.mock_referral') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="record-inner-id-col md:!w-64">
                            <div class="min-w-0">
                                <div class="record-inner-label">{{ __('specimens.encounter') }}</div>
                                <div class="record-inner-id-value font-semibold whitespace-nowrap">{{ __('specimens.mock_encounter') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <x-forms.loading />
    </section>
</div>
