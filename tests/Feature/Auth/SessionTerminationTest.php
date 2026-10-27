<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\EHealth\Services\TokenStorage;
use App\Auth\SessionBinder;
use App\Classes\eHealth\Api\Auth as AuthApi;
use App\Classes\eHealth\EHealthResponse;
use App\Classes\eHealth\Middleware\CheckSessionToken;
use App\Events\LegalEntityCreate;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Http\Controllers\Auth\EHealthLoginController;
use App\Http\Middleware\EnsureSingleSession;
use App\Livewire\Actions\Logout;
use App\Livewire\LegalEntity\CreateLegalEntity;
use App\Livewire\LegalEntity\Forms\LegalEntitiesForms;
use App\Livewire\LegalEntity\LegalEntity as LegalEntityComponent;
use App\Models\LegalEntity;
use App\Models\Role;
use App\Models\User;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class SessionTerminationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'database']);
        Http::preventStrayRequests();
    }

    #[DataProvider('terminationScenarios')]
    public function test_inactivity_timeout_ends_the_bound_session(string $guard, string $defaultGuard, string $remoteResult): void
    {
        [$user, $request, $sessionId] = $this->signIn($guard, $defaultGuard);
        $this->expectRemoteLogout($guard, $remoteResult);
        Session::put('last_activity', now()->subMinutes(61)->timestamp);

        $response = new EnsureSingleSession(new SessionBinder())->handle(
            $request,
            fn ($request) => new CheckSessionToken(app(TokenStorage::class))->handle(
                $request,
                fn () => $this->fail('An expired session reached the protected action.')
            )
        );

        $this->assertSame(route('login'), $response->getTargetUrl());
        $this->assertSame(__('auth.session_expired'), session('error'));
        $this->assertSessionEnded($user, $sessionId, invalidated: false);
    }

    #[DataProvider('terminationScenarios')]
    public function test_normal_logout_ends_the_bound_session(string $guard, string $defaultGuard, string $remoteResult): void
    {
        [$user, $request, $sessionId] = $this->signIn($guard, $defaultGuard);
        $this->expectRemoteLogout($guard, $remoteResult);
        $csrfToken = session()->token();

        $response = new Logout()();

        $this->assertSame(route('login'), $response->getTargetUrl());
        $this->assertNotSame($sessionId, Session::getId());
        $this->assertNotSame($csrfToken, session()->token());
        $this->assertFalse(session()->has('error'));
        $this->assertSessionEnded($user, $sessionId);
    }

    public static function terminationScenarios(): array
    {
        return [
            'web without an eHealth token' => ['web', 'web', 'none'],
            'eHealth with its default guard' => ['ehealth', 'ehealth', 'success'],
            'eHealth on a request whose default guard is web' => ['ehealth', 'web', 'success'],
            'eHealth logout returns 401' => ['ehealth', 'web', 'unauthorized'],
            'eHealth logout loses its connection' => ['ehealth', 'web', 'connection'],
        ];
    }

    public function test_an_active_web_session_without_an_ehealth_token_continues(): void
    {
        [$user, $request, $sessionId] = $this->signIn('web', 'web');
        $this->expectRemoteLogout('web', 'none');
        Session::put('last_activity', now()->subMinutes(59)->timestamp);

        $response = new CheckSessionToken(app(TokenStorage::class))->handle($request, fn () => response('active'));

        $this->assertSame('active', $response->getContent());
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertSame($sessionId, $user->fresh()->sessionId);
        $this->assertFalse(session()->has('error'));
    }

    public function test_successful_legal_entity_creation_logs_out_the_web_user(): void
    {
        [$user, $request, $sessionId] = $this->signIn('web', 'web');
        $this->expectRemoteLogout('web', 'none');
        Event::fake([LegalEntityCreate::class]);
        $typeId = DB::table('legal_entity_types')->where('name', LegalEntity::TYPE_PRIMARY_CARE)->value('id')
            ?? DB::table('legal_entity_types')->insertGetId(['name' => LegalEntity::TYPE_PRIMARY_CARE]);
        $entity = LegalEntity::create([
            'uuid' => (string) Str::uuid(),
            'legal_entity_type_id' => $typeId,
            'status' => 'NEW',
            'is_active' => false,
        ]);
        setPermissionsTeamId($entity->id);
        foreach (['web', 'ehealth'] as $guard) {
            foreach (['OWNER', 'REORGANIZATION_OWNER'] as $role) {
                Role::findOrCreate($role, $guard);
            }
        }

        // Isolate the final creation step from signing and upstream API data mapping.
        // Keep the real createUser(), guard switching, success handler and Logout action.
        $component = Mockery::mock(CreateLegalEntity::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $component->legalEntityForm = new LegalEntitiesForms($component, 'legalEntityForm');
        $component->legalEntityForm->owner = ['email' => $user->email];
        foreach (['legalEntity' => $entity, 'entityCacheKey' => 'test.entity', 'ownerCacheKey' => 'test.owner', 'stepCacheKey' => 'test.steps'] as $property => $value) {
            new ReflectionProperty(LegalEntityComponent::class, $property)->setValue($component, $value);
        }
        $component->shouldReceive('createNewLegalEntity')->once()->andReturn($entity);
        $component->shouldReceive('createEmployeeRequest')->once()->with($entity, [], 'request-uuid')->andReturnNull();

        $response = new ReflectionMethod(LegalEntityComponent::class, 'handleSuccessResponse')->invoke(
            $component,
            ['urgent' => ['employee_request_id' => 'request-uuid']]
        );

        $this->assertSame(route('login'), $response->getTargetUrl());
        $this->assertSame(__('forms.le_create_successfully'), session('success'));
        $this->assertFalse(session()->has('error'));
        $this->assertSame('web', Auth::getDefaultDriver());
        $this->assertDatabaseHas('legal_entities', ['id' => $entity->id]);
        Event::assertDispatched(LegalEntityCreate::class, fn ($event) => $event->authenticatedUser->is($user) && $event->owner->is($user));
        $this->assertSessionEnded($user, $sessionId);
    }

    #[DataProvider('loginGuards')]
    public function test_failed_oauth_login_releases_a_real_bound_user(string $guard): void
    {
        [$user, $request, $sessionId] = $this->signIn($guard, $guard);
        Http::fake(['*' => Http::response(['data' => []])]);

        $response = new ReflectionMethod(EHealthLoginController::class, 'breakAuth')->invoke(
            new EHealthLoginController(),
            'Original login error'
        );

        $this->assertSame(route('login'), $response->getTargetUrl());
        $this->assertSame('Original login error', session('error'));
        if ($guard === 'web') {
            Http::assertNothingSent();
        }
        $this->assertSessionEnded($user, $sessionId);
    }

    public static function loginGuards(): array
    {
        return [['web'], ['ehealth']];
    }

    private function signIn(string $guard, string $defaultGuard): array
    {
        $user = User::forceCreate([
            'uuid' => (string) Str::uuid(),
            'email' => 'session-'.Str::random(12).'@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        Session::start();
        if ($guard === 'ehealth') {
            app(TokenStorage::class)->store([
                'value' => 'test-access-token',
                'expires_at' => now()->addHour()->timestamp,
                'details' => ['refresh_token' => 'test-refresh-token'],
            ]);
        }
        Auth::guard($guard)->login($user);
        new SessionBinder()->bind($user);
        Session::put('single_role_login', 'OWNER');
        Session::save();
        Auth::shouldUse($defaultGuard);
        $request = Request::create('/livewire/update', 'POST', cookies: [
            config('session.cookie').'_binding' => session('session_binding.token'),
        ]);
        $request->setLaravelSession(app('session.store'));
        $this->app->instance('request', $request);

        return [$user, $request, Session::getId()];
    }

    private function expectRemoteLogout(string $guard, string $result): void
    {
        $api = Mockery::mock(AuthApi::class);
        if ($guard === 'web') {
            $api->shouldNotReceive('logout');
        } else {
            $expectation = $api->shouldReceive('logout')->once()->with('test-access-token');
            if ($result === 'unauthorized') {
                $expectation->andThrow(new EHealthResponseException(new EHealthResponse(new PsrResponse(
                    401,
                    ['Content-Type' => 'application/json'],
                    '{"error":{"message":"Token expired"}}'
                ))));
            } elseif ($result === 'connection') {
                $expectation->andThrow(new EHealthConnectionException('Test connection failure'));
            } else {
                $expectation->andReturn(new EHealthResponse(new PsrResponse(200)));
            }
        }
        $this->instance(AuthApi::class, $api);
    }

    private function assertSessionEnded(User $user, string $oldSessionId, bool $invalidated = true): void
    {
        // StartSession saves the cleared session after the middleware/action returns.
        Session::save();
        $this->assertNull($user->fresh()->sessionId);
        if ($invalidated) {
            $this->assertDatabaseMissing('sessions', ['id' => $oldSessionId]);
        } else {
            $payload = DB::table('sessions')->where('id', $oldSessionId)->value('payload');
            $this->assertNotNull($payload);
            $attributes = unserialize(base64_decode($payload), ['allowed_classes' => false]);
            $this->assertArrayNotHasKey(Auth::guard('web')->getName(), $attributes);
            $this->assertArrayNotHasKey(Auth::guard('ehealth')->getName(), $attributes);
            $this->assertArrayNotHasKey(config('ehealth.api.oauth.bearer_token'), $attributes);
        }
        $this->assertFalse(app(TokenStorage::class)->hasBearerToken());
        $this->assertFalse(session()->has('single_role_login'));
        $this->assertFalse(session()->has('session_binding'));
        $cookie = Cookie::queued(config('session.cookie').'_binding');
        $this->assertNotNull($cookie);
        $this->assertLessThanOrEqual(now()->timestamp, $cookie->getExpiresTime());

        // A fresh HTTP request resolves fresh guards from the remaining session data.
        Auth::forgetGuards();
        $this->assertGuest('web');
        $this->assertGuest('ehealth');
        $error = session('error');
        $response = new CheckSessionToken(app(TokenStorage::class))->handle(
            Request::create('/mis/login'),
            fn () => response('guest')
        );
        $this->assertSame('guest', $response->getContent());
        $this->assertSame($error, session('error'));
    }
}
