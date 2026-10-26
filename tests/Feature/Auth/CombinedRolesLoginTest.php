<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\EHealth\Services\TokenStorage;
use App\Classes\eHealth\Api\Auth as AuthApi;
use App\Classes\eHealth\EHealthResponse;
use App\Events\EHealthUserLogin;
use App\Http\Controllers\Auth\EHealthLoginController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\LoginDev;
use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use App\Models\Permission;
use App\Models\Relations\Party;
use App\Models\Role;
use App\Models\User;
use App\Repositories\PartyRepository;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class CombinedRolesLoginTest extends TestCase
{
    use DatabaseTransactions;

    private const array ROLE_SCOPES = [
        'OWNER' => 'connection:write',
        'ASSISTANT' => 'encounter:write',
        'RECEPTIONIST' => 'person_request:write',
        'REORGANIZATION_OWNER' => 'client:read',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    #[DataProvider('initialRoleAssignments')]
    public function test_combined_roles_are_assigned_to_both_guards_without_duplicates(array $initial): void
    {
        [$entity, $party, $user, $employees] = $this->fixture($initial);
        $this->actingAs($user, 'ehealth');
        session()->forget('single_role_login');

        new PartyRepository()->syncUserEmployeesAndRoles($party, $entity);
        new PartyRepository()->syncUserEmployeesAndRoles($party, $entity);

        $this->assertAllRoleRows($entity, $user);
        foreach ($employees as $employee) {
            $this->assertDatabaseHas('employee_users', ['employee_id' => $employee->id, 'user_id' => $user->id]);
        }
        $this->assertSame('ehealth', Auth::getDefaultDriver());
    }

    public static function initialRoleAssignments(): array
    {
        $all = array_keys(self::ROLE_SCOPES);

        return [
            'no roles yet' => [[]],
            'all roles already assigned' => [['web' => $all, 'ehealth' => $all]],
            'only web roles exist' => [['web' => $all]],
            'only eHealth roles exist' => [['ehealth' => $all]],
            'eHealth is missing ASSISTANT' => [['web' => $all, 'ehealth' => ['OWNER', 'RECEPTIONIST', 'REORGANIZATION_OWNER']]],
        ];
    }

    #[DataProvider('grantedScopes')]
    public function test_oauth_callback_keeps_all_combined_roles_and_their_granted_scopes(array $scopes, array $activeRoles): void
    {
        $all = array_keys(self::ROLE_SCOPES);
        [$entity, $party, $user] = $this->fixture(['web' => $all, 'ehealth' => $all]);
        config(['ehealth.api.callback_prod' => true, 'ehealth.api.domain' => 'https://ehealth.example.test']);
        Event::forget(EHealthUserLogin::class);
        Event::listen(EHealthUserLogin::class, fn () => new PartyRepository()->syncUserEmployeesAndRoles($party, $entity));
        Session::start();
        Session::put(config('ehealth.api.auth_ehealth'), $user->id);
        Session::put('selected_legal_entity_uuid_for_ehealth', $entity->uuid);
        Session::put('logined_guard', 'ehealth');
        Session::put('mis_2fa', ['user_id' => $user->id, 'verified_at' => now()->toIso8601String()]);
        Http::fake(['*' => Http::response(['data' => [
            'value' => 'test-token', 'user_id' => $user->uuid, 'expires_at' => now()->addHour()->timestamp,
            'details' => ['client_id' => $entity->uuid, 'refresh_token' => 'test-refresh', 'scope' => implode(' ', $scopes)],
        ]])]);

        $response = new EHealthLoginController()(Request::create('/ehealth/oauth', 'GET', ['code' => 'test-code']));

        $this->assertSame(route('dashboard', [$entity]), $response->getTargetUrl());
        $this->assertAuthenticatedAs($user, 'ehealth');
        $this->assertAllRoleRows($entity, $user);
        $this->assertEqualsCanonicalizing($activeRoles, $user->fresh()->allowedRoles->all());
        $this->assertEqualsCanonicalizing($scopes, app(TokenStorage::class)->getTokenScopes());
        Http::assertSent(function (HttpRequest $request) {
            $requested = explode(' ', $request['token']['scope']);

            return count($requested) === count(self::ROLE_SCOPES) && array_diff($requested, array_values(self::ROLE_SCOPES)) === [];
        });
        $this->assertFalse(session()->has('single_role_login'));
    }

    public static function grantedScopes(): array
    {
        return [
            'all role scopes granted' => [array_values(self::ROLE_SCOPES), array_keys(self::ROLE_SCOPES)],
            'only assistant scope granted' => [['encounter:write'], ['ASSISTANT']],
        ];
    }

    #[DataProvider('loginForms')]
    public function test_combined_login_clears_a_previously_selected_single_role(string $form): void
    {
        $all = array_keys(self::ROLE_SCOPES);
        [$entity, $party, $user] = $this->fixture(['web' => $all, 'ehealth' => $all]);
        Session::put('single_role_login', 'OWNER');
        if ($form === 'standard') {
            $component = new Login();
            $component->legalEntityUuid = $entity->uuid;
            $url = new ReflectionMethod(Login::class, 'buildEHealthLoginUrl')->invoke($component, $user);
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $this->assertEqualsCanonicalizing(array_values(self::ROLE_SCOPES), explode(' ', $query['scope']));
        } else {
            $api = Mockery::mock(AuthApi::class);
            $api->shouldReceive('login')->once()->with($user->email, 'password')->andReturn($this->response(['user_id' => $user->uuid, 'value' => 'app-token']));
            $api->shouldReceive('authorize')->once()->withArgs(
                fn ($token, $scopes, $uuid) =>
                $token === 'app-token' && $uuid === $entity->uuid
                && count(explode(' ', $scopes)) === count(self::ROLE_SCOPES)
                && array_diff(array_values(self::ROLE_SCOPES), explode(' ', $scopes)) === []
            )->andReturn($this->response(['value' => 'test-code', 'details' => ['client_id' => $entity->uuid]]));
            $this->instance(AuthApi::class, $api);
            $component = Mockery::mock(LoginDev::class)->makePartial()->shouldAllowMockingProtectedMethods();
            $component->legalEntityUuid = $entity->uuid;
            $component->email = $user->email;
            $component->shouldReceive('validate')->once()->andReturn(['email' => $user->email, 'password' => 'password', 'legalEntityUuid' => $entity->uuid]);
            $component->shouldReceive('ensureIsNotRateLimited')->once()->andReturnTrue();
            $component->login();
        }

        $this->assertFalse(session()->has('single_role_login'));
    }

    public static function loginForms(): array
    {
        return [['standard'], ['dev']];
    }

    public function test_single_owner_login_does_not_grant_the_alias_to_an_ineligible_party_user(): void
    {
        [$entity, $party, $user] = $this->fixture([]);
        $otherUser = User::forceCreate([
            'uuid' => (string) Str::uuid(), 'email' => 'assistant-'.Str::random(12).'@example.com',
            'password' => bcrypt('password'), 'party_id' => $party->id,
            'inserted_at' => '2025-04-01 00:00:00', 'email_verified_at' => now(),
        ]);
        $employee = Employee::create([
            'uuid' => (string) Str::uuid(), 'employee_type' => 'ASSISTANT',
            'legal_entity_id' => $entity->id, 'legal_entity_uuid' => $entity->uuid,
            'party_id' => $party->id, 'user_id' => $otherUser->id,
            'status' => 'APPROVED', 'is_active' => true, 'position' => 'P1',
            'start_date' => '2025-05-01', 'inserted_at' => '2025-05-01 00:00:00',
        ]);
        $employee->users()->attach($otherUser->id);
        $this->actingAs($user, 'ehealth');
        session()->put('single_role_login', 'OWNER');

        new PartyRepository()->syncUserEmployeesAndRoles($party, $entity);

        $this->assertAllRoleRows($entity, $user);
        foreach (['web', 'ehealth'] as $guard) {
            $this->assertTrue($otherUser->fresh()->hasRole('ASSISTANT', $guard));
            $this->assertFalse($otherUser->fresh()->hasRole('OWNER', $guard));
            $this->assertFalse($otherUser->fresh()->hasRole('REORGANIZATION_OWNER', $guard));
        }
    }

    public function test_combined_role_sync_preserves_roles_and_employees_in_another_legal_entity(): void
    {
        [$entity, $party, $user] = $this->fixture([]);
        $otherEntity = LegalEntity::create([
            'uuid' => (string) Str::uuid(), 'legal_entity_type_id' => $entity->legal_entity_type_id,
            'status' => 'ACTIVE', 'sync_status' => 'COMPLETED', 'is_active' => true,
        ]);
        $employee = Employee::create([
            'uuid' => (string) Str::uuid(), 'employee_type' => 'HR',
            'legal_entity_id' => $otherEntity->id, 'legal_entity_uuid' => $otherEntity->uuid,
            'party_id' => $party->id, 'user_id' => $user->id,
            'status' => 'APPROVED', 'is_active' => true, 'position' => 'P1',
            'start_date' => '2025-02-01', 'inserted_at' => '2025-02-01 00:00:00',
        ]);
        $employee->users()->attach($user->id);
        setPermissionsTeamId($otherEntity->id);
        foreach (['web', 'ehealth'] as $guard) {
            Auth::shouldUse($guard);
            Role::findOrCreate('HR', $guard);
            $user->assignRole('HR');
        }
        setPermissionsTeamId($entity->id);
        $this->actingAs($user, 'ehealth');

        new PartyRepository()->syncUserEmployeesAndRoles($party, $entity);

        $this->assertAllRoleRows($entity, $user);
        $this->assertDatabaseHas('employee_users', ['employee_id' => $employee->id, 'user_id' => $user->id]);
        setPermissionsTeamId($otherEntity->id);
        foreach (['web', 'ehealth'] as $guard) {
            $this->assertTrue($user->fresh()->hasRole('HR', $guard));
            $this->assertFalse($user->fresh()->hasRole('OWNER', $guard));
        }
    }

    private function fixture(array $initial): array
    {
        $typeId = DB::table('legal_entity_types')->where('name', LegalEntity::TYPE_PRIMARY_CARE)->value('id')
            ?? DB::table('legal_entity_types')->insertGetId(['name' => LegalEntity::TYPE_PRIMARY_CARE]);
        $entity = LegalEntity::create([
            'uuid' => (string) Str::uuid(), 'legal_entity_type_id' => $typeId,
            'status' => 'ACTIVE', 'sync_status' => 'COMPLETED', 'is_active' => true,
        ]);
        setPermissionsTeamId($entity->id);
        $party = Party::create(['uuid' => (string) Str::uuid(), 'first_name' => 'Test', 'last_name' => 'Combined', 'birth_date' => '1990-01-01', 'gender' => 'MALE']);
        $user = User::forceCreate([
            'uuid' => (string) Str::uuid(), 'email' => 'combined-'.Str::random(12).'@example.com',
            'password' => bcrypt('password'), 'party_id' => $party->id,
            'inserted_at' => '2025-01-01 00:00:00', 'email_verified_at' => now(),
        ]);
        foreach (['web', 'ehealth'] as $guard) {
            Auth::shouldUse($guard);
            foreach (self::ROLE_SCOPES as $roleName => $scope) {
                $permission = Permission::findOrCreate($scope, $guard);
                $permission->legalEntityTypes()->syncWithoutDetaching([$typeId]);
                Role::findOrCreate($roleName, $guard)->givePermissionTo($permission);
            }
            if (!empty($initial[$guard])) {
                $user->assignRole($initial[$guard]);
            }
        }
        $employees = [];
        foreach (['OWNER', 'ASSISTANT', 'RECEPTIONIST'] as $type) {
            $employee = Employee::create([
                'uuid' => (string) Str::uuid(), 'employee_type' => $type,
                'legal_entity_id' => $entity->id, 'legal_entity_uuid' => $entity->uuid,
                'party_id' => $party->id, 'user_id' => $user->id,
                'status' => 'APPROVED', 'is_active' => true, 'position' => 'P1',
                'start_date' => '2025-02-01', 'inserted_at' => '2025-02-01 00:00:00',
            ]);
            $employee->users()->attach($user->id);
            $employees[] = $employee;
        }
        Auth::shouldUse('ehealth');

        return [$entity, $party, $user, $employees];
    }

    private function assertAllRoleRows(LegalEntity $entity, User $user): void
    {
        $rows = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', $user->getMorphClass())
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.legal_entity_id', $entity->id)
            ->get(['roles.name', 'roles.guard_name']);
        $this->assertCount(8, $rows);
        foreach (['web', 'ehealth'] as $guard) {
            $this->assertEqualsCanonicalizing(array_keys(self::ROLE_SCOPES), $rows->where('guard_name', $guard)->pluck('name')->all());
        }
    }

    private function response(array $data): EHealthResponse
    {
        return new EHealthResponse(new PsrResponse(200), fn () => $data);
    }
}
