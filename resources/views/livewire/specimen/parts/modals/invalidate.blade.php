<x-modal wire:model="showInvalidateModal" maxWidth="3xl">
    <div class="bg-white p-6 text-left md:p-8 dark:bg-gray-800">
        <h2 class="mb-6 text-xl font-bold text-gray-900 md:text-2xl dark:text-white">
            {{ __('specimens.invalidate_title') }}
        </h2>

        <div class="form-row-2 mt-6">
            <div class="form-group group">
                <select id="invalidate-reason" wire:model="form.invalidateReason" class="input-select peer w-full">
                    <option value="" selected>{{ __('forms.select') }}</option>
                    @foreach ($dictionaries['specimen_invalidate_reasons'] ?? [] as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </select>
                <label for="invalidate-reason" class="label"> {{ __('specimens.invalidate_reason') }}* </label>
                @error('form.invalidateReason')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="mt-8 flex items-center justify-start gap-4">
            <button type="button" @click="show = false" class="button-minor min-w-30">{{ __('forms.cancel') }}</button>

            <button type="button" wire:click="invalidate" wire:loading.attr="disabled" class="button-danger min-w-30">
                <span wire:loading.remove wire:target="invalidate"> {{ __('specimens.invalidate') }} </span>
                <span wire:loading wire:target="invalidate"> ... </span>
            </button>
        </div>
    </div>
</x-modal>
