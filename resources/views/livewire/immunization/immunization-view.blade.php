@use('App\Models\MedicalEvents\Sql\CodeableConcept')

@php
    $vaccine = $immunization->vaccineCode?->coding->first();
    $site = $immunization->site?->coding->first();
    $route = $immunization->route?->coding->first();
    $reportOrigin = $immunization->reportOrigin?->coding->first();
    $doseQuantity = $immunization->doseQuantity;

    $title = collect([$vaccine?->code, $dictionaries['eHealth/vaccine_codes'][$vaccine?->code] ?? null])
        ->filter()
        ->implode(' - ') ?: __('immunizations.label');
@endphp

<div>
    <section class="section-form p-6">
        <x-header-navigation class="breadcrumb-form" title="{{ $title }}">
            <x-slot name="title">{{ $title }}</x-slot>

            <x-slot name="actions">
                <button
                    wire:click.prevent="sync"
                    type="button"
                    class="button-sync flex items-center gap-2 px-4 py-2 text-sm shadow-sm"
                >
                    @icon('refresh', 'w-4 h-4')
                    <span>{{ __('forms.synchronise_with_eHealth') }}</span>
                </button>
            </x-slot>
        </x-header-navigation>

        <div class="form shift-content">
            <fieldset class="fieldset">
                <legend class="legend">{{ __('forms.main_information') }}</legend>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="vaccine"
                            id="vaccine"
                            class="input peer"
                            value="{{ $title }}"
                            disabled
                        />
                        <label for="vaccine" class="label">{{ __('immunizations.vaccine_code_and_name') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="immunizationId"
                            id="immunizationId"
                            class="input peer"
                            value="{{ $immunization->uuid }}"
                            disabled
                        />
                        <label for="immunizationId" class="label">{{ __('forms.ehealth_id') }}</label>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="status"
                            id="status"
                            class="input peer"
                            value="{{ $immunization->status->label() }}"
                            disabled
                        />
                        <label for="status" class="label">{{ __('forms.status.label') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="wasPerformed"
                            id="wasPerformed"
                            class="input peer"
                            value="{{ $immunization->notGiven ? __('forms.no') : __('forms.yes') }}"
                            disabled
                        />
                        <label for="wasPerformed" class="label">{{ __('immunizations.was_performed') }}</label>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="date"
                            id="date"
                            class="input peer"
                            value="{{ $immunization->date ? convertToAppDateFormat($immunization->date) . ' ' . $immunization->time : '-' }}"
                            disabled
                        />
                        <label for="date" class="label">{{ __('immunizations.date_time_performed') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="encounter"
                            id="encounter"
                            class="input peer"
                            value="{{ $immunization->context?->value ?? '-' }}"
                            disabled
                        />
                        <label for="encounter" class="label">{{ __('encounters.label') }}</label>
                    </div>
                </div>

                <div class="form-row-2">
                    @if ($immunization->primarySource)
                        <div class="form-group group">
                            <input
                                type="text"
                                name="performer"
                                id="performer"
                                class="input peer"
                                value="{{ $immunization->performer?->displayValue ?? $immunization->performer?->value ?? '-' }}"
                                disabled
                            />
                            <label for="performer" class="label">{{ __('medical-events.performer') }}</label>
                        </div>
                    @else
                        <div class="form-group group">
                            <input
                                type="text"
                                name="reportOrigin"
                                id="reportOrigin"
                                class="input peer"
                                value="{{ $dictionaries['eHealth/immunization_report_origins'][$reportOrigin?->code] ?? '-' }}"
                                disabled
                            />
                            <label
                                for="reportOrigin"
                                class="label"
                            >{{ __('medical-events.information_source') }}</label>
                        </div>
                    @endif
                </div>

                @foreach ($immunization->explanations as $explanation)
                    @php
                        $reason = $explanation->reasons?->coding->first();
                        $reasonNotGiven = $explanation->reasonsNotGiven?->coding->first();
                    @endphp
                    <div class="form-row-2" wire:key="explanation-{{ $loop->index }}">
                        <div class="form-group group">
                            <input
                                type="text"
                                name="explanation{{ $loop->index }}"
                                id="explanation{{ $loop->index }}"
                                class="input peer"
                                value="{{
                                    $reason
                                    ? $dictionaries['eHealth/reason_explanations'][$reason->code] ?? '-'
                                    : $dictionaries['eHealth/reason_not_given_explanations'][$reasonNotGiven?->code] ?? '-'
                                }}"
                                disabled
                            />
                            <label
                                for="explanation{{ $loop->index }}"
                                class="label"
                            >{{ __('immunizations.explanation') }}</label>
                        </div>
                    </div>
                @endforeach
            </fieldset>

            <fieldset class="fieldset mt-8">
                <legend class="legend">{{ __('immunizations.vaccine_information') }}</legend>

                <div class="form-row-3">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="manufacturer"
                            id="manufacturer"
                            class="input peer"
                            value="{{ $immunization->manufacturer ?? '-' }}"
                            disabled
                        />
                        <label for="manufacturer" class="label">{{ __('immunizations.manufacturer') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="lotNumber"
                            id="lotNumber"
                            class="input peer"
                            value="{{ $immunization->lotNumber ?? '-' }}"
                            disabled
                        />
                        <label for="lotNumber" class="label">{{ __('immunizations.lot_number') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="expirationDate"
                            id="expirationDate"
                            class="input peer"
                            value="{{ $immunization->expirationDate ? convertToAppDateFormat($immunization->expirationDate) : '-' }}"
                            disabled
                        />
                        <label for="expirationDate" class="label">{{ __('immunizations.expiration_date') }}</label>
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="site"
                            id="site"
                            class="input peer"
                            value="{{ $dictionaries['eHealth/immunization_body_sites'][$site?->code] ?? '-' }}"
                            disabled
                        />
                        <label for="site" class="label">{{ __('immunizations.site') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="route"
                            id="route"
                            class="input peer"
                            value="{{ $dictionaries['eHealth/vaccination_routes'][$route?->code] ?? '-' }}"
                            disabled
                        />
                        <label for="route" class="label">{{ __('immunizations.input_route') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="doseQuantity"
                            id="doseQuantity"
                            class="input peer"
                            value="{{ $doseQuantity ? $doseQuantity->value . ' ' . ($dictionaries['eHealth/immunization_dosage_units'][$doseQuantity->code] ?? $doseQuantity->unit) : '-' }}"
                            disabled
                        />
                        <label for="doseQuantity" class="label">{{ __('immunizations.dose_quantity') }}</label>
                    </div>
                </div>
            </fieldset>

            <fieldset class="fieldset mt-8">
                <legend class="legend">{{ __('immunizations.vaccination_protocol') }}</legend>

                @forelse ($immunization->vaccinationProtocols as $protocol)
                    @php($authority = $protocol->authority?->coding->first())
                    <div
                        class="mb-4 space-y-1 text-sm text-gray-900 last:mb-0 dark:text-gray-100"
                        wire:key="protocol-{{ $loop->index }}"
                    >
                        <p>
                            <span class="font-semibold">{{ __('immunizations.protocol_author') }}:</span>
                            {{ $dictionaries['eHealth/vaccination_authorities'][$authority?->code] ?? '-' }}
                        </p>
                        <p>
                            <span class="font-semibold">{{ __('immunizations.target_diseases') }}:</span>
                            {{ $protocol->targetDiseases->map(static fn (CodeableConcept $disease): string => $dictionaries['eHealth/vaccination_target_diseases'][$disease->coding->first()?->code] ?? '-')->implode(', ') ?: '-' }}
                        </p>
                        <p>
                            <span class="font-semibold">{{ __('immunizations.dose_sequence') }}:</span>
                            {{ $protocol->doseSequence ?? '-' }}
                        </p>
                        <p>
                            <span class="font-semibold">{{ __('immunizations.series') }}:</span>
                            {{ $protocol->series ?? '-' }}
                        </p>
                        <p>
                            <span class="font-semibold">{{ __('immunizations.series_of_doses_by_protocol') }}:</span>
                            {{ $protocol->seriesDoses ?? '-' }}
                        </p>
                        @if ($protocol->description)
                            <p>
                                <span class="font-semibold">{{ __('immunizations.protocol_description') }}:</span>
                                {{ $protocol->description }}
                            </p>
                        @endif
                    </div>
                @empty
                    <div class="text-sm text-gray-500 italic">{{ __('forms.no_data') }}</div>
                @endforelse
            </fieldset>

            <fieldset class="fieldset mt-8">
                <legend class="legend">{{ __('immunizations.reactions') }}</legend>

                @forelse ($immunization->reactions as $reaction)
                    <p class="text-sm text-gray-900 dark:text-gray-100" wire:key="reaction-{{ $loop->index }}">
                        {{ $reaction->displayValue ?? $reaction->detail?->displayValue ?? $reaction->detail?->value }}
                    </p>
                @empty
                    <div class="text-sm text-gray-500 italic">{{ __('forms.no_data') }}</div>
                @endforelse
            </fieldset>

            <fieldset class="fieldset mt-8">
                <legend class="legend">{{ __('forms.additional_information') }}</legend>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="ehealthInsertedAt"
                            id="ehealthInsertedAt"
                            class="input peer"
                            value="{{ $immunization->ehealthInsertedAt ?? '-' }}"
                            disabled
                        />
                        <label for="ehealthInsertedAt" class="label">{{ __('forms.inserted_at') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="ehealthUpdatedAt"
                            id="ehealthUpdatedAt"
                            class="input peer"
                            value="{{ $immunization->ehealthUpdatedAt ?? '-' }}"
                            disabled
                        />
                        <label for="ehealthUpdatedAt" class="label">{{ __('forms.updated_at') }}</label>
                    </div>
                </div>

                @if ($immunization->explanatoryLetter)
                    <div class="form-row">
                        <div class="form-group group">
                            <label for="explanatoryLetter" class="label-modal mb-1">
                                {{ __('medical-events.cancel_modal.explanation_label') }}
                            </label>
                            <textarea
                                name="explanatoryLetter"
                                id="explanatoryLetter"
                                class="textarea"
                                rows="3"
                                disabled
                            >{{ $immunization->explanatoryLetter }}</textarea>
                        </div>
                    </div>
                @endif
            </fieldset>

            <div class="mt-8">
                <a
                    href="{{ $personId ? route('persons.immunizations', [legalEntity(), 'person' => $personId]) : route('prepersons.immunizations', [legalEntity(), 'preperson' => $prepersonId]) }}"
                    class="button-minor px-6 py-2"
                >{{ __('forms.back') }}</a>
            </div>
        </div>
    </section>

    <livewire:components.x-message :key="now()->timestamp" />
</div>
