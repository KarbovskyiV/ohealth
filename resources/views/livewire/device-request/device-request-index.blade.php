<div
    class="shift-content space-y-5 p-6"
    x-data
    @print-device-request.window="window.printSandboxedHtml($event.detail.html)"
>
    @assets
        <script src="{{ asset('js/print-sandboxed.js') }}"></script>
    @endassets
    <livewire:components.x-message :consume-messages="true" :key="(string) str()->uuid()" />
    <x-forms.loading />
    <x-header-navigation class="items-start">
        <x-slot name="title">
            {{ __('device-requests.labels.registry') . ($person ? ' — ' . $person->fullName : '') }}
        </x-slot>
    </x-header-navigation>
    @if ($errors->any())
        <div role="alert" class="rounded bg-red-50 p-4 text-red-800">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    @if (!$person)
        <form wire:submit="searchPatients" class="flex gap-3">
            <label
                >{{ __('device-requests.labels.patient_surname') }}<input
                    wire:model="patientSearch"
                    class="input" /></label
            ><button class="button-primary">{{ __('device-requests.labels.find_patient') }}</button>
        </form>
        @foreach ($patients as $patient)
            <button wire:click="openPatient({{ $patient['id'] }})" class="button-minor">{{ $patient['name'] }}</button>
        @endforeach
    @else
        @can('create', [\App\Models\MedicalEvents\Sql\DeviceRequestRequest::class, $legalEntity])
            <a
                class="button-primary inline-block"
                href="{{ route('device-requests.create', ['legalEntity' => $legalEntity, 'person' => $person]) }}"
            >{{ __('device-requests.labels.create') }}</a>
        @endcan
        @if ($drafts)
            <section>
                <h2 class="font-semibold">{{ __('device-requests.labels.drafts') }}</h2>
                @foreach ($drafts as $draft)
                    <a
                        class="button-minor inline-block"
                        href="{{ route('device-requests.create', ['legalEntity' => $legalEntity, 'person' => $person, 'draft' => $draft['uuid']]) }}"
                    >{{ $draft['device'] }} · {{ $draft['updatedAt'] }} — {{ __('device-requests.labels.open_draft') }}</a>
                @endforeach
            </section>
        @endif
        <form wire:submit="search" class="grid gap-4 md:grid-cols-3">
            <div>
                <label for="device-request-entity">{{ __('device-requests.labels.requester_entity') }}</label>
                <input
                    id="device-request-entity"
                    class="input w-full @error('requesterEntitySearch') input-error @enderror"
                    wire:model="requesterEntitySearch"
                    list="device-request-entities"
                    placeholder="{{ __('device-requests.labels.entity_search_placeholder') }}"
                    autocomplete="off"
                />
                <datalist id="device-request-entities">
                    @foreach ($legalEntityOptions as $entity)
                        <option value="{{ $entity['name'] }}" label="{{ $entity['uuid'] }}"></option>
                    @endforeach
                </datalist>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('device-requests.labels.entity_search_hint') }}
                </p>
                <x-input-error for="requesterEntitySearch" />
            </div>
            @foreach (['code' => __('device-requests.labels.device_code'), 'context_episode_id' => __('device-requests.labels.episode'), 'encounter' => __('device-requests.labels.encounter'), 'program' => __('device-requests.labels.program')] as $key => $label)
                <label>{{ $label }}<input class="input w-full" wire:model="filters.{{ $key }}" /></label>
            @endforeach
            <label
                >{{ __('device-requests.labels.status') }}<select
                    wire:model="filters.status"
                    class="input-select w-full"
                >
                    <option value="">{{ __('device-requests.labels.all') }}</option>
                    @foreach (['active' => __('device-requests.labels.active'), 'completed' => __('device-requests.labels.completed'), 'revoked' => __('device-requests.labels.revoked'), 'entered-in-error' => __('device-requests.labels.entered_in_error')] as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach</select></label
            ><button class="button-primary">{{ __('device-requests.labels.search') }}</button>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr>
                        <th>{{ __('device-requests.labels.number') }}</th>
                        <th>{{ __('device-requests.labels.device') }}</th>
                        <th>{{ __('device-requests.labels.status') }}</th>
                        <th>{{ __('device-requests.labels.quantity') }}</th>
                        <th>{{ __('device-requests.labels.program_short') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $record)
                        <tr wire:key="request-{{ $record['id'] }}">
                            <td>{{ $record['requisition'] ?? '—' }}</td>
                            <td>
                                {{ data_get($record, 'code_reference.display_value', data_get($record, 'code.coding.0.display', data_get($record, 'code.coding.0.code', '—'))) }}
                            </td>
                            <td>{{ \App\Enums\Person\DeviceRequestStatus::labelFor($record['status']) }}</td>
                            <td>
                                {{ data_get($record, 'quantity.value', '—') }} {{ data_get($record, 'quantity.unit', data_get($record, 'quantity.code')) }}
                            </td>
                            <td>{{ data_get($record, 'program.display_value', '—') }}</td>
                            <td>
                                <button wire:click="showDetails('{{ $record['id'] }}')" class="button-minor">
                                    {{ __('device-requests.labels.details') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">{{ __('device-requests.labels.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex gap-3">
            <button wire:click="goToPage({{ $page - 1 }})" class="button-minor" @disabled($page <= 1)>
                {{ __('device-requests.labels.previous') }}</button
            ><span>{{ $page }} / {{ $totalPages }}</span
            ><button wire:click="goToPage({{ $page + 1 }})" class="button-minor" @disabled($page >= $totalPages)>
                {{ __('device-requests.labels.next') }}
            </button>
        </div>
        @if ($selected)
            <section class="space-y-4 rounded border p-4">
                <h2 class="text-lg font-semibold">
                    {{ __('device-requests.labels.request') }} {{ $selected['requisition'] ?? '' }}
                </h2>
                @include('livewire.device-request.record-details', ['record' => $selected])
                @foreach ($catalog as $device)
                    <details class="rounded border p-3">
                        <summary>
                            {{ __('device-requests.labels.catalog_info') }}: {{ data_get($device, 'device_names.0.name', $device['model_number'] ?? '') }}
                        </summary>
                        @include('livewire.device-request.record-value', ['value' => $device])
                    </details>
                @endforeach
                <div class="flex flex-wrap gap-3">
                    <button wire:click="printout" class="button-minor">{{ __('device-requests.labels.print') }}</button>
                    @if ($selected['status'] === 'active')
                        @can('device_request:revoke')
                            <button wire:click="startAction('revoke')" class="button-minor">
                                {{ __('device-requests.labels.revoke') }}
                            </button>
                        @endcan
                        @if (!isset($selected['quantity']))
                            @can('device_request:complete')
                                <button
                                    wire:click="complete"
                                    wire:confirm="{{ __('device-requests.labels.complete_confirm') }}"
                                    class="button-minor"
                                >
                                    {{ __('device-requests.labels.complete') }}
                                </button>
                            @endcan
                        @endif
                    @endif
                    @if (!in_array($selected['status'], ['entered-in-error', 'entered_in_error']))
                        @can('device_request:mark_in_error')
                            <button wire:click="startAction('mark_in_error')" class="button-minor">
                                {{ __('device-requests.labels.mark_in_error') }}
                            </button>
                        @endcan
                    @endif
                    @can('device_dispense:read')
                        <button wire:click="loadDispenses" class="button-minor">
                            {{ __('device-requests.labels.dispenses') }}
                        </button>
                    @endcan
                </div>
                @if ($phone && $selected['status'] === 'active')
                    @can('device_request:resend')
                        <div>
                            <p>{{ __('device-requests.labels.sms_hint') }}: {{ $phone }}</p>
                            <label
                                ><input type="checkbox" wire:model="phoneConfirmed" />
                                {{ __('device-requests.labels.phone_confirmed') }}</label
                            ><button
                                wire:click="resendSms"
                                wire:confirm="{{ __('device-requests.labels.sms_confirm') }}"
                                class="button-minor"
                            >
                                {{ __('device-requests.labels.resend') }}
                            </button>
                        </div>
                    @endcan
                @endif
                @if ($action)
                    <div class="space-y-3">
                        <label
                            >{{ __('device-requests.labels.reason') }}<select
                                wire:model="reason"
                                class="input-select w-full"
                            >
                                <option value="">{{ __('device-requests.labels.choose_reason') }}</option>
                                @foreach ($reasons as $code => $label)
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach</select></label
                        ><label
                            >{{ __('device-requests.labels.explanation') }}<textarea
                                wire:model="reasonText"
                                class="input w-full"
                            ></textarea></label
                        ><button wire:click="openSignatureModal" class="button-primary">
                            {{ __('device-requests.labels.sign_change') }}
                        </button>
                    </div>
                @endif
                @foreach ($dispenses as $dispense)
                    <button wire:click="showDispense('{{ $dispense['id'] }}')" class="button-minor">
                        {{ __('device-requests.labels.dispense') }} {{ $dispense['id'] }} · {{ $dispense['status'] ?? '' }}
                    </button>
                @endforeach
                @if ($dispenseDetails)
                    <h3 class="font-semibold">{{ __('device-requests.labels.dispense_details') }}</h3>
                    @include('livewire.device-request.record-details', ['record' => $dispenseDetails])
                @endif
            </section>
        @endif
    @endif
    <x-signature-modal method="sign" />
</div>
