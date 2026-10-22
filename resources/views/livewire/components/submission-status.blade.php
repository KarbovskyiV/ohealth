<div>
    <fieldset class="fieldset-card p-4 sm:p-8">
        <legend class="legend">{{ __('encounters.submission.label') }}</legend>

        <div class="flex flex-col gap-6 sm:flex-row sm:items-end">
            <div class="form-group group flex-1">
                <input type="text" id="ehealthStatus" class="input text-gray-500" value="{{ $statusLabel }}" readonly />
            </div>

            @if ($editUrl !== null)
                <div class="mb-1">
                    <a href="{{ $editUrl }}" wire:navigate class="button-primary px-8"> {{ __('forms.update') }} </a>
                </div>
            @endif
        </div>

        @if ($jobId !== null)
            <button
                type="button"
                wire:click="openDetails"
                wire:loading.attr="disabled"
                wire:target="openDetails"
                class="mt-4 cursor-pointer text-sm font-medium text-blue-600 transition-colors hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
            >
                {{ __('encounters.submission.details') }}
            </button>
        @endif
    </fieldset>

    <x-modal wire:model="showDetails" maxWidth="xl">
        <div class="p-6 text-left md:p-8">
            <h2 class="mb-6 text-xl font-bold text-gray-900 dark:text-white">
                {{ __('encounters.submission.details') }}
            </h2>

            <dl class="space-y-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('encounters.submission.job_id') }}</dt>
                    <dd class="font-medium break-all text-gray-900 dark:text-gray-100">{{ $job['id'] ?? '' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('forms.status.label') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $job['status'] ?? '' }}</dd>
                </div>
                @if (!empty($job['created']))
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('encounters.submission.created') }}</dt>
                        @foreach ($job['created'] as $created)
                            <dd
                                wire:key="submission-created-{{ $created['id'] }}"
                                class="text-gray-900 dark:text-gray-100"
                            >
                                <span class="font-medium">{{ $created['entity'] }}</span>
                                <span class="break-all text-gray-500 dark:text-gray-400">{{ $created['id'] }}</span>
                            </dd>
                        @endforeach
                    </div>
                @endif
                @if (isset($job['error']))
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('encounters.submission.error') }}</dt>
                        <dd class="font-medium whitespace-pre-line text-red-600 dark:text-red-400">
                            {{ $job['error'] }}
                        </dd>
                    </div>
                @endif
            </dl>

            <div class="mt-8">
                <button type="button" @click="show = false" class="button-minor min-w-30 justify-center">
                    {{ __('forms.close') }}
                </button>
            </div>
        </div>
    </x-modal>

    <livewire:components.x-message :key="time()" />
</div>
