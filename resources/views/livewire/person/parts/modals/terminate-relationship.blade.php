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
                    <div role="alert" class="mb-6 rounded-lg p-4" style="background-color: #fffbe6">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex-shrink-0">
                                @icon('alert-circle', 'w-5 h-5 text-gray-600 dark:text-gray-400')
                            </div>
                            <div class="flex-1 text-sm text-gray-700 dark:text-gray-300">
                                <p class="mb-2">{{ __('patients.terminate_relationship_warning_1') }}</p>
                                <p>{{ __('patients.terminate_relationship_warning_2') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex justify-start gap-4">
                        <button
                            type="button"
                            @click="
                                const parentData = getParentData();
                                if (parentData) parentData.showTerminateModal = false;
                            "
                            class="button-minor"
                        >
                            {{ __('forms.cancel') }}
                        </button>

                        <button
                            type="button"
                            @click="
                                const parentData = getParentData();
                                if (parentData) {
                                    parentData.showTerminateModal = false;
                                    parentData.showSignatureDrawer = false;
                                    parentData.showAuthDrawer = false;
                                }
                            "
                            class="button-outline-primary"
                        >
                            {{ __('patients.to_authentication_methods') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
