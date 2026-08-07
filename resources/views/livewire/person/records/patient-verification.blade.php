@php
    use App\Models\MedicalEvents\Sql\Encounter;

    $verificationRows = [
        [
            'source_key' => 'drfo',
            'source_label' => __('patients.verification_sources.drfo'),
            'status_label' => __('patients.status.not_verified'),
            'status_class' => 'badge-red',
            'reason_label' => 'Автоматична',
            'comment' => '-',
            'recommendation' => __('patients.verification_recommendations.drfo'),
        ],
        [
            'source_key' => 'dracs_death',
            'source_label' => __('patients.verification_sources.dracs_death'),
            'status_label' => __('patients.status.not_verified'),
            'status_class' => 'badge-red',
            'reason_label' => 'Автоматична',
            'comment' => '-',
            'recommendation' => __('patients.verification_recommendations.dracs_death'),
        ],
        [
            'source_key' => 'dracs_birth',
            'source_label' => __('patients.verification_sources.dracs_birth'),
            'status_label' => __('patients.status.not_verified'),
            'status_class' => 'badge-red',
            'reason_label' => 'Автоматична',
            'comment' => '-',
            'recommendation' => __('patients.verification_recommendations.dracs_birth'),
        ],
        [
            'source_key' => 'dms_passport',
            'source_label' => __('patients.verification_sources.dms_passport'),
            'status_label' => __('patients.status.not_verified'),
            'status_class' => 'badge-red',
            'reason_label' => 'Автоматична',
            'comment' => '-',
            'recommendation' => __('patients.verification_recommendations.dms_passport'),
        ],
        [
            'source_key' => 'unzr',
            'source_label' => __('patients.verification_sources.unzr'),
            'status_label' => __('patients.status.not_verified'),
            'status_class' => 'badge-red',
            'reason_label' => 'Автоматична',
            'comment' => '-',
            'recommendation' => __('patients.verification_recommendations.unzr'),
        ],
        [
            'source_key' => 'nhs',
            'source_label' => __('patients.verification_sources.nhs'),
            'status_label' => __('patients.status.not_verified'),
            'status_class' => 'badge-red',
            'reason_label' => 'Автоматична',
            'comment' => '-',
            'recommendation' => __('patients.verification_recommendations.nhs', ['comment' => '{details.nhs.verification_comment}']),
        ],
    ];
@endphp

<x-layouts.patient :personId="$personId" :prepersonId="$prepersonId" :patientFullName="$patientFullName" :activeTab="'verification'">
    <x-slot name="headerActions">
        @can('create', Encounter::class)
            <a href="{{ $prepersonId
                ? route('prepersons.encounter.create', [legalEntity(), 'preperson' => $prepersonId])
                : route('encounter.create', [legalEntity(), 'person' => $personId]) }}"
               class="flex items-center gap-2 button-primary px-5 py-2 text-sm shadow-sm"
            >
                @icon('plus', 'w-4 h-4')
                {{ __('patients.starts_interacting') }}
            </a>
        @endcan

        <button type="button"
                class="button-primary-outline whitespace-nowrap px-5 py-2 text-sm"
        >
            {{ __('patients.data_access') }}
        </button>

        <button type="button"
                class="button-sync flex items-center gap-2 whitespace-nowrap px-5 py-2 text-sm shadow-sm"
        >
            @icon('refresh', 'w-4 h-4')
            {{ __('forms.synchronise_with_eHealth') }}
        </button>
    </x-slot>

    <div class="breadcrumb-form p-4 shift-content space-y-6"
         x-data="{
             showUpdateModal: false,
             status: 'VERIFIED',
             reason: 'MANUAL_DECEASED',
             comment: '{{ __('patients.comment_death_confirmed') }}'
         }"
    >

        {{-- Warning Banner --}}
        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/30 flex items-start gap-3">
            <div class="flex-shrink-0 text-red-600 dark:text-red-400 mt-0.5">
                @icon('alert-circle', 'w-5 h-5')
            </div>
            <div class="text-sm leading-relaxed space-y-1">
                <h4 class="font-bold text-red-700 dark:text-red-400">
                    {{ __('patients.verification_warning_banner_title') }}
                </h4>
                <p class="text-red-600 dark:text-red-300">
                    {{ __('patients.verification_warning_banner_text') }}
                </p>
                <p class="text-red-600 dark:text-red-300">
                    {{ __('patients.verification_warning_banner_notice') }}
                </p>
            </div>
        </div>

        {{-- Verification Details Table --}}
        <div class="index-table-wrapper overflow-x-auto">
            <table class="index-table">
                <thead class="index-table-thead">
                    <tr>
                        <th class="index-table-th w-[20%] uppercase">{{ __('patients.verification_direction') }}</th>
                        <th class="index-table-th w-[15%] uppercase whitespace-nowrap">{{ __('patients.verification_status_header') }}</th>
                        <th class="index-table-th w-[15%] uppercase">{{ __('patients.verification_reason_header') }}</th>
                        <th class="index-table-th w-[10%] uppercase">{{ __('patients.verification_comment_header') }}</th>
                        <th class="index-table-th w-[40%] uppercase">{{ __('patients.verification_recommendations_header') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($verificationRows as $row)
                        <tr class="index-table-tr" wire:key="verification-row-{{ $row['source_key'] }}">
                            <td class="index-table-td font-semibold text-gray-900 dark:text-white align-top">
                                {{ $row['source_label'] }}
                            </td>
                            <td class="index-table-td align-top whitespace-nowrap">
                                <span class="{{ $row['status_class'] }} inline-block whitespace-nowrap">
                                    {{ $row['status_label'] }}
                                </span>
                            </td>
                            <td class="index-table-td align-top text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ $row['reason_label'] }}
                            </td>
                            <td class="index-table-td align-top text-gray-600 dark:text-gray-400">
                                {{ $row['comment'] }}
                            </td>
                            <td class="index-table-td align-top text-xs leading-relaxed text-gray-600 dark:text-gray-300">
                                {{ $row['recommendation'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Action Button --}}
        <div>
            <button
                type="button"
                @click="showUpdateModal = true"
                class="button-primary-outline inline-flex items-center gap-2"
            >
                {{ __('patients.update_data') }}
            </button>
        </div>

        {{-- Update Verification Modal --}}
        <div
            x-show="showUpdateModal"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
            @keydown.escape.window="showUpdateModal = false"
        >
            {{-- Backdrop --}}
            <div
                x-show="showUpdateModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-[#343e4d]/80 transition-opacity"
                @click="showUpdateModal = false"
            ></div>

            {{-- Modal Dialog --}}
            <div class="relative flex min-h-screen items-center justify-center p-4 sm:p-6">
                <div
                    x-show="showUpdateModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative w-full max-w-4xl transform rounded-xl bg-white p-8 sm:p-12 text-left shadow-2xl transition-all dark:bg-gray-800"
                    @click.stop
                >
                    {{-- Title --}}
                    <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-8" id="modal-title">
                        {{ __('patients.update_verification_dracs_title') }}
                    </h3>

                    {{-- Form --}}
                    <form @submit.prevent="showUpdateModal = false" class="space-y-6">
                        {{-- Status --}}
                        <div class="space-y-1.5 max-w-xs">
                            <label for="modal-status" class="block text-xs font-normal text-gray-500 dark:text-gray-400">
                                {{ __('patients.status_field') }}
                            </label>
                            <div class="border-b border-gray-300 dark:border-gray-600 focus-within:border-blue-600">
                                <select
                                    id="modal-status"
                                    x-model="status"
                                    class="w-full bg-transparent py-1.5 text-sm font-normal text-gray-900 dark:text-white focus:outline-none cursor-pointer border-0 p-0"
                                >
                                    <option value="VERIFIED" class="dark:bg-gray-800">{{ __('patients.status.verified') }}</option>
                                    <option value="NOT_VERIFIED" class="dark:bg-gray-800">{{ __('patients.status.not_verified') }}</option>
                                </select>
                            </div>
                        </div>

                        {{-- Reason --}}
                        <div class="space-y-1.5 max-w-xs">
                            <label for="modal-reason" class="block text-xs font-normal text-gray-500 dark:text-gray-400">
                                {{ __('patients.verification_reason_field') }}
                            </label>
                            <div class="border-b border-gray-300 dark:border-gray-600 focus-within:border-blue-600">
                                <select
                                    id="modal-reason"
                                    x-model="reason"
                                    class="w-full bg-transparent py-1.5 text-sm font-normal text-gray-900 dark:text-white focus:outline-none cursor-pointer border-0 p-0"
                                >
                                    <option value="MANUAL_DECEASED" class="dark:bg-gray-800">{{ __('patients.reason_manual_confirmed') }}</option>
                                </select>
                            </div>
                        </div>

                        {{-- Comment --}}
                        <div class="space-y-2 pt-2">
                            <label for="modal-comment" class="block text-sm font-bold text-gray-900 dark:text-white">
                                {{ __('forms.comment') }}
                            </label>
                            <textarea
                                id="modal-comment"
                                x-model="comment"
                                rows="5"
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent p-4 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none resize-none shadow-sm"
                                placeholder="{{ __('patients.comment_death_confirmed') }}"
                            ></textarea>
                        </div>

                        {{-- Footer Buttons --}}
                        <div class="flex items-center gap-4 pt-4">
                            <button
                                type="button"
                                @click="showUpdateModal = false"
                                class="button-minor px-6 py-2.5"
                            >
                                {{ __('forms.cancel') }}
                            </button>

                            <button
                                type="button"
                                @click="showUpdateModal = false"
                                class="rounded-md bg-blue-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                            >
                                {{ __('patients.update_data_in_ehealth') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.patient>
