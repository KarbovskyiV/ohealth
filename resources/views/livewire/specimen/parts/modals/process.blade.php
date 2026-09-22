<x-modal wire:model="showProcessModal" maxWidth="3xl">
    <div class="bg-white p-6 text-left md:p-8 dark:bg-gray-800">
        <h2 class="mb-6 text-xl font-bold text-gray-900 md:text-2xl dark:text-white">
            {{ __('specimens.mark_received_title') }}
        </h2>

        <div class="form-row-2 mt-6">
            <div class="flex w-full items-start">
                <div class="form-group group mb-0! min-w-0 flex-1">
                    <div class="datepicker-wrapper w-full">
                        <input
                            type="text"
                            id="received-date"
                            wire:key="received-date-{{ data_get($specimen, 'uuid') }}"
                            wire:model="form.receivedDate"
                            datepicker-format="{{ frontendDateFormat() }}"
                            datepicker-min-date="{{ formatDisplayDate(convertToLocalTimezone(data_get($specimen, 'collection.collectedDateTime') ?? data_get($specimen, 'collection.collectedPeriod.end') ?? '')) }}"
                            datepicker-max-date="{{ now()->format(config('app.date_format')) }}"
                            class="datepicker-input with-leading-icon input peer w-full"
                            placeholder=" "
                            required
                        />
                        <label for="received-date" class="wrapped-label w-auto! max-w-none! overflow-visible!">
                            {{ __('specimens.date_time_received') }}
                        </label>
                    </div>
                </div>

                <div class="group relative mb-0! w-24! shrink-0">
                    <label for="received-time" class="sr-only">{{ __('specimens.date_time_received') }} (час)</label>
                    <div class="relative flex w-full items-center">
                        @icon('mingcute-time-fill', 'absolute top-2.5 left-2.5 w-4 h-4 text-gray-500')
                        <input
                            type="text"
                            id="received-time"
                            wire:model="form.receivedTime"
                            class="timepicker-uk with-leading-icon input peer w-full"
                            placeholder=" "
                            required
                        />
                    </div>
                </div>
            </div>
        </div>

        @error('form.receivedDate')
            <p class="text-error mt-1 text-xs">{{ $message }}</p>
        @enderror
        @error('form.receivedTime')
            <p class="text-error mt-1 text-xs">{{ $message }}</p>
        @enderror

        <div class="mt-8 flex items-center justify-start gap-4">
            <button type="button" @click="show = false" class="button-minor min-w-30">{{ __('forms.cancel') }}</button>

            <button
                type="button"
                wire:click="process"
                wire:loading.attr="disabled"
                class="button-primary min-w-30"
            >
                <span wire:loading.remove wire:target="process"> {{ __('forms.confirm') }} </span>
                <span wire:loading wire:target="process"> ... </span>
            </button>
        </div>
    </div>
</x-modal>
