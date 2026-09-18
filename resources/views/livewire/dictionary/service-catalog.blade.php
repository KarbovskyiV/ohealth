<div>
    @if ($selectionMode)
        <div class="mb-8">
            <h3 class="modal-header !border-b-0 pb-0 mb-8">
                {{ __('dictionaries.service_catalog.search_service') }}
            </h3>

            @include('livewire.dictionary.parts.service-catalog-filters')
        </div>
    @else
        <x-header-navigation class="breadcrumb-form">
            <x-slot name="title">
                {{ __('dictionaries.service_catalog.title') }}
            </x-slot>

            <x-slot name="navigation">
                @include('livewire.dictionary.parts.service-catalog-filters')
            </x-slot>
        </x-header-navigation>
    @endif

    <div @class(['flow-root mt-8 pl-3.5', 'shift-content' => !$selectionMode,])>
        <div @class(['max-w-screen-xl' => !$selectionMode, 'w-full' => $selectionMode])>
            <div class="index-table-wrapper">
                <table class="index-table">
                    <thead class="index-table-thead">
                    <tr>
                        <th class="index-table-th w-[40%]">
                            {{ __('forms.name') }}
                        </th>
                        <th class="index-table-th w-[20%]">
                            {{ __('dictionaries.service_catalog.allowed_for_en') }}
                        </th>
                        <th class="index-table-th w-[20%]">
                            {{ __('forms.code') }}
                        </th>
                        <th class="index-table-th w-[20%]">
                            {{ __('forms.status.label') }}
                        </th>
                        @if ($selectionMode)
                            <th class="index-table-th w-[10%] text-center">
                                {{ __('forms.action') }}
                            </th>
                        @endif
                    </tr>
                    </thead>

                    <tbody x-data="{ openIds: {} }">
                    @forelse($services as $item)
                        @php
                            $itemId = $item['id'] ?? ('item-' . $loop->index);
                            $hasGroups = !empty($item['groups']);
                            $hasServices = !empty($item['services']);
                            $hasChildren = $hasGroups || $hasServices;
                        @endphp

                        {{-- Main category/service --}}
                        <tr class="index-table-tr">
                            <td class="index-table-td-primary">
                                <div class="flex items-start gap-3">
                                    <div class="mt-1 flex-shrink-0 w-6">
                                        @if ($hasChildren)
                                            <button type="button"
                                                    @click="openIds['{{ $itemId }}'] = !openIds['{{ $itemId }}']"
                                                    class="cursor-pointer p-0.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 inline-block"
                                                    :aria-expanded="!!openIds['{{ $itemId }}']"
                                            >
                                                <span class="inline-block transition-transform duration-200"
                                                      :class="openIds['{{ $itemId }}'] ? 'rotate-0' : '-rotate-90'"
                                                >
                                                    @icon('chevron-down', 'w-4 h-4 text-gray-800 dark:text-white')
                                                </span>
                                            </button>
                                        @endif
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-semibold">{{ $item['name'] ?? '' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="index-table-td">
                                @if (!empty($item['request_allowed']))
                                    <span class="text-lg font-semibold text-green-600">+</span>
                                @else
                                    <span class="text-lg font-semibold text-red-600">-</span>
                                @endif
                            </td>
                            <td class="index-table-td font-semibold">
                                {{ $item['code'] ?? '-' }}
                            </td>
                            <td class="index-table-td">
                                @if (!empty($item['is_active']))
                                    <span class="badge-green">
                                        {{ __('forms.status.active') }}
                                    </span>
                                @else
                                    <span class="badge-red">
                                        {{ __('forms.status.non_active') }}
                                    </span>
                                @endif
                            </td>
                        </tr>

                        {{-- Services directly under this item --}}
                        @if ($hasServices)
                            @foreach ($item['services'] as $service)
                                <tr x-cloak
                                    class="index-table-tr bg-blue-50/50 dark:bg-blue-900/20"
                                    x-show="openIds['{{ $itemId }}']"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                >
                                    <td class="index-table-td-primary">
                                        <div class="flex items-start gap-3">
                                            <div class="mt-1 flex-shrink-0 w-6 ml-6">
                                                @icon('chevron-right', 'w-3 h-3 text-gray-400 mt-1')
                                            </div>
                                            <div class="flex flex-col">
                                                <span>{{ $service['name'] ?? '' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="index-table-td">
                                        @if (!empty($service['request_allowed']))
                                            <span class="text-lg font-semibold text-green-600">+</span>
                                        @else
                                            <span class="text-lg font-semibold text-red-600">-</span>
                                        @endif
                                    </td>
                                    <td class="index-table-td font-semibold">
                                        {{ $service['code'] ?? '-' }}
                                    </td>
                                    <td class="index-table-td">
                                        @if (!empty($service['is_active']))
                                            <span class="badge-green">
                                                {{ __('forms.status.active') }}
                                            </span>
                                        @else
                                            <span class="badge-red">
                                                {{ __('forms.status.non_active') }}
                                            </span>
                                        @endif
                                    </td>
                                    @if ($selectionMode)
                                        <td class="index-table-td text-center">
                                            @if (!empty($service['is_active']))
                                                <button
                                                    type="button"
                                                    wire:click="selectService(@js($service['id']))"
                                                    class="inline-flex cursor-pointer items-center justify-center"
                                                    title="{{ __('forms.select') }}"
                                                >
                                                    @icon('plus-circle', 'w-6 h-6')
                                                </button>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endif

                        {{-- Groups under this item --}}
                        @if ($hasGroups)
                            @foreach ($item['groups'] as $group)
                                @php
                                    $groupId = $group['id'] ?? ('group-' . $loop->parent->index . '-' . $loop->index);
                                    $groupHasGroups = !empty($group['groups']);
                                    $groupHasServices = !empty($group['services']);
                                    $groupHasChildren = $groupHasGroups || $groupHasServices;
                                @endphp

                                {{-- Group row --}}
                                <tr x-cloak
                                    class="index-table-tr bg-gray-50/50 dark:bg-gray-800/50"
                                    x-show="openIds['{{ $itemId }}']"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                >
                                    <td class="index-table-td-primary">
                                        <div class="flex items-start gap-3">
                                            <div class="mt-1 flex-shrink-0 w-6 ml-6">
                                                @if ($groupHasChildren)
                                                    <button type="button"
                                                            @click="openIds['{{ $groupId }}'] = !openIds['{{ $groupId }}']"
                                                            class="p-0.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 inline-block"
                                                            :aria-expanded="!!openIds['{{ $groupId }}']"
                                                    >
                                                        <span class="inline-block transition-transform duration-200"
                                                              :class="openIds['{{ $groupId }}'] ? 'rotate-0' : '-rotate-90'"
                                                        >
                                                            @icon('chevron-down', 'w-4 h-4 text-gray-600 dark:text-gray-400')
                                                        </span>
                                                    </button>
                                                @else
                                                    @icon('chevron-right', 'w-3 h-3 text-gray-400 mt-1')
                                                @endif
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ $group['name'] ?? '' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="index-table-td">
                                        @if (!empty($group['request_allowed']))
                                            <span class="text-lg font-semibold text-green-600">+</span>
                                        @else
                                            <span class="text-lg font-semibold text-red-600">-</span>
                                        @endif
                                    </td>
                                    <td class="index-table-td font-semibold">
                                        {{ $group['code'] ?? '-' }}
                                    </td>
                                    <td class="index-table-td">
                                        @if (!empty($group['is_active']))
                                            <span class="badge-green">
                                                {{ __('forms.status.active') }}
                                            </span>
                                        @else
                                            <span class="badge-red">
                                                {{ __('forms.status.non_active') }}
                                            </span>
                                        @endif
                                    </td>
                                    @if ($selectionMode)
                                        <td class="index-table-td"></td>
                                    @endif
                                </tr>

                                {{-- Subgroups --}}
                                @if ($groupHasGroups)
                                    @foreach ($group['groups'] as $subgroup)
                                        @php
                                            $subgroupId = $subgroup['id'] ?? ('subgroup-' . $loop->parent->parent->index . '-' . $loop->parent->index . '-' . $loop->index);
                                            $subgroupHasServices = !empty($subgroup['services']);
                                        @endphp

                                        {{-- Subgroup row --}}
                                        <tr x-cloak
                                            class="index-table-tr bg-yellow-50/50 dark:bg-yellow-900/20"
                                            x-show="openIds['{{ $itemId }}'] && openIds['{{ $groupId }}']"
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0"
                                            x-transition:enter-end="opacity-100"
                                        >
                                            <td class="index-table-td-primary">
                                                <div class="flex items-start gap-3">
                                                    <div class="mt-1 flex-shrink-0 w-6 ml-12">
                                                        @if ($subgroupHasServices)
                                                            <button type="button"
                                                                    @click="openIds['{{ $subgroupId }}'] = !openIds['{{ $subgroupId }}']"
                                                                    class="p-0.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 inline-block"
                                                                    :aria-expanded="!!openIds['{{ $subgroupId }}']"
                                                            >
                                                                <span
                                                                    class="inline-block transition-transform duration-200"
                                                                    :class="openIds['{{ $subgroupId }}'] ? 'rotate-0' : '-rotate-90'"
                                                                >
                                                                    @icon('chevron-down', 'w-4 h-4 text-gray-600 dark:text-gray-400')
                                                                </span>
                                                            </button>
                                                        @else
                                                            @icon('chevron-right', 'w-3 h-3 text-gray-400 mt-1')
                                                        @endif
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span>{{ $subgroup['name'] ?? '' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="index-table-td">
                                                @if (!empty($subgroup['request_allowed']))
                                                    <span class="text-lg font-semibold text-green-600">+</span>
                                                @else
                                                    <span class="text-lg font-semibold text-red-600">-</span>
                                                @endif
                                            </td>
                                            <td class="index-table-td font-semibold">
                                                {{ $subgroup['code'] ?? '-' }}
                                            </td>
                                            <td class="index-table-td">
                                                @if (!empty($subgroup['is_active']))
                                                    <span class="badge-green">
                                                        {{ __('forms.status.active') }}
                                                    </span>
                                                @else
                                                    <span class="badge-red">
                                                        {{ __('forms.status.non_active') }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>

                                        {{-- Services under subgroup --}}
                                        @if ($subgroupHasServices)
                                            @foreach ($subgroup['services'] as $service)
                                                <tr x-cloak
                                                    class="index-table-tr bg-green-50/50 dark:bg-green-900/20"
                                                    x-show="openIds['{{ $itemId }}'] && openIds['{{ $groupId }}'] && openIds['{{ $subgroupId }}']"
                                                    x-transition:enter="transition ease-out duration-150"
                                                    x-transition:enter-start="opacity-0"
                                                    x-transition:enter-end="opacity-100"
                                                >
                                                    <td class="index-table-td-primary">
                                                        <div class="flex items-start gap-3">
                                                            <div class="mt-1 flex-shrink-0 w-6 ml-18">
                                                                @icon('chevron-right', 'w-3 h-3 text-gray-400 mt-1')
                                                            </div>
                                                            <div class="flex flex-col">
                                                                <span>{{ $service['name'] ?? '' }}</span>
                                                                @if (!empty($service['category']))
                                                                    <span
                                                                        class="text-xs text-gray-600 bg-gray-100 dark:bg-gray-700 px-1 rounded inline-block w-fit mt-1">
                                                                        {{ $this->dictionaries['SERVICE_CATEGORY'][$service['category']] }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="index-table-td">
                                                        @if (!empty($service['request_allowed']))
                                                            <span class="text-lg font-semibold text-green-600">+</span>
                                                        @else
                                                            <span class="text-lg font-semibold text-red-600">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="index-table-td font-semibold">
                                                        {{ $service['code'] ?? '-' }}
                                                    </td>
                                                    <td class="index-table-td">
                                                        @if (!empty($service['is_active']))
                                                            <span class="badge-green">
                                                                {{ __('forms.status.active') }}
                                                            </span>
                                                        @else
                                                            <span class="badge-red">
                                                                {{ __('forms.status.non_active') }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    @if ($selectionMode)
                                                        <td class="index-table-td text-center">
                                                            @if (!empty($service['is_active']))
                                                                <button
                                                                    type="button"
                                                                    wire:click="selectService(@js($service['id']))"
                                                                    class="inline-flex cursor-pointer items-center justify-center"
                                                                    title="{{ __('forms.select') }}"
                                                                >
                                                                    @icon('plus-circle', 'w-6 h-6')
                                                                </button>
                                                            @endif
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @endif
                                    @endforeach
                                @endif

                                {{-- Services directly under group --}}
                                @if ($groupHasServices && !$groupHasGroups)
                                    @foreach ($group['services'] as $service)
                                        <tr x-cloak
                                            class="index-table-tr bg-green-50/50 dark:bg-green-900/20"
                                            x-show="openIds['{{ $itemId }}'] && openIds['{{ $groupId }}']"
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0"
                                            x-transition:enter-end="opacity-100"
                                        >
                                            <td class="index-table-td-primary">
                                                <div class="flex items-start gap-3">
                                                    <div class="mt-1 flex-shrink-0 w-6 ml-12">
                                                        @icon('chevron-right', 'w-3 h-3 text-gray-400 mt-1')
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span>{{ $service['name'] ?? '' }}</span>
                                                        @if (!empty($service['category']))
                                                            <span
                                                                class="text-xs text-gray-600 bg-gray-100 dark:bg-gray-700 px-1 rounded inline-block w-fit mt-1">
                                                                {{ $service['category'] }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="index-table-td">
                                                @if (!empty($service['request_allowed']))
                                                    <span class="text-lg font-semibold text-green-600">+</span>
                                                @else
                                                    <span class="text-lg font-semibold text-red-600">-</span>
                                                @endif
                                            </td>
                                            <td class="index-table-td font-semibold">
                                                {{ $service['code'] ?? '-' }}
                                            </td>
                                            <td class="index-table-td">
                                                @if (!empty($service['is_active']))
                                                    <span class="badge-green">
                                                        {{ __('forms.status.active') }}
                                                    </span>
                                                @else
                                                    <span class="badge-red">
                                                        {{ __('forms.status.non_active') }}
                                                    </span>
                                                @endif
                                            </td>
                                            @if ($selectionMode)
                                                <td class="index-table-td text-center">
                                                    @if (!empty($service['is_active']))
                                                        <button
                                                            type="button"
                                                            wire:click="selectService(@js($service['id']))"
                                                            class="inline-flex cursor-pointer items-center justify-center"
                                                            title="{{ __('forms.select') }}"
                                                        >
                                                            @icon('plus-circle', 'w-6 h-6')
                                                        </button>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        @endif
                    @empty
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if ($services->isEmpty())
                <x-nothing-found class="!mx-auto mt-8 shift-content" maxWidth="" />
            @endif
        </div>
    </div>

    <div class="pagination">
        {{ $this->services->links() }}
    </div>

    <x-forms.loading />
    <livewire:components.x-message :key="time()" />
</div>
