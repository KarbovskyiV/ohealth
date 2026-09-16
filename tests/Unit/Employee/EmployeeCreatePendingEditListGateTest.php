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
    #[DataProvider('pendingEditActionProvider')]
    public function resolve_pending_edit_action_matches_list_status(?string $remoteStatus, string $expected): void
    {
        $listener = new EmployeeCreate();
        $method = new ReflectionMethod(EmployeeCreate::class, 'resolvePendingEditAction');

        $this->assertSame($expected, $method->invoke($listener, $remoteStatus));
    }

    /**
     * @return array<string, array{0: ?string, 1: string}>
     */
    public static function pendingEditActionProvider(): array
    {
        return [
            'missing from list' => [null, 'skip'],
            'empty status' => ['', 'skip'],
            'still new' => ['NEW', 'skip'],
            'legacy signed' => ['SIGNED', 'skip'],
            'approved' => ['APPROVED', 'apply'],
            'rejected' => ['REJECTED', 'reject'],
            'expired' => ['EXPIRED', 'expire'],
            'unknown' => ['SOMETHING_ELSE', 'skip'],
        ];
    }

    #[Test]
    public function resolve_statuses_skips_list_and_flashes_when_user_lacks_scope(): void
    {
        $api = Mockery::mock(EmployeeRequestApi::class);
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
    public function resolve_statuses_fetches_list_when_user_has_scope_and_pending_edits(): void
    {
        $uuid = (string) Str::uuid();

        $response = Mockery::mock(EHealthResponse::class);
        $response->shouldReceive('validate')->once()->andReturn([
            ['uuid' => $uuid, 'status' => 'APPROVED'],
        ]);

        $api = Mockery::mock(EmployeeRequestApi::class);
        $api->shouldReceive('getMany')->once()->andReturn($response);
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
    public function fetch_remote_request_status_map_uses_page_size_max(): void
    {
        $legalEntity = new LegalEntity([
            'edrpou' => '12345678',
        ]);
        $legalEntity->id = 10;

        $firstUuid = (string) Str::uuid();
        $secondUuid = (string) Str::uuid();

        $response = Mockery::mock(EHealthResponse::class);
        $response->shouldReceive('validate')->once()->andReturn([
            ['uuid' => $firstUuid, 'status' => 'APPROVED'],
            ['uuid' => $secondUuid, 'status' => 'NEW'],
        ]);

        $api = Mockery::mock(EmployeeRequestApi::class);
        $api->shouldReceive('getMany')
            ->once()
            ->withArgs(function (array $filters, ?int $page): bool {
                return ($filters['edrpou'] ?? null) === '12345678'
                    && ($filters['page_size'] ?? null) === (int) config('ehealth.api.page_size_max', 500)
                    && $page === 1;
            })
            ->andReturn($response);
        $this->instance(EmployeeRequestApi::class, $api);

        $listener = new EmployeeCreate();
        $method = new ReflectionMethod(EmployeeCreate::class, 'fetchRemoteRequestStatusMap');
        $map = $method->invoke($listener, $legalEntity);

        $this->assertSame([
            $firstUuid => 'APPROVED',
            $secondUuid => 'NEW',
        ], $map->all());
    }

    #[Test]
    public function fetch_remote_request_status_map_returns_empty_on_api_failure(): void
    {
        $legalEntity = new LegalEntity(['edrpou' => '12345678']);
        $legalEntity->id = 10;

        $api = Mockery::mock(EmployeeRequestApi::class);
        $api->shouldReceive('getMany')->once()->andThrow(new \RuntimeException('eHealth down'));
        $this->instance(EmployeeRequestApi::class, $api);

        $listener = new EmployeeCreate();
        $method = new ReflectionMethod(EmployeeCreate::class, 'fetchRemoteRequestStatusMap');

        $this->assertTrue($method->invoke($listener, $legalEntity)->isEmpty());
    }
}
