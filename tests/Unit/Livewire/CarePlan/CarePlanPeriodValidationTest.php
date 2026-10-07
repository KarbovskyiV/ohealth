<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire\CarePlan;

use App\Livewire\CarePlan\Forms\CarePlanForm;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Tests\TestCase;

class CarePlanPeriodValidationTest extends TestCase
{
    public function test_form_blocks_end_before_start_with_localized_field_error(): void
    {
        $form = $this->form('10.10.2026', '09.10.2026');

        try {
            $form->validate();
            $this->fail('An end before the start must be rejected before signing.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('care-plan.period_end_before_start')], $exception->errors()['form.periodEnd']);
        }
    }

    public function test_equal_later_and_omitted_end_dates_remain_valid(): void
    {
        foreach (['10.10.2026', '11.10.2026', ''] as $end) {
            $validated = $this->form('10.10.2026', $end)->validate();
            $this->assertSame($end, $validated['periodEnd']);
        }
    }

    private function form(string $start, string $end): CarePlanForm
    {
        $component = new class extends Component
        {};
        $form = new CarePlanForm($component, 'form');
        $form->category = 'THERAPY';
        $form->title = 'Test plan';
        $form->termsOfService = 'EPISODE';
        $form->periodStart = $start;
        $form->periodEnd = $end;

        return $form;
    }
}
