<x-dialog-drawer
    id="medications-drawer-right"
    x-model="showMedicationDrawer"
    maxWidth="4/5"
    zIndex="40"
    overlayWidth="100%"
    onCloseClick="showMedicationDrawer = false"
>
    <x-slot name="title">{{ __('care-plan.new_medication_prescription') }}</x-slot>

    {{-- Content --}}
    <form>
        {{-- Program Selection Section --}}
        <fieldset class="fieldset">
            <legend class="legend">{{ __('care-plan.program_selection') }}</legend>

            <div class="form-row-3">
                <div class="form-group group">
                    <label for="medication_program" class="label"> {{ __('care-plan.program') }}* </label>
                    <select
                        id="medication_program"
                        name="medication_program"
                        class="input-select peer"
                        wire:model.live="selectedProgram"
                    >
                        <option value="">{{ __('care-plan.prescription_medication') }}</option>
                        @foreach (($dictionaries['medical_programs_medication'] ?? $dictionaries['medical_programs'] ?? []) as $id => $name)
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
                aria-controls="medications-drawer-right"
                @click="showMedicationDrawer = false"
            >
                {{ __('forms.cancel') }}
            </button>

            <button
                type="button"
                class="button-primary"
                aria-controls="medication-search-drawer-right"
                @click="
                    showMedicationDrawer = false;
                    showMedicationSearchDrawer = true;
                "
            >
                {{ __('forms.continue') }}
            </button>
        </div>
    </form>
</x-dialog-drawer>
