<div>
    <x-header-navigation class="items-start" x-data="{ showFilter: false }"
        :breadcrumbs="[
            ['label' => __('forms.home'), 'url' => route('dashboard', [legalEntity()])],
            ['label' => __('patients.data_access')]
        ]"
    >

        <x-slot name="title">{{ __('approval.data_access_list') }}</x-slot>

        <div class="mt-3 ml-0 flex flex-col gap-2 self-start sm:flex-row sm:flex-wrap">
            <button
                type="button"
                class="button-sync flex items-center gap-2 whitespace-nowrap"
            >
                @icon('refresh', 'w-4 h-4')
                <span>{{ __('forms.synchronise_with_eHealth') }}</span>
            </button>
        </div>

        <x-slot name="navigation">
            <div class="-my-4 flex flex-col">
                <form wire:submit.prevent="applyFilters">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div class="flex w-full flex-col items-stretch gap-2 lg:flex-row lg:items-end lg:gap-4">
                            <div class="w-full lg:w-96">
                                <x-forms.form-group>
                                    <x-slot name="label">
                                        <label
                                            for="approval_search"
                                            class="mb-2 block flex items-center gap-1 text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            <svg
                                                class="h-4 w-4 text-gray-500 dark:text-gray-400"
                                                aria-hidden="true"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    stroke="currentColor"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
                                                />
                                            </svg>
                                            <span>{{ __('approval.search_access') }}</span>
                                        </label>
                                    </x-slot>
                                    <x-slot name="input">
                                        <div class="form-group group w-full">
                                            <input
                                                type="text"
                                                id="approval_search"
                                                placeholder=" "
                                                class="input peer"
                                                wire:model.defer="search"
                                                autocomplete="off"
                                            />
                                            <label for="approval_search" class="label">{{ __('forms.patient') }}</label>
                                        </div>
                                    </x-slot>
                                </x-forms.form-group>
                            </div>
                            <button
                                type="button"
                                class="button-minor flex w-full items-center justify-center gap-2 self-stretch lg:w-auto lg:-translate-y-[9px] lg:self-auto"
                                @click="showFilter = ! showFilter"
                            >
                                @icon('adjustments', 'w-4 h-4')
                                <span>{{ __('forms.additional_search_parameters') }}</span>
                            </button>
                        </div>

                        <div x-cloak x-show="showFilter" x-transition class="mt-1 pt-0 w-full">
                            <div class="form-row-4">
                                <div class="form-group group">
                                    <input
                                        wire:model.defer="filter.data_id"
                                        id="filter_data_id"
                                        class="input peer"
                                        placeholder=" "
                                        autocomplete="off"
                                    />
                                    <label for="filter_data_id" class="label">{{ __('approval.data_id') }}</label>
                                </div>
                                <div class="form-group group">
                                    <input
                                        wire:model.defer="filter.reason_id"
                                        id="filter_reason_id"
                                        class="input peer"
                                        placeholder=" "
                                        autocomplete="off"
                                    />
                                    <label for="filter_reason_id" class="label">{{ __('approval.reason_id') }}</label>
                                </div>
                            </div>
                            <div class="form-row-4">
                                <div class="form-group group">
                                    <select
                                        wire:model.defer="filter.granted_to"
                                        id="filter_granted_to"
                                        class="input peer text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                                    >
                                        <option value="">{{ __('forms.select') }}</option>
                                    </select>
                                    <label for="filter_granted_to" class="label">{{ __('approval.granted_to') }}</label>
                                </div>
                                <div class="form-group group">
                                    <select
                                        wire:model.defer="filter.data_type"
                                        id="filter_data_type"
                                        class="input peer text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                                    >
                                        <option value="">{{ __('forms.select') }}</option>
                                    </select>
                                    <label for="filter_data_type" class="label">{{ __('approval.data_type') }}</label>
                                </div>
                            </div>
                            <div class="form-row-4">
                                <div class="form-group group">
                                    <select
                                        wire:model.defer="filter.status"
                                        id="filter_status"
                                        class="input peer text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                                    >
                                        <option value="">{{ __('forms.select') }}</option>
                                    </select>
                                    <label for="filter_status" class="label">{{ __('forms.status.label') }}</label>
                                </div>
                                <div class="form-group group">
                                    <select
                                        wire:model.defer="filter.access_level"
                                        id="filter_access_level"
                                        class="input peer text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                                    >
                                        <option value="">{{ __('forms.select') }}</option>
                                    </select>
                                    <label for="filter_access_level" class="label">{{ __('approval.access_level') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 mb-9 flex w-full flex-col gap-2 sm:flex-row">
                            <button type="submit" class="button-primary flex items-center gap-2">
                                @icon('search', 'w-4 h-4')
                                <span>{{ __('forms.search') }}</span>
                            </button>

                            <button type="button" wire:click="resetFilters" class="button-primary-outline-red">
                                {{ __('forms.reset_all_filters') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </x-slot>
    </x-header-navigation>

    <div class="flow-root mt-8 shift-content pl-3.5">
        <div class="max-w-screen-xl">
            @php
                use Illuminate\Pagination\LengthAwarePaginator;

                $mockData = collect([
                    [
                        'id'              => 1,
                        'patient'         => 'Шевченко Т.Г.',
                        'dob'             => '03.02.1995 р.н.',
                        'granted_to_type' => 'Співробітник',
                        'granted_to_name' => 'Франко І.Я.',
                        'data_type'       => 'Група діагнозів',
                        'data'            => 'B25-B34 - Інші вірусні хвороби',
                        'reason_id'       => '9183a36b-4d45-4244-9339-63d81cd08d9c',
                        'access_level'    => 'Читання',
                        'status'          => 'Активний',
                    ],
                    [
                        'id'              => 2,
                        'patient'         => 'Шевченко Т.Г.',
                        'dob'             => '03.02.1995 р.н.',
                        'granted_to_type' => 'Організація',
                        'granted_to_name' => 'ТОВ "Лікарня №1"',
                        'data_type'       => 'Діагностичний звіт',
                        'data'            => '7c3da506-804d-4550-8993-bf17f9ee0400',
                        'reason_id'       => '9183a36b-4d45-4244-9339-63d81cd08d9c',
                        'access_level'    => 'Читання',
                        'status'          => 'Активний',
                    ]
                ]);

                $page = LengthAwarePaginator::resolveCurrentPage();
                $perPage = 15;
                $items = $mockData->slice(($page - 1) * $perPage, $perPage);
                $approvals = new LengthAwarePaginator($items, $mockData->count(), $perPage, $page, [
                    'path' => LengthAwarePaginator::resolveCurrentPath()
                ]);
            @endphp
            @if($approvals->isNotEmpty())
                <div class="index-table-wrapper">
                    <table class="index-table w-full">
                        <thead class="index-table-thead">
                        <tr>
                            <th class="index-table-th w-[14%]">{{ __('forms.patient') }}</th>
                            <th class="index-table-th w-[18%]">{{ __('approval.granted_to') }}</th>
                            <th class="index-table-th w-[10%]">{{ __('approval.data_type') }}</th>
                            <th class="index-table-th w-[15%]">{{ __('approval.data') }}</th>
                            <th class="index-table-th w-[15%]">{{ __('approval.based_on') }}</th>
                            <th class="index-table-th w-[10%]">{{ __('approval.access_level') }}</th>
                            <th class="index-table-th w-[12%]">{{ __('forms.status.label') }}</th>
                            <th class="index-table-th w-[6%]">{{ __('forms.action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($approvals as $item)
                            <tr class="index-table-tr" wire:key="approval-row-{{ $item['id'] }}">
                                <td class="index-table-td-primary">
                                    {{ $item['patient'] }}<br>
                                    <span class="text-xs text-gray-500">{{ $item['dob'] }}</span>
                                </td>
                                <td class="index-table-td">
                                    {{ $item['granted_to_type'] }}<br>
                                    {{ $item['granted_to_name'] }}
                                </td>
                                <td class="index-table-td">{{ $item['data_type'] }}</td>
                                <td class="index-table-td">{{ $item['data'] }}</td>
                                <td class="index-table-td break-all">{{ $item['reason_id'] }}</td>
                                <td class="index-table-td">{{ $item['access_level'] }}</td>
                                <td class="index-table-td whitespace-nowrap">
                                    <span class="badge-green whitespace-nowrap">{{ $item['status'] }}</span>
                                </td>
                                <td class="index-table-td-actions relative">
                                    <div class="flex justify-center relative">
                                        <div x-data="{
                                                open: false,
                                                toggle() {
                                                    if (this.open) { return this.close(); }
                                                    this.$refs.button.focus();
                                                    this.open = true;
                                                },
                                                close(focusAfter) {
                                                    if (!this.open) return;
                                                    this.open = false;
                                                    focusAfter && focusAfter.focus();
                                                }
                                            }"
                                             @keydown.escape.prevent.stop="close($refs.button)"
                                             @focusin.window="!$refs.panel.contains($event.target) && close()"
                                             class="relative"
                                        >
                                            <button @click="toggle()" x-ref="button" type="button" class="hover:text-primary cursor-pointer outline-none">
                                                <svg class="svg-hover-action w-6 h-6 text-gray-800 dark:text-gray-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z"/>
                                                </svg>
                                            </button>

                                            <div x-show="open" x-cloak x-ref="panel" x-transition.origin.top.left @click.outside="close($refs.button)" class="absolute right-0 mt-2 rounded-md bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 shadow-md z-50">
                                                <a href="{{ route('data-access.view', [legalEntity(), $item['id']]) }}" class="flex whitespace-nowrap items-center gap-2 w-full first-of-type:rounded-t-md px-4 py-2.5 text-left text-sm text-gray-600 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                    @icon('eye', 'w-5 h-5 text-gray-600 dark:text-gray-300')
                                                    {{ __('forms.view_details') }}
                                                </a>
                                                <button type="button" class="flex whitespace-nowrap items-center gap-2 w-full last-of-type:rounded-b-md px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-gray-600">
                                                    @icon('cancel', 'w-5 h-5 text-red-600')
                                                    {{ __('approval.revoke_access') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    {{ $approvals->links() }}
                </div>
            @else
                <x-nothing-found />
            @endif
        </div>
    </div>
</div>
