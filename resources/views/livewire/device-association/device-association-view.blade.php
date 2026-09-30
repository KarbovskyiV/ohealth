@php
    use App\Models\MedicalEvents\Sql\DeviceAssociation;

    $title = $deviceName ?? __('device-associations.label');
    $bodySiteCode = $deviceAssociation->bodySite?->coding->first()?->code;
    $reportOriginCode = $deviceAssociation->reportOrigin?->coding->first()?->code;
    $statusReasonCode = $deviceAssociation->statusReason?->coding->first()?->code;
@endphp

<div>
    <section class="section-form p-6">
        <x-header-navigation class="breadcrumb-form" title="{{ $title }}">
            <x-slot name="title">{{ $title }}</x-slot>

            <x-slot name="actions">
                @can('view', DeviceAssociation::class)
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
                        <input
                            type="text"
                            name="deviceName"
                            id="deviceName"
                            class="input peer"
                            value="{{ $deviceName ?? '-' }}"
                            disabled
                        />
                        <label for="deviceName" class="label">{{ __('device-associations.device') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="deviceId"
                            id="deviceId"
                            class="input peer"
                            value="{{ $deviceAssociation->device?->value ?? '-' }}"
                            disabled
                        />
                        <label for="deviceId" class="label">{{ __('device-associations.device_id') }}</label>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="status"
                            id="status"
                            class="input peer"
                            value="{{ $dictionaries['device_association_statuses'][$deviceAssociation->status->value] }}"
                            disabled
                        />
                        <label for="status" class="label">{{ __('device-associations.association_status') }}</label>
                    </div>
                    <div class="form-group group">
                        <div class="datepicker-wrapper">
                            <input
                                type="text"
                                name="associationDate"
                                id="associationDate"
                                class="datepicker-input with-leading-icon input peer"
                                value="{{ $deviceAssociation->associationDate ?: '-' }}"
                                placeholder=" "
                                disabled
                            />
                            <label for="associationDate" class="wrapped-label">
                                {{ __('device-associations.association_date_short') }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="bodySite"
                            id="bodySite"
                            class="input peer"
                            value="{{ data_get($dictionaries, 'eHealth/body_structures.' . $bodySiteCode) ?? '-' }}"
                            disabled
                        />
                        <label for="bodySite" class="label">{{ __('device-associations.body_site') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="bodySiteText"
                            id="bodySiteText"
                            class="input peer"
                            value="{{ $deviceAssociation->bodySite?->text ?: '-' }}"
                            disabled
                        />
                        <label for="bodySiteText" class="label">
                            {{ __('device-associations.body_site_comment') }}
                        </label>
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group group">
                        <div class="datepicker-wrapper">
                            <input
                                type="text"
                                name="recordedDate"
                                id="recordedDate"
                                class="datepicker-input with-leading-icon input peer"
                                value="{{ $deviceAssociation->recordedDate ?: '-' }}"
                                placeholder=" "
                                disabled
                            />
                            <label for="recordedDate" class="wrapped-label">
                                {{ __('device-associations.recorded') }}
                            </label>
                        </div>
                    </div>
                    <div class="form-group group w-1/2!">
                        <div class="relative flex items-center">
                            @icon('mingcute-time-fill', 'svg-input left-2.5')
                            <input
                                type="text"
                                name="recordedTime"
                                id="recordedTime"
                                class="input peer pl-10!"
                                value="{{ $deviceAssociation->recordedTime ?: '-' }}"
                                placeholder=" "
                                disabled
                            />
                            <label for="recordedTime" class="sr-only">{{ __('forms.time') }}</label>
                        </div>
                    </div>
                </div>

                @if ($statusReasonCode)
                    <div class="form-row-2">
                        <div class="form-group group">
                            <input
                                type="text"
                                name="statusReason"
                                id="statusReason"
                                class="input peer"
                                value="{{ $dictionaries['device_association_status_reasons'][$statusReasonCode] }}"
                                disabled
                            />
                            <label for="statusReason" class="label">
                                {{ __('device-associations.status_reason') }}
                            </label>
                        </div>
                    </div>
                @endif

                <div class="form-row">
                    <div class="form-group group">
                        <label for="explanatoryLetter" class="label-modal mb-1">
                            {{ __('device-associations.explanatory_letter') }}
                        </label>
                        <textarea
                            name="explanatoryLetter"
                            id="explanatoryLetter"
                            class="textarea"
                            disabled
                            rows="3"
                        >{{ $deviceAssociation->explanatoryLetter }}</textarea>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="encounterId"
                            id="encounterId"
                            class="input peer"
                            value="{{ $deviceAssociation->context?->value ?? '-' }}"
                            disabled
                        />
                        <label for="encounterId" class="label">{{ __('patients.encounter_id') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="deviceAssociationId"
                            id="deviceAssociationId"
                            class="input peer"
                            value="{{ $deviceAssociation->uuid }}"
                            disabled
                        />
                        <label for="deviceAssociationId" class="label">{{ __('device-associations.id') }}</label>
                    </div>
                </div>
            </fieldset>

            <fieldset class="fieldset mt-8">
                <legend class="legend">{{ __('forms.additional_information') }}</legend>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="recorder"
                            id="recorder"
                            class="input peer"
                            value="{{ $deviceAssociation->recorder?->displayValue ?? $deviceAssociation->recorder?->value ?? '-' }}"
                            disabled
                        />
                        <label for="recorder" class="label">{{ __('device-associations.recorder') }}</label>
                    </div>
                </div>

                <div
                    class="form-row-2 mb-4"
                    x-data="{ isOtherSource: {{ $deviceAssociation->primarySource ? 'false' : 'true' }} }"
                >
                    <div class="form-group group">
                        <div class="flex items-center gap-4 pt-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                {{ __('devices.source_data') }}
                            </span>
                            <label for="isOtherSource" class="flex cursor-pointer items-center gap-2">
                                <input
                                    type="radio"
                                    name="isOtherSource"
                                    id="isOtherSource"
                                    :checked="isOtherSource"
                                    disabled
                                    class="default-radio"
                                />
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('devices.other_source') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group group" x-show="isOtherSource">
                        <div class="relative flex-1">
                            <input
                                type="text"
                                name="reportOrigin"
                                id="reportOrigin"
                                class="input peer w-full"
                                value="{{ data_get($dictionaries, 'eHealth/report_origins.' . $reportOriginCode) ?? '-' }}"
                                disabled
                            />
                            <label for="reportOrigin" class="label">{{ __('devices.source_reference') }}</label>
                        </div>
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group group">
                        <div class="datepicker-wrapper">
                            <input
                                type="text"
                                name="ehealthInsertedDate"
                                id="ehealthInsertedDate"
                                class="datepicker-input with-leading-icon input peer"
                                value="{{ $deviceAssociation->ehealthInsertedDate ?: '-' }}"
                                placeholder=" "
                                disabled
                            />
                            <label for="ehealthInsertedDate" class="wrapped-label">
                                {{ __('devices.created_at_system') }}
                            </label>
                        </div>
                    </div>
                    <div class="form-group group w-1/2!">
                        <div class="relative flex items-center">
                            @icon('mingcute-time-fill', 'svg-input left-2.5')
                            <input
                                type="text"
                                name="ehealthInsertedTime"
                                id="ehealthInsertedTime"
                                class="input peer pl-10!"
                                value="{{ $deviceAssociation->ehealthInsertedTime ?: '-' }}"
                                placeholder=" "
                                disabled
                            />
                            <label for="ehealthInsertedTime" class="sr-only">{{ __('forms.time') }}</label>
                        </div>
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group group">
                        <div class="datepicker-wrapper">
                            <input
                                type="text"
                                name="ehealthUpdatedDate"
                                id="ehealthUpdatedDate"
                                class="datepicker-input with-leading-icon input peer"
                                value="{{ $deviceAssociation->ehealthUpdatedDate ?: '-' }}"
                                placeholder=" "
                                disabled
                            />
                            <label for="ehealthUpdatedDate" class="wrapped-label">
                                {{ __('devices.updated_at_system') }}
                            </label>
                        </div>
                    </div>
                    <div class="form-group group w-1/2!">
                        <div class="relative flex items-center">
                            @icon('mingcute-time-fill', 'svg-input left-2.5')
                            <input
                                type="text"
                                name="ehealthUpdatedTime"
                                id="ehealthUpdatedTime"
                                class="input peer pl-10!"
                                value="{{ $deviceAssociation->ehealthUpdatedTime ?: '-' }}"
                                placeholder=" "
                                disabled
                            />
                            <label for="ehealthUpdatedTime" class="sr-only">{{ __('forms.time') }}</label>
                        </div>
                    </div>
                </div>
            </fieldset>

            <div class="mt-8">
                <a
                    href="{{ $personId ? route('persons.device-associations', [legalEntity(), 'person' => $personId]) : route('prepersons.device-associations', [legalEntity(), 'preperson' => $prepersonId]) }}"
                    class="button-minor px-6 py-2"
                >{{ __('forms.back') }}</a>
            </div>
        </div>
    </section>

    <livewire:components.x-message :key="now()->timestamp" />
</div>
