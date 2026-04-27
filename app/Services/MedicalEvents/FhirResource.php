<?php

declare(strict_types=1);

namespace App\Services\MedicalEvents;

class FhirResource
{
    private array $data = [];

    /**
     * Create a new instance.
     *
     * @return self
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Set the coding system and code.
     *
     * @param  string  $system
     * @param  string  $code
     * @return self
     */
    public function coding(string $system, string $code): self
    {
        $this->data = [
            'system' => $system,
            'code' => $code
        ];

        return $this;
    }

    /**
     * Build a FHIR Coding structure.
     *
     * @return array
     */
    public function toCoding(): array
    {
        return $this->data;
    }

    /**
     * Build a FHIR CodeableConcept structure.
     *
     * @param  string  $text
     * @return array
     */
    public function toCodeableConcept(string $text = ''): array
    {
        return [
            'coding' => [$this->data],
            'text' => $text
        ];
    }

    /**
     * Build a FHIR Identifier structure.
     *
     * @param  string  $uuid
     * @param  string  $text
     * @return array
     */
    public function toIdentifier(string $uuid, string $text = ''): array
    {
        return [
            'identifier' => [
                'type' => $this->toCodeableConcept($text),
                'value' => $uuid
            ]
        ];
    }
}
