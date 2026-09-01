<div>
    <section>
        <x-header-navigation
            x-data="{
                showFilter: true,
                showAdditional: false,
                focusNext(el) {
                    let container = el.closest('.breadcrumb-form');
                    if (! container) return;
                    let elements = Array.from(
                        container.querySelectorAll(
                            'input:not([readonly]):not([type=hidden]):not([type=checkbox]), select, button.button-primary',
                        ),
                    ).filter((element) => element.offsetWidth > 0 && element.offsetHeight > 0);
                    let index = elements.indexOf(el);
                    if (index > -1 && elements[index + 1]) {
                        elements[index + 1].focus();
                    }
                },
            }"
            class="breadcrumb-form"
        >
            <x-slot name="title">{{ __('patients.patient_verifications') }}</x-slot>
            <x-slot name="navigation">
                <div class="mb-8 block justify-end gap-4 sm:flex sm:items-center">
                    <button class="flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-800">
                        @icon('plus', 'w-4 h-4')
                        <span>{{ __('preperson.label_single') }}</span>
                    </button>
                    <button class="button-primary flex items-center gap-2">
                        @icon('plus', 'w-4 h-4')
                        <span>{{ __('patients.add_patient') }}</span>
                    </button>
                    <button
                        wire:click="{{ !$isSync ? 'sync' : '' }}"
                        type="button"
                        class="{{ $isSync ? 'button-sync-disabled' : 'button-sync' }} flex items-center gap-2 whitespace-nowrap"
                        {{ $isSync ? 'disabled' : '' }}
                    >
                        @icon('refresh', 'w-4 h-4')
                        <span>{{ ($syncStatus === 'PAUSED' || $syncStatus === 'FAILED') ? __('forms.sync_retry') : __('forms.synchronise_with_eHealth') }}</span>
                    </button>
                </div>

                <div class="mb-8 flex items-center gap-1 font-semibold text-gray-900 dark:text-white">
                    @icon('search-outline', 'w-4.5 h-4.5')
                    <p>{{ __('patients.patient_search') }}</p>
                </div>

                <div class="form-row-3 mb-4">
                    <div class="form-group group">
                        <input
                            wire:model="searchForm.employee_id"
                            type="text"
                            id="filterEmployeeId"
                            class="input peer"
                            placeholder=" "
                            autocomplete="off"
                            x-on:keydown.enter.prevent="focusNext($el)"
                        />
                        <label for="filterEmployeeId" class="label"> {{ __('forms.employee_id') }} </label>
                    </div>

                    <div class="form-group">
                        <select
                            wire:model="searchForm.verification_status"
                            id="filterVerificationStatus"
                            class="input-select peer"
                            x-on:keydown.enter.prevent="focusNext($el)"
                        >
                            <option value="">{{ __('forms.select') }}</option>
                            <option value="CHANGES_NEEDED">{{ __('patients.status.changes_needed') }}</option>
                            <option value="VERIFIED">{{ __('patients.status.verified') }}</option>
                            <option value="VERIFICATION_NEEDED">{{ __('patients.status.verification_needed') }}</option>
                            <option value="NOT_VERIFIED">{{ __('patients.status.not_verified') }}</option>
                        </select>
                        <label for="filterVerificationStatus" class="label">
                            {{ __('patients.verification_status') }}
                        </label>
                    </div>

                    <div class="form-group">
                        <select
                            wire:model="searchForm.status"
                            id="filterStatus"
                            class="input-select peer"
                            x-on:keydown.enter.prevent="focusNext($el)"
                        >
                            <option value="">{{ __('forms.select') }}</option>
                            <option value="active">{{ __('forms.status.active') }}</option>
                            <option value="inactive">{{ __('forms.status.non_active') }}</option>
                        </select>
                        <label for="filterStatus" class="label"> {{ __('forms.status.label') }} </label>
                    </div>
                </div>

                <div class="mt-6 mb-9 flex gap-2">
                    <button type="button" wire:click.prevent="search" class="button-primary flex items-center gap-2">
                        @icon('search', 'w-4 h-4')
                        <span>{{ __('forms.search') }}</span>
                    </button>
                    <button type="button" wire:click="resetFilters" class="button-primary-outline-red">
                        {{ __('forms.reset_all_filters') }}
                    </button>
                </div>
            </x-slot>
        </x-header-navigation>

        <div class="mb-16 space-y-6 pl-3.5">
            @if ($hasResults)
                <fieldset class="shift-content mt-6 mb-16 max-w-6xl rounded-lg border border-gray-200 p-4 shadow sm:p-8 sm:pb-10 dark:border-gray-700 dark:bg-gray-800">
                    <legend class="legend flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-2">
                            <span>{{ trim(($searchForm['lastName'] ?? '') . ' ' . ($searchForm['firstName'] ?? '') . ' ' . ($searchForm['secondName'] ?? '')) ?: 'Шевченко Тарас Григорович' }}</span>
                            <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-0.5 text-xs font-normal text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                {{ $searchForm['language'] ?: __('patients.ukrainian') }}
                            </span>
                        </span>
                    </legend>

                    <div class="mt-2 flex flex-wrap items-center justify-between gap-4 pb-4">
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-gray-500">
                            <span class="flex items-center gap-1.5 font-medium text-gray-700 dark:text-gray-300">
                                @icon('calendar-outline', 'w-5 h-5 text-gray-800 dark:text-white')
                                <span>{{ __('forms.birth_date_abbreviated') ?? 'Д.Н.' }} {{ $searchForm['birthDate'] ?: __('contracts.not_specified') }}</span>
                            </span>

                            <span class="flex min-w-0 items-center gap-1.5 font-medium text-gray-700 dark:text-gray-300">
                                @icon('tabler-phone', 'w-5 h-5 text-gray-800 dark:text-white')
                                <a
                                    href="#"
                                    class="truncate hover:underline"
                                >{{ $searchForm['phone'] ?: '+380XXXXXXXXX' }}</a>
                            </span>

                            <span class="flex items-center gap-1.5 font-medium text-gray-700 dark:text-gray-300">
                                @icon('men', 'w-5 h-5 text-gray-800 dark:text-white')
                                <span>{{ __('patients.male') }}</span>
                            </span>
                        </div>

                        <div class="flex items-center space-x-6">
                            <a
                                href="#"
                                class="flex cursor-pointer items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800"
                            >
                                @icon('file-lines', 'w-4 h-4')
                                <span>{{ __('patients.view_record') }}</span>
                            </a>
                            <button
                                type="button"
                                class="flex cursor-pointer items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800"
                            >
                                @icon('plus', 'w-4 h-4 text-blue-600')
                                <span>{{ __('patients.start_interacting') }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flow-root">
                        <div class="max-w-7xl">
                            <table class="table-input w-full table-auto">
                                <thead class="thead-input">
                                    <tr>
                                        <th
                                            scope="col"
                                            class="th-input w-1/6 text-left text-xs font-bold tracking-wider text-gray-500 uppercase"
                                        >
                                            {{ mb_strtoupper(__('patients.verification_sources.drfo')) }}
                                        </th>
                                        <th
                                            scope="col"
                                            class="th-input w-1/6 text-left text-xs font-bold tracking-wider text-gray-500 uppercase"
                                        >
                                            {{ mb_strtoupper(__('patients.verification_sources.dracs_death')) }}
                                        </th>
                                        <th
                                            scope="col"
                                            class="th-input w-1/6 text-left text-xs font-bold tracking-wider text-gray-500 uppercase"
                                        >
                                            {{ mb_strtoupper(__('patients.verification_sources.dracs_birth')) }}
                                        </th>
                                        <th
                                            scope="col"
                                            class="th-input w-1/6 text-left text-xs font-bold tracking-wider text-gray-500 uppercase"
                                        >
                                            {{ mb_strtoupper(__('patients.verification_sources.dms_passport')) }}
                                        </th>
                                        <th
                                            scope="col"
                                            class="th-input w-1/6 text-left text-xs font-bold tracking-wider text-gray-500 uppercase"
                                        >
                                            {{ mb_strtoupper(__('patients.verification_sources.unzr')) }}
                                        </th>
                                        <th
                                            scope="col"
                                            class="th-input w-1/6 text-left text-xs font-bold tracking-wider text-gray-500 uppercase"
                                        >
                                            {{ mb_strtoupper(__('patients.verification_sources.nhs')) }}
                                        </th>
                                        <th
                                            scope="col"
                                            class="th-input w-16 text-center text-xs font-bold tracking-wider text-gray-500 uppercase"
                                        >
                                            {{ mb_strtoupper(__('forms.action')) }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="td-input align-middle whitespace-nowrap">
                                            <span class="badge-green rounded px-2 py-0.5 text-xs">{{ __('forms.verified') }}</span>
                                        </td>
                                        <td class="td-input align-middle whitespace-nowrap">
                                            <span class="badge-green rounded px-2 py-0.5 text-xs">{{ __('forms.verified') }}</span>
                                        </td>
                                        <td class="td-input align-middle whitespace-nowrap">
                                            <span class="badge-green rounded px-2 py-0.5 text-xs">{{ __('forms.verified') }}</span>
                                        </td>
                                        <td class="td-input align-middle whitespace-nowrap">
                                            <span class="badge-green rounded px-2 py-0.5 text-xs">{{ __('forms.verified') }}</span>
                                        </td>
                                        <td class="td-input align-middle whitespace-nowrap">
                                            <span class="badge-green rounded px-2 py-0.5 text-xs">{{ __('forms.verified') }}</span>
                                        </td>
                                        <td class="td-input align-middle whitespace-nowrap">
                                            <span class="badge-green rounded px-2 py-0.5 text-xs">{{ __('forms.verified') }}</span>
                                        </td>
                                        <td class="td-input text-center align-middle">
                                            <div
                                                class="relative inline-block"
                                                x-data="{ openInteractionDropdown: false }"
                                                @click.outside="openInteractionDropdown = false"
                                            >
                                                <button
                                                    @click="openInteractionDropdown = ! openInteractionDropdown"
                                                    class="inline-block cursor-pointer rounded-full p-1.5 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700"
                                                    title="{{ __('forms.action') }}"
                                                    type="button"
                                                >
                                                    @icon('edit-user-outline', 'w-6 h-6 text-gray-800 dark:text-gray-200')
                                                </button>

                                                <div
                                                    x-show="openInteractionDropdown"
                                                    x-transition
                                                    x-cloak
                                                    class="absolute right-0 z-50 mt-2 w-64 rounded-lg border border-gray-200 bg-white py-1 text-left shadow-md dark:border-gray-600 dark:bg-gray-700"
                                                >
                                                    <a
                                                        @click="openInteractionDropdown = false"
                                                        class="dropdown-button !flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-600"
                                                    >
                                                        @icon('file-text', 'w-4 h-4 text-gray-400')
                                                        {{ __('patients.sign_declaration') }}
                                                    </a>

                                                    <a
                                                        @click="openInteractionDropdown = false"
                                                        class="dropdown-button !flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-600"
                                                    >
                                                        @icon('activity', 'w-4 h-4 text-gray-400')
                                                        {{ __('diagnostic-reports.create') }}
                                                    </a>

                                                    <a
                                                        @click="openInteractionDropdown = false"
                                                        class="dropdown-button !flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-600"
                                                    >
                                                        @icon('settings', 'w-4 h-4 text-gray-400')
                                                        {{ __('procedures.create') }}
                                                    </a>

                                                    <a
                                                        @click="openInteractionDropdown = false"
                                                        class="dropdown-button !flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-600"
                                                    >
                                                        @icon('book', 'w-4 h-4 text-gray-400')
                                                        {{ __('episodes.create') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </fieldset>
            @else
                <div class="shift-content mt-6 max-w-6xl">
                    <x-nothing-found />
                </div>
            @endif
        </div>
    </section>
</div>
