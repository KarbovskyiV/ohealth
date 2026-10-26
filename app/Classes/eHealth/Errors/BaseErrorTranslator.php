<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Errors;

use App\Contracts\ApiErrorTranslator;

/**
 * Translates the errors every API resource can return. A resource with its own errors extends this class and lists
 * them in messages(), they are checked before the base ones.
 */
class BaseErrorTranslator implements ApiErrorTranslator
{
    /**
     * Get the Ukrainian text of an error message the API returned, or null when the message is unknown.
     *
     * @param  string  $message
     * @return string|null
     */
    public function translate(string $message): ?string
    {
        $message = trim($message);

        foreach ($this->messages() + $this->baseMessages() as $original => $translation) {
            if (preg_match($this->pattern($original), $message, $matches) === 1) {
                $placeholders = array_filter($matches, is_string(...), ARRAY_FILTER_USE_KEY);

                return str_replace(
                    array_map(static fn (string $name): string => ":$name", array_keys($placeholders)),
                    $placeholders,
                    $translation
                );
            }
        }

        return null;
    }

    /**
     * Messages of the resource: original message => translation. A {name} in the message matches any text and
     * replaces :name in the translation.
     *
     * @return array
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Messages every resource can return: original message => translation.
     *
     * @return array
     */
    private function baseMessages(): array
    {
        return [
            'Invalid access token' => __('errors.ehealth.messages.invalid_access_token'),
            'Access token validation failed' => __('errors.ehealth.messages.access_token_validation_failed'),
            'Invalid scope(s)' => __('errors.ehealth.messages.access_denied'),
            'Forbidden' => __('errors.ehealth.messages.access_denied'),
            'Access denied. Party is not verified' => __('errors.ehealth.messages.party_not_verified'),
            'Access denied. Party is deceased' => __('errors.ehealth.messages.party_deceased'),
            'Legal entity not found' => __('errors.ehealth.messages.legal_entity_not_found'),
            'client_id refers to legal entity that is not active' => __('errors.ehealth.messages.legal_entity_not_active'),
            'Division not found' => __('errors.ehealth.messages.division_not_found'),
            'Division is not active' => __('errors.ehealth.messages.division_not_active'),
            'value is not allowed in enum' => __('errors.ehealth.messages.value_not_allowed_in_enum'),
            'Request validation fails' => __('errors.ehealth.messages.validation_error'),
            'Employee not found' => __('errors.ehealth.messages.employee_not_found'),
            "Employee doesn't match with user" => __('errors.ehealth.messages.employee_user_mismatch'),
            'Employee is not active' => __('errors.ehealth.messages.employee_not_active'),
            'Employee does not belong to legal entity from token' => __('errors.ehealth.messages.employee_of_another_legal_entity'),
            'Employee is not verified' => __('errors.ehealth.messages.employee_not_verified'),
            'Device definition not found' => __('errors.ehealth.messages.device_definition_not_found')
        ];
    }

    /**
     * Turn the original message into a regular expression that captures each {name} under that name, e.g.
     * 'has {status} status' becomes '/^has (?<status>.+?) status\.?$/iu'. Letter case and a trailing period are ignored.
     *
     * @param  string  $original
     * @return string
     */
    private function pattern(string $original): string
    {
        $escaped = preg_quote($original, '/');
        $withPlaceholders = str_replace(['\{', '\}'], ['(?<', '>.+?)'], $escaped);

        return '/^' . $withPlaceholders . '\.?$/iu';
    }
}
