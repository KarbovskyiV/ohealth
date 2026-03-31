<x-layouts.patient :id="$id" :patientFullName="$patientFullName">
    <div class="form shift-content">
        <div class="flex items-center justify-between mb-6">
            <h2 class="title">{{ __('care-plan.care_plans') }}</h2>
            <a href="{{ route('care-plan.create', [legalEntity(), 'patientUuid' => $uuid]) }}" class="button-primary">
                + {{ __('care-plan.new_care_plan') }}
            </a>
        </div>

        <div class="table-container-responsive overflow-x-auto">
            <fieldset class="p-4 sm:p-8 sm:pb-10 border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 w-full">
                <legend class="legend">{{ __('care-plan.care_plans') }}</legend>

                <div class="flow-root mt-4">
                    <div class="max-w-screen-xl overflow-x-auto">
                        <table class="table-input w-full min-w-[1000px] text-sm">
                            <thead class="thead-input">
                                <tr>
                                    <th scope="col" class="th-input text-left">{{ __('care-plan.name_care_plan') }}</th>
                                    <th scope="col" class="th-input text-left w-32">{{ __('care-plan.requisition') }}</th>
                                    <th scope="col" class="th-input text-left w-48">{{ __('care-plan.category') }}</th>
                                    <th scope="col" class="th-input text-left w-28">{{ __('forms.start_date') }}</th>
                                    <th scope="col" class="th-input text-left">{{ __('care-plan.author') }}</th>
                                    <th scope="col" class="th-input text-left w-28">{{ __('forms.status.label') }}</th>
                                    <th scope="col" class="th-input text-center w-24">{{ __('forms.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($carePlans as $plan)
                                    @php
                                        $status = is_array($plan->status) ? ($plan->status['coding'][0]['code'] ?? ($plan->status['text'] ?? '')) : $plan->status;
                                        $statusDisplay = is_array($plan->status) ? ($plan->status['text'] ?? ($plan->status['coding'][0]['display'] ?? $status)) : $status;
                                        
                                        $categoryCode = is_array($plan->category) 
                                            ? ($plan->category['coding'][0]['code'] ?? null) 
                                            : $plan->category;

                                        $categoryLabel = $dictionaries['care_plan_categories'][$categoryCode] 
                                            ?? (is_array($plan->category) 
                                                ? ($plan->category['text'] ?? ($plan->category['coding'][0]['display'] ?? $categoryCode)) 
                                                : $plan->category);
                                    @endphp
                                    <tr wire:key="care-plan-{{ $plan->id }}">
                                        <td class="td-input break-words text-blue-600 font-medium">
                                            <a href="{{ route('care-plan.show', [legalEntity(), $plan->id]) }}" class="hover:underline">
                                                {{ $plan->title }}
                                            </a>
                                        </td>
                                        <td class="td-input break-words">{{ $plan->requisition ?? '-' }}</td>
                                        <td class="td-input break-words">{{ $categoryLabel ?? '-' }}</td>
                                        <td class="td-input">{{ $plan->period_start?->format('d.m.Y') ?? '-' }}</td>
                                        <td class="td-input break-words">{{ $plan->author?->party?->full_name ?? '-' }}</td>
                                        <td class="td-input">
                                            @if(in_array(strtoupper($status), ['ACTIVE', 'SUPLETED']))
                                                <span class="badge-green">{{ $statusDisplay }}</span>
                                            @elseif(in_array(strtoupper($status), ['NEW', 'DRAFT']))
                                                <span class="badge-yellow">{{ $statusDisplay }}</span>
                                            @else
                                                <span class="badge-red">{{ $statusDisplay }}</span>
                                            @endif
                                        </td>
                                        <td class="td-input text-center">
                                            <a href="{{ route('care-plan.show', [legalEntity(), $plan->id]) }}" 
                                               class="button-minor inline-flex items-center justify-center p-1.5"
                                               title="{{ __('forms.show') }}"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="td-input text-center py-10 text-gray-400 italic">
                                            {{ __('care-plan.no_care_plans') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </fieldset>
        </div>
    </div>
    <x-forms.loading />
</x-layouts.patient>

