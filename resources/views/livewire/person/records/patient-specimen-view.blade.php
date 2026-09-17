<x-layouts.patient :personId="$personId" :prepersonId="$prepersonId" :patientFullName="$patientFullName" :hideNavigation="true" title="{{ __('specimens.specimen') }} {{ $specimenId }}" activeTab="specimens">
    <x-slot name="headerActions">
        {{-- Empty to hide the 'Нова взаємодія' button --}}
    </x-slot>
    <div class="shift-content pl-3.5 mt-8 max-w-6xl">
        <fieldset class="fieldset mb-6">
            <legend class="legend">{{ __('specimens.general_info') }}</legend>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="specimen_types" disabled />
                    <label class="label">{{ __('specimens.specimen_type') }}</label>
                </div>
                <div class="form-group group">
                    <input type="text" class="input peer" value="condition" disabled />
                    <label class="label">{{ __('specimens.specimen_condition') }}</label>
                </div>
            </div>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="{{ __('specimens.clarification') }}" disabled />
                    <label class="label">{{ __('specimens.specimen_type_clarification') }}</label>
                </div>
                <div class="form-group group">
                    <input type="text" class="input peer" value="{{ __('specimens.slight_hemolysis') }}" disabled />
                    <label class="label">{{ __('specimens.specimen_condition_clarification') }}</label>
                </div>
            </div>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="12321-21321-1231-12332" disabled />
                    <label class="label">{{ __('specimens.identifier') }}</label>
                </div>
                <div class="flex items-start w-full">
                    <div class="form-group group flex-1 min-w-0 !mb-0">
                        <div class="datepicker-wrapper w-full">
                            <input type="text" class="datepicker-input with-leading-icon input peer w-full" value="02.04.2025" disabled />
                            <label class="wrapped-label !overflow-visible !w-auto !max-w-none">{{ __('specimens.date_time_received') }}</label>
                        </div>
                    </div>
                    <div class="relative group !w-24 shrink-0 !mb-0">
                        <label class="sr-only">{{ __('specimens.time') }}</label>
                        <div class="relative flex items-center w-full">
                            @icon('mingcute-time-fill', 'absolute top-2.5 right-12 w-4 h-4 text-gray-500')
                            <input type="text" class="input peer !pl-0 !pr-1 text-right w-full" value="12:00" disabled />
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-row-2 mb-6">
                <div class="flex items-start w-full">
                    <div class="form-group group flex-1 min-w-0 !mb-0">
                        <div class="datepicker-wrapper w-full">
                            <input type="text" class="datepicker-input with-leading-icon input peer w-full" value="02.04.2025" disabled />
                            <label class="wrapped-label !overflow-visible !w-auto !max-w-none">{{ __('specimens.created_in_system') }}</label>
                        </div>
                    </div>
                    <div class="relative group !w-24 shrink-0 !mb-0">
                        <label class="sr-only">{{ __('specimens.time') }}</label>
                        <div class="relative flex items-center w-full">
                            @icon('mingcute-time-fill', 'absolute top-2.5 right-12 w-4 h-4 text-gray-500')
                            <input type="text" class="input peer !pl-0 !pr-1 text-right w-full" value="12:00" disabled />
                        </div>
                    </div>
                </div>
                <div class="flex items-start w-full">
                    <div class="form-group group flex-1 min-w-0 !mb-0">
                        <div class="datepicker-wrapper w-full">
                            <input type="text" class="datepicker-input with-leading-icon input peer w-full" value="02.04.2025" disabled />
                            <label class="wrapped-label !overflow-visible !w-auto !max-w-none">{{ __('specimens.updated_in_system') }}</label>
                        </div>
                    </div>
                    <div class="relative group !w-24 shrink-0 !mb-0">
                        <label class="sr-only">{{ __('specimens.time') }}</label>
                        <div class="relative flex items-center w-full">
                            @icon('mingcute-time-fill', 'absolute top-2.5 right-12 w-4 h-4 text-gray-500')
                            <input type="text" class="input peer !pl-0 !pr-1 text-right w-full" value="12:00" disabled />
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="{{ __('specimens.available') }}" disabled />
                    <label class="label">{{ __('specimens.status') }}</label>
                </div>
                <div class="form-group group">
                    <input type="text" class="input peer" value="specimen_invalidate_reasons" disabled />
                    <label class="label">{{ __('specimens.unavailability_reason') }}</label>
                </div>
            </div>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="request" disabled />
                    <label class="label">{{ __('specimens.electronic_referral') }}</label>
                </div>
                <div class="form-group group">
                    <input type="text" class="input peer" value="parent" disabled />
                    <label class="label">{{ __('specimens.parent_specimen') }}</label>
                </div>
            </div>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="request" disabled />
                    <label class="label">{{ __('specimens.healthcare_service_where_collected') }}</label>
                </div>
                <div class="form-group group">
                    <input type="text" class="input peer" value="Шевченко" disabled />
                    <label class="label">{{ __('specimens.employee_created_record') }}</label>
                </div>
            </div>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="1233-1234-12341-124313" disabled />
                    <label class="label">{{ __('specimens.related_encounter') }}</label>
                </div>
                <div></div>
            </div>
            <div class="mt-6">
                <div class="form-group">
                    <label class="peer appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ __('specimens.note') }}</label>
                    <textarea class="textarea mt-1 min-h-[80px]" disabled>note</textarea>
                </div>
            </div>
        </fieldset>

        <fieldset class="fieldset mb-6">
            <legend class="legend">{{ __('specimens.collection_details') }}</legend>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="collector" disabled />
                    <label class="label">{{ __('specimens.collector') }}</label>
                </div>
                <div class="form-group group">
                    <div class="datepicker-wrapper">
                        <input type="text" class="datepicker-input with-leading-icon input peer" value="02.04.2025 - 04.06.2025" disabled />
                        <label class="wrapped-label">{{ __('specimens.collection_date_time_or_period') }}</label>
                    </div>
                </div>
            </div>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="specimen_collection_methods" disabled />
                    <label class="label">{{ __('specimens.collection_method') }}</label>
                </div>
                <div class="form-group group flex items-start gap-4">
                    <div class="flex-1 relative">
                        <input type="text" class="input peer" value="5" disabled />
                        <label class="label">{{ __('specimens.collection_duration') }}</label>
                    </div>
                    <div class="w-32">
                        <input type="text" class="input peer" value="{{ __('specimens.minutes') }}" disabled />
                    </div>
                </div>
            </div>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="body_site" disabled />
                    <label class="label">{{ __('specimens.body_site') }}</label>
                </div>
                <div class="form-group group flex items-start gap-4">
                    <div class="flex-1 relative">
                        <input type="text" class="input peer" value="5" disabled />
                        <label class="label">{{ __('specimens.material_amount') }}</label>
                    </div>
                    <div class="w-32">
                        <input type="text" class="input peer" value="{{ __('specimens.ml') }}" disabled />
                    </div>
                </div>
            </div>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="collection.procedure" disabled />
                    <label class="label">{{ __('specimens.procedure_during_collection') }}</label>
                </div>
                <div class="form-group group">
                    <input type="text" class="input peer" value="fasting_statuses" disabled />
                    <label class="label">{{ __('specimens.fasting_status') }}</label>
                </div>
            </div>
        </fieldset>

        <fieldset class="fieldset mb-6">
            <legend class="legend">{{ __('specimens.container') }}</legend>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="identifier" disabled />
                    <label class="label">{{ __('specimens.identifier') }}</label>
                </div>
                <div class="form-group group">
                    <input type="text" class="input peer" value="container.type.coding" disabled />
                    <label class="label">{{ __('specimens.container_type') }}</label>
                </div>
            </div>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="container.description" disabled />
                    <label class="label">{{ __('specimens.container_description') }}</label>
                </div>
                <div class="form-group group">
                    <input type="text" class="input peer" value="{{ __('specimens.clarification') }}" disabled />
                    <label class="label">{{ __('specimens.container_type_clarification') }}</label>
                </div>
            </div>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="container.additive_codeable_concept.coding" disabled />
                    <label class="label">{{ __('specimens.additive') }}</label>
                </div>
                <div class="form-group group flex items-start gap-4">
                    <div class="flex-1 relative">
                        <input type="text" class="input peer" value="5" disabled />
                        <label class="label">{{ __('specimens.container_volume') }}</label>
                    </div>
                    <div class="w-32">
                        <input type="text" class="input peer" value="{{ __('specimens.minutes') }}" disabled />
                    </div>
                </div>
            </div>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="{{ __('specimens.clarification') }}" disabled />
                    <label class="label">{{ __('specimens.additive_clarification') }}</label>
                </div>
                <div class="form-group group flex items-start gap-4">
                    <div class="flex-1 relative">
                        <input type="text" class="input peer" value="5" disabled />
                        <label class="label">{{ __('specimens.biomaterial_amount_in_container') }}</label>
                    </div>
                    <div class="w-32">
                        <input type="text" class="input peer" value="{{ __('specimens.ml') }}" disabled />
                    </div>
                </div>
            </div>
        </fieldset>

        <fieldset class="fieldset mb-6">
            <legend class="legend">{{ __('specimens.patient_info') }}</legend>
            <div class="form-row-2 mb-6">
                <div class="form-group group">
                    <input type="text" class="input peer" value="{{ __('specimens.female') }}" disabled />
                    <label class="label">{{ __('specimens.patient_gender') }}</label>
                </div>
                <div class="form-group group">
                    <input type="text" class="input peer" value="18" disabled />
                    <label class="label">{{ __('specimens.patient_age') }}</label>
                </div>
            </div>
        </fieldset>

        <div class="mt-8 mb-8 flex justify-start">
            <a href="{{ $personId ? route('persons.specimens', ['legalEntity' => request()->route('legalEntity'), 'person' => $personId]) : route('prepersons.specimens', ['legalEntity' => request()->route('legalEntity'), 'preperson' => $prepersonId]) }}" class="button-outline px-6 py-2.5 rounded-lg border border-gray-300">{{ __('forms.back') }}</a>
        </div>
    </div>
</x-layouts.patient>

