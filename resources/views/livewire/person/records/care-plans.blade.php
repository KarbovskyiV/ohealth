<div class="form shift-content">
    <div class="flex items-center justify-between mb-6">
        <h2 class="title">{{ __('care-plan.care_plans') }}</h2>
        <a href="{{ route('carePlan.create', [legalEntity(), 'patientUuid' => $uuid]) }}" class="button-primary">
            + {{ __('care-plan.new_care_plan') }}
        </a>
    </div>

    <div class="space-y-4">
        @forelse($carePlans as $plan)
            <div class="record-inner-card">
                <div class="record-inner-header">
                    <div class="record-inner-checkbox-col">
                        <input type="checkbox" class="default-checkbox w-5 h-5">
                    </div>

                    <div class="record-inner-column flex-1">
                        <div class="record-inner-label">{{ __('care-plan.name_care_plan') }}</div>
                        <div class="record-inner-value text-[16px]">{{ $plan->title }}</div>
                    </div>

                    <div class="record-inner-column-bordered w-full md:w-36 shrink-0">
                        <div class="record-inner-label">{{ __('forms.status.label') }}</div>
                        <div>
                            @php
                                $status = is_array($plan->status) ? ($plan->status['coding'][0]['code'] ?? ($plan->status['text'] ?? '')) : $plan->status;
                                $statusDisplay = is_array($plan->status) ? ($plan->status['text'] ?? ($plan->status['coding'][0]['display'] ?? $status)) : $status;
                            @php
                            <span class="record-inner-status-badge {{ in_array(strtoupper($status), ['ACTIVE', 'active']) ? '' : 'bg-gray-100 text-gray-800' }}">
                                {{ $statusDisplay }}
                            </span>
                        </div>
                    </div>

                    <div class="record-inner-action-col" x-data="{ openMenu: false }">
                        <button @click="openMenu = !openMenu"
                                @click.away="openMenu = false"
                                class="record-inner-action-btn"
                        >
                            @icon('edit-user-outline', 'w-5 h-5')
                        </button>

                        <div x-show="openMenu"
                             x-transition.opacity.duration.200ms
                             class="absolute right-[50%] md:right-0 top-1/2 md:top-[80%] w-56 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-lg rounded-xl z-20 py-2"
                             style="display: none;"
                        >
                            <a href="{{ route('carePlan.show', [legalEntity(), $plan->id]) }}"
                               class="w-full text-left px-4 py-2.5 text-[14px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-3 transition-colors">
                                <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                {{ __('forms.show') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="record-inner-body">
                    <div class="record-inner-grid-container">
                        <div class="flex items-start justify-between gap-2 xl:gap-4 overflow-hidden">
                            <div>
                                <div class="record-inner-label">{{ __('care-plan.requisition') }}</div>
                                <div class="record-inner-value">{{ $plan->requisition ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="record-inner-label">{{ __('care-plan.category') }}</div>
                                <div class="record-inner-value">
                                    @if(is_array($plan->category))
                                        {{ $plan->category['text'] ?? $plan->category['coding'][0]['display'] ?? $plan->category['coding'][0]['code'] ?? '-' }}
                                    @else
                                        {{ $plan->category ?? '-' }}
                                    @endif
                                </div>
                            </div>
                            <div>
                                <div class="record-inner-label">{{ __('forms.start_date') }}</div>
                                <div class="record-inner-value">{{ $plan->period_start?->format('d.m.Y') ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="record-inner-label">{{ __('care-plan.author') }}</div>
                                <div class="record-inner-value">
                                    {{ $plan->author?->party?->full_name ?? '-' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="record-inner-id-col">
                        <div class="min-w-0">
                            <div class="record-inner-label">ID ECO3</div>
                            <div class="record-inner-id-value">{{ $plan->ehealth_id ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-10 bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
                <p class="text-gray-400">{{ __('care-plan.no_care_plans') }}</p>
            </div>
        @endforelse
    </div>
</div>
