<div
    x-show="showDiagnosisGroupsDrawer"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    @click="showDiagnosisGroupsDrawer = false; selectedDataType = ''; selectedDiagnosisGroup = '';"
    class="fixed inset-0 bg-gray-900/50"
    style="z-index: 55"
></div>

<div
    x-show="showDiagnosisGroupsDrawer"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="translate-x-full"
    x-cloak
    class="fixed top-0 right-0 h-screen w-4/5 overflow-y-auto bg-white p-4 pt-20 shadow-2xl transition-transform dark:bg-gray-800"
    style="z-index: 55"
    id="data-access-diagnosis-groups-drawer"
    tabindex="-1"
>
    <h3 class="modal-header">
        {{ __('patients.get_data_access') }}
    </h3>

    <div class="mt-8 max-w-md">
        <div class="form-group group">
            <select class="input-select peer w-full pointer-events-none">
                <option value="diagnosis_groups" selected>{{ __('patients.diagnosis_group') }}</option>
            </select>
            <label class="label">{{ __('patients.data_type') }}</label>
        </div>

        <div class="mt-8 form-group group">
            <select class="input-select peer w-full" x-model="selectedDiagnosisGroup">
                <option value="" disabled selected>{{ __('forms.select') }}</option>
                <option value="B25-B34">B25-B34 - Інші вірусні хвороби</option>
            </select>
            <label class="label">{{ __('patients.diagnosis_group') }}</label>
        </div>

        <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800" x-show="selectedDiagnosisGroup === 'B25-B34'" x-cloak>
            <p class="text-sm font-medium text-gray-900 dark:text-white">B25-B34 - Інші вірусні хвороби</p>
            <a href="#" class="text-xs text-gray-500 underline hover:text-gray-700 dark:text-gray-400 mt-1 inline-block">{{ __('patients.list_of_diagnosis_codes') }}</a>
        </div>

        <div class="mt-8 flex gap-3">
            <button class="button-minor" type="button" @click="showDiagnosisGroupsDrawer = false; selectedDataType = ''; selectedDiagnosisGroup = '';">
                {{ __('forms.cancel') }}
            </button>
            <button class="button-primary" type="button" @click="showDiagnosisCodesDrawer = true">
                {{ __('patients.get_access') }}
            </button>
        </div>
    </div>
</div>
