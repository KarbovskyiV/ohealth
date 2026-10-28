<?php

declare(strict_types=1);

namespace App\Livewire\EncounterPackage;

use App\Livewire\EncounterPackage\Concerns\ManagesEncounterReferrals;
use Illuminate\View\View;

class EncounterReferralCreate extends EncounterDocumentCreate
{
    use ManagesEncounterReferrals;

    protected function documentAbility(): string
    {
        return 'createServiceRequest';
    }

    protected function initializeComponent(): void
    {
        $this->openEncounterReferralDrawer();
        $submissionId = request()->query('submission');
        if (!is_string($submissionId) || (!ctype_digit($submissionId) && !\Illuminate\Support\Str::isUuid($submissionId))) {
            return;
        }
        $encounter = $this->resolveEncounterModelForStandalone();
        $submissionUuid = ctype_digit($submissionId)
            ? \App\Repositories\MedicalEvents\Repository::serviceRequest()->findForPerson((int) $submissionId, (int) $this->personId)->uuid
            : $submissionId;
        $record = app(\App\Services\MedicalEvents\MedicalRequestOwnership::class)->serviceForEncounter($submissionUuid, $encounter);
        $resource = \App\Repositories\MedicalEvents\Repository::serviceRequest()->getResource($record);
        $fields = (new \App\Services\MedicalEvents\Mappers\ServiceRequestMapper())->fromFhir($resource);
        foreach (array_keys($this->encounterReferralForm) as $field) {
            if (array_key_exists($field, $fields)) {
                $this->encounterReferralForm[$field] = $fields[$field] ?? $this->encounterReferralForm[$field];
            }
        }
        $authMethodId = data_get($resource, 'informWith.authMethodId');
        $authMethod = collect($this->encounterReferralAuthMethods)->first(
            static fn (array $method): bool => ($method['uuid'] ?? null) === $authMethodId
        );
        $this->encounterReferralForm['inform_with'] = $authMethod !== null
            ? \App\Services\MedicalEvents\InformWith::formValue($authMethod)
            : (string) $authMethodId;
        foreach (['started_at' => 'start_time', 'ended_at' => 'end_time'] as $dateField => $timeField) {
            if (!empty($fields[$dateField])) {
                $date = \Carbon\CarbonImmutable::parse($fields[$dateField])->setTimezone(config('app.timezone'));
                $this->encounterReferralForm[$dateField] = $date->format('d.m.Y');
                $this->encounterReferralForm[$timeField] = $date->format('H:i');
            }
        }
        $this->encounterReferralSelectedService = [
            'id' => $fields['service_id'],
            'code' => $fields['service_id'],
            'name' => data_get($resource, 'code.display', __('encounters.service')),
        ];
        $this->encounterReferralRequestIdToSign = $record->uuid;
        $this->checkEncounterReferralSubmission();
    }

    public function sign(): void
    {
        if ($this->actionType === 'sign_referral') {
            $this->signEncounterReferral();
        }
    }

    protected function afterEncounterReferralSubmitted(): void
    {
        $this->redirectRoute('encounter.referral.create', [
            'legalEntity' => $this->legalEntityId,
            'person' => $this->personId,
            'encounter' => $this->encounterId,
            'submission' => \App\Repositories\MedicalEvents\Repository::serviceRequest()->findByUuid($this->encounterReferralRequestIdToSign)?->id,
        ], navigate: true);
    }

    protected function afterEncounterReferralSigned(): void
    {
        $this->returnToEncounter();
    }

    public function render(): View
    {
        return view('livewire.encounter-package.encounter-referral-create');
    }
}
