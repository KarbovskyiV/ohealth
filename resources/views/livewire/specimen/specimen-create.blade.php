@php
    $patientName = $patientFullName ?? __('forms.patient');
    $title = __('specimens.add_specimen') . ' - ' . $patientName;
@endphp

<x-layouts.patient
    :personId="$personId"
    :prepersonId="$prepersonId"
    :patientFullName="$patientName"
    :hideNavigation="true"
    :title="$title"
    activeTab="specimens"
>
    <x-slot name="headerActions"></x-slot>

    <div class="breadcrumb-form shift-content p-4">
        <form
            wire:submit.prevent="save"
            x-data="{
                modalSpecimen: $wire.entangle('form.specimen'),
                specimenTypesDictionary: $wire.dictionaries['specimen_types'],

                init() {
                    this.modalSpecimen = new Specimen(this.modalSpecimen);
                },

                get currentEmployee() {
                    return (
                        $wire.registeredByEmployees.find(
                            (employee) => employee.uuid === this.modalSpecimen.registeredById,
                        ) || {}
                    );
                },

                parentOptions() {
                    return $wire.specimens;
                },

                changeRegisteredBy() {
                    if (this.modalSpecimen.collectorType === 'current') {
                        this.changeCollectorType();
                    }
                },

                changeCollectorType() {
                    const collectorIds = {
                        current: this.currentEmployee.uuid,
                        patient: $wire.patientUuid,
                    };

                    this.modalSpecimen.collectorId = collectorIds[this.modalSpecimen.collectorType] || '';
                },
            }"
        >
            <div wire:ignore>
                @include('livewire.specimen.parts.general-info')
                @include('livewire.specimen.parts.material-collection')
                @include('livewire.specimen.parts.containers')
            </div>

            <div class="mt-6 flex justify-start gap-4">
                <button type="submit" class="button-primary-outline flex items-center gap-2">
                    @icon('archive', 'w-4 h-4')
                    {{ __('forms.save') }}
                </button>

                <button
                    @click="$wire.showSignatureModal = true"
                    type="button"
                    class="button-primary flex items-center gap-2"
                >
                    @icon('key', 'w-5 h-5')
                    {{ __('forms.complete_the_interaction_and_sign') }}
                    @icon('arrow-right', 'w-5 h-5')
                </button>
            </div>
        </form>
    </div>

    <x-signature-modal method="sign" />

    @include('livewire.specimen.parts.specimen-classes')
</x-layouts.patient>
