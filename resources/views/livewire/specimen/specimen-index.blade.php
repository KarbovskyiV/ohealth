@use(App\Enums\Specimen\Status as SpecimenStatus)

<div>
    <livewire:components.x-message :key="time()" />
    <x-header-navigation class="items-start" x-data="{ showFilter: false }">
        <x-slot name="title">{{ __('specimens.title') }}</x-slot>

        <div class="mt-3 ml-0 flex flex-col gap-2 self-start sm:flex-row sm:flex-wrap">
            <a
                href="{{ route('specimens.create', [legalEntity()]) }}"
                class="button-primary flex items-center gap-2"
                wire:navigate
            >
                @icon('plus', 'w-4 h-4')
                {{ __('specimens.new_specimen') }}
            </a>

            <button
                type="button"
                wire:click.prevent="$refresh"
                class="button-sync flex items-center gap-2 whitespace-nowrap"
            >
                @icon('refresh', 'w-4 h-4')
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
                                x-mask="9999-9999-9999-9999"
                                type="text"
                                name="searchId"
                                id="searchId"
                                class="input peer w-full"
                                placeholder=" "
                                autocomplete="off"
                            />
                            <label for="searchId" class="label">{{ __('specimens.accession_identifier') }}</label>
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

            @if ($specimen)
                @php
                    $collectedDateTime = data_get($specimen, 'collection.collectedDateTime');
                    $collectedPeriodStart = data_get($specimen, 'collection.collectedPeriod.start');
                    $collectedPeriodEnd = data_get($specimen, 'collection.collectedPeriod.end');
                    $status = SpecimenStatus::from(data_get($specimen, 'status'));
                @endphp
                <div class="space-y-4">
                    <div class="record-inner-card" wire:key="specimen-{{ data_get($specimen, 'uuid') }}">
                        <div class="record-inner-header">
                            <div class="record-inner-checkbox-col">
                                <label for="specimenRecord" class="sr-only">{{ __('forms.select') }}</label>
                                <input type="checkbox" id="specimenRecord" class="default-checkbox h-5 w-5" />
                            </div>
                            <div class="record-inner-column flex-1">
                                <div class="record-inner-label">
                                    {{ __('specimens.specimen_number') }} {{ data_get($specimen, 'accessionIdentifier') ?? '-' }}
                                </div>
                                <div class="record-inner-value text-[17px] font-semibold text-gray-900 dark:text-gray-100">
                                    {{ data_get($dictionaries, 'specimen_types.' . data_get($specimen, 'type.coding.0.code'), '-') }}
                                </div>
                            </div>

                            <div class="record-inner-column-bordered w-full shrink-0 md:w-48">
                                <div class="record-inner-label">{{ __('forms.status.label') }}</div>
                                <div>
                                    <span class="{{ $status->color() }}">{{ $status->label() }}</span>
                                </div>
                            </div>

                            <div class="record-inner-action-col">
                                <div
                                    class="relative flex justify-center"
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
                                >
                                    <button
                                        x-ref="button"
                                        @click="toggle()"
                                        :aria-expanded="open"
                                        type="button"
                                        class="inline-flex cursor-pointer items-center rounded-lg p-2 text-gray-500 hover:text-gray-800"
                                    >
                                        @icon('edit-user-outline', 'w-6 h-6 text-gray-800 dark:text-white')
                                    </button>

                                    <div
                                        x-ref="panel"
                                        x-show="open"
                                        x-cloak
                                        x-transition
                                        class="absolute top-full right-0 z-50 mt-2 w-72 rounded bg-white shadow-lg dark:bg-gray-700"
                                        style="display: none"
                                    >
                                        <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button
                                                    type="button"
                                                    wire:click="view"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-5 py-2 whitespace-nowrap text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600"
                                                >
                                                    @icon('eye', 'w-5 h-5')
                                                    {{ __('forms.view_details') }}
                                                </button>
                                            </li>
                                            <li>
                                                <button
                                                    type="button"
                                                    @click="$wire.showReceivedForResearchModal = true; $wire.openReceivedForResearchModal('{{ data_get($specimen, 'uuid') }}'); close($refs.button)" class="flex w-full cursor-pointer items-center gap-2 px-5 py-2 whitespace-nowrap text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600"
                                                >
                                                    @icon('checkmark-circle', 'w-5 h-5 shrink-0')
                                                    {{ __('specimens.received_for_research') }}
                                                </button>
                                            </li>
                                            <li class="mt-1 border-t border-gray-100 pt-1 dark:border-gray-600">
                                                <button
                                                    type="button"
                                                    @click="$wire.showMarkUnavailableModal = true; $wire.openMarkUnavailableModal('{{ data_get($specimen, 'uuid') }}'); close($refs.button)" class="flex w-full cursor-pointer items-center gap-2 px-5 py-2 text-left whitespace-nowrap text-red-600 transition-colors hover:bg-gray-100 dark:hover:bg-gray-600"
                                                >
                                                    @icon('cancel', 'w-5 h-5 shrink-0')
                                                    <span>{{ __('specimens.mark_unavailable') }}</span>
                                                </button>
                                            </li>
                                            <li>
                                                <button
                                                    type="button"
                                                    @click="$wire.showMarkUnsatisfactoryModal = true; $wire.openMarkUnsatisfactoryModal('{{ data_get($specimen, 'uuid') }}'); close($refs.button)" class="flex w-full cursor-pointer items-center gap-2 px-5 py-2 text-left whitespace-nowrap text-red-600 transition-colors hover:bg-gray-100 dark:hover:bg-gray-600"
                                                >
                                                    @icon('cancel', 'w-5 h-5 shrink-0')
                                                    <span>{{ __('specimens.mark_unsatisfactory') }}</span>
                                                </button>
                                            </li>
                                            <li>
                                                <button
                                                    type="button"
                                                    @click="$wire.showMarkEnteredInErrorModal = true; $wire.openMarkEnteredInErrorModal('{{ data_get($specimen, 'uuid') }}'); close($refs.button)" class="flex w-full cursor-pointer items-center gap-2 px-5 py-2 text-left whitespace-nowrap text-red-600 transition-colors hover:bg-gray-100 dark:hover:bg-gray-600"
                                                >
                                                    @icon('cancel', 'w-5 h-5 shrink-0')
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
                                        <div class="record-inner-label text-[10px] uppercase">
                                            {{ __('specimens.collection') }}
                                        </div>
                                        <div class="record-inner-value font-semibold">
                                            @if ($collectedDateTime)
                                                {{ $collectedDateTime }}
                                            @else
                                                {{ $collectedPeriodStart ?? '-' }}
                                                @if ($collectedPeriodEnd)
                                                    -<br

                                                    />{{ $collectedPeriodEnd }}
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <div class="record-inner-label text-[10px] uppercase">
                                            {{ __('specimens.parent_specimen') }}
                                        </div>
                                        <div class="record-inner-value font-semibold">
                                            @forelse (data_get($specimen, 'parent', []) as $parent)
                                                <div>{{ data_get($parent, 'identifier.value') }}</div>
                                            @empty
                                                -
                                            @endforelse
                                        </div>
                                    </div>
                                    <div>
                                        <div class="record-inner-label text-[10px] uppercase">
                                            {{ __('specimens.containers_and_type') }}
                                        </div>
                                        <div class="record-inner-value font-semibold">
                                            @forelse (data_get($specimen, 'container', []) as $container)
                                                <div>
                                                    {{ data_get($container, 'identifier') }}, {{ data_get($dictionaries, 'specimen_container_types.' . data_get($container, 'type.coding.0.code'), '-') }}
                                                </div>
                                            @empty
                                                -
                                            @endforelse
                                        </div>
                                    </div>
                                    <div>
                                        <div class="record-inner-label text-[10px] uppercase">
                                            {{ __('specimens.created_by') }}
                                        </div>
                                        <div class="record-inner-value font-semibold">
                                            {{ data_get($specimen, 'registeredBy.displayValue') ?? '-' }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="record-inner-label text-[10px] uppercase">
                                            {{ __('specimens.electronic_referral') }}
                                        </div>
                                        <div class="record-inner-value font-semibold">
                                            @forelse (data_get($specimen, 'request', []) as $request)
                                                <div>{{ data_get($request, 'identifier.value') }}</div>
                                            @empty
                                                -
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="record-inner-id-col md:w-64!">
                                <div class="min-w-0">
                                    <div class="record-inner-label">{{ __('specimens.encounter') }}</div>
                                    <div class="record-inner-id-value font-semibold whitespace-nowrap">
                                        {{ data_get($specimen, 'context.identifier.value') ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <x-nothing-found :description="null" />
            @endif
        </div>

        <x-forms.loading />
    </section>

    @include('livewire.specimen.parts.modals.received-for-research-modal')
    @include('livewire.specimen.parts.modals.mark-unavailable-modal')
    @include('livewire.specimen.parts.modals.mark-unsatisfactory-modal')
    @include('livewire.specimen.parts.modals.mark-entered-in-error-modal')
</div>
