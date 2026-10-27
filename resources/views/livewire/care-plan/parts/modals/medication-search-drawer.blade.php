<x-dialog-drawer
    id="medication-search-drawer-right"
    x-data="{ showFilter: false }"
    x-model="showMedicationSearchDrawer"
    :stopClickPropagation="true"
    maxWidth="4/5"
    zIndex="48"
    customWidth="w-full sm:w-[calc(80%-30px)]"
    overlayWidth="80%"
    onCloseClick="showMedicationSearchDrawer = false"
>
    <x-slot name="title">{{ __('care-plan.new_medication_prescription') }}</x-slot>

    {{-- Search Input --}}
    <div class="mb-4">
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                @icon('search-outline', 'w-5 h-5 text-gray-500')
            </div>
            <input
                type="text"
                class="input peer w-full ps-10"
                placeholder="{{ __('care-plan.medication_search_placeholder') }}"
                wire:model="searchQuery"
                wire:keydown.enter="searchMedications"
            />
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="mb-6 flex flex-wrap gap-2">
        <button type="button" wire:click="searchMedications" class="button-primary flex items-center gap-2">
            @icon('search', 'w-4 h-4')
            <span>{{ __('forms.search') }}</span>
        </button>
        <button type="button" wire:click="$set('searchQuery', '')" class="button-primary-outline-red">
            {{ __('forms.reset_all_filters') }}
        </button>
        <button type="button" class="button-minor flex items-center gap-2" @click="showFilter = ! showFilter">
            @icon('adjustments', 'w-4 h-4')
            <span>{{ __('forms.additional_search_parameters') }}</span>
        </button>
    </div>

    {{-- Filters --}}
    <div x-show="showFilter" x-cloak x-transition class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="form-group group">
            <label class="label"> {{ __('care-plan.inn_name') }} </label>
            <select class="input-select peer w-full">
                <option selected value="">{{ __('care-plan.medication_search_placeholder') }}</option>
            </select>
        </div>
        <div class="form-group group">
            <label class="label"> {{ __('care-plan.atc_code') }} </label>
            <select class="input-select peer w-full">
                <option selected value="">{{ __('care-plan.code') }}</option>
            </select>
        </div>
        <div class="form-group group">
            <label class="label"> {{ __('care-plan.dosage_form') }} </label>
            <select class="input-select peer w-full">
                <option selected value="">{{ __('care-plan.tablets') }}</option>
            </select>
        </div>
        <div class="form-group group">
            <label class="label"> {{ __('care-plan.prescription_form_type') }} </label>
            <select class="input-select peer w-full">
                <option selected value="">{{ __('care-plan.type') }}</option>
            </select>
        </div>
    </div>

    {{-- Results --}}
    <div class="mb-6 space-y-4">
        @forelse ($searchResults as $drug)
            <fieldset class="fieldset">
                <legend class="legend">{{ $drug['name'] ?? 'Лікарський засіб' }}</legend>

                <div class="mb-4 space-y-1 text-sm text-gray-700 dark:text-gray-300">
                    <p>
                        <span class="text-gray-500">{{ __('care-plan.inn_basic') }}:</span>
                        {{ $drug['innm_name'] ?? 'МНН відсутнє' }}
                    </p>
                    <p>
                        <span class="text-gray-500">{{ __('care-plan.dosage_form') }}:</span>
                        {{ $drug['dosage_form'] ?? 'Форма випуску відсутня' }}
                    </p>
                    <p><span class="text-gray-500">Код АТХ:</span> {{ $drug['medication_code_atc'] ?? '-' }}</p>
                    <p><span class="text-gray-500">Одиниця виміру:</span> {{ $drug['package_unit'] ?? '-' }}</p>
                </div>

                <button
                    type="button"
                    wire:click="selectProduct({{ json_encode($drug) }}, 'medication_request')"
                    class="button-primary"
                >
                    Обрати для призначення
                </button>
            </fieldset>
        @empty
            <div class="py-8 text-center text-gray-400 italic">
                @if (empty($searchQuery))
                    Введіть запит для пошуку лікарських засобів
                @else
                    Нічого не знайдено за запитом "{{ $searchQuery }}"
                @endif
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        <button
            type="button"
            class="button-minor"
            aria-controls="medication-search-drawer-right"
            @click="showMedicationSearchDrawer = false"
        >
            {{ __('forms.cancel') }}
        </button>
    </div>
</x-dialog-drawer>
