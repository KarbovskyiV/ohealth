{{-- Modal for terminating relationship with representative --}}
<div
    x-data="{
        getParentData() {
            const scope = document.querySelector('[data-confidant-scope]');

            return scope ? Alpine.$data(scope) : null;
        },
        isVisible: false,
    }"
    x-effect="isVisible = getParentData()?.showTerminateModal ?? false"
>
    <template x-teleport="body">
        <div
            x-show="isVisible"
            style="display: none; z-index: 100"
            @keydown.escape.prevent.stop="
                const parentData = getParentData();
                if (parentData) parentData.showTerminateModal = false;
            "
            role="dialog"
            aria-modal="true"
            class="modal"
        >
            <div x-transition.opacity class="fixed inset-0 bg-black/30"></div>
            <div
                x-transition
                @click="
                    const parentData = getParentData();
                    if (parentData) parentData.showTerminateModal = false;
                "
                class="modal-wrapper"
            >
                <div @click.stop x-trap.noscroll.inert="isVisible" class="modal-content mx-auto w-full max-w-lg">
                    {{-- Title --}}
                    <h2 class="mb-6 text-xl font-bold text-gray-900 dark:text-white">
                        {{ __('patients.relationship_terminated') }}
                    </h2>

                    {{-- Warning Box --}}
                    <div
                        role="alert"
                        class="mb-6 rounded-lg bg-yellow-50 p-4 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300"
                    >
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 shrink-0">
                                @icon('alert-circle', 'w-5 h-5')
                            </div>
                            <div class="flex-1 text-sm">
                                <p class="mb-2">{{ __('patients.terminate_relationship_warning_1') }}</p>
                                <p>{{ __('patients.terminate_relationship_warning_2') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Action Button --}}
                    <div class="flex justify-start">
                        <button
                            type="button"
                            @click="
                                const parentData = getParentData();
                                if (parentData) parentData.showTerminateModal = false;
                            "
                            class="button-primary"
                        >
                            {{ __('forms.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
