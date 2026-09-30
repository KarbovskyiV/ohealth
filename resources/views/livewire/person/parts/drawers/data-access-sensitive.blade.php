<div
    x-show="showSensitiveDataDrawer"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    @click="showSensitiveDataDrawer = false; selectedDataType = '';"
    class="fixed inset-0 bg-gray-900/50"
    style="z-index: 55"
></div>

<div
    x-show="showSensitiveDataDrawer"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="translate-x-full"
    x-cloak
    class="fixed top-0 right-0 h-screen w-4/5 overflow-y-auto bg-white p-4 pt-20 shadow-2xl transition-transform dark:bg-gray-800"
    style="z-index: 55"
    id="data-access-sensitive-drawer"
    tabindex="-1"
>
    <h3 class="modal-header">
        {{ __('patients.get_data_access') }}
    </h3>

    <div class="mt-8 max-w-md">
        <div class="form-group group mb-6">
            <select class="input-select peer w-full pointer-events-none">
                <option value="sensitive" selected>{{ __('patients.data_access_types.sensitive') }}</option>
            </select>
            <label class="label">{{ __('patients.data_type') }}</label>
            <label class="label">{{ __('patients.data_type') }}</label>
        </div>

        <div class="form-group group mb-6">
            <select class="input-select peer w-full" x-model="selectedSensitiveGroup">
                <option value="" disabled selected>{{ __('forms.select') }}</option>
                <template x-for="group in forbiddenGroups" :key="group.id">
                    <option :value="group.id" x-text="group.name"></option>
                </template>
            </select>
            <label class="label">{{ __('dictionaries.forbidden_group.group_label') }}</label>
        </div>

        <template x-if="selectedSensitiveGroup">
            <div class="mt-4 mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800" x-cloak>
                <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="forbiddenGroups.find(g => g.id === selectedSensitiveGroup)?.name"></div>
                
                <div class="mt-2 flex flex-col gap-1">
                    <button type="button" class="text-left text-xs text-blue-600 hover:underline inline-block w-fit" @click="showSensitiveCodesDrawer = true">
                        {{ __('patients.list_of_diagnosis_codes') }}
                    </button>
                    <button type="button" class="text-left text-xs text-blue-600 hover:underline inline-block w-fit">
                        {{ __('dictionaries.forbidden_group.services_list_title') }}
                    </button>
                </div>
            </div>
        </template>

        <div class="form-group group mb-6">
            <input type="text" class="input peer w-full pointer-events-none" value="{{ __('patients.read') }}" readonly>
            <label class="label">{{ __('patients.access_level') }}</label>
        </div>

        <div class="mt-8 flex gap-3">
            <button class="button-minor" type="button" @click="showSensitiveDataDrawer = false; selectedDataType = '';">
                {{ __('forms.cancel') }}
            </button>
            <button class="button-primary" type="button" @click="showSensitiveCodesDrawer = true">
                {{ __('patients.get_access') }}
            </button>
        </div>
    </div>
</div>

