<?php

declare(strict_types=1);

namespace App\Livewire\Person\Traits;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Enums\Person\AuthenticationMethod;
use App\Enums\Person\AuthenticationMethodAction;
use App\Enums\Person\AuthStep;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthException;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\Person\Person;
use App\Models\Relations\AuthenticationMethod as AuthenticationMethodModel;
use App\Repositories\Repository;
use App\Rules\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

trait InteractsWithAuthenticationMethods
{
    public function syncAuthMethods(): void
    {
        try {
            $response = EHealth::person()->getAuthMethods($this->uuid);
            $authenticationMethods = $response->validate();
            $person = Person::whereUuid($this->uuid)->firstOrFail();

            try {
                Repository::authenticationMethod()->sync($person, $authenticationMethods);

                $this->authenticationMethods = Arr::toCamelCase($authenticationMethods);
                Session::flash('success', __('patients.messages.auth_methods_synced'));
            } catch (Throwable $exception) {
                $this->handleDatabaseErrors($exception, 'Failed to update authentication methods');
            }
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when getting auth methods');
        }
    }

    public function openAuthMethodModal(): void
    {
        $this->showAuthMethodModal = true;
        $this->authStep = AuthStep::INITIAL;
    }

    public function selectAuthMethod(string $uuid, string $type, AuthStep $step): void
    {
        $this->selectedAuthMethodUuid = $uuid;
        $this->selectedAuthMethodType = $type;
        $this->authStep = $step;
    }

    public function createOtpAuthMethod(): void
    {
        try {
            Validator::make([
                'action' => AuthenticationMethodAction::INSERT->value,
                'authenticationMethod' => [
                    'type' => AuthenticationMethod::OTP->value,
                    'phoneNumber' => $this->newPhoneNumber
                ]
            ], $this->rulesForInsert())->validate();
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->form->phoneNumber = $this->newPhoneNumber;
        $this->changePhoneNumber($this->newPhoneNumber);
    }

    /**
     * Get validation rules for creating an authentication method.
     *
     * @return array
     */
    protected function rulesForInsert(): array
    {
        return [
            'action' => ['required', 'string', 'in:' . AuthenticationMethodAction::INSERT->value],
            'authenticationMethod' => ['required', 'array'],
            'authenticationMethod.type' => [
                'required',
                'string',
                function (string $attribute, mixed $value, callable $fail): void {
                    $currentTypes = collect($this->authenticationMethods)->pluck('type');

                    $person = Person::whereUuid($this->uuid)->firstOrFail();

                    if ($value !== AuthenticationMethod::THIRD_PERSON->value && $person->confidantPersons()->exists()) {
                        $fail(__('patients.errors.authMethod.only_third_person_for_person_with_confidants'));

                        return;
                    }

                    if ($value === AuthenticationMethod::OFFLINE->value
                        && $currentTypes->contains(AuthenticationMethod::OFFLINE->value)) {
                        $fail(__('patients.errors.person_already_has_offline_auth_method'));

                        return;
                    }

                    if ($value === AuthenticationMethod::OFFLINE->value
                        && $currentTypes->contains(AuthenticationMethod::OTP->value)) {
                        $fail(__('patients.errors.cannot_set_offline_auth_method_if_person_has_otp'));

                        return;
                    }

                    if ($person->age <= config('ehealth.no_self_auth_age') && in_array($value, [
                            AuthenticationMethod::OTP->value,
                            AuthenticationMethod::OFFLINE->value
                        ], true)) {
                        $fail(__('patients.errors.cannot_have_self_auth_method'));
                    }
                }
            ],
            'authenticationMethod.phoneNumber' => [
                'required_if:authenticationMethod.type,' . AuthenticationMethod::OTP->value,
                'prohibited_if:authenticationMethod.type,' . AuthenticationMethod::OFFLINE->value,
                new PhoneNumber()
            ],
            'authenticationMethod.value' => [
                'required_if:authenticationMethod.type,' . AuthenticationMethod::THIRD_PERSON->value,
                'prohibited_if:authenticationMethod.type,' . AuthenticationMethod::OTP->value . ',' . AuthenticationMethod::OFFLINE->value,
                'uuid'
            ],
            'authenticationMethod.alias' => [
                'nullable',
                'required_if:authenticationMethod.type,' . AuthenticationMethod::THIRD_PERSON->value,
                'string',
                'max:255'
            ]
        ];
    }

    public function createOfflineAuthMethod(): void
    {
        try {
            Validator::make([
                'action' => AuthenticationMethodAction::INSERT->value,
                'authenticationMethod' => ['type' => AuthenticationMethod::OFFLINE->value]
            ], $this->rulesForInsert())->validate();
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        try {
            $response = EHealth::person()->insertAuthMethod($this->uuid, AuthenticationMethod::OFFLINE);

            $this->requestId = $response->validate()['id'];
            $this->uploadedDocuments = $response->validate()['documents'];
            $this->authStep = AuthStep::CHANGE_FROM_OFFLINE;
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when creating auth method request');
        }
    }

    /**
     * Approve creating an OFFLINE authentication method.
     */
    public function approveCreatingOffline(): void
    {
        try {
            $this->uploadDocuments();
            $response = EHealth::person()->approveAuthMethod($this->uuid, $this->requestId);

            try {
                Person::whereUuid($this->uuid)->firstOrFail()
                    ->authenticationMethods()
                    ->create($response->validate());
            } catch (Throwable $exception) {
                $this->handleDatabaseErrors($exception, 'Failed to create authentication method');

                return;
            }

            $this->showAuthMethodModal = false;
            Session::flash('success', __('patients.messages.offline_auth_method_added'));
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when approving offline auth method');
        }
    }

    public function verifyOwnership(): void
    {
        try {
            $validated = $this->validate(['form.phoneNumber' => ['required', new PhoneNumber()]]);
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        try {
            $response = EHealth::verification()->findByPhoneNumber($validated['form']['phoneNumber']);

            if ($response->validate()['phone_number'] === $validated['form']['phoneNumber']) {
                $this->changePhoneNumber($response->validate()['phone_number']);

                return;
            }
        } catch (EHealthConnectionException $exception) {
            $exception->handle('Error when finding for OTP verification');

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            if ($exception->getCode() === 404) {
                try {
                    EHealth::verification()->initialize(['phone_number' => $validated['form']['phoneNumber']]);
                    $this->authStep = AuthStep::VERIFY_PHONE;
                } catch (EHealthException|EHealthConnectionException $exception) {
                    $exception->handle('Error when initialize OTP verification request');

                    return;
                }
            }
        }
    }

    public function completeVerifyingOwnership(): void
    {
        try {
            $validated = $this->validate(['code' => ['required', 'integer']]);
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        try {
            EHealth::verification()->complete($this->form->phoneNumber, $validated);
            $this->authStep = AuthStep::COMPLETE_VERIFICATION;
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when complete OTP verification request');

            return;
        }
    }

    public function updatePhoneNumber(): void
    {
        $this->changePhoneNumber($this->newPhoneNumber);
    }

    protected function changePhoneNumber(string $phoneNumber): void
    {
        $validated = Validator::make(
            ['newPhoneNumber' => $phoneNumber],
            ['newPhoneNumber' => 'required', new PhoneNumber()]
        )->validate();

        try {
            $response = EHealth::person()->insertAuthMethod(
                $this->uuid,
                AuthenticationMethod::OTP,
                $validated['newPhoneNumber']
            );
            $this->requestId = $response->validate()['id'];
            $urgent = $response->getUrgent();
            $this->uploadedDocuments = $urgent['documents'] ?? [];

            if (data_get($urgent, 'authentication_method_current.type') === AuthenticationMethod::OFFLINE->value) {
                $this->authStep = AuthStep::CHANGE_FROM_OFFLINE;
            } else {
                $this->authStep = AuthStep::CHANGE_PHONE;
            }
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when creating auth method request');
        }
    }

    /**
     * Approve phone number with a verification code.
     */
    public function approveUpdatingPhoneNumber(): void
    {
        $validated = $this->validate(['verificationCode' => ['required', 'digits:4']]);

        try {
            EHealth::person()->approveAuthMethod($this->uuid, $this->requestId, Arr::toSnakeCase($validated));

            try {
                Person::whereUuid($this->uuid)->firstOrFail()
                    ->authenticationMethods()
                    ->whereType(AuthenticationMethod::OTP)
                    ->update(['phone_number' => $this->form->phoneNumber]);

                Session::flash('success', __('patients.messages.phone_number_changed'));
            } catch (Throwable $exception) {
                $this->handleDatabaseErrors($exception, 'Failed to update authentication method phone number');

                return;
            }

            $this->showAuthMethodModal = false;
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when approving changing auth phone number');
        }
    }

    /**
     * Approve changing an authentication method from OFFLINE to OTP.
     */
    public function approveChangingType(): void
    {
        try {
            $this->uploadDocuments();
            $response = EHealth::person()->approveAuthMethod($this->uuid, $this->requestId);

            try {
                Person::whereUuid($this->uuid)->firstOrFail()
                    ->authenticationMethods()
                    ->whereType(AuthenticationMethod::OFFLINE)
                    ->update(['uuid' => $response->validate()['id'], 'type' => AuthenticationMethod::OTP]);
            } catch (Throwable $exception) {
                $this->handleDatabaseErrors($exception, 'Failed to update authentication method type');

                return;
            }

            $this->showAuthMethodModal = false;
            Session::flash('success', __('patients.messages.auth_method_changed_offline_to_sms'));
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when approving auth method (from OFFLINE to OTP)');
        }
    }

    /**
     * Start an authentication method alias update.
     */
    public function updateAliasName(): void
    {
        try {
            $validated = Validator::make([
                'action' => AuthenticationMethodAction::UPDATE->value,
                'authenticationMethod' => [
                    'uuid' => $this->selectedAuthMethodUuid,
                    'alias' => $this->alias
                ]
            ], $this->rulesForUpdate())->validate();
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        try {
            $response = EHealth::person()->updateAuthMethod(
                $this->uuid,
                $validated['authenticationMethod']['uuid'],
                $validated['authenticationMethod']['alias']
            );

            $this->requestId = $response->validate()['id'];
            $this->alias = $validated['authenticationMethod']['alias'];

            try {
                AuthenticationMethodModel::whereUuid($validated['authenticationMethod']['uuid'])
                    ->update(['alias' => $validated['authenticationMethod']['alias']]);
            } catch (Throwable $exception) {
                $this->handleDatabaseErrors($exception, 'Failed to update authentication method type');

                return;
            }

            $this->authStep = AuthStep::UPDATE_ALIAS;
            Session::flash('success', __('patients.messages.method_name_updated'));
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when updating alias auth method');
        }
    }

    /**
     * Get validation rules for updating an authentication method.
     *
     * @return array
     */
    protected function rulesForUpdate(): array
    {
        return [
            'action' => ['required', 'string', 'in:UPDATE'],
            'authenticationMethod' => ['required', 'array'],
            'authenticationMethod.uuid' => ['required', 'uuid'],
            'authenticationMethod.alias' => ['required', 'string', 'max:255']
        ];
    }

    /**
     * Approve an authentication method alias update.
     */
    public function approveUpdatingAlias(): void
    {
        try {
            if ($this->selectedAuthMethodType === AuthenticationMethod::OFFLINE->value) {
                $this->uploadDocuments();
                EHealth::person()->approveAuthMethod($this->uuid, $this->requestId);
            } else {
                $validated = $this->validate(['verificationCode' => ['required', 'digits:4']]);
                EHealth::person()->approveAuthMethod($this->uuid, $this->requestId, Arr::toSnakeCase($validated));
            }

            try {
                AuthenticationMethodModel::whereUuid($this->selectedAuthMethodUuid)->update(['alias' => $this->alias]);
            } catch (Throwable $exception) {
                $this->handleDatabaseErrors($exception, 'Failed to update authentication method alias');

                return;
            }

            $this->showAuthMethodModal = false;
            Session::flash('success', __('patients.messages.auth_method_name_changed'));
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when approving auth method request');
        }
    }

    public function deactivateAuthMethod(?string $authMethodUuid): void
    {
        if (!$authMethodUuid) {
            Session::flash('error', __('patients.messages.sync_auth_methods_and_try_again'));

            return;
        }

        try {
            $validated = Validator::make([
                'action' => AuthenticationMethodAction::DEACTIVATE->value,
                'authenticationMethod' => ['uuid' => $authMethodUuid]
            ], $this->rulesForDeactivate())->validate();
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->selectedAuthMethodUuid = $validated['authenticationMethod']['uuid'];

        try {
            $response = EHealth::person()->deactivateAuthMethod($this->uuid, $this->selectedAuthMethodUuid);
            $this->requestId = $response->validate()['id'];
            $this->authStep = AuthStep::APPROVE_DEACTIVATING_METHOD;
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when deactivating auth method');
        }
    }

    /**
     * Get validation rules for deactivating an authentication method.
     *
     * @return array
     */
    protected function rulesForDeactivate(): array
    {
        return [
            'action' => ['required', 'string', 'in:DEACTIVATE'],
            'authenticationMethod' => ['required', 'array'],
            'authenticationMethod.uuid' => [
                'required',
                'uuid',
                function (string $attribute, mixed $value, callable $fail): void {
                    $authenticationMethod = collect($this->authenticationMethods)->firstWhere('uuid', $value);

                    if ($authenticationMethod['type'] !== AuthenticationMethod::THIRD_PERSON->value) {
                        $fail(__('patients.errors.authMethod.only_third_person_can_be_deactivated'));

                        return;
                    }

                    if (count($this->authenticationMethods) <= 1) {
                        $fail(__('patients.errors.authMethod.cannot_deactivate_last'));

                        return;
                    }

                    if (!CarbonImmutable::parse($authenticationMethod['ehealthEndedAt'])->isFuture()) {
                        $fail(__('patients.errors.authMethod.cannot_deactivate_inactive'));
                    }
                }
            ]
        ];
    }

    /**
     * Approve an authentication method deactivation.
     */
    public function approveDeactivatingAuthMethod(): void
    {
        try {
            $validated = $this->form->validate($this->form->rulesForApprove());
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        try {
            EHealth::person()->approveAuthMethod($this->uuid, $this->requestId, Arr::toSnakeCase($validated));

            try {
                AuthenticationMethodModel::whereUuid($this->selectedAuthMethodUuid)->delete();

                $this->showAuthMethodModal = false;
                Session::flash('success', __('patients.messages.auth_method_deactivated'));
            } catch (Throwable $exception) {
                $this->handleDatabaseErrors($exception, 'Error when approving deactivate auth method');

                return;
            }
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when approve deactivating auth method');
        }
    }

    /**
     * Approve adding a THIRD_PERSON authentication method.
     */
    public function approveAddingNewMethod(): void
    {
        $validated = $this->validate(['verificationCode' => ['required', 'digits:4']]);

        try {
            EHealth::person()->approveAuthMethod($this->uuid, $this->requestId, Arr::toSnakeCase($validated));

            $forCreate = [
                'type' => AuthenticationMethod::THIRD_PERSON,
                'value' => $this->confidantPersonId,
                'alias' => $this->alias
            ];

            try {
                Person::whereUuid($this->uuid)->firstOrFail()
                    ->authenticationMethods()
                    ->create($forCreate);

                Session::flash('success', __('patients.messages.new_auth_method_added'));
            } catch (Throwable $exception) {
                $this->handleDatabaseErrors($exception, 'Failed to update authentication method phone number');

                return;
            }

            $this->showAuthMethodModal = false;
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when approving changing auth phone number');
        }
    }

    public function chooseConfidantFromRelation(string $confidantPersonId): void
    {
        $this->confidantPersonId = $confidantPersonId;
        $this->authStep = AuthStep::ADD_ALIAS_FOR_THIRD_PERSON;
    }

    public function addAuthMethodFromRelation(string $alias): void
    {
        $this->alias = $alias;

        try {
            $response = EHealth::person()->insertAuthMethod(
                $this->uuid,
                AuthenticationMethod::THIRD_PERSON,
                value: $this->confidantPersonId,
                alias: $alias
            );
            $this->requestId = $response->validate()['id'];
            $this->authStep = AuthStep::APPROVE_ADDING_NEW_METHOD;
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when adding auth method from relation');
        }
    }

    public function resendCode(): void
    {
        try {
            EHealth::person()->resendAuthOtp($this->uuid, $this->requestId);
            Session::flash('success', __('patients.messages.code_resent_to_phone'));
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when resending SMS');
        }
    }
}
