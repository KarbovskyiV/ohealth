<div
    x-show="showDiagnosisCodesDrawer"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    @click="showDiagnosisCodesDrawer = false"
    class="fixed inset-0 bg-gray-900/50"
    style="z-index: 65"
></div>

<div
    x-show="showDiagnosisCodesDrawer"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="translate-x-full"
    x-cloak
    class="fixed top-0 right-0 h-screen w-4/5 overflow-y-auto bg-white p-4 pt-20 shadow-2xl transition-transform dark:bg-gray-800"
    style="z-index: 65"
    id="data-access-diagnosis-codes-drawer"
    tabindex="-1"
>
    <h3 class="modal-header">
        B25-B34 - Інші вірусні хвороби - {{ __('patients.list_of_diagnosis_codes') }}
    </h3>

    <div class="mt-8 max-w-3xl">
        <div class="flex items-center gap-6 mb-8">
            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('patients.coding') }}</span>
            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="coding_system" value="ICPC" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ICPC</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="coding_system" value="MKX-10" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" checked>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">МКХ-10</span>
                </label>
            </div>
        </div>

        <div class="space-y-1.5 text-sm font-medium text-gray-900 dark:text-gray-100">
            <p>B20 - Хвороба, зумовлена вірусом імунодефіциту людини [ВІЛ], яка проявляється інфекційними та паразитарними хворобам</p>
            <p>B21 - Хвороба, зумовлена вірусом імунодефіциту людини [ВІЛ], внаслідок чого виникають злоякісні овоутворення</p>
            <p>22 - Хвороба, зумовлена вірусом імунодефіциту людини [ВІЛ], з проявами інши точнених хворо</p>
            <p>B23.0 - Гострий ВІЛ-інфекційний синдром</p>
            <p>B23.8 - Хвороба ВІЛ з проявами інших уточнених станів</p>
            <p>B24 - Хвороба, зумовлена вірусом імунодефіциту людини [ВІЛ], неуточнена</p>
            <p>098.7 - Вірус імунодефіциту людини [ВІЛ] під час вагітності, пологів та післяпологового періоду</p>
            <p>R75 - Лабораторне виявлення вірусу імунодефіциту людини [ВІЛ]</p>
            <p>Z11.4 - Спеціальне скринінгове обстеження з метою виявлення інфікування вірусом імунодефіциту людини [ВІЛ]</p>
            <p>Z20.6 - Контакт з хворим або можливість зараження вірусом імунодефіциту людини [ВІЛ]</p>
            <p>Z21 - Безсимптомне носійство вірусу імунодефіциту людини [ВІЛ]</p>
            <p>Z71.7 - Консультації з питань, пов'язаних з вірусом імунодефіциту людини [ВІЛ]</p>
        </div>

        <div class="mt-8 flex gap-3">
            <button class="button-minor" type="button" @click="showDiagnosisCodesDrawer = false">
                {{ __('forms.back') }}
            </button>
        </div>
    </div>
</div>
