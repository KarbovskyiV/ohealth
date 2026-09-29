<div
    x-show="showServiceCodesDrawer"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    @click="showServiceCodesDrawer = false"
    class="fixed inset-0 bg-gray-900/50"
    style="z-index: 65"
></div>

<div
    x-show="showServiceCodesDrawer"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="translate-x-full"
    x-cloak
    class="fixed top-0 right-0 h-screen w-4/5 overflow-y-auto bg-white p-4 pt-20 shadow-2xl transition-transform dark:bg-gray-800"
    style="z-index: 65"
    id="data-access-service-codes-drawer"
    tabindex="-1"
>
    <h3 class="modal-header mb-8">
        451 - Стоматологічна рентгеноскопія - {{ __('patients.list_of_services') }}
    </h3>

    <div class="mt-8 max-w-3xl">
        <div class="space-y-1.5 text-sm font-medium text-gray-900 dark:text-gray-100">
            <p>97022-00, - Інтраоральна періапікальна або прикусна рентгенографія, одна експозиція</p>
            <p>97025-00 - Інтраоральна оклюзійна рентгенографія, одна експозиція</p>
            <p>97039-00 - Томографія черепа, або частини черепа</p>
        </div>

        <div class="mt-12 flex gap-3">
            <button class="button-minor" type="button" @click="showServiceCodesDrawer = false">
                {{ __('forms.back') }}
            </button>
        </div>
    </div>
</div>
