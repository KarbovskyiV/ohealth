<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\EHealth\Services\TokenStorage;
use App\Classes\eHealth\Middleware\CheckSessionToken;
use App\Http\Controllers\Auth\EHealthLoginController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class EHealthFailedLoginTest extends TestCase
{
    public function test_failed_local_login_preserves_the_original_error_on_the_next_request(): void
    {
        $this->prepareFailedLogin();
        Http::fake(['*' => Http::response(['data' => []])]);

        $response = $this->abortLogin('Original login listener error');

        // Check the middleware that used to replace the original failure with "Invalid session".
        $nextResponse = new CheckSessionToken(app(TokenStorage::class))->handle(
            Request::create('/livewire/update', 'POST'),
            fn () => response('next request reached')
        );

        $this->assertSame('next request reached', $nextResponse->getContent());
        $this->assertGuest('ehealth');
        $this->assertGuest('web');
        $this->assertSame(route('dev.login'), $response->getTargetUrl());
        $this->assertSame('Original login listener error', session('error'));
        $this->assertFalse(session()->has('single_role_login'));
        $this->assertFalse(session()->has('selected_legal_entity_uuid_for_ehealth'));
        $this->assertFalse(app(TokenStorage::class)->hasBearerToken());
    }

    #[DataProvider('logoutFailures')]
    public function test_remote_logout_failure_does_not_mask_the_login_error(string $failure): void
    {
        $this->prepareFailedLogin();
        Http::fake(['*' => $failure === 'connection'
            ? Http::failedConnection()
            : Http::response(['error' => ['message' => 'Token expired']], 401)]);

        $response = $this->abortLogin('Original login listener error');

        $this->assertSame(route('dev.login'), $response->getTargetUrl());
        $this->assertSame('Original login listener error', session('error'));
        $this->assertGuest('ehealth');
        $this->assertFalse(app(TokenStorage::class)->hasBearerToken());
    }

    public static function logoutFailures(): array
    {
        return [['unauthorized'], ['connection']];
    }

    public function test_login_marker_without_a_token_does_not_trigger_remote_logout(): void
    {
        $this->app['env'] = 'production';
        Session::put(config('ehealth.api.auth_ehealth'), 2);
        Http::fake();

        $response = $this->abortLogin('auth.login.error.legal_entity.invalid_session');

        Http::assertNothingSent();
        $this->assertSame(route('login'), $response->getTargetUrl());
        $this->assertSame(__('auth.login.error.legal_entity.invalid_session'), session('error'));
    }

    private function prepareFailedLogin(): void
    {
        $this->app['env'] = 'local';
        Route::get('/dev/login', fn () => response('Dev login'))->name('dev.login');
        Route::getRoutes()->refreshNameLookups();
        config(['ehealth.api.domain' => 'https://ehealth.example.test']);
        Session::start();
        Session::put(config('ehealth.api.oauth.bearer_token'), 'test-access-token');
        Session::put('single_role_login', 'REORGANIZATION_OWNER');
        Session::put('selected_legal_entity_uuid_for_ehealth', '11111111-1111-4111-8111-111111111111');

        // No database or live eHealth login is needed to reproduce the partially authenticated state.
        $user = new User();
        $user->forceFill(['id' => 2, 'uuid' => '22222222-2222-4222-8222-222222222222']);
        $this->actingAs($user, 'ehealth');
        Session::put(Auth::guard('ehealth')->getName(), $user->uuid);
    }

    private function abortLogin(string $error): \Illuminate\Http\RedirectResponse
    {
        return new ReflectionMethod(EHealthLoginController::class, 'breakAuth')
            ->invoke(new EHealthLoginController(), $error);
    }
}
