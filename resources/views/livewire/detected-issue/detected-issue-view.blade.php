@php
    use App\Models\MedicalEvents\Sql\DetectedIssue;

    $title = $deviceName ?? __('detected-issues.label');
    $codeCode = $detectedIssue->code?->coding->first()?->code;
    $reportOriginCode = $detectedIssue->reportOrigin?->coding->first()?->code;
    $statusReasonCode = $detectedIssue->statusReason?->coding->first()?->code;
@endphp

<div>
    <section class="section-form p-6">
        <x-header-navigation class="breadcrumb-form" title="{{ $title }}">
            <x-slot name="title">{{ $title }}</x-slot>

            <x-slot name="actions">
                @can('view', DetectedIssue::class)
                    <button
                        wire:click.prevent="sync"
                        type="button"
                        class="button-sync flex items-center gap-2 px-4 py-2 text-sm shadow-sm"
                    >
                        @icon('refresh', 'w-4 h-4')
                        <span>{{ __('forms.synchronise_with_eHealth') }}</span>
                    </button>
                @endcan
            </x-slot>
        </x-header-navigation>

        <div class="form shift-content">
            <fieldset class="fieldset">
                <legend class="legend">{{ __('forms.main_information') }}</legend>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input type="text" class="input peer" value="{{ $deviceName ?? '-' }}" disabled />
                        <label class="label">{{ __('detected-issues.device') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            class="input peer"
                            value="{{ $detectedIssue->subject?->value ?? '-' }}"
                            disabled
                        />
                        <label class="label">{{ __('detected-issues.device_id') }}</label>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            class="input peer"
                            value="{{ $dictionaries['detected_issue_statuses'][$detectedIssue->status->value] }}"
                            disabled
                        />
                        <label class="label">{{ __('detected-issues.status') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            class="input peer"
                            value="{{ data_get($dictionaries, 'detected_issue_codes.' . $codeCode) ?? '-' }}"
                            disabled
                        />
                        <label class="label">{{ __('detected-issues.type') }}</label>
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group group">
                        <div class="datepicker-wrapper">
                            <input
                                type="text"
                                class="datepicker-input with-leading-icon input peer"
                                value="{{ $detectedIssue->identifiedDate ?: '-' }}"
                                placeholder=" "
                                disabled
                            />
                            <label class="wrapped-label">{{ __('detected-issues.identified_at') }}</label>
                        </div>
                    </div>
                    <div class="form-group group w-1/2!">
                        <div class="relative flex items-center">
                            @icon('mingcute-time-fill', 'svg-input left-2.5')
                            <input
                                type="text"
                                class="input peer pl-10!"
                                value="{{ $detectedIssue->identifiedTime ?: '-' }}"
                                placeholder=" "
                                disabled
                            />
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group group">
                        <label class="label-modal mb-1">{{ __('detected-issues.detail') }}</label>
                        <textarea class="textarea" disabled rows="3">{{ $detectedIssue->detail }}</textarea>
                    </div>
                </div>

                @if ($statusReasonCode)
                    <div class="form-row-2">
                        <div class="form-group group">
                            <input
                                type="text"
                                class="input peer"
                                value="{{ $dictionaries['detected_issue_status_reasons'][$statusReasonCode] }}"
                                disabled
                            />
                            <label class="label">{{ __('detected-issues.status_reason') }}</label>
                        </div>
                    </div>
                @endif

                <div class="form-row">
                    <div class="form-group group">
                        <label class="label-modal mb-1">{{ __('detected-issues.explanatory_letter') }}</label>
                        <textarea class="textarea" disabled rows="3">{{ $detectedIssue->explanatoryLetter }}</textarea>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            class="input peer"
                            value="{{ $detectedIssue->encounter?->value ?? '-' }}"
                            disabled
                        />
                        <label class="label">{{ __('patients.encounter_id') }}</label>
                    </div>
                    <div class="form-group group">
                        <input type="text" class="input peer" value="{{ $detectedIssue->uuid }}" disabled />
                        <label class="label">{{ __('detected-issues.id') }}</label>
                    </div>
                </div>
            </fieldset>

            <fieldset class="fieldset mt-8">
                <legend class="legend">{{ __('forms.additional_information') }}</legend>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            class="input peer"
                            value="{{ $implicatedDeviceName ?? $detectedIssue->implicated?->value ?? '-' }}"
                            disabled
                        />
                        <label class="label">{{ __('detected-issues.implicated_device') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            class="input peer"
                            value="{{ $detectedIssue->basedOn?->value ?? '-' }}"
                            disabled
                        />
                        <label class="label">{{ __('detected-issues.based_on') }}</label>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            class="input peer"
                            value="{{ $detectedIssue->recorder?->displayValue ?? $detectedIssue->recorder?->value ?? '-' }}"
                            disabled
                        />
                        <label class="label">{{ __('detected-issues.recorder') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            class="input peer"
                            value="{{ $detectedIssue->author?->displayValue ?? $detectedIssue->author?->value ?? '-' }}"
                            disabled
                        />
                        <label class="label">{{ __('detected-issues.author') }}</label>
                    </div>
                </div>

                <div
                    class="form-row-2 mb-4"
                    x-data="{ isOtherSource: {{ $detectedIssue->primarySource ? 'false' : 'true' }} }"
                >
                    <div class="form-group group">
                        <div class="flex items-center gap-4 pt-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                {{ __('devices.source_data') }}
                            </span>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" :checked="isOtherSource" disabled class="default-radio" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('devices.other_source') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group group" x-show="isOtherSource">
                        <div class="relative flex-1">
                            <input
                                type="text"
                                class="input peer w-full"
                                value="{{ data_get($dictionaries, 'eHealth/report_origins.' . $reportOriginCode) ?? '-' }}"
                                disabled
                            />
                            <label class="label">{{ __('devices.source_reference') }}</label>
                        </div>
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group group">
                        <div class="datepicker-wrapper">
                            <input
                                type="text"
                                class="datepicker-input with-leading-icon input peer"
                                value="{{ $detectedIssue->ehealthInsertedDate ?: '-' }}"
                                placeholder=" "
                                disabled
                            />
                            <label class="wrapped-label">{{ __('devices.created_at_system') }}</label>
                        </div>
                    </div>
                    <div class="form-group group w-1/2!">
                        <div class="relative flex items-center">
                            @icon('mingcute-time-fill', 'svg-input left-2.5')
                            <input
                                type="text"
                                class="input peer pl-10!"
                                value="{{ $detectedIssue->ehealthInsertedTime ?: '-' }}"
                                placeholder=" "
                                disabled
                            />
                        </div>
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group group">
                        <div class="datepicker-wrapper">
                            <input
                                type="text"
                                class="datepicker-input with-leading-icon input peer"
                                value="{{ $detectedIssue->ehealthUpdatedDate ?: '-' }}"
                                placeholder=" "
                                disabled
                            />
                            <label class="wrapped-label">{{ __('devices.updated_at_system') }}</label>
                        </div>
                    </div>
                    <div class="form-group group w-1/2!">
                        <div class="relative flex items-center">
                            @icon('mingcute-time-fill', 'svg-input left-2.5')
                            <input
                                type="text"
                                class="input peer pl-10!"
                                value="{{ $detectedIssue->ehealthUpdatedTime ?: '-' }}"
                                placeholder=" "
                                disabled
                            />
                        </div>
                    </div>
                </div>
            </fieldset>

            <div class="mt-8">
                <a
                    href="{{ $personId ? route('persons.device-issues', [legalEntity(), 'person' => $personId]) : route('prepersons.device-issues', [legalEntity(), 'preperson' => $prepersonId]) }}"
                    class="button-minor px-6 py-2"
                >{{ __('forms.back') }}</a>
            </div>
        </div>
    </section>

    <livewire:components.x-message :key="now()->timestamp" />
</div>
