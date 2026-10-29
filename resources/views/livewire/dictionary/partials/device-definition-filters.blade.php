<div class="flex flex-col gap-4" x-data="{ showFilter: false }">
    <div class="flex max-w-sm flex-col gap-4">
        {{-- Program --}}
        <div class="form-group group">
            <label for="programSelect" class="default-label mb-2"> {{ __('dictionaries.program_label') }}* </label>

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

        <button type="button" class="button-minor flex items-center gap-2" @click="showFilter = ! showFilter">
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
                    <option value="{{ $classificationType['code'] }}">{{ $classificationType['name'] }}</option>
                @endforeach
            </select>
            <label for="deviceType" class="label"> {{ __('dictionaries.medical_device.device_type') }} </label>
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
