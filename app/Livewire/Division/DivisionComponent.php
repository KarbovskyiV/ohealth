<?php

declare(strict_types=1);

namespace App\Livewire\Division;

use Arr;
use Livewire\Component;
use App\Models\Division;
use App\Traits\FormTrait;
use App\Repositories\Repository;
use App\Traits\WorkTimeUtilities;
use App\Livewire\Division\Forms\DivisionForm;
use App\Classes\eHealth\Api\Division as DivisionApi;
use App\Dto\Division\Model as DivisionData;
use App\Dto\Division\Form as DivisionFormData;
use App\Models\Relations\Address;
use App\Traits\Addresses\AddressSearch;
use App\Traits\Addresses\ReceptionAddressSearch;
use Symfony\Component\ObjectMapper\ObjectMapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class DivisionComponent extends Component
{
    use FormTrait;
    use WorkTimeUtilities;
    use AddressSearch;
    use ReceptionAddressSearch;

    /**
     * The form model instance for handling division data.
     *
     * @var DivisionForm
     */
    public DivisionForm $divisionForm;

    public function setDivisionData(Division $division): void
    {
        $data = new ObjectMapper(propertyAccessor: PropertyAccess::createPropertyAccessor())
            ->map($division, DivisionFormData::class);

        $this->divisionForm->setDivision($data->toArray());
        $this->address = $data->address(Address::DEFAULT_TYPE);
        $this->receptionAddress = $data->address(Address::RECEPTION_TYPE);
        $this->divisionForm->showReceptionAddress = $data->hasReceptionAddress();
    }

    /**
     * Array containing dictionary names only used within the component.
     *
     * @var array
     */
    public array $dictionaryNames = [
        'DIVISION_TYPE',
        'SETTLEMENT_TYPE',
        'PHONE_TYPE'
    ];

    /**
     * Handles data type conversion for location coordinates after component hydration.
     *
     * This method is automatically called by Livewire after the component receives data
     * from the browser but before the data is applied to the component's properties.
     * It ensures that latitude and longitude values are always stored as float type,
     * even if they come from the form as strings.
     *
     * - If a coordinate value is empty, it's converted to 0
     * - If a value exists, it's properly cast to float
     *
     * @return void
     */
    public function hydrate()
    {
        $lat = $this->divisionForm->division['location']['latitude'] ?? null;
        $this->divisionForm->division['location']['latitude'] = is_null($lat)
            ? null
            : (float) $lat;

        $lng = $this->divisionForm->division['location']['longitude'] ?? null;
        $this->divisionForm->division['location']['longitude'] = is_null($lng)
            ? null
            : (float) $lng;
    }

    /**
     * Sets the dictionary for this component.
     *
     * @return static Returns the current instance for method chaining
     */
    protected function setDictionary(): static
    {
        $this->getDictionary();

        return $this;
    }

    /**
     * Validate the data coming from the form(s)
     *
     * @return bool
     */
    public function validateDivision(): bool
    {
        $error = $this->divisionForm->doValidation();

        if ($error) {
            session()->flash('error', $error);

            return false;
        }

        return true;

    }

    /**
     * Prepares and normalizes division data for an outgoing API request.
     *
     * This method:
     * - Removes the 'legal_entity_id' key from the division form data (as it is not needed in the request)
     * - Passes the division data through the schema service for normalization and snake_case conversion
     * - Removes any empty keys from the resulting array
     *
     * @return array The normalized and cleaned division data ready for API request
     */
    protected function prepareRequestData(): array
    {
        // This key as is don't need here. But schema has the same key means the legalEntity_uuid
        Arr::forget($this->divisionForm->division, 'legal_entity_id');

        $this->divisionForm->division['addresses'] = array_values($this->divisionForm->division['addresses']);

        $divisionData = schemaService()
            ->setDataSchema($this->divisionForm->division, app(DivisionApi::class))
            ->requestSchemaNormalize('schemaRequest')
            ->snakeCaseKeys(true)
            ->getNormalizedData();

        return removeEmptyKeys($divisionData);
    }

    /**
     * Store division data to the database
     *
     * @return null|Division
     */
    protected function saveToDB(): ?Division
    {
        $divisionData = new ObjectMapper(propertyAccessor: PropertyAccess::createPropertyAccessorBuilder()
            ->disableExceptionOnInvalidPropertyPath()
            ->getPropertyAccessor())
            ->map($this->divisionForm, DivisionData::class);

        $division = $divisionData->uuid ? Division::where('uuid', $divisionData->uuid)->first() : null;
        if (is_null($division) && isset($divisionData->id)) {
            $division = Division::find($divisionData->id);
        }
        if (is_null($division)) {
            $division = new Division();
        }

        return Repository::division()->saveMappedDivision($divisionData, $division, legalEntity());
    }

    /**
     * Filters an array of dictionaries based on allowed items.
     *
     * @param  array  $source  The source array of dictionaries to filter
     * @param  array  $allowedItems  Array of allowed items to filter by
     * @return array The filtered array containing only allowed items
     */
    protected function filterDictionaries(array $source, array $allowedItems): array
    {
        $arr = [];

        foreach ($source as $key => $dictionary) {

            if (\in_array($key, array_keys($allowedItems))) {
                $arr[$key] = array_filter($dictionary, fn ($item) => \in_array($item, $allowedItems[$key]), ARRAY_FILTER_USE_KEY);

                continue;
            }

            $arr[$key] = $dictionary;
        }

        return $arr;
    }
}
