<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Errors;

/**
 * Translates the errors of the merge request resource.
 */
final class MergeRequestErrorTranslator extends BaseErrorTranslator
{
    /**
     * Messages of the merge request resource: original message => translation.
     *
     * @return array
     */
    protected function messages(): array
    {
        return [
            'Person not found' => __('patients.api_errors.person_not_found'),
            'Person is not active' => __('preperson.api_errors.person_not_active'),
            'Preperson not found' => __('preperson.api_errors.preperson_not_found'),
            'Preperson is not active' => __('preperson.api_errors.preperson_not_active'),
            'Preperson has no episodes' => __('preperson.api_errors.preperson_has_no_episodes'),
            'Person has no auth methods' => __('preperson.api_errors.person_has_no_auth_methods'),
            'Only author of merge request is allowed to approve it' => __('preperson.api_errors.only_author_can_approve'),
            'Your scope does not allow to access this resource. Missing allowances: merge_request:write' => __('preperson.api_errors.merge_request_write_not_allowed'),
            'Cannot be confirmed by method with not approved confidant person relationship' => __('patients.api_errors.confidant_relationship_not_approved'),
            "User doesn't belong to legal entity where the merge request was created" => __('preperson.api_errors.user_of_another_legal_entity'),
            'Incorrect status to approve merge request' => __('preperson.api_errors.incorrect_status_to_approve')
        ];
    }
}
