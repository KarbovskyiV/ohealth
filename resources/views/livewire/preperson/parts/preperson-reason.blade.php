@use(App\Enums\Preperson\Reason)

<fieldset class="fieldset">
    <legend class="legend">{{ __('preperson.reason') }}</legend>

    <div class="mb-6 flex flex-col gap-3 rounded-xl border border-red-100 bg-red-50 p-6 dark:border-red-900/30 dark:bg-red-950/20">
        <div class="flex items-center gap-2">
            @icon('alert-circle', 'w-5 h-5 text-red-600 dark:text-red-400')
            <h4 class="text-lg font-bold text-red-600 dark:text-red-400">{{ __('preperson.warning_title') }}</h4>
        </div>
        <div class="text-sm leading-relaxed whitespace-pre-line text-red-500 dark:text-red-300">
            {{ __('preperson.warning_text') }}
        </div>
    </div>

    <div class="form-row-2">
        <div class="form-group">
            <select
                x-model="reason"
                name="reason"
                id="reason"
                class="input-select peer @error('form.reasonContext.reason') input-error @enderror"
                required
            >
                <option value="" selected>{{ __('forms.select') }}</option>
                @foreach (Reason::options() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <label for="reason" class="label"> {{ __('preperson.reason') }} </label>

            @error('form.reasonContext.reason')
                <p class="text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- EMERGENCY_HOSPITALIZATION -->
    <div class="form-row-2" x-show="reason === 'EMERGENCY_HOSPITALIZATION'" x-cloak>
        <div class="form-group group">
            <div class="relative w-full">
                <input
                    wire:model="form.reasonContext.ambulanceCardNumber"
                    type="text"
                    name="ambulanceCardNumber"
                    id="ambulanceCardNumber"
                    class="input peer @error('form.reasonContext.ambulanceCardNumber') input-error @enderror"
                    placeholder=" "
                    autocomplete="off"
                />
                <label for="ambulanceCardNumber" class="label"> {{ __('preperson.ambulance_card_number') }} </label>
                <button
                    type="button"
                    class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600"
                    x-show="$wire.form.reasonContext.ambulanceCardNumber"
                    @click="$wire.set('form.reasonContext.ambulanceCardNumber', '')"
                >
                    @icon('close', 'w-4 h-4')
                </button>
            </div>

            @error('form.reasonContext.ambulanceCardNumber')
                <p class="text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- POLICE_HOSPITALIZATION -->
    <div class="form-row-2" x-show="reason === 'POLICE_HOSPITALIZATION'" x-cloak>
        <div class="form-group group">
            <div class="relative w-full">
                <input
                    wire:model="form.reasonContext.policeReportId"
                    type="text"
                    name="policeReportId"
                    id="policeReportId"
                    class="input peer @error('form.reasonContext.policeReportId') input-error @enderror"
                    placeholder=" "
                    autocomplete="off"
                />
                <label for="policeReportId" class="label"> {{ __('preperson.police_report_id') }} </label>
                <button
                    type="button"
                    class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600"
                    x-show="$wire.form.reasonContext.policeReportId"
                    @click="$wire.set('form.reasonContext.policeReportId', '')"
                >
                    @icon('close', 'w-4 h-4')
                </button>
            </div>

            @error('form.reasonContext.policeReportId')
                <p class="text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group group">
            <div class="datepicker-wrapper">
                <input
                    wire:model="form.reasonContext.policeReportDate"
                    datepicker-max-date="{{ now()->format(config('app.date_format')) }}"
                    type="text"
                    name="policeReportDate"
                    id="policeReportDate"
                    class="datepicker-input with-leading-icon input peer @error('form.reasonContext.policeReportDate') input-error @enderror"
                    placeholder=" "
                    autocomplete="off"
                />
                <label for="policeReportDate" class="wrapped-label"> {{ __('preperson.police_report_date') }} </label>
            </div>

            @error('form.reasonContext.policeReportDate')
                <p class="text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- NEWBORN_WITHOUT_CERTIFICATE -->
    <div class="form-row-2" x-show="reason === 'NEWBORN_WITHOUT_CERTIFICATE'" x-cloak>
        <div class="form-group w-full">
            <label for="childBirthTime" class="label">
                <span>{{ __('preperson.child_birth_time') }}</span>
            </label>
            <div class="relative w-full">
                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center">
                    @icon('clock', 'w-5 h-5 text-gray-500 dark:text-gray-400')
                </div>
                <input
                    type="text"
                    wire:model="form.reasonContext.childBirthTime"
                    name="childBirthTime"
                    id="childBirthTime"
                    class="input timepicker-uk text-gray-900 dark:text-white border-t-0 border-r-0 border-l-0 border-b border-gray-300 focus:ring-0 px-0 ps-8 @error('form.reasonContext.childBirthTime') input-error @enderror"
                    placeholder="00:00"
                    autocomplete="off"
                    :required="reason === 'NEWBORN_WITHOUT_CERTIFICATE'"
                />
            </div>

            @error('form.reasonContext.childBirthTime')
                <p class="text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- OTHER_HOSPITALIZATION -->
    <div class="form-row-2" x-show="reason === 'OTHER_HOSPITALIZATION'" x-cloak>
        <div class="form-group group">
            <label for="otherReason" class="label-secondary"> {{ __('preperson.other_reason') }} * </label>
            <textarea
                wire:model="form.reasonContext.otherReason"
                id="otherReason"
                name="otherReason"
                rows="4"
                class="textarea @error('form.reasonContext.otherReason') input-error @enderror"
                placeholder="Текст для введення"
                autocomplete="off"
                :required="reason === 'OTHER_HOSPITALIZATION'"
            ></textarea>

            @error('form.reasonContext.otherReason')
                <p class="text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>
</fieldset>
