<x-modal wire:model="showReceivedForResearchModal" maxWidth="3xl">
    <div class="p-6 md:p-8 text-left bg-white dark:bg-gray-800">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mb-6">
            {{ __('specimens.mark_received_title') }}
        </h2>

        <div class="form-row-2 mt-6">
            <div class="flex w-full items-start">
                <div class="form-group group mb-0! min-w-0 flex-1">
                    <div class="datepicker-wrapper w-full">
                        <input
                            type="text"
                            id="received-date"
                            wire:model="receivedForResearchDate"
                            datepicker-format="{{ frontendDateFormat() }}"
                            class="datepicker-input with-leading-icon input peer w-full"
                            placeholder=" "
                            required
                        />
                        <label for="received-date" class="wrapped-label w-auto! max-w-none! overflow-visible!">
                            {{ __('specimens.date_time_received') }}
                        </label>
                        @error('receivedForResearchDate')
                            <span class="text-sm text-red-500 absolute -bottom-5">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                
                <div class="group relative mb-0! w-24! shrink-0">
                    <label for="received-time" class="sr-only">{{ __('specimens.date_time_received') }} (час)</label>
                    <div class="relative flex w-full items-center">
                        @icon('mingcute-time-fill', 'absolute top-2.5 left-2.5 w-4 h-4 text-gray-500')
                        <input
                            type="text"
                            id="received-time"
                            wire:model="receivedForResearchTime"
                            class="timepicker-uk with-leading-icon input peer w-full"
                            placeholder=" "
                            required
                        />
                        @error('receivedForResearchTime')
                            <span class="text-sm text-red-500 absolute -bottom-5 right-0 whitespace-nowrap">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 flex items-center justify-start gap-4">
            <button type="button" @click="show = false" class="button-minor min-w-[120px]">
                {{ __('forms.cancel') }}
            </button>

            <button type="button"
                    wire:click="markReceivedForResearch"
                    wire:loading.attr="disabled"
                    class="button-primary min-w-[120px]"
            >
                <span wire:loading.remove wire:target="markReceivedForResearch">
                    {{ __('forms.confirm') }}
                </span>
                <span wire:loading wire:target="markReceivedForResearch">
                    ...
                </span>
            </button>
        </div>
    </div>
</x-modal>
