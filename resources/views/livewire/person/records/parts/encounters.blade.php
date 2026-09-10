@use('App\Enums\Person\EncounterStatus')

@php
    $limit = $limit ?? null;
    $hasLimit = $limit && count($this->encounters) > $limit;
@endphp

<div @if ($hasLimit) x-data="{ limit: {{ $limit }} }" @endif>
    @foreach ($this->encounters as $index => $encounter)
        <div class="record-inner-card" @if ($hasLimit) x-show="limit > {{ $index }}" @endif>
            <div class="record-inner-header">
                <div class="record-inner-checkbox-col">
                    <input type="checkbox" class="default-checkbox h-5 w-5" />
                </div>

                <div class="record-inner-column flex-1">
                    <div class="record-inner-label">{{ __('forms.date') }}</div>
                    <div class="record-inner-value text-[20px] font-semibold">
                        {{ data_get($encounter, 'period.start', '-') }}
                    </div>
                </div>

                <div class="record-inner-column-bordered w-full shrink-0 md:w-36">
                    <div class="record-inner-label">{{ __('forms.status.label') }}</div>
                    <div>
                        @php($status = EncounterStatus::from(data_get($encounter, 'status')))
                        <span @class([$status->color()])> {{ $status->label() }} </span>
                    </div>
                </div>

                <div class="record-inner-action-col">
                    <button class="record-inner-action-btn cursor-pointer">
                        @icon('edit-user-outline', 'w-5 h-5')
                    </button>
                </div>
            </div>

            <div class="record-inner-body">
                <div class="record-inner-grid-container">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="min-w-0">
                            <div class="record-inner-label">{{ __('patients.class') }}</div>
                            <div class="record-inner-value truncate">
                                {{ data_get($this->dictionaries, 'eHealth/encounter_classes.' . data_get($encounter, 'class.code'), data_get($encounter, 'class.code', '-')) }}
                            </div>
                        </div>

                        <div class="min-w-0">
                            <div class="record-inner-label">{{ __('forms.type') }}</div>
                            <div class="record-inner-value truncate">
                                {{ data_get($this->dictionaries, 'eHealth/encounter_types.' . data_get($encounter, 'type.coding.0.code'), data_get($encounter, 'type.coding.0.code', '-')) }}
                            </div>
                        </div>

                        <div class="min-w-0">
                            <div class="record-inner-label">{{ __('patients.doctor_speciality') }}</div>
                            <div class="record-inner-value truncate">
                                {{ $this->dictionaries['SPECIALITY_TYPE'][data_get($encounter, 'performerSpeciality.coding.0.code')] ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="record-inner-id-col">
                    <div class="min-w-0">
                        <div class="record-inner-label">{{ __('forms.ehealth_id') }}</div>
                        <div class="record-inner-id-value">{{ data_get($encounter, 'uuid', '-') }}</div>
                    </div>
                    <div class="min-w-0">
                        <div class="record-inner-label">{{ __('episodes.id') }}</div>
                        <div class="record-inner-id-value">
                            {{ data_get($encounter, 'episode.identifier.value', '-') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @if ($hasLimit)
        <div x-show="limit < {{ count($this->encounters) }}" class="mt-4 flex justify-start">
            <button type="button" @click="limit += 5" class="item-add">{{ __('patients.show_more') }}</button>
        </div>
    @endif
</div>
