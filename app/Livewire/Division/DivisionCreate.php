<?php

declare(strict_types=1);

namespace App\Livewire\Division;

use Exception;
use Throwable;
use App\Models\Division;
use App\Models\LegalEntity;
use App\Models\Relations\Phone;
use App\Classes\eHealth\EHealth;
use App\Dto\Division\Ehealth as EhealthData;
use App\Dto\Division\Model as DivisionData;
use App\Repositories\Repository;
use App\Traits\WorkTimeUtilities;
use Symfony\Component\ObjectMapper\ObjectMapper;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Livewire\Features\SupportRedirects\Redirector;

class DivisionCreate extends DivisionComponent
{
    use WorkTimeUtilities;

    /**
     * Array containing dictionary names only used within the component.
     *
     * @var array
     */
    protected array $allowedDictionaryItems = [];

    public function mount(LegalEntity $legalEntity)
    {
        $this->allowedDictionaryItems = [
            'PHONE_TYPE' => Phone::getPhoneTypes(),
            'DIVISION_TYPE' => Division::getValidDivisionTypes()
        ];

        // Get rid of unused dictionary items
        $this->dictionaries = $this
            ->setDictionary()
            ->filterDictionaries($this->dictionaries, $this->allowedDictionaryItems);
    }

    /**
     * Handle updates to the division location latitude field.
     *
     * This method is automatically called by Livewire when the
     * divisionForm.division.location.latitude property is updated.
     * It ensures the value is always stored as a float.
     *
     * @param  mixed  $value  The value for latitude from input field
     * @return void
     */
    public function updatedDivisionFormDivisionLocationLatitude($value): void
    {
        $this->divisionForm->division['location']['latitude'] = empty($value) && !is_numeric($value)
            ? null
            : (float) number_format((float) $value, 6, '.', '');
    }

    /**
     * Handle updates to the division location longitude field.
     *
     * This method is automatically called by Livewire when the
     * divisionForm.division.location.longitude property is updated.
     * It ensures the value is always stored as a float.
     *
     * @param  mixed  $value  The value for longitude from input field
     * @return void
     */
    public function updatedDivisionFormDivisionLocationLongitude($value): void
    {
        $this->divisionForm->division['location']['longitude'] = empty($value) && !is_numeric($value)
            ? null
            : (float) number_format((float) $value, 6, '.', '');
    }

    /**
     * Store data from the Division's form into the DB
     *
     * @param  bool  $justSave  Whether to show a success message after saving (true by default)
     * @return Division|RedirectResponse|Redirector|null
     */
    public function store(bool $justSave = true): Division|RedirectResponse|Redirector|null
    {
        if (Auth::user()->cannot('create', Division::class)) {
            session()->flash('error', __('divisions.policy.deny.create'));

            return null;
        }

        if ($this->validateDivision()) {
            try {
                $division = $this->saveToDB();

                return $justSave
                    ? Redirect::route('division.edit', [legalEntity(), $division])->with('success', __('forms.saved_successfully'))
                    : $division;
            } catch (Throwable $err) {
                Log::channel('db_errors')->error('Cannot save Division\'s data!', ['error' => $err->getMessage()]);

                session()->flash('error', __('errors.database.messages.save_error'));
            }
        }

        return null;
    }

    /**
     * Combined method used both preliminary saving and modification Division's data
     *
     * @return void
     */
    public function create(): void
    {
        // Preliminary store data in the DB
        $division = $this->store(false);

        if (!$division || !$division instanceof Division) {
            return;
        }

        // Send request to the eHealth and store request data
        $this->divisionCreate($division);
    }

    /**
     * Sends the prepared division data to the eHealth API and handles the response
     *
     * This method orchestrates the final step of creating a division. It takes a
     * pre-saved local division record, sends its data to the eHealth service for
     * creation, and upon a successful response, updates the local record with the
     * synchronized data (e.g., the eHealth UUID). If the API call is successful,
     * it redirects the user to the division index page with a success message.
     * Otherwise, it logs the error and displays an error message to the user.
     *
     * @param  Division  $division  The pre-saved division model instance to be created in eHealth
     * @return void This method does not return a value but performs a redirect on success
     */
    protected function divisionCreate(Division $division): void
    {
        $divisionEhealthMapped = new ObjectMapper(propertyAccessor: PropertyAccess::createPropertyAccessor())
            ->map($this->divisionForm, EhealthData::class)
            ->toArray();

        try {
            $response = EHealth::division()->create(data: $divisionEhealthMapped)->validate();
            // If the response is empty, it means the create failed and uncatched
            if ($response->isEmpty()) {
                throw new Exception('eHealth return empty response!');
            }

        } catch (Throwable $err) {
            Log::channel('e_health_errors')->error(self::class . '::divisionCreate()', ['error' => $err->getMessage()]);
            session()->flash('error', __('errors.ehealth.messages.request_error'));

            return;
        }

        $divisionModelMapped = new ObjectMapper(propertyAccessor: PropertyAccess::createPropertyAccessorBuilder()
            ->disableExceptionOnInvalidPropertyPath()
            ->getPropertyAccessor())
            ->map($response, DivisionData::class);

        try {
            Repository::division()->saveMappedDivision($divisionModelMapped, $division, legalEntity());
        } catch (Throwable $err) {
            Log::channel('db_errors')->error(self::class . '::divisionCreate()', ['error' => $err->getMessage()]);
            session()->flash('error', __('errors.database.messages.save_error'));

            return;
        }

        $this->redirect(route('division.index', [legalEntity()]), navigate: true);

        session()->flash('success', __('forms.success_response'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.division.division-create');
    }
}
