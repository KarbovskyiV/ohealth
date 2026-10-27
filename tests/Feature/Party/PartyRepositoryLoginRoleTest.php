<?php

declare(strict_types=1);

namespace Tests\Feature\Party;

use App\Enums\Status;
use App\Enums\User\Role;
use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Models\Role as ModelsRole;
use App\Models\User;
use App\Repositories\PartyRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PartyRepositoryLoginRoleTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('ownerLoginScenarios')]
    public function test_owner_login_uses_the_owner_employee(string $entityStatus, string $employeeStatus, string $loginRole): void
    {
        [$legalEntity, $party, $user, $employee] = $this->createFixture($entityStatus, $employeeStatus, Role::OWNER->value);
        session()->put('single_role_login', $loginRole);

        new PartyRepository()->syncUserEmployeesAndRoles($party, $legalEntity);

        $this->assertDatabaseHas('employee_users', ['employee_id' => $employee->id, 'user_id' => $user->id]);
        foreach (['web', 'ehealth'] as $guard) {
            $this->assertTrue($user->fresh()->hasRole($loginRole, $guard));
        }
        $this->assertSame('ehealth', Auth::getDefaultDriver());
    }

    public static function ownerLoginScenarios(): array
    {
        return [
            'local status not synchronized yet' => [Status::ACTIVE->value, Status::APPROVED->value, Role::REORGANIZATION_OWNER->value],
            'reorganized entity and employee' => [Status::REORGANIZED->value, Status::REORGANIZED->value, Role::REORGANIZATION_OWNER->value],
            'reorganized entity with approved owner' => [Status::REORGANIZED->value, Status::APPROVED->value, Role::REORGANIZATION_OWNER->value],
            'ordinary owner login' => [Status::ACTIVE->value, Status::APPROVED->value, Role::OWNER->value],
        ];
    }

    public function test_a_missing_employee_for_the_selected_role_does_not_grant_that_role(): void
    {
        [$legalEntity, $party, $user, $employee] = $this->createFixture(Status::ACTIVE->value, Status::APPROVED->value, Role::DOCTOR->value);
        session()->put('single_role_login', Role::HR->value);

        new PartyRepository()->syncUserEmployeesAndRoles($party, $legalEntity);

        $this->assertDatabaseHas('employee_users', ['employee_id' => $employee->id, 'user_id' => $user->id]);
        foreach (['web', 'ehealth'] as $guard) {
            $this->assertFalse($user->fresh()->hasRole(Role::HR->value, $guard));
            $this->assertTrue($user->fresh()->hasRole(Role::DOCTOR->value, $guard));
        }
    }

    private function createFixture(string $entityStatus, string $employeeStatus, string $employeeType): array
    {
        $typeId = DB::table('legal_entity_types')->where('name', LegalEntity::TYPE_PRIMARY_CARE)->value('id')
            ?? DB::table('legal_entity_types')->insertGetId(['name' => LegalEntity::TYPE_PRIMARY_CARE]);
        $legalEntity = LegalEntity::create([
            'uuid' => (string) Str::uuid(),
            'legal_entity_type_id' => $typeId,
            'status' => $entityStatus,
            'sync_status' => 'COMPLETED',
            'is_active' => true,
        ]);
        $party = Party::create([
            'uuid' => (string) Str::uuid(),
            'first_name' => 'Test',
            'last_name' => 'Owner',
            'birth_date' => '1990-01-01',
            'gender' => 'MALE',
        ]);
        $user = User::forceCreate([
            'uuid' => (string) Str::uuid(),
            'email' => 'login-role-'.Str::random(12).'@example.com',
            'password' => bcrypt('password'),
            'party_id' => $party->id,
            'inserted_at' => '2025-01-01 00:00:00',
            'email_verified_at' => now(),
        ]);
        $employee = Employee::create([
            'uuid' => (string) Str::uuid(),
            'legal_entity_id' => $legalEntity->id,
            'legal_entity_uuid' => $legalEntity->uuid,
            'party_id' => $party->id,
            'user_id' => $user->id,
            'employee_type' => $employeeType,
            'status' => $employeeStatus,
            'is_active' => true,
            'inserted_at' => '2025-02-01 00:00:00',
            'start_date' => '2025-02-01',
            'position' => 'P1',
        ]);
        $employee->users()->attach($user->id);
        setPermissionsTeamId($legalEntity->id);
        foreach (['web', 'ehealth'] as $guard) {
            foreach ([Role::OWNER, Role::REORGANIZATION_OWNER, Role::DOCTOR, Role::HR] as $role) {
                ModelsRole::findOrCreate($role->value, $guard);
            }
        }
        $this->actingAs($user, 'ehealth');

        return [$legalEntity, $party, $user, $employee];
    }
}
