<?php

declare(strict_types=1);

namespace Tests\Feature\Employee;

use App\Livewire\Employee\EmployeeComponent;
use App\Livewire\Employee\EmployeeCreate;
use App\Livewire\Employee\Forms\EmployeeForm;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class EmployeeNoTaxIdDocumentUpdatesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
    }

    #[Test]
    public function editingIdCardUpdatesTheNoTaxIdNumber(): void
    {
        Livewire::test(EmployeeDocumentUpdateTestComponent::class)
            ->set('form.party.noTaxId', true)
            ->set('form.documents', [['type' => 'NATIONAL_ID', 'number' => '111222333']])
            ->assertSet('form.party.taxId', '111222333')
            ->set('form.documents', [['type' => 'NATIONAL_ID', 'number' => '111222334']])
            ->assertSet('form.party.taxId', '111222334');
    }

    #[Test]
    public function addingIdentityDocumentClearsTheMissingDocumentError(): void
    {
        Livewire::test(EmployeeDocumentUpdateTestComponent::class)
            ->set('form.party.noTaxId', true)
            ->call('markMissingDocumentError')
            ->assertHasErrors('form.party.taxId')
            ->set('form.documents', [['type' => 'NATIONAL_ID', 'number' => '111222333']])
            ->assertHasNoErrors('form.party.taxId');
    }

    #[Test]
    public function unrelatedDocumentDoesNotClearMissingIdentityDocumentError(): void
    {
        Livewire::test(EmployeeDocumentUpdateTestComponent::class)
            ->set('form.party.noTaxId', true)
            ->call('markMissingDocumentError')
            ->set('form.documents', [['type' => 'TEMPORARY_PASSPORT', 'number' => 'AB123456']])
            ->assertHasErrors('form.party.taxId');
    }

    #[Test]
    public function editingDocumentDoesNotReplaceAnActualTaxId(): void
    {
        Livewire::test(EmployeeDocumentUpdateTestComponent::class)
            ->set('form.party.taxId', '1234567890')
            ->set('form.documents', [['type' => 'NATIONAL_ID', 'number' => '111222333']])
            ->assertSet('form.party.taxId', '1234567890');
    }

    #[Test]
    public function savePreparationUsesTheCurrentDocumentInsteadOfAStaleNumber(): void
    {
        $component = new EmployeeCreate();
        $component->form = new EmployeeForm($component, 'form');
        $component->form->employeeType = 'HR';
        $component->form->party['noTaxId'] = true;
        $component->form->party['taxId'] = '111222333';
        $component->form->documents = [['type' => 'NATIONAL_ID', 'number' => '111222334']];

        (new ReflectionMethod(EmployeeCreate::class, 'applyEmployeeTypeBusinessRules'))->invoke($component);

        $this->assertSame('111222334', $component->form->party['taxId']);
    }
}

class EmployeeDocumentUpdateTestComponent extends EmployeeComponent
{
    public function markMissingDocumentError(): void
    {
        $this->addError('form.party.taxId', __('forms.no_tax_id_document_required'));
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
