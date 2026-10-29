<?php

declare(strict_types=1);

namespace Tests\Unit\Employee;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeSyncUserIdLookupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.sync_regression', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
        DB::setDefaultConnection('sync_regression');
        Schema::create('users', static function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->unsignedBigInteger('person_id')->nullable();
        });
        DB::table('users')->insert(['id' => 7, 'email' => 'employee@example.test']);
    }

    protected function tearDown(): void
    {
        DB::purge('sync_regression');
        parent::tearDown();
    }

    public function test_lookup_returns_a_scalar_without_hydrating_user_or_eager_loading_person(): void
    {
        $previous = Model::preventsAccessingMissingAttributes();
        Model::preventAccessingMissingAttributes();
        DB::enableQueryLog();

        try {
            $this->assertSame(7, (new UserRepository())->findIdByEmail('employee@example.test'));
            $this->assertCount(1, DB::getQueryLog());
            $this->assertArrayHasKey('person', (new User())->newQuery()->getEagerLoads());
        } finally {
            Model::preventAccessingMissingAttributes($previous);
        }
    }

    public function test_unknown_email_returns_null(): void
    {
        $this->assertNull((new UserRepository())->findIdByEmail('unknown@example.test'));
    }

    public function test_missing_email_does_not_query_users(): void
    {
        DB::enableQueryLog();

        $this->assertNull((new UserRepository())->findIdByEmail(null));
        $this->assertSame([], DB::getQueryLog());
    }
}
