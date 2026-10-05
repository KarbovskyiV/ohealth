<?php

declare(strict_types=1);

namespace Tests\Feature\Division;

use App\Classes\eHealth\Api\Division as DivisionApi;
use App\Classes\eHealth\Api\Responses\Collections\DivisionCreate as DivisionResponse;
use App\Classes\eHealth\EHealthResponse;
use App\Dto\Division\Model as DivisionData;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Livewire\Division\DivisionEdit;
use App\Livewire\Division\Forms\DivisionForm;
use App\Models\Division;
use App\Models\LegalEntity;
use App\Repositories\DivisionRepository;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class DivisionUpdateErrorHandlingTest extends TestCase
{
    private function divisionComponent(): DivisionEdit
    {
        $component = new class extends DivisionEdit
        {
            public ?string $redirectUrl = null;

            public function redirect($url, $navigate = false): void
            {
                $this->redirectUrl = $url;
            }
        };
        $component->divisionForm = new DivisionForm($component, 'divisionForm');
        $component->divisionForm->division = [
            'uuid' => 'division-uuid', 'name' => 'Updated division', 'type' => 'CLINIC',
            'email' => 'division@example.com', 'externalId' => null,
            'addresses' => ['residence' => ['type' => 'RESIDENCE', 'settlementId' => 'settlement-uuid']],
            'phones' => [['type' => 'MOBILE', 'number' => '+380501234567']],
            'location' => ['latitude' => 0, 'longitude' => 0],
            'workingHours' => ['mon' => [['08:00', '17:30']], 'sun' => []],
        ];

        $entity = new LegalEntity();
        $entity->id = 1;
        $this->app->instance('legalEntity', $entity);

        return $component;
    }

    private function mockResponse(array $overrides = [], ?string $uuid = 'division-uuid'): void
    {
        $response = $this->mock(EHealthResponse::class);
        $response->shouldReceive('validate')->once()->andReturn(new DivisionResponse(array_replace([
            'uuid' => 'division-uuid', 'name' => 'API division', 'type' => 'CLINIC',
            'email' => 'division@example.com', 'status' => 'ACTIVE',
            'addresses' => [['type' => 'RESIDENCE', 'settlement_id' => 'settlement-uuid']],
            'phones' => [['type' => 'MOBILE', 'number' => '+380501234567']],
        ], $overrides)));
        $this->mock(DivisionApi::class)->shouldReceive('update')->once()
            ->with($uuid, Mockery::type('array'))->andReturn($response);
    }

    private function expectChannelError(string $channel): void
    {
        $logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('error')->once();
        Log::shouldReceive('channel')->once()->with($channel)->andReturn($logger);
    }

    private function assertFailure(DivisionEdit $component, string $message): void
    {
        $this->assertSame(__($message), session('error'));
        $this->assertNull($component->redirectUrl);
        $this->assertNull(session('success'));
    }

    public function test_maps_request_and_saves_response_into_existing_division(): void
    {
        $component = $this->divisionComponent();
        $input = $component->divisionForm->division;
        $division = new Division();
        $division->id = 42;
        $response = $this->mock(EHealthResponse::class);
        $response->shouldReceive('validate')->once()->andReturn(new DivisionResponse([
            'uuid' => 'division-uuid', 'name' => 'API division', 'type' => 'CLINIC',
            'email' => 'division@example.com', 'status' => 'ACTIVE',
            'addresses' => [['type' => 'RESIDENCE', 'settlement_id' => 'settlement-uuid']],
            'phones' => [['type' => 'MOBILE', 'number' => '+380501234567']],
        ]));
        $this->mock(DivisionApi::class)->shouldReceive('update')->once()
            ->with('division-uuid', Mockery::on(function (array $payload): bool {
                $this->assertSame('Updated division', $payload['name']);
                $this->assertSame(['latitude' => 0, 'longitude' => 0], $payload['location']);
                $this->assertSame([['08.00', '17.30']], $payload['working_hours']['mon']);
                $this->assertSame([], $payload['working_hours']['sun']);
                $this->assertSame('settlement-uuid', $payload['addresses'][0]['settlement_id']);
                $this->assertArrayNotHasKey('uuid', $payload);

                return true;
            }))->andReturn($response);
        $this->mock(DivisionRepository::class)->shouldReceive('saveMappedDivision')->once()
            ->with(Mockery::on(function (DivisionData $data): bool {
                $this->assertSame('API division', $data->name);
                $this->assertSame('settlement-uuid', $data->addresses[0]->settlementId);
                $this->assertSame('+380501234567', $data->phones[0]->number);

                return true;
            }), $division, legalEntity())->andReturn($division);

        $component->divisionUpdate($division);

        $this->assertSame(42, $division->id);
        $this->assertSame($input, $component->divisionForm->division);
        $this->assertSame(route('division.index', [legalEntity()]), $component->redirectUrl);
        $this->assertSame(__('forms.success_response'), session('success'));
        $this->assertNull(session('error'));
    }

    public function test_draft_update_passes_null_uuid_and_preserves_local_model(): void
    {
        $component = $this->divisionComponent();
        unset($component->divisionForm->division['uuid']);
        $division = new Division();
        $division->id = 42;
        $this->mockResponse(uuid: null);
        $this->mock(DivisionRepository::class)->shouldReceive('saveMappedDivision')->once()
            ->with(Mockery::type(DivisionData::class), $division, legalEntity())->andReturn($division);

        $component->divisionUpdate($division);

        $this->assertSame(42, $division->id);
        $this->assertSame(__('forms.success_response'), session('success'));
        $this->assertSame(route('division.index', [legalEntity()]), $component->redirectUrl);
        $this->assertNull(session('error'));
    }

    public function test_api_failure_does_not_attempt_database_saving(): void
    {
        $component = $this->divisionComponent();
        $this->mock(DivisionApi::class)->shouldReceive('update')->once()
            ->andThrow(new RuntimeException('API failed'));
        $this->mock(DivisionRepository::class)->shouldNotReceive('saveMappedDivision');
        $this->expectChannelError('e_health_errors');

        $component->divisionUpdate(new Division());

        $this->assertFailure($component, 'errors.ehealth.messages.request_error');
    }

    public function test_response_validation_failure_does_not_attempt_database_saving(): void
    {
        $component = $this->divisionComponent();
        $response = $this->mock(EHealthResponse::class);
        $response->shouldReceive('validate')->once()->andThrow(new EHealthValidationException([]));
        $this->mock(DivisionApi::class)->shouldReceive('update')->once()->andReturn($response);
        $this->mock(DivisionRepository::class)->shouldNotReceive('saveMappedDivision');
        $this->expectChannelError('e_health_errors');

        $component->divisionUpdate(new Division());

        $this->assertFailure($component, 'errors.ehealth.messages.request_error');
    }

    public function test_empty_response_does_not_attempt_database_saving(): void
    {
        $component = $this->divisionComponent();
        $response = $this->mock(EHealthResponse::class);
        $response->shouldReceive('validate')->once()->andReturn(new DivisionResponse());
        $this->mock(DivisionApi::class)->shouldReceive('update')->once()->andReturn($response);
        $this->mock(DivisionRepository::class)->shouldNotReceive('saveMappedDivision');
        $this->expectChannelError('e_health_errors');

        $component->divisionUpdate(new Division());

        $this->assertFailure($component, 'errors.ehealth.messages.request_error');
    }

    public function test_request_mapping_failure_does_not_call_api_or_save(): void
    {
        $component = $this->divisionComponent();
        $component->divisionForm->division['name'] = [];
        $this->mock(DivisionApi::class)->shouldNotReceive('update');
        $this->mock(DivisionRepository::class)->shouldNotReceive('saveMappedDivision');
        $this->expectChannelError('e_health_errors');

        $component->divisionUpdate(new Division());

        $this->assertFailure($component, 'errors.ehealth.messages.request_error');
    }

    public function test_response_mapping_failure_uses_database_error_handling(): void
    {
        $component = $this->divisionComponent();
        $this->mockResponse(['name' => []]);
        $this->mock(DivisionRepository::class)->shouldNotReceive('saveMappedDivision');
        $this->expectChannelError('db_errors');

        $component->divisionUpdate(new Division());

        $this->assertFailure($component, 'errors.database.messages.save_error');
    }

    public function test_database_failure_does_not_redirect_or_flash_success(): void
    {
        $component = $this->divisionComponent();
        $this->mockResponse();
        $this->mock(DivisionRepository::class)->shouldReceive('saveMappedDivision')->once()
            ->andThrow(new RuntimeException('Database failed'));
        $this->expectChannelError('db_errors');

        $component->divisionUpdate(new Division());

        $this->assertFailure($component, 'errors.database.messages.save_error');
    }
}
