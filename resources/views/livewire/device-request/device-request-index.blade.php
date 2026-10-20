<div x-data="{ showCreateRequest: false }">
    <livewire:components.x-message
        :consume-messages="true"
        :key="(string) str()->uuid()"
    />
    <x-forms.loading />

    <x-header-navigation class="items-start">
        <x-slot name="title">
            {{ __('device-requests.index_title') }}
        </x-slot>

        <div
            class="mt-3 ml-0 flex flex-col gap-2 self-start sm:flex-row sm:flex-wrap"
        >
            <button
                type="button"
                data-modal-target="create-device-modal"
                data-modal-toggle="create-device-modal"
                class="button-primary flex items-center gap-2"
            >
                @icon ('plus', 'w-4 h-4')
                <span>{{ __('device-requests.create_device') }}</span>
            </button>
            <button
                type="button"
                @click="showCreateRequest = true"
                class="button-primary-outline flex items-center gap-2"
            >
                @icon ('plus', 'w-4 h-4')
                <span>{{ __('device-requests.breadcrumb') }}</span>
            </button>
        </div>
    </x-header-navigation>

    <div class="shift-content mt-8 flow-root pl-3.5">
        <div class="max-w-screen-xl">
            <div
                class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"
            >
                <p class="font-semibold">{{ __('device-requests.executor_deferred') }}</p>
                <p class="mt-2">{{ __('device-requests.executor_description') }}</p>
            </div>

            <div
                class="mt-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="mb-4 w-full">
                    <p class="text-sm font-normal text-gray-500 dark:text-gray-400">{{ __('device-requests.list_description') }}</p>
                </div>

                <!-- Placeholder for table/list -->
                <div
                    class="rounded-lg bg-gray-100 p-4 text-sm text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                >
                    {{ __('device-requests.empty_list') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Create Modal (Includes DeviceRequestForm) -->
    <div
        id="create-device-modal"
        tabindex="-1"
        aria-hidden="true"
        class="h-modal fixed top-0 right-0 left-0 z-50 hidden w-full items-center justify-center overflow-x-hidden overflow-y-auto md:inset-0 md:h-full"
    >
        <div class="relative h-full w-full max-w-4xl p-4 md:h-auto">
            <div class="relative rounded-lg bg-white shadow dark:bg-gray-800">
                <div
                    class="flex items-start justify-between rounded-t border-b p-4 dark:border-gray-600"
                >
                    <h3
                        class="text-xl font-semibold text-gray-900 dark:text-white"
                    >
                        {{ __('device-requests.create_device_title') }}
                    </h3>
                    <button
                        type="button"
                        class="ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                        data-modal-hide="create-device-modal"
                    >
                        @icon ('close')
                    </button>
                </div>
                <div class="space-y-6 p-6">
                    @livewire ('device-request.device-request-form', ['legalEntity' => $legalEntity])
                </div>
            </div>
        </div>
    </div>
    <x-dialog-drawer
        x-model="showCreateRequest"
        maxWidth="4/5"
        zIndex="50"
        onCloseClick="showCreateRequest = false"
        title="{{ __('device-requests.title') }}"
    >
        @include ('livewire.device-request.device-request-create')
    </x-dialog-drawer>
</div>
