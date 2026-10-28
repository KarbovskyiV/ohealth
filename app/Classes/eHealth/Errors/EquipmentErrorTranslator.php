<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Errors;

/**
 * Translates the errors of the equipment resource.
 */
final class EquipmentErrorTranslator extends BaseErrorTranslator
{
    /**
     * Messages of the equipment resource: original message => translation.
     *
     * @return array
     */
    protected function messages(): array
    {
        return [
            'equipment already has {availability_status} availability_status' => __('equipments.api_errors.availability_status_already_set'),
            'Your scope does not allow to access this resource. Missing allowances: equipment:write' => __('equipments.api_errors.equipment_write_not_allowed'),
            'User is not allowed to create devices for this division' => __('equipments.api_errors.division_not_allowed'),
            'Parent equipment not found' => __('equipments.api_errors.parent_not_found'),
            'Referenced parent equipment is not active' => __('equipments.api_errors.parent_not_active'),
            'Referenced parent equipment belongs to another legal entity' => __('equipments.api_errors.parent_of_another_legal_entity'),
            'Referenced device definition must be of the same type as equipment' => __('equipments.api_errors.device_definition_type_mismatch'),
            'Serial number is required for this type of equipment' => __('equipments.api_errors.serial_number_required'),
            'Status must be active' => __('equipments.api_errors.status_must_be_active'),
            'Availability status must be available' => __('equipments.api_errors.availability_status_must_be_available'),
            'Manufacture date must be equal to or earlier than current date' => __('equipments.api_errors.manufacture_date_in_future'),
            'One and only one key is allowed from the list: [value_integer, value_decimal, value_boolean, value_string], but the following are present: {present}' => __('equipments.api_errors.one_property_value_allowed'),
            'type mismatch. Expected {expected_type} but got {actual_type}' => __('equipments.api_errors.type_mismatch'),
            'Only value_string is allowed for dictionary values' => __('equipments.api_errors.dictionary_value_must_be_string'),
            'Inventory number must be unique' => __('equipments.api_errors.inventory_number_not_unique'),
            'At least one name must be provided' => __('equipments.api_errors.name_required'),
            'Device name type must not be duplicated' => __('equipments.api_errors.name_type_duplicated'),
            'Your scope does not allow to access this resource. Missing allowances: equipment:read' => __('equipments.api_errors.equipment_read_not_allowed'),
            'Invalid transition. Equipment is in final status' => __('equipments.api_errors.final_status_transition'),
            'It is prohibited to change status for equipment with availability status = {availability_status}' => __('equipments.api_errors.status_change_prohibited'),
            'Error reason is required' => __('equipments.api_errors.error_reason_required'),
            'Invalid status. Equipment must be active' => __('equipments.api_errors.equipment_must_be_active')
        ];
    }
}
