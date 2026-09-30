@php
    $observationName = ($observation->code)?->text ?: __('observations.label');
    $observationTitle = $observationName . ' | ' . $observation->effectiveDate;
@endphp

<div>
    <section class="section-form p-6">
        <x-header-navigation class="breadcrumb-form" title="{{ $observationTitle }}">
            <x-slot name="title">{{ $observationTitle }}</x-slot>
        </x-header-navigation>

        <div class="form shift-content">

            <fieldset class="fieldset">
                <legend class="legend">{{ __('observations.result') }}</legend>
                <div class="form-row-2">
                    <div class="form-group group">
                        <input type="text" class="input peer" value="{{ ($observation)->value?->valueQuantity?->value }}" disabled />
                        <label class="label">{{ __('observations.result') }}</label>
                    </div>
                    <div class="form-group group">
                        <input type="text" class="input peer" value="{{ ($observation->interpretation)?->text ?: '-' }}" disabled />
                        <label class="label">{{ __('observations.interpretation') }}</label>
                    </div>
                </div>
                @if($observation->referenceRanges && $observation->referenceRanges->isNotEmpty())
                    @php
                        $range = $observation->referenceRanges->first();
                        $rangeText = collect([
                            $range->low?->value,
                            '-',
                            $range->high?->value,
                            $range->high?->unit ?? $range->low?->unit
                        ])->filter(fn($v) => $v !== null && $v !== '')->implode(' ');
                    @endphp
                    <div class="form-row-2 mt-4">
                        <div class="form-group group">
                            <input type="text" class="input peer" value="{{ $range->text ?? $rangeText ?? '-' }}" disabled />
                            <label class="label">{{ __('observations.reference_range') }}</label>
                        </div>
                    </div>
                @endif
            </fieldset>

            <fieldset class="fieldset mt-8">
                <legend class="legend">{{ __('observations.components') }}</legend>
                @if($observation->components && $observation->components->isNotEmpty())
                    @foreach($observation->components as $component)
                        <h4 class="font-semibold text-gray-700 dark:text-gray-200 {{ $loop->first ? 'mt-4' : 'mt-10' }} mb-6">{{ ($component->code)?->text ?: __('observations.component') }}</h4>
                        <div class="form-row-2">
                            <div class="form-group group">
                                <input type="text" class="input peer" value="{{ ($component)->value?->valueQuantity?->value }}" disabled />
                                <label class="label">{{ __('observations.result') }}</label>
                            </div>
                            <div class="form-group group">
                                <input type="text" class="input peer" value="{{ ($component->interpretation)?->text ?: '-' }}" disabled />
                                <label class="label">{{ __('observations.interpretation') }}</label>
                            </div>
                        </div>
                        @if($component->referenceRanges && $component->referenceRanges->isNotEmpty())
                            @php
                                $compRange = $component->referenceRanges->first();
                                $compRangeText = collect([
                                    $compRange->low?->value,
                                    '-',
                                    $compRange->high?->value,
                                    $compRange->high?->unit ?? $compRange->low?->unit
                                ])->filter(fn($v) => $v !== null && $v !== '')->implode(' ');
                            @endphp
                            <div class="form-row-2 mt-4">
                                <div class="form-group group">
                                    <input type="text" class="input peer" value="{{ $compRange->text ?? $compRangeText ?? '-' }}" disabled />
                                    <label class="label">{{ __('observations.reference_range') }}</label>
                                </div>
                            </div>
                        @endif
                    @endforeach
                @else
                    <div class="form-row mt-4">
                        <div class="text-gray-500 italic text-sm">{{ __('observations.no_components') }}</div>
                    </div>
                @endif
            </fieldset>

            <fieldset class="fieldset mt-8">
                <legend class="legend">{{ __('observations.general_info') }}</legend>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input type="text" class="input peer" value="{{ ($observation->categories->first()?->text) ?: '-' }}" disabled />
                        <label class="label">{{ __('observations.category') }}</label>
                    </div>
                    <div class="form-group group">
                        <select class="input-select peer" disabled>
                            <option selected>{{ $observationName }}</option>
                        </select>
                        <label class="label">{{ __('observations.code_and_name') }}</label>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input type="text" class="input peer" value="{{ $observation->status?->label() ?? '-' }}" disabled />
                        <label class="label">{{ __('observations.status_label') }}</label>
                    </div>
                    <div class="form-group group">
                        <input type="text" class="input peer" value="{{ ($observation->method)?->text ?: '-' }}" disabled />
                        <label class="label">{{ __('observations.method_label') }}</label>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input type="text" class="input peer" value="{{ ($observation->bodySite)?->text ?: '-' }}" disabled />
                        <label class="label">{{ __('observations.body_site_label') }}</label>
                    </div>
                    <div class="form-group group">
                        <input type="text" class="input peer" value="{{ $observation->device?->displayValue ?? '-' }}" disabled />
                        <label class="label">{{ __('observations.device') }}</label>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <div class="datepicker-wrapper">
                            <input
                                type="text"
                                class="datepicker-input with-leading-icon input peer"
                                value="{{ $observation->effectivePeriodStartDate ?: $observation->effectiveDate ?: '-' }}"
                                placeholder=" "
                                disabled
                            />
                            <label class="wrapped-label">{{ __('observations.effective_date_label') }}</label>
                        </div>
                    </div>

                    <div class="form-group group">
                        <div class="relative flex items-center border-b-2 border-gray-300 dark:border-gray-600">
                            <div class="datepicker-wrapper flex-1">
                                <input
                                    type="text"
                                    class="datepicker-input with-leading-icon input peer border-b-0!"
                                    value="{{ $observation->issuedDate ?: '-' }}"
                                    placeholder=" "
                                    disabled
                                />
                                <label class="wrapped-label">{{ __('observations.result_received_at') }}</label>
                            </div>
                            <div class="relative flex items-center w-28">
                                @icon('mingcute-time-fill', 'svg-input left-2.5')
                                <input
                                    type="text"
                                    class="input peer pl-10! border-b-0!"
                                    value="{{ $observation->issuedTime ?: '-' }}"
                                    placeholder=" "
                                    disabled
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-row mt-4">
                    <div class="form-group group">
                        <label class="label-modal mb-1">{{ __('observations.comment') }}</label>
                        <textarea class="textarea" disabled rows="3">{{ $observation->comment ?: '' }}</textarea>
                    </div>
                </div>

                <div class="form-row-2 mt-4">
                    <div class="form-group group">
                        <input type="text" class="input peer" value="{{ $observation->performer?->displayValue ?? $observation->performer?->value ?? '-' }}" disabled />
                        <label class="label">{{ __('observations.performer') }}</label>
                    </div>
                </div>

                <div class="form-row-2 mt-4">
                    <div class="form-group group">
                        <input type="text" class="input peer" value="-" disabled />
                        <label class="label">{{ __('observations.managing_organization') }}</label>
                    </div>
                    <div class="form-group group">
                        <input type="text" class="input peer" value="{{ $observation->diagnosticReport?->value ?? '-' }}" disabled />
                        <label class="label">{{ __('observations.diagnostic_report') }}</label>
                    </div>
                </div>

                <div class="form-row-2 mt-4">
                    <div class="form-group group">
                        <input type="text" class="input peer" value="{{ $observation->specimen?->value ?? '-' }}" disabled />
                        <label class="label">{{ __('observations.specimen') }}</label>
                    </div>
                    <div class="form-group group">
                        <input type="text" class="input peer" value="{{ $observation->context?->value ?? '-' }}" disabled />
                        <label class="label">{{ __('observations.context') }}</label>
                    </div>
                </div>


                <div class="form-row-2 mt-4">
                    <div class="form-group group">
                        <div class="relative flex items-center border-b-2 border-gray-300 dark:border-gray-600">
                            <div class="datepicker-wrapper flex-1">
                                <input
                                    type="text"
                                    class="datepicker-input with-leading-icon input peer border-b-0!"
                                    value="{{ $observation->ehealth_inserted_at ? convertToAppDateFormat($observation->ehealth_inserted_at) : '-' }}"
                                    placeholder=" "
                                    disabled
                                />
                                <label class="wrapped-label">{{ __('observations.inserted_at_label') }}</label>
                            </div>
                            <div class="relative flex items-center w-28">
                                @icon('mingcute-time-fill', 'svg-input left-2.5')
                                <input
                                    type="text"
                                    class="input peer pl-10! border-b-0!"
                                    value="{{ $observation->ehealth_inserted_at ? \Carbon\CarbonImmutable::parse($observation->ehealth_inserted_at)->format('H:i') : '-' }}"
                                    placeholder=" "
                                    disabled
                                />
                            </div>
                        </div>
                    </div>

                    <div class="form-group group">
                        <div class="relative flex items-center border-b-2 border-gray-300 dark:border-gray-600">
                            <div class="datepicker-wrapper flex-1">
                                <input
                                    type="text"
                                    class="datepicker-input with-leading-icon input peer border-b-0!"
                                    value="{{ $observation->ehealth_updated_at ? convertToAppDateFormat($observation->ehealth_updated_at) : '-' }}"
                                    placeholder=" "
                                    disabled
                                />
                                <label class="wrapped-label">{{ __('observations.updated_at_label') }}</label>
                            </div>
                            <div class="relative flex items-center w-28">
                                @icon('mingcute-time-fill', 'svg-input left-2.5')
                                <input
                                    type="text"
                                    class="input peer pl-10! border-b-0!"
                                    value="{{ $observation->ehealth_updated_at ? \Carbon\CarbonImmutable::parse($observation->ehealth_updated_at)->format('H:i') : '-' }}"
                                    placeholder=" "
                                    disabled
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>

            <div class="mt-8">
                <a
                    href="{{ $personId ? route('persons.observations', [legalEntity(), 'person' => $personId]) : route('prepersons.observations', [legalEntity(), 'preperson' => $prepersonId]) }}"
                    class="button-minor px-6 py-2"
                >{{ __('forms.back') }}</a>
            </div>
        </div>
    </section>

    <livewire:components.x-message :key="now()->timestamp" />
</div>
