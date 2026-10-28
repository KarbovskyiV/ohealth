<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MedicalEvents;

use App\Classes\eHealth\Api\CarePlanActivity;
use App\Services\MedicalEvents\EHealthJobResolver;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class CarePlanActivityLifecycleServiceTest extends TestCase
{
    public function test_signed_create_returns_the_job_without_polling_and_preserves_signed_data(): void
    {
        config(['ehealth.api.domain' => 'https://ehealth.invalid']);
        Http::fake(['*' => Http::response(['data' => ['job_id' => 'job-test']])]);
        $resolver = Mockery::mock(EHealthJobResolver::class);
        $resolver->shouldNotReceive('resolve');
        $this->instance(EHealthJobResolver::class, $resolver);
        $factory = Http::getFacadeRoot();
        $api = new CarePlanActivity($factory);
        $stubs = (function () {
            return $this->stubCallbacks;
        })->call($factory);
        $api->stub($stubs)->preventStrayRequests();

        $result = $api->create('patient-test', 'plan-test', ['signed_data' => 'unchanged-signature', 'signed_data_encoding' => 'base64'])->getData();

        $this->assertSame('job-test', $result['job_id']);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST' && $request['signed_data'] === 'unchanged-signature' && $request['signed_data_encoding'] === 'base64');
    }

    public function test_cancel_returns_the_job_without_polling_and_preserves_the_action_payload(): void
    {
        config(['ehealth.api.domain' => 'https://ehealth.invalid']);
        Http::fake(['*' => Http::response(['data' => ['job_id' => 'job-test']])]);
        $resolver = Mockery::mock(EHealthJobResolver::class);
        $resolver->shouldNotReceive('resolve');
        $this->instance(EHealthJobResolver::class, $resolver);
        $factory = Http::getFacadeRoot();
        $api = new CarePlanActivity($factory);
        $stubs = (function () {
            return $this->stubCallbacks;
        })->call($factory);
        $api->stub($stubs)->preventStrayRequests();
        $payload = ['signed_data' => 'unchanged-signature', 'signed_data_encoding' => 'base64'];

        $result = $api->cancel('patient-test', 'plan-test', 'activity-test', $payload)->getData();

        $this->assertSame('job-test', $result['job_id']);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->method() === 'PATCH' && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/actions/cancel') && $request->data() === $payload);
    }

    public function test_complete_returns_the_job_without_polling_and_preserves_the_action_payload(): void
    {
        config(['ehealth.api.domain' => 'https://ehealth.invalid']);
        Http::fake(['*' => Http::response(['data' => ['job_id' => 'job-test']])]);
        $resolver = Mockery::mock(EHealthJobResolver::class);
        $resolver->shouldNotReceive('resolve');
        $this->instance(EHealthJobResolver::class, $resolver);
        $factory = Http::getFacadeRoot();
        $api = new CarePlanActivity($factory);
        $stubs = (function () {
            return $this->stubCallbacks;
        })->call($factory);
        $api->stub($stubs)->preventStrayRequests();
        $payload = ['detail' => ['status_reason' => ['coding' => [['system' => 'test', 'code' => 'reason']]]], 'outcome_codeable_concept' => [['coding' => [['system' => 'test', 'code' => 'outcome']]]]];

        $result = $api->complete('patient-test', 'plan-test', 'activity-test', $payload)->getData();

        $this->assertSame('job-test', $result['job_id']);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->method() === 'PATCH' && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/actions/complete') && $request->data() === $payload);
    }
}
