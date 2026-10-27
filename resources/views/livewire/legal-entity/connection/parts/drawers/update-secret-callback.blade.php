{{-- Expects: $connection (App\Models\Connection) --}}
{{-- Requires host x-data with: openUpdateSecretDrawer, isSecretUpdated, openUpdateCallbackDrawer, isCallbackUpdated --}}

@use('App\Classes\eHealth\Api\Connection')

{{-- UPDATE CLIENT SECRET --}}
@can('updateSecret', $connection)
<div x-data="{ updatedSecret: '' }"
     x-init="$watch('openUpdateSecretDrawer', value => { if (!value) { updatedSecret = ''; isSecretUpdated = false; } })"
>
<x-dialog-drawer x-model="openUpdateSecretDrawer" maxWidth="4/5" overlayWidth="100%"
    x-on:connection-secret-updated.window="if ($event.detail.connectionId === {{ $connection->id }} && openUpdateSecretDrawer) { updatedSecret = $event.detail.secret; isSecretUpdated = true; }"
>
    <x-slot name="title">
        <span class="text-xl font-semibold">
            <span x-show="isSecretUpdated" x-cloak>{{ __('legal-entity-connection.secret_updated_title') }}</span>
            <span x-show="!isSecretUpdated">{{ __('legal-entity-connection.update_secret_title') }}</span>
        </span>
    </x-slot>

    <div class="mt-6 max-w-5xl">
        <div x-show="isSecretUpdated" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                <div class="form-group group">
                    <input
                        type="text"
                        id="updated_secret_{{ $connection->id }}"
                        class="input peer"
                        placeholder=" "
                        x-model="updatedSecret"
                        disabled
                    >
                    <label for="updated_secret_{{ $connection->id }}" class="label">{{ __('legal-entity-connection.secret_string_label') }}</label>
                </div>

                <div class="hidden md:block"></div>

                <div class="form-group group">
                    <input
                        type="text"
                        id="updated_client_id_{{ $connection->uuid }}"
                        class="input peer"
                        placeholder=" "
                        value="{{ $connection->legalEntity->uuid }}"
                        disabled
                    >
                    <label for="updated_client_id_{{ $connection->uuid }}" class="label">{{ __('legal-entity-connection.client_id_label') }}</label>
                </div>

                <div class="form-group group">
                    <input
                        type="text"
                        id="updated_callback_{{ $connection->uuid }}"
                        class="input peer"
                        placeholder=" "
                        value="{{ $connection->redirectUri }}"
                        disabled
                    >
                    <label for="updated_callback_{{ $connection->uuid }}" class="label">{{ __('legal-entity-connection.callback_url_label') }}</label>
                </div>

                <div class="form-group group">
                    <input
                        type="text"
                        id="updated_consumer_id_{{ $connection->uuid }}"
                        class="input peer"
                        placeholder=" "
                        value="{{ $connection->consumerUuid }}"
                        disabled
                    >
                    <label for="updated_consumer_id_{{ $connection->uuid }}" class="label">{{ __('legal-entity-connection.consumer_id_label') }}</label>
                </div>

                <div class="form-group group">
                    <input
                        type="text"
                        id="updated_conn_id_{{ $connection->uuid }}"
                        class="input peer"
                        placeholder=" "
                        value="{{ $connection->uuid }}"
                        disabled
                    >
                    <label for="updated_conn_id_{{ $connection->uuid }}" class="label">{{ __('legal-entity-connection.conn_id_label') }}</label>
                </div>
            </div>

            <div class="flex flex-row items-center gap-4 mt-8">
                <button
                    type="button"
                    @click="openUpdateSecretDrawer = false; isSecretUpdated = false"
                    class="button-minor px-6"
                >
                    {{ __('legal-entity-connection.btn_close') }}
                </button>
            </div>
        </div>

        <div x-show="!isSecretUpdated">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                <div class="form-group group">
                    <input
                        type="text"
                        id="secret_client_id_{{ $connection->uuid }}"
                        class="input peer"
                        placeholder=" "
                        value="{{ $connection->legalEntity->uuid }}"
                        disabled
                    >
                    <label for="secret_client_id_{{ $connection->uuid }}" class="label">{{ __('legal-entity-connection.client_id_label_lower') }}</label>
                </div>

                <div class="form-group group">
                    <input
                        type="text"
                        id="secret_conn_id_{{ $connection->uuid }}"
                        class="input peer"
                        placeholder=" "
                        value="{{ $connection->uuid }}"
                        disabled
                    >
                    <label for="secret_conn_id_{{ $connection->uuid }}" class="label">{{ __('legal-entity-connection.conn_id_label_lower') }}</label>
                </div>
            </div>

            <div class="flex flex-row items-center gap-4 mt-8">
                <button
                    type="button"
                    @click="openUpdateSecretDrawer = false"
                    class="button-minor px-6"
                >
                    {{ __('legal-entity-connection.btn_back') }}
                </button>

                @can('updateSecret', $connection)
                    <button
                        type="button"
                        wire:click="refreshSecret({{ $connection->id }})"
                        wire:loading.attr="disabled"
                        wire:target="refreshSecret"
                        class="button-primary px-6"
                    >
                        {{ __('legal-entity-connection.btn_update') }}
                    </button>
                @endcan
            </div>
        </div>
    </div>
</x-dialog-drawer>
</div>
@endcan

{{-- UPDATE CALLBACK URI --}}
<x-dialog-drawer x-model="openUpdateCallbackDrawer" maxWidth="4/5" overlayWidth="100%"
    x-on:connection-callback-updated.window="if ($event.detail.connectionId === {{ $connection->id }}) isCallbackUpdated = true"
>
    <x-slot name="title">
        <span class="text-xl font-semibold">
            <span x-show="isCallbackUpdated" x-cloak>{{ __('legal-entity-connection.callback_updated_title') }}</span>
            <span x-show="!isCallbackUpdated">{{ __('legal-entity-connection.update_callback_title') }}</span>
        </span>
    </x-slot>

    <div
        class="mt-6 max-w-5xl"
        wire:key="connection-callback-{{ $connection->id }}-{{ $connection->updated_at?->timestamp }}"
        x-init="$watch('openUpdateCallbackDrawer', value => { if (value) { isCallbackUpdated = false; } })"
    >
        <div x-show="isCallbackUpdated" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                <div class="form-group group">
                    <input
                        type="text"
                        id="updated_callback_url_{{ $connection->id }}"
                        class="input peer"
                        placeholder=" "
                        value="{{ $connection->redirectUri }}"
                        disabled
                    >
                    <label for="updated_callback_url_{{ $connection->id }}" class="label">{{ __('legal-entity-connection.callback_url_label') }}</label>
                </div>

                <div class="form-group group">
                    <input
                        type="text"
                        id="updated_callback_consumer_id_{{ $connection->id }}"
                        class="input peer"
                        placeholder=" "
                        value="{{ $connection->consumerUuid }}"
                        disabled
                    >
                    <label for="updated_callback_consumer_id_{{ $connection->id }}" class="label">{{ __('legal-entity-connection.consumer_id_label') }}</label>
                </div>

                <div class="form-group group">
                    <input
                        type="text"
                        id="updated_callback_client_id_{{ $connection->id }}"
                        class="input peer"
                        placeholder=" "
                        value="{{ $connection->legalEntity->uuid }}"
                        disabled
                    >
                    <label for="updated_callback_client_id_{{ $connection->id }}" class="label">{{ __('legal-entity-connection.client_id_label') }}</label>
                </div>

                <div class="form-group group">
                    <input
                        type="text"
                        id="updated_callback_conn_id_{{ $connection->id }}"
                        class="input peer"
                        placeholder=" "
                        value="{{ $connection->uuid }}"
                        disabled
                    >
                    <label for="updated_callback_conn_id_{{ $connection->id }}" class="label">{{ __('legal-entity-connection.conn_id_label') }}</label>
                </div>
            </div>

            <div class="flex flex-row items-center gap-4 mt-8">
                <button
                    type="button"
                    @click="openUpdateCallbackDrawer = false; isCallbackUpdated = false"
                    class="button-minor px-6"
                >
                    {{ __('legal-entity-connection.btn_close') }}
                </button>
            </div>
        </div>
        <div x-show="!isCallbackUpdated">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                <div class="form-group group">
                    <input
                        type="text"
                        id="callback_client_id_{{ $connection->legalEntity->uuid }}"
                        class="input peer"
                        placeholder=" "
                        value="{{ $connection->legalEntity->uuid }}"
                        disabled
                    >
                    <label for="callback_client_id_{{ $connection->legalEntity->uuid }}" class="label">{{ __('legal-entity-connection.client_id_label_lower') }}</label>
                </div>

                <div class="form-group group">
                    <input
                        type="text"
                        id="callback_conn_id_{{ $connection->uuid }}"
                        class="input peer"
                        placeholder=" "
                        value="{{ $connection->uuid }}"
                        disabled
                    >
                    <label for="callback_conn_id_{{ $connection->uuid }}" class="label">{{ __('legal-entity-connection.conn_id_label_lower') }}</label>
                </div>

                <div class="form-group group">
                    <input
                        type="text"
                        id="callback_url_input_{{ $connection->uuid }}"
                        class="input peer"
                        placeholder=" "
                        value="{{ Connection::redirectUri() }}"
                        disabled
                    >
                    <label for="callback_url_input_{{ $connection->uuid }}" class="label">{{ __('legal-entity-connection.callback_url_label') }}</label>
                </div>
            </div>

            <div class="flex flex-row items-center gap-4 mt-8">
                <button
                    type="button"
                    @click="openUpdateCallbackDrawer = false"
                    class="button-minor px-6"
                >
                    {{ __('legal-entity-connection.btn_back') }}
                </button>

                @can('update', $connection)
                    <button
                        type="button"
                        wire:click="update({{ $connection->id }})"
                        wire:loading.attr="disabled"
                        wire:target="update"
                        class="button-primary px-6"
                    >
                        {{ __('legal-entity-connection.btn_update') }}
                    </button>
                @endcan
            </div>
        </div>
    </div>
</x-dialog-drawer>
