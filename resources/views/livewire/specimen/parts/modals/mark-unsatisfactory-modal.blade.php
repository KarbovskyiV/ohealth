<x-modal wire:model="showMarkUnsatisfactoryModal" maxWidth="3xl">
    <div class="p-6 md:p-8 text-left bg-white dark:bg-gray-800">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mb-6">
            {{ __('specimens.mark_unsatisfactory_title') }}
        </h2>

        <div class="form-row-2 mt-6">
            <div class="form-group group">
                <select
                    id="unsatisfactory-reason"
                    wire:model="unsatisfactoryReason"
                    class="input-select peer w-full"
                >
                    <option value="" selected>{{ __('forms.select') }}</option>
                    @foreach ($dictionaries['specimen_reject_reasons'] ?? [] as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </select>
                <label for="unsatisfactory-reason" class="label">
                    {{ __('specimens.unsatisfactory_reason') }}*
                </label>
                @error('unsatisfactoryReason')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="mt-8 flex items-center justify-start gap-4">
            <button type="button" @click="show = false" class="button-minor min-w-[120px]">
                {{ __('forms.cancel') }}
            </button>

            <button type="button"
                    wire:click="markUnsatisfactory"
                    wire:loading.attr="disabled"
                    class="button-danger min-w-[120px]"
            >
                <span wire:loading.remove wire:target="markUnsatisfactory">
                    {{ __('specimens.mark_unsatisfactory') }}
                </span>
                <span wire:loading wire:target="markUnsatisfactory">
                    ...
                </span>
            </button>
        </div>
    </div>
</x-modal>
