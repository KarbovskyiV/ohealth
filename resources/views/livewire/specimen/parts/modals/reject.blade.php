<x-modal wire:model="showRejectModal" maxWidth="3xl">
    <div class="bg-white p-6 text-left md:p-8 dark:bg-gray-800">
        <h2 class="mb-6 text-xl font-bold text-gray-900 md:text-2xl dark:text-white">
            {{ __('specimens.reject_title') }}
        </h2>

        <div class="form-row-2 mt-6">
            <div class="form-group group">
                <select id="reject-reason" wire:model="rejectReason" class="input-select peer w-full">
                    <option value="" selected>{{ __('forms.select') }}</option>
                    @foreach ($dictionaries['specimen_reject_reasons'] ?? [] as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </select>
                <label for="reject-reason" class="label"> {{ __('specimens.reject_reason') }}* </label>
                @error('rejectReason')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="mt-8 flex items-center justify-start gap-4">
            <button type="button" @click="show = false" class="button-minor min-w-30">
                {{ __('forms.cancel') }}
            </button>

            <button type="button" wire:click="reject" wire:loading.attr="disabled" class="button-danger min-w-30">
                <span wire:loading.remove wire:target="reject"> {{ __('specimens.reject') }} </span>
                <span wire:loading wire:target="reject"> ... </span>
            </button>
        </div>
    </div>
</x-modal>
