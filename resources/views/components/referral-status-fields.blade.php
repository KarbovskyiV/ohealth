@props(['reasonModel', 'letterModel', 'reasons'])

<div class="space-y-4">
    <div>
        <label class="default-label" for="referral-status-reason">{{ __('care-plan.referral_status_reason') }} *</label>
        <select id="referral-status-reason" wire:model="{{ $reasonModel }}" class="input-modal">
            <option value="">{{ __('forms.select') }}</option>
            @foreach ($reasons as $code => $description)
                <option value="{{ $code }}">{{ $description }}</option>
            @endforeach
        </select>
        @error($reasonModel)
            <p class="text-error">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="default-label" for="referral-explanatory-letter"
            >{{ __('care-plan.referral_recall_letter') }} *</label>
        <textarea id="referral-explanatory-letter" wire:model="{{ $letterModel }}" class="input-modal"></textarea>
        @error($letterModel)
            <p class="text-error">{{ $message }}</p>
        @enderror
    </div>
</div>
