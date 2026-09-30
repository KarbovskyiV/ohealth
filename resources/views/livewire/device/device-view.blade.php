@php
    use App\Models\MedicalEvents\Sql\DeviceProperty;

    $deviceName = $device->names->first()?->value;
    $typeCode = $device->type?->coding->first()?->code;
    $reportOriginCode = $device->reportOrigin?->coding->first()?->code;
    $statusReasonCoding = $device->statusReason?->coding->first();
@endphp

<div>
    <section class="section-form p-6">
        <x-header-navigation class="breadcrumb-form" title="{{ $deviceName }}">
            <x-slot name="title">{{ $deviceName }}</x-slot>

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
                            name="type"
                            id="type"
                            class="input peer"
                            value="{{ data_get($dictionaries, 'device_definition_classification_type.' . $typeCode) ?? '-' }}"
                            disabled
                        />
                        <label for="type" class="label">{{ __('devices.type') }}*</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="deviceName"
                            id="deviceName"
                            class="input peer"
                            value="{{ $deviceName ?? '-' }}"
                            disabled
                        />
                        <label for="deviceName" class="label">{{ __('devices.name') }}*</label>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="status"
                            id="status"
                            class="input peer"
                            value="{{ $device->status->label() }}"
                            disabled
                        />
                        <label for="status" class="label">{{ __('forms.status.label') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="statusReason"
                            id="statusReason"
                            class="input peer"
                            value="{{ data_get($dictionaries, $statusReasonCoding?->system . '.' . $statusReasonCoding?->code) ?? '-' }}"
                            disabled
                        />
                        <label for="statusReason" class="label">{{ __('devices.entered_in_error_reason') }}</label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group group">
                        <label for="explanatoryLetter" class="label-modal mb-1">
                            {{ __('devices.explanatory_letter') }}
                        </label>
                        <textarea
                            name="explanatoryLetter"
                            id="explanatoryLetter"
                            class="textarea"
                            disabled
                            rows="3"
                        >{{ $device->explanatoryLetter }}</textarea>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="encounterId"
                            id="encounterId"
                            class="input peer"
                            value="{{ $device->context?->value ?? '-' }}"
                            disabled
                        />
                        <label for="encounterId" class="label">{{ __('devices.encounter_id_label') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="deviceId"
                            id="deviceId"
                            class="input peer"
                            value="{{ $device->uuid }}"
                            disabled
                        />
                        <label for="deviceId" class="label">{{ __('devices.device_id_label') }}</label>
                    </div>
                </div>
            </fieldset>

            <fieldset class="fieldset mt-8">
                <legend class="legend">{{ __('forms.additional_information') }}</legend>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="modelNumber"
                            id="modelNumber"
                            class="input peer"
                            value="{{ $device->modelNumber ?? '-' }}"
                            disabled
                        />
                        <label for="modelNumber" class="label">{{ __('devices.model_number') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="definition"
                            id="definition"
                            class="input peer"
                            value="{{ $device->definition?->value ?? '-' }}"
                            disabled
                        />
                        <label
                            for="definition"
                            class="label max-w-full truncate"
                            title="{{ __('devices.definition_full') }}"
                        >{{ __('devices.definition_full') }}</label>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="manufacturer"
                            id="manufacturer"
                            class="input peer"
                            value="{{ $device->manufacturer ?? '-' }}"
                            disabled
                        />
                        <label for="manufacturer" class="label">{{ __('devices.manufacturer') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="serialNumber"
                            id="serialNumber"
                            class="input peer"
                            value="{{ $device->serialNumber ?? '-' }}"
                            disabled
                        />
                        <label for="serialNumber" class="label">{{ __('devices.serial_number') }}</label>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="lotNumber"
                            id="lotNumber"
                            class="input peer"
                            value="{{ $device->lotNumber ?? '-' }}"
                            disabled
                        />
                        <label for="lotNumber" class="label">{{ __('devices.lot_number') }}</label>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="manufactureDate"
                            id="manufactureDate"
                            class="input peer"
                            value="{{ $device->manufactureDate ?? '-' }}"
                            disabled
                        />
                        <label for="manufactureDate" class="label">{{ __('devices.manufacture_date') }}</label>
                    </div>
                    <div class="form-group group">
                        <input
                            type="text"
                            name="expirationDate"
                            id="expirationDate"
                            class="input peer"
                            value="{{ $device->expirationDate ?? '-' }}"
                            disabled
                        />
                        <label for="expirationDate" class="label">{{ __('devices.expiration_date') }}</label>
                    </div>
                </div>

                @foreach ($device->identifiers as $identifier)
                    @php($externalSystemCode = $identifier->type->first()?->coding->first()?->code)
                    <div class="form-row-2">
                        <div class="form-group group">
                            <input
                                type="text"
                                name="externalSystem[]"
                                id="externalSystem-{{ $loop->index }}"
                                class="input peer"
                                value="{{ data_get($dictionaries, 'external_system.' . $externalSystemCode) ?? '-' }}"
                                disabled
                            />
                            <label for="externalSystem-{{ $loop->index }}" class="label">
                                {{ __('devices.external_system') }}
                            </label>
                        </div>
                        <div class="form-group group">
                            <input
                                type="text"
                                name="externalSystemIdentifier[]"
                                id="externalSystemIdentifier-{{ $loop->index }}"
                                class="input peer"
                                value="{{ $identifier->value }}"
                                disabled
                            />
                            <label for="externalSystemIdentifier-{{ $loop->index }}" class="label">
                                {{ __('devices.external_system_identifier') }}
                            </label>
                        </div>
                    </div>
                @endforeach

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="parent"
                            id="parent"
                            class="input peer"
                            value="{{ $device->parent?->value ?? '-' }}"
                            disabled
                        />
                        <label for="parent" class="label">{{ __('devices.parent') }}</label>
                    </div>
                </div>

                @foreach ($device->properties as $property)
                    @php($propertyCode = $property->code?->coding->first()?->code)
                    <div class="form-row-2">
                        <div class="form-group group">
                            <input
                                type="text"
                                name="property[]"
                                id="property-{{ $loop->index }}"
                                class="input peer"
                                value="{{ data_get($dictionaries, 'device_properties.' . $propertyCode) ?? '-' }}"
                                disabled
                            />
                            <label for="property-{{ $loop->index }}" class="label">{{ __('devices.property') }}</label>
                        </div>
                        <div class="form-group group">
                            <input
                                type="text"
                                name="propertyValue[]"
                                id="propertyValue-{{ $loop->index }}"
                                class="input peer"
                                value="{{ DeviceProperty::displayValue($property) }}"
                                disabled
                            />
                            <label
                                for="propertyValue-{{ $loop->index }}"
                                class="label"
                            >{{ __('devices.value') }}</label>
                        </div>
                    </div>
                @endforeach

                <div class="form-row-2">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="recorder"
                            id="recorder"
                            class="input peer"
                            value="{{ $device->recorder?->displayValue ?? $device->recorder?->value ?? '-' }}"
                            disabled
                        />
                        <label for="recorder" class="label">{{ __('devices.recorder') }}</label>
                    </div>
                </div>

                <div
                    class="form-row-2 mb-4"
                    x-data="{ isOtherSource: {{ $device->primarySource ? 'false' : 'true' }} }"
                >
                    <div class="form-group group">
                        <div class="flex items-center gap-4 pt-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('devices.source_data') }}</span>
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
                                value="{{ $device->ehealthInsertedDate ?: '-' }}"
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
                                value="{{ $device->ehealthInsertedTime ?: '-' }}"
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
                                value="{{ $device->ehealthUpdatedDate ?: '-' }}"
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
                                value="{{ $device->ehealthUpdatedTime ?: '-' }}"
                                placeholder=" "
                                disabled
                            />
                            <label for="ehealthUpdatedTime" class="sr-only">{{ __('forms.time') }}</label>
                        </div>
                    </div>
                </div>

                <div class="form-row mt-4">
                    <div class="form-group group">
                        <label for="note" class="label-modal mb-1">{{ __('devices.notes') }}</label>
                        <textarea
                            name="note"
                            id="note"
                            class="textarea"
                            disabled
                            rows="4"
                        >{{ $device->note }}</textarea>
                    </div>
                </div>
            </fieldset>

            <div class="mt-8">
                <a
                    href="{{ $personId ? route('persons.devices', [legalEntity(), 'person' => $personId]) : route('prepersons.devices', [legalEntity(), 'preperson' => $prepersonId]) }}"
                    class="button-minor px-6 py-2"
                >{{ __('forms.back') }}</a>
            </div>
        </div>
    </section>

    <livewire:components.x-message :key="now()->timestamp" />
</div>
