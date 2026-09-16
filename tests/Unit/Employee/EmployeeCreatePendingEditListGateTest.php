<?php

declare(strict_types=1);

namespace Tests\Unit\Employee;

use App\Classes\eHealth\Api\EmployeeRequest as EmployeeRequestApi;
use App\Classes\eHealth\EHealthResponse;
use App\Enums\Employee\RequestStatus;
use App\Listeners\eHealth\EmployeeCreate;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class EmployeeCreatePendingEditListGateTest extends TestCase
{
    #[Test]
    #[DataProvider('requiresRevisionUpdateProvider')]
    public function requires_revision_update_matches_remote_status(?string $remoteStatus, false|string $expected): void
    {
        $listener = new EmployeeCreate();
        $method = new ReflectionMethod(EmployeeCreate::class, 'requiresRevisionUpdate');

        $this->assertSame($expected, $method->invoke($listener, $remoteStatus));
    }

    /**
     * @return array<string, array{0: ?string, 1: false|string}>
     */
    public static function requiresRevisionUpdateProvider(): array
    {
        return [
            'missing' => [null, false],
            'empty' => ['', false],
            'still new' => ['NEW', false],
            'legacy signed' => ['SIGNED', false],
            'approved' => ['APPROVED', 'APPROVED'],
            'rejected' => ['REJECTED', 'REJECTED'],
            'expired' => ['EXPIRED', 'EXPIRED'],
            'unknown' => ['SOMETHING_ELSE', false],
        ];
    }

    #[Test]
    public function resolve_statuses_skips_api_and_flashes_when_user_lacks_scope(): void
    {
        $api = Mockery::mock(EmployeeRequestApi::class);
        $api->shouldNotReceive('getDetails');
        $api->shouldNotReceive('getMany');
        $this->instance(EmployeeRequestApi::class, $api);

        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 7;
        $user->shouldReceive('can')->with('employee_request:read')->andReturn(false);

        $legalEntity = new LegalEntity(['edrpou' => '12345678']);
        $legalEntity->id = 10;

        $pendingEdit = new EmployeeRequest([
            'uuid' => (string) Str::uuid(),
            'status' => RequestStatus::NEW,
            'employee_id' => 55,
        ]);
        $pendingEdit->id = 1;

        $listener = new EmployeeCreate();
        $method = new ReflectionMethod(EmployeeCreate::class, 'resolveRemoteRequestStatusesForLogin');
        $map = $method->invoke($listener, $user, collect([$pendingEdit]), $legalEntity);

        $this->assertTrue($map->isEmpty());
        $this->assertSame(
            __('employees.sync.pending_edit_needs_specialist'),
            Session::get('warning')
        );
    }

    #[Test]
    public function resolve_statuses_fetches_by_id_when_user_has_scope_and_pending_edits(): void
    {
        $uuid = (string) Str::uuid();

        $response = Mockery::mock(EHealthResponse::class);
        $response->shouldReceive('validate')->once()->andReturn([
            'uuid' => $uuid,
            'status' => 'APPROVED',
        ]);

        $api = Mockery::mock(EmployeeRequestApi::class);
        $api->shouldReceive('getDetails')->once()->with($uuid)->andReturn($response);
        $api->shouldNotReceive('getMany');
        $this->instance(EmployeeRequestApi::class, $api);

        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 7;
        $user->shouldReceive('can')->with('employee_request:read')->andReturn(true);

        $legalEntity = new LegalEntity(['edrpou' => '12345678']);
        $legalEntity->id = 10;

        $pendingEdit = new EmployeeRequest([
            'uuid' => $uuid,
            'status' => RequestStatus::NEW,
            'employee_id' => 55,
        ]);
        $pendingEdit->id = 1;

        $listener = new EmployeeCreate();
        $method = new ReflectionMethod(EmployeeCreate::class, 'resolveRemoteRequestStatusesForLogin');
        $map = $method->invoke($listener, $user, collect([$pendingEdit]), $legalEntity);

        $this->assertSame([$uuid => 'APPROVED'], $map->all());
        $this->assertNull(Session::get('warning'));
    }

    #[Test]
    public function fetch_remote_statuses_skips_failed_get_details(): void
    {
        $uuid = (string) Str::uuid();

        $api = Mockery::mock(EmployeeRequestApi::class);
        $api->shouldReceive('getDetails')->once()->with($uuid)->andThrow(new \RuntimeException('eHealth down'));
        $this->instance(EmployeeRequestApi::class, $api);

        $pendingEdit = new EmployeeRequest([
            'uuid' => $uuid,
            'status' => RequestStatus::NEW,
            'employee_id' => 55,
        ]);
        $pendingEdit->id = 1;

        $listener = new EmployeeCreate();
        $method = new ReflectionMethod(EmployeeCreate::class, 'fetchRemoteStatusesByRequestIds');

        $this->assertTrue($method->invoke($listener, collect([$pendingEdit]))->isEmpty());
    }
}
