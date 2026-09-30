<section class="section-form">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <x-header-navigation class="breadcrumb-form flex-1 min-w-0"
            :breadcrumbs="[
                ['label' => __('forms.home'), 'url' => route('dashboard', [legalEntity()])],
                ['label' => __('patients.data_access'), 'url' => route('data-access.index', [legalEntity()])],
                ['label' => __('forms.view_details')]
            ]"
        >
            <x-slot name="title">{{ __('approval.access_details') }}</x-slot>

            <div class="mt-3 ml-0 flex flex-col gap-2 self-start sm:flex-row sm:flex-wrap">
                <button
                    type="button"
                    class="button-sync flex items-center gap-2 whitespace-nowrap"
                >
                    @icon('refresh', 'w-4 h-4')
                    <span>{{ __('forms.synchronise_with_eHealth') }}</span>
                </button>
            </div>
        </x-header-navigation>
    </div>

    <div class="shift-content pl-3.5 mt-8">
        <fieldset class="fieldset">
            <div class="form-row-2">
                <div class="form-group group">
                    <span class="label">{{ __('approval.data_id') }}</span>
                    <div class="input h-auto min-h-10.5 py-2.5 wrap-break-word whitespace-normal text-sm">
                        d5a5d991-0bf7-476f-b3cf-bec73f044b2e
                    </div>
                </div>
                <div class="form-group group">
                    <span class="label">{{ __('approval.data_type') }}</span>
                    <div class="input h-auto min-h-10.5 py-2.5 wrap-break-word whitespace-normal text-sm">
                        {{ __('Діагностичний звіт') }}
                    </div>
                </div>
            </div>

            <div class="form-row-2 mt-4">
                <div class="form-group group">
                    <span class="label">{{ __('approval.access_level') }}</span>
                    <div class="input h-auto min-h-10.5 py-2.5 wrap-break-word whitespace-normal text-sm">
                        Читання
                    </div>
                </div>
                <div class="form-group group">
                    <span class="label">{{ __('forms.status.label') }}</span>
                    <div class="input h-auto min-h-10.5 py-2.5 wrap-break-word whitespace-normal text-sm">
                        Активний
                    </div>
                </div>
            </div>

            <div class="form-row-2 mt-4">
                <div class="form-group group">
                    <span class="label">{{ __('approval.granted_to') }}</span>
                    <div class="input h-auto min-h-10.5 py-2.5 wrap-break-word whitespace-normal text-sm">
                        Співробітник Франко І.Я.
                    </div>
                </div>
                <div class="form-group group">
                    <span class="label">{{ __('approval.access_author') }}</span>
                    <div class="input h-auto min-h-10.5 py-2.5 wrap-break-word whitespace-normal text-sm">
                        Співробітник Франко І.Я.
                    </div>
                </div>
            </div>

            <div class="form-row-2 mt-4">
                <div class="form-group group">
                    <span class="label">{{ __('approval.access_expires') }}</span>
                    <div class="input h-auto min-h-10.5 py-2.5 wrap-break-word whitespace-normal text-sm flex items-center gap-2">
                        @icon('calendar', 'w-4 h-4 text-gray-400')
                        02.04.2025
                    </div>
                </div>
                <div class="form-group group">
                    <span class="label">{{ __('forms.patient') }}</span>
                    <div class="input h-auto min-h-10.5 py-2.5 wrap-break-word whitespace-normal text-sm">
                        Шевченко Т.Г. 03.02.1995 р.н.
                    </div>
                </div>
            </div>

            <div class="form-row-2 mt-4">
                <div class="form-group group">
                    <span class="label">{{ __('approval.reason') }}</span>
                    <div class="input h-auto min-h-10.5 py-2.5 wrap-break-word whitespace-normal text-sm">
                        7c3da506-804d-4550-8993-bf17f9ee0402
                    </div>
                </div>
            </div>
        </fieldset>

        <div class="mt-8">
            <a href="{{ route('data-access.index', [legalEntity()]) }}" class="button-minor inline-block">
                {{ __('forms.back') }}
            </a>
        </div>
    </div>
</section>
