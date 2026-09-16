<?php

declare(strict_types=1);

namespace Tests\Unit\Employee;

use App\Classes\eHealth\Api\EmployeeRequest as EmployeeRequestApi;
use App\Enums\Employee\RequestStatus;
use App\Listeners\eHealth\EmployeeCreate;
use App\Models\Employee\EmployeeRequest;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class EmployeeCreatePendingEditListGateTest extends TestCase
{
    #[Test]
    #[DataProvider('shouldSkipPendingEditProvider')]
    public function should_skip_pending_edit_on_login_uses_local_state_only(
        RequestStatus $status,
        ?int $employeeId,
        bool $expectedSkip
    ): void {
        $request = new EmployeeRequest([
            'uuid' => (string) Str::uuid(),
            'status' => $status,
            'employee_id' => $employeeId,
        ]);

        $listener = new EmployeeCreate();
        $method = new ReflectionMethod(EmployeeCreate::class, 'shouldSkipPendingEditOnLogin');

        $this->assertSame($expectedSkip, $method->invoke($listener, $request));
    }

    /**
     * @return array<string, array{0: RequestStatus, 1: ?int, 2: bool}>
     */
    public static function shouldSkipPendingEditProvider(): array
    {
        return [
            'pending edit (NEW + employee_id)' => [RequestStatus::NEW, 55, true],
            'pending edit (SIGNED + employee_id)' => [RequestStatus::SIGNED, 55, true],
            'pending create (NEW, no employee)' => [RequestStatus::NEW, null, false],
            'already approved edit' => [RequestStatus::APPROVED, 55, false],
            'rejected edit' => [RequestStatus::REJECTED, 55, false],
        ];
    }

    #[Test]
    public function pending_edit_gate_never_calls_employee_request_api(): void
    {
        $api = Mockery::mock(EmployeeRequestApi::class);
        $api->shouldNotReceive('getDetails');
        $api->shouldNotReceive('getMany');
        $this->instance(EmployeeRequestApi::class, $api);

        $pendingEdit = new EmployeeRequest([
            'uuid' => (string) Str::uuid(),
            'status' => RequestStatus::NEW,
            'employee_id' => 55,
        ]);

        $listener = new EmployeeCreate();
        $skipMethod = new ReflectionMethod(EmployeeCreate::class, 'shouldSkipPendingEditOnLogin');

        $this->assertTrue($skipMethod->invoke($listener, $pendingEdit));
    }
}
