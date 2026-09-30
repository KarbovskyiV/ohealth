<div
    x-show="showAllDataDrawer"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    @click="showAllDataDrawer = false; selectedDataType = '';"
    class="fixed inset-0 bg-gray-900/50"
    style="z-index: 55"
></div>

<div
    x-show="showAllDataDrawer"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="translate-x-full"
    x-cloak
    class="fixed top-0 right-0 h-screen w-4/5 overflow-y-auto bg-white p-4 pt-20 shadow-2xl transition-transform dark:bg-gray-800"
    style="z-index: 55"
    id="data-access-all-drawer"
    tabindex="-1"
>
    <h3 class="modal-header">
        {{ __('patients.get_data_access') }}
    </h3>

    <div class="mt-8 max-w-md">
        <div class="form-group group">
            <select class="input-select peer w-full pointer-events-none">
                <option value="all" selected>{{ __('patients.data_access_types.all') }}</option>
            </select>
            <label class="label">{{ __('patients.data_type') }}</label>
        </div>

        <div class="mt-8 flex gap-3">
            <button class="button-minor" type="button" @click="showAllDataDrawer = false; selectedDataType = '';">
                {{ __('forms.cancel') }}
            </button>
            <button class="button-primary" type="button" @click="showAllDataDrawer = false; selectedDataType = ''; showDataAccessDrawer = false; $wire.set('authStep', {{ \App\Enums\Person\AuthStep::INITIAL->value }}); $wire.set('showAuthMethodModal', true);">
                {{ __('patients.get_access') }}
            </button>
        </div>
    </div>
</div>

