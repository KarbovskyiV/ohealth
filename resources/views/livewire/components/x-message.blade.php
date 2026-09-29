<div
    x-data="message(@js((bool) (session('error') || session('success') || session('status') || session('info') || session('warning'))))"
    @if ($listenAsync)
        @flash-message.window="handleFlash($event)"
        x-init="setupListeners()"
    @endif
>
    @if (session('error') || session('success') || session('status') || session('info') || session('warning'))
        <div class="alert-message fixed top-6 right-2 z-99999 flex w-auto" x-show="showAlertMessage">
            @session('error')
                <div
                    role="alert"
                    class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-gray-800 dark:text-red-400"
                >
                    <span class="font-medium whitespace-pre-line">{{ session('error') }}</span>
                </div>
            @endsession

            @session('success')
                <div
                    role="alert"
                    class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-gray-800 dark:text-green-400"
                >
                    <span class="font-medium whitespace-pre-line">{{ session('success') }}</span>
                </div>
                {{ session()->forget('success') }}
            @endsession

            @session('info')
                <div
                    role="alert"
                    class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-900 dark:bg-gray-800 dark:text-blue-300"
                >
                    <span class="font-medium whitespace-pre-line">{{ session('info') }}</span>
                </div>
            @endsession

            @session('warning')
                <div
                    role="alert"
                    class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800 dark:border-yellow-900 dark:bg-gray-800 dark:text-yellow-300"
                >
                    <span class="font-medium whitespace-pre-line">{{ session('warning') }}</span>
                </div>
            @endsession

            @session('status')
                <x-message.successes>
                    <x-slot name="status">{{ session('status') }}</x-slot>
                </x-message.successes>
            @endsession

            <button
                type="button"
                @click.stop="showAlertMessage = false"
                aria-label="Close"
                class="absolute -top-2 -right-1 z-[100000] inline-flex h-6 w-6 cursor-pointer items-center justify-center rounded-full border border-red-300 bg-white/90 text-gray-600 shadow-lg drop-shadow-sm transition-all hover:border-2 hover:border-red-400 hover:bg-white hover:text-gray-800 active:border-red-600"
            >
                @icon('close', 'w-3.5 h-3.5')
            </button>
        </div>
    @endif

    <div
        class="alert-message fixed top-6 right-2 z-99999 flex w-auto"
        x-show="showDynamicMessage"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-[-20px]"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-[-20px]"
        style="display: none"
    >
        <div
            role="alert"
            :class="{
                'text-red-800 bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-900':
                    dynamicType === 'error',
                'text-green-800 bg-green-50 dark:bg-gray-800 dark:text-green-400 border border-green-200 dark:border-green-900':
                    dynamicType === 'success',
                'text-blue-800 bg-blue-50 dark:bg-gray-800 dark:text-blue-300 border border-blue-200 dark:border-blue-900':
                    dynamicType === 'info',
                'text-yellow-800 bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-900':
                    dynamicType === 'warning',
            }"
            class="mb-4 rounded-lg p-4 pr-10 text-sm shadow-xl"
        >
            <span class="font-medium whitespace-pre-line" x-text="dynamicText"></span>
        </div>
        <button
            type="button"
            @click="showDynamicMessage = false"
            aria-label="Close"
            class="absolute -top-2 -right-1 z-[100000] inline-flex h-6 w-6 cursor-pointer items-center justify-center rounded-full border border-red-300 bg-white/90 text-gray-600 shadow-lg drop-shadow-sm transition-all hover:border-2 hover:border-red-400 hover:bg-white hover:text-gray-800 active:border-red-600"
        >
            @icon('close', 'w-3.5 h-3.5')
        </button>
    </div>
</div>

@if ($consumeMessages)
    @php(session()->forget(['error', 'success', 'status', 'info', 'warning']))
@endif

<script>
    (() => {
        const registerMessage = () =>
            Alpine.data('message', (showAlertMessage = false) => ({
                showAlertMessage,
                showDynamicMessage: false,
                dynamicText: '',
                dynamicType: 'success',
                alertTimeout: null,
                init() {
                    if (this.showAlertMessage) {
                        this.alertTimeout = setTimeout(() => (this.showAlertMessage = false), 30000);
                    }
                },
                destroy() {
                    clearTimeout(this.alertTimeout);
                },
                setupListeners() {
                    let handler = (data) => {
                        let item = Array.isArray(data)
                            ? data[0]
                            : data.detail
                              ? Array.isArray(data.detail)
                                  ? data.detail[0]
                                  : data.detail
                              : data;
                        if (item && (item.message || item.text)) {
                            let text = item.message || item.text;
                            if (
                                window._lastFlashMessage === text &&
                                window._lastFlashTime &&
                                Date.now() - window._lastFlashTime < 500
                            ) {
                                return;
                            }
                            window._lastFlashMessage = text;
                            window._lastFlashTime = Date.now();

                            this.showAlertMessage = false;
                            this.dynamicText = text;
                            this.dynamicType = item.type || 'success';
                            this.showDynamicMessage = true;
                            if (window._flashToastTimeout) clearTimeout(window._flashToastTimeout);
                            window._flashToastTimeout = setTimeout(() => (this.showDynamicMessage = false), 30000);
                        }
                    };
                    window.addEventListener('flashMessage', handler);
                    window.addEventListener('flash-message', handler);
                    if (typeof Livewire !== 'undefined') {
                        Livewire.on('flashMessage', handler);
                    } else {
                        document.addEventListener('livewire:init', () => {
                            Livewire.on('flashMessage', handler);
                        });
                    }
                },
                handleFlash(event) {
                    let item = event.detail ? (Array.isArray(event.detail) ? event.detail[0] : event.detail) : event;
                    if (item && (item.message || item.text)) {
                        let text = item.message || item.text;
                        if (
                            window._lastFlashMessage === text &&
                            window._lastFlashTime &&
                            Date.now() - window._lastFlashTime < 500
                        ) {
                            return;
                        }
                        window._lastFlashMessage = text;
                        window._lastFlashTime = Date.now();

                        this.showAlertMessage = false;
                        this.dynamicText = text;
                        this.dynamicType = item.type || 'success';
                        this.showDynamicMessage = true;
                        if (window._flashToastTimeout) clearTimeout(window._flashToastTimeout);
                        window._flashToastTimeout = setTimeout(() => (this.showDynamicMessage = false), 30000);
                    }
                },
            }));
        if (window.Alpine) {
            registerMessage();
        } else {
            document.addEventListener('alpine:init', registerMessage, { once: true });
        }
    })();
</script>
