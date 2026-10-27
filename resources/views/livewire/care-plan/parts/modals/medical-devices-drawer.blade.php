<x-dialog-drawer
    id="medical-devices-drawer-right"
    x-model="showMedicalDeviceDrawer"
    maxWidth="4/5"
    zIndex="40"
    overlayWidth="100%"
    onCloseClick="showMedicalDeviceDrawer = false"
>
    <x-slot name="title">{{ __('care-plan.new_medical_device_prescription') }}</x-slot>

    {{-- Content --}}
    <form>
        {{-- Program Selection Section --}}
        <fieldset class="fieldset">
            <legend class="legend">{{ __('care-plan.program_selection') }}</legend>

            <div class="form-row-3">
                <div class="form-group group">
                    <label for="medical_device_program" class="label"> {{ __('care-plan.program') }} </label>
                    <select
                        id="medical_device_program"
                        name="medical_device_program"
                        class="input-select peer"
                        wire:model.live="selectedProgram"
                    >
                        <option value="">{{ __('care-plan.without_medical_program') }}</option>
                        @foreach (($dictionaries['medical_programs_device'] ?? $dictionaries['medical_programs'] ?? []) as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </fieldset>

        <div class="mt-6 flex justify-start gap-3">
            <button
                type="button"
                class="button-minor"
                aria-controls="medical-devices-drawer-right"
                @click="showMedicalDeviceDrawer = false"
            >
                {{ __('forms.cancel') }}
            </button>

            <button type="button" class="button-primary" wire:click="openMedicalDeviceSearch">
                {{ __('forms.continue') }}
            </button>
        </div>
    </form>
</x-dialog-drawer>
