<div class="flex flex-col -my-4" x-data="{ showFilter: false }">
    <div class="flex mb-4 flex-col w-full">
        <div class="w-full lg:w-96">
            <label
                for="serviceSearch"
                class="text-sm font-medium text-gray-900 dark:text-white block mb-2 flex items-center gap-1"
            >
                @icon('search-outline', 'w-4.5 h-4.5')
                <span>{{ __('dictionaries.service_catalog.search_services') }}</span>
            </label>

            <div class="form-group group w-full">
                <input
                    type="text"
                    id="serviceSearch"
                    class="input peer w-full"
                    placeholder=" "
                    wire:model="searchBy"
                    wire:keydown.enter="search"
                />

                <label for="serviceSearch" class="label">
                    {{ __('dictionaries.service_catalog.search_placeholder') }}
                </label>
            </div>
        </div>
    </div>

    <div class="mb-4 mt-6 flex flex-col gap-2 w-full sm:flex-row">
        <button
            type="button"
            wire:click="search"
            class="flex items-center gap-2 button-primary"
        >
            @icon('search', 'w-4 h-4')
            <span>{{ __('forms.search') }}</span>
        </button>

        <button
            type="button"
            wire:click="resetFilters"
            class="button-primary-outline-red me-0"
        >
            {{ __('forms.reset_all_filters') }}
        </button>

        <button
            type="button"
            class="button-minor flex items-center gap-2"
            @click="showFilter = !showFilter"
        >
            @icon('adjustments', 'w-4 h-4')
            <span>{{ __('forms.additional_search_parameters') }}</span>
        </button>
    </div>

    <div
        x-cloak
        x-show="showFilter"
        x-transition
        class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6 w-full mt-4 mb-9 md:mb-5"
    >
        <div class="form-group group">
            <select
                wire:model="serviceCategory"
                id="filterServiceCategory"
                class="peer input-select w-full"
            >
                <option value="">{{ __('forms.select') }}</option>

                @foreach ($this->dictionaries['SERVICE_CATEGORY'] ?? [] as $key => $value)
                    @if ($allowedCategories === [] || in_array($key, $allowedCategories, true))
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endif
                @endforeach
            </select>

            <label
                for="filterServiceCategory"
                class="label peer-focus:text-blue-600 peer-valid:text-blue-600"
            >
                {{ __('dictionaries.service_catalog.service_category') }}
            </label>
        </div>

        <div class="form-group group">
            <select
                wire:model="serviceGroupActive"
                id="filterServiceGroupActive"
                class="peer input-select w-full"
            >
                <option value="">{{ __('forms.select') }}</option>
                <option value="1">{{ __('forms.yes') }}</option>
                <option value="0">{{ __('forms.no') }}</option>
            </select>

            <label
                for="filterServiceGroupActive"
                class="label peer-focus:text-blue-600 peer-valid:text-blue-600"
            >
                {{ __('dictionaries.service_catalog.service_group_active') }}
            </label>
        </div>

        <div class="form-group group">
            <select
                wire:model="serviceActive"
                id="filterServiceActive"
                class="peer input-select w-full"
            >
                <option value="">{{ __('forms.select') }}</option>
                <option value="1">{{ __('forms.yes') }}</option>
                <option value="0">{{ __('forms.no') }}</option>
            </select>

            <label
                for="filterServiceActive"
                class="label peer-focus:text-blue-600 peer-valid:text-blue-600"
            >
                {{ __('dictionaries.service_catalog.service_active') }}
            </label>
        </div>

        <div class="form-group group">
            <select
                wire:model="allowedForEn"
                id="filterAllowedForEn"
                class="peer input-select w-full"
            >
                <option value="">{{ __('forms.select') }}</option>
                <option value="1">{{ __('forms.yes') }}</option>
                <option value="0">{{ __('forms.no') }}</option>
            </select>

            <label
                for="filterAllowedForEn"
                class="label peer-focus:text-blue-600 peer-valid:text-blue-600"
            >
                {{ __('dictionaries.service_catalog.allowed_for_en') }}
            </label>
        </div>
    </div>
</div>