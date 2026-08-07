<fieldset
    class="p-4 sm:p-8 sm:pb-10 mb-16 mt-6 border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 max-w-[1280px]"
    xmlns="http://www.w3.org/1999/html"
    x-data="{ title: '{{ __('forms.accreditation') }}', index: 5 }"
    x-init="typeof addHeader !== 'undefined' && addHeader(title, index)"
    x-show="activeStep === index || isEdit"
    x-cloak
    :key="`step-${index}`"
>
    <template x-if="isEdit">
        <legend x-text="title" class="legend"></legend>
    </template>

    <div x-data="{ showAccreditation: $wire.entangle('legalEntityForm.accreditationShow') }">
        <div class='form-row-3'>
            <div class="form-group group">
                <input
                    type="checkbox"
                    id="accreditationShow"
                    class="default-checkbox text-blue-500 focus:ring-blue-300"
                    x-model="showAccreditation"
                    :checked="showAccreditation"
                >

                <label for="accreditationShow" class="ms-2 text-sm font-medium text-gray-500 dark:text-gray-300">{{ __('forms.accreditationShow') }}</label>
            </div>
        </div>

        <div
            class='form-row-3'
            x-show="showAccreditation"
            x-data="{
                category: $wire.entangle('legalEntityForm.accreditation.category'),
                acdIssuedDate: $wire.entangle('legalEntityForm.accreditation.issuedDate'),
                acdOrderDate: $wire.entangle('legalEntityForm.accreditation.orderDate'),
                acdExpiryDate: $wire.entangle('legalEntityForm.accreditation.expiryDate'),
                clearFields() {
                    if (this.category === 'NO_ACCREDITATION') {
                        // Clear the values in the fields
                        this.acdOrderDate = '';
                        this.acdIssuedDate = '';
                        this.acdExpiryDate = '';
                    }
                }
            }"
            x-effect="if (category === 'NO_ACCREDITATION') clearFields()"
        >
            <div class="form-group group">
                <select
                    required
                    id="accreditationСategory"
                    x-model="category"
                    aria-describedby="@error('legalEntityForm.accreditation.category') accreditationCategoryErrorHelp @enderror"
                    class="input-select @error('legalEntityForm.accreditation.category') input-error border-red-500 focus:border-red-500 scroll-to-error @enderror peer"
                >
                    <option value="_placeholder_" selected hidden>-- {{ __('forms.select') }} --</option>

                    @isset($dictionaries['ACCREDITATION_CATEGORY'])
                            @foreach($dictionaries['ACCREDITATION_CATEGORY'] as $k => $category)
                                <option {{ $legalEntityForm->accreditation['category'] === $k ? 'selected' : '' }} value="{{ $k }}">
                                    {{ $category }}
                                </option>
                            @endforeach
                        @endisset
                </select>

                @error('legalEntityForm.accreditation.category')
                    <p id="accreditationCategoryErrorHelp" class="text-error">
                        {{ $message }}
                    </p>
                @enderror

                <label for="accreditationСategory" class="label z-10">
                    {{ __('forms.accreditation_category') }}
                </label>
            </div>

            <div class="form-group group">

                <input
                    required
                    type="text"
                    placeholder=" "
                    id="accreditationOrderNumber"
                    wire:model="legalEntityForm.accreditation.orderNo"
                    aria-describedby="@error('legalEntityForm.accreditation.orderNo') accreditationOrderNumberErrorHelp @enderror"
                    class="input @error('legalEntityForm.accreditation.orderNo') input-error border-red-500 focus:border-red-500 scroll-to-error @enderror peer"
                />

                @error('legalEntityForm.accreditation.orderNo')
                    <p id="accreditationOrderNumberErrorHelp" class="text-error">
                        {{ $message }}
                    </p>
                @enderror

                <label for="accreditationOrderNumber" class="label z-10">
                    {{ __('forms.accreditation_order_no') }}
                </label>
            </div>

            <div class="form-group group">
                <svg class="svg-input" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                </svg>

                <input
                    :disabled="category === 'NO_ACCREDITATION'"
                    type="text"
                    placeholder=" "
                    datepicker-format="{{ frontendDateFormat() }}"
                    id="accreditationIssuedDate"
                    x-model="acdIssuedDate"
                    aria-describedby="@error('legalEntityForm.accreditation.issuedDate') accreditationIssuedDateErrorHelp @enderror"
                    class="input datepicker-input @error('legalEntityForm.accreditation.issuedDate') input-error border-red-500 focus:border-red-500 scroll-to-error @enderror peer"
                />

                @error('legalEntityForm.accreditation.issuedDate')
                    <p id="accreditationIssuedDateErrorHelp" class="text-error">
                        {{ $message }}
                    </p>
                @enderror

                <label for="accreditationIssuedDate" class="label z-10">
                    {{ __('forms.accreditationIssuedDate') }}
                </label>
            </div>

            <div class="form-group group">
                <svg class="svg-input" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                </svg>

                <input
                    :disabled="category === 'NO_ACCREDITATION'"
                    type="text"
                    placeholder=" "
                    datepicker-format="{{ frontendDateFormat() }}"
                    id="accreditationExpiryDate"
                    x-model="acdExpiryDate"
                    aria-describedby="@error('legalEntityForm.accreditation.expiryDate') accreditationExpiryDateErrorHelp @enderror"
                    class="input datepicker-input @error('legalEntityForm.accreditation.expiryDate') input-error border-red-500 focus:border-red-500 scroll-to-error @enderror peer"
                />

                @error('legalEntityForm.accreditation.expiryDate')
                    <p id="accreditationExpiryDateErrorHelp" class="text-error">
                        {{ $message }}
                    </p>
                @enderror

                <label for="accreditationExpiryDate" class="label z-10">
                    {{ __('forms.end_date') }}
                </label>
            </div>

            <div class="form-group group">
                <svg class="svg-input" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                </svg>

                <input
                    :required="category !== 'NO_ACCREDITATION'"
                    :disabled="category === 'NO_ACCREDITATION'"
                    type="text"
                    placeholder=" "
                    datepicker-format="{{ frontendDateFormat() }}"
                    id="accreditationOrderDate"
                    x-model="acdOrderDate"
                    aria-describedby="@error('legalEntityForm.accreditation.orderDate') accreditationOrderDateErrorHelp @enderror"
                    class="input datepicker-input @error('legalEntityForm.accreditation.orderDate') input-error border-red-500 focus:border-red-500 scroll-to-error @enderror peer"
                />

                @error('legalEntityForm.accreditation.orderDate')
                    <p id="accreditationOrderDateErrorHelp" class="text-error">
                        {{ $message }}
                    </p>
                @enderror

                <label for="accreditationOrderDate" class="label z-10">
                    {{ __('forms.accreditation_order_date') }}
                </label>
            </div>
        </div>
    </div>
</fieldset>
