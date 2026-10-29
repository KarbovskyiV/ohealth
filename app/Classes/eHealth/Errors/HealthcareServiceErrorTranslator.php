<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Errors;

/**
 * Translates the errors of the healthcare service resource.
 */
final class HealthcareServiceErrorTranslator extends BaseErrorTranslator
{
    /**
     * Messages of the healthcare service resource: original message => translation.
     *
     * @return array
     */
    protected function messages(): array
    {
        return [
            'Your scope does not allow to access this resource. Missing allowances: healthcare_service:write' => __('healthcare-services.api_errors.write_not_allowed'),
            'Your scope does not allow to access this resource. Missing allowances: healthcare_service:read' => __('healthcare-services.api_errors.read_not_allowed'),
            'Invalid legal entity status' => __('healthcare-services.api_errors.invalid_legal_entity_status'),
            '{legal_entity_type} is not allowed to create healthcare services' => __('healthcare-services.api_errors.legal_entity_type_not_allowed'),
            'Division should be active' => __('healthcare-services.api_errors.division_must_be_active'),
            'Division must be active' => __('healthcare-services.api_errors.division_must_be_active'),
            'Division should belong to your legal entity' => __('healthcare-services.api_errors.division_of_another_legal_entity'),
            'Healthcare service category is not allowed for legal entity type' => __('healthcare-services.api_errors.category_not_allowed_for_legal_entity_type'),
            'Healthcare service category must have linked license' => __('healthcare-services.api_errors.category_license_required'),
            'License must not be submitted for healthcare service category' => __('healthcare-services.api_errors.license_not_allowed_for_category'),
            'License for legal entity does not exist' => __('healthcare-services.api_errors.license_not_found'),
            'License type does not match healthcare service category' => __('healthcare-services.api_errors.license_type_mismatch'),
            'division_id, category, speciality_type and providing_condition combination should be unique' => __('healthcare-services.api_errors.speciality_and_providing_condition_not_unique'),
            'division_id, category and type combination should be unique' => __('healthcare-services.api_errors.category_and_type_not_unique'),
            'Should not be present when all_day = true' => __('healthcare-services.api_errors.time_not_allowed_for_all_day'),
            'Should be present when all_day = false' => __('healthcare-services.api_errors.time_required_unless_all_day'),
            'healthcare_service.status healthcare service cannot be updated' => __('healthcare-services.api_errors.status_cannot_be_updated'),
            'healthcare_service.status healthcare service cannot be DEACTIVATED' => __('healthcare-services.api_errors.status_cannot_be_deactivated'),
            'healthcare_service.status healthcare service cannot be ACTIVATED' => __('healthcare-services.api_errors.status_cannot_be_activated')
        ];
    }
}
