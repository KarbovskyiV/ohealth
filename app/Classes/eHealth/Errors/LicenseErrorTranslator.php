<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Errors;

/**
 * Translates the errors of the license resource.
 */
final class LicenseErrorTranslator extends BaseErrorTranslator
{
    /**
     * Messages of the license resource: original message => translation.
     *
     * @return array
     */
    protected function messages(): array
    {
        return [
            'Your scope does not allow to access this resource. Missing allowances: license:write' => __('licenses.api_errors.write_not_allowed'),
            'Your scope does not allow to access this resource. Missing allowances: license:read' => __('licenses.api_errors.read_not_allowed'),
            'Legal entity must be in active or suspended status' => __('licenses.api_errors.invalid_legal_entity_status'),
            'Only additional license can be created' => __('licenses.api_errors.only_additional_can_be_created'),
            'Only additional license can be updated' => __('licenses.api_errors.only_additional_can_be_updated'),
            'No active primary license found for legal entity' => __('licenses.api_errors.active_primary_license_not_found'),
            'License with type {type} is already present' => __('licenses.api_errors.type_already_present'),
            'Legal entity type and license type mismatch' => __('licenses.api_errors.legal_entity_type_mismatch'),
            'License is expired' => __('licenses.api_errors.expired'),
            'License can not be issued later than active from date' => __('licenses.api_errors.issued_after_active_from'),
            'License can not have active from date later than expiration date' => __('licenses.api_errors.active_from_after_expiry'),
            'License was not found' => __('licenses.api_errors.not_found'),
            "License doesn't correspond to your legal entity" => __('licenses.api_errors.of_another_legal_entity'),
            'Additional license can not be changed to primary' => __('licenses.api_errors.cannot_change_to_primary'),
            'License type can not be updated' => __('licenses.api_errors.type_cannot_be_updated')
        ];
    }
}
