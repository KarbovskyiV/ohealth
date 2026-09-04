<?php

declare(strict_types=1);

namespace Tests\Feature\Party;

use App\Events\EHealthUserLogin;
use App\Jobs\PartyVerificationSync;
use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class PartyVerificationSyncStatusOnLoginTest extends TestCase
{
    use DatabaseTransactions;

    private function createFixture(string $roleType): array
    {
        $typeId = \Illuminate\Support\Facades\DB::table('legal_entity_types')->where('name', 'PRIMARY_CARE')->value('id')
            ?? \Illuminate\Support\Facades\DB::table('legal_entity_types')->insertGetId(['name' => 'PRIMARY_CARE']);

        $legalEntity = LegalEntity::create([
            'uuid' => (string) Str::uuid(),
            'status' => 'ACTIVE',
            'sync_status' => 'COMPLETED',
            'legal_entity_type_id' => $typeId,
            'is_active' => true,
        ]);

        if (config('permission.teams')) {
            setPermissionsTeamId($legalEntity->id);
        }

        $party = Party::create([
            'uuid' => (string) Str::uuid(),
            'first_name' => 'Test',
            'last_name' => 'User',
            'tax_id' => (string) random_int(1000000000, 9999999999),
            'birth_date' => '1990-01-01',
            'gender' => 'MALE',
            'verification_status' => 'NOT_VERIFIED',
        ]);

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'email' => strtolower($roleType) . '_' . Str::random(6) . '@example.com',
            'password' => Hash::make('password'),
            'party_id' => $party->id,
        ]);

        $employee = Employee::create([
            'uuid' => (string) Str::uuid(),
            'full_name' => "Test {$roleType}",
            'employee_type' => $roleType,
            'status' => \App\Enums\Status::APPROVED->value,
            'legal_entity_id' => $legalEntity->id,
            'is_active' => true,
            'position' => "{$roleType} Position",
            'start_date' => now()->format('Y-m-d'),
            'user_id' => $user->id,
            'party_id' => $party->id,
        ]);

        $user->employees()->attach($employee->id);

        $spatieRole = SpatieRole::firstOrCreate(
            ['name' => $roleType, 'guard_name' => 'web'],
            ['team_id' => config('permission.teams') ? $legalEntity->id : null]
        );
        $user->assignRole($spatieRole);

        // EHealthUserLogin encrypts the bearer token from TokenStorage/session on construct.
        $this->withSession([
            config('ehealth.api.oauth.bearer_token') => 'test-ehealth-token',
        ]);

        return compact('legalEntity', 'party', 'user', 'employee');
    }

    private function assertPartyVerificationSyncNotQueued(): void
    {
        $this->assertTrue(
            Bus::batched(fn ($batch) => $batch->name === 'Party Verification Status Sync')->isEmpty(),
            'Party Verification Status Sync batch was dispatched unexpectedly.'
        );
    }

    #[Test]
    public function first_login_skips_party_verification_sync(): void
    {
        Bus::fake();

        ['legalEntity' => $legalEntity, 'user' => $user] = $this->createFixture('HR');

        $event = new EHealthUserLogin(
            user: $user,
            legalEntity: $legalEntity,
            authUserUUID: $user->uuid,
            scopes: [PartyVerificationSync::SCOPE_REQUIRED],
            isFirstLogin: true
        );

        event($event);

        $this->assertPartyVerificationSyncNotQueued();
    }

    #[Test]
    public function missing_read_scope_skips_party_verification_sync(): void
    {
        Bus::fake();

        ['legalEntity' => $legalEntity, 'user' => $user] = $this->createFixture('HR');

        $event = new EHealthUserLogin(
            user: $user,
            legalEntity: $legalEntity,
            authUserUUID: $user->uuid,
            scopes: ['party_verification:details', 'employee:read'],
            isFirstLogin: false
        );

        event($event);

        $this->assertPartyVerificationSyncNotQueued();
    }

    #[Test]
    public function owner_login_with_read_scope_skips_party_verification_sync(): void
    {
        Bus::fake();

        ['legalEntity' => $legalEntity, 'user' => $user] = $this->createFixture('OWNER');

        $event = new EHealthUserLogin(
            user: $user,
            legalEntity: $legalEntity,
            authUserUUID: $user->uuid,
            scopes: [PartyVerificationSync::SCOPE_REQUIRED, 'declaration:read'],
            isFirstLogin: false
        );

        event($event);

        $this->assertPartyVerificationSyncNotQueued();
    }

    #[Test]
    public function admin_login_with_read_scope_skips_party_verification_sync(): void
    {
        Bus::fake();

        ['legalEntity' => $legalEntity, 'user' => $user] = $this->createFixture('ADMIN');

        $event = new EHealthUserLogin(
            user: $user,
            legalEntity: $legalEntity,
            authUserUUID: $user->uuid,
            scopes: [PartyVerificationSync::SCOPE_REQUIRED, 'declaration:read'],
            isFirstLogin: false
        );

        event($event);

        $this->assertPartyVerificationSyncNotQueued();
    }

    #[Test]
    public function hr_login_without_owner_admin_queues_party_verification_sync(): void
    {
        Bus::fake();

        ['legalEntity' => $legalEntity, 'user' => $user] = $this->createFixture('HR');

        Cache::forget('party_verification_last_run:' . $legalEntity->id);

        $event = new EHealthUserLogin(
            user: $user,
            legalEntity: $legalEntity,
            authUserUUID: $user->uuid,
            scopes: [PartyVerificationSync::SCOPE_REQUIRED, 'employee:read'],
            isFirstLogin: false
        );

        event($event);

        Bus::assertBatched(function ($batch) {
            return $batch->name === 'Party Verification Status Sync'
                && count($batch->jobs) === 1
                && $batch->jobs[0] instanceof PartyVerificationSync
                && ($batch->options['sync_entity'] ?? null) === LegalEntity::ENTITY_PARTY_VERIFICATION;
        });

        $this->assertTrue(Cache::has('party_verification_last_run:' . $legalEntity->id));
    }

    #[Test]
    public function hr_login_within_24_hours_skips_due_to_cache(): void
    {
        Bus::fake();

        ['legalEntity' => $legalEntity, 'user' => $user] = $this->createFixture('HR');

        Cache::put('party_verification_last_run:' . $legalEntity->id, true, 86400);

        $event = new EHealthUserLogin(
            user: $user,
            legalEntity: $legalEntity,
            authUserUUID: $user->uuid,
            scopes: [PartyVerificationSync::SCOPE_REQUIRED],
            isFirstLogin: false
        );

        event($event);

        $this->assertPartyVerificationSyncNotQueued();
    }
}
