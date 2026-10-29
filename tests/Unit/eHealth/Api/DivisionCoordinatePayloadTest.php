<?php

declare(strict_types=1);

namespace Tests\Unit\eHealth\Api;

use App\Classes\eHealth\Api\Division;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DivisionCoordinatePayloadTest extends TestCase
{
    public static function coordinates(): array
    {
        return [
            '14 decimal places' => ['30.99999999999999', '50.99999999999999', '30.99999999999999', '50.99999999999999'],
            '15 decimal places' => ['30.999999999999999', '50.999999999999999', '30.99999999999999', '50.99999999999999'],
            'reported coordinates' => ['30.1234567890123456789', '50.324231234567890123456789', '30.12345678901234', '50.32423123456789'],
            'beyond float precision' => ['89.999999999999999999', '-179.999999999999999999', '89.99999999999999', '-179.99999999999999'],
            'normalized boundary' => ['89.99999999999999', '179.99999999999999', '89.99999999999999', '179.99999999999999'],
            'commas and trailing zeros' => ['50,45000000000000000', '30,52000000000000000', '50.45', '30.52'],
            'scientific notation' => ['8.999999999999999999e1', '-1.799999999999999999e2', '89.99999999999999', '-179.99999999999999'],
            'small scientific notation' => ['1.234567890123456789e-10', '-1.234567890123456789e-10', '0.00000000012345', '-0.00000000012345'],
            'leading zeros and plus' => ['+050.450000000', '-030.520000000', '50.45', '-30.52'],
            'fraction only' => [',1234567890', '-,0000000000190', '0.123456789', '-0.000000000019'],
            'trailing separator' => ['30.', '-50.', '30', '-50'],
            'zeros' => ['0,0000', '-0.0000', '0', '0'],
            'integer values' => [0, 0, '0', '0'],
            'float values' => [50.123456789, 30.987654321, '50.123456789', '30.987654321'],
            'below supported scale' => ['1e-15', '-1e-15', '0', '0'],
        ];
    }

    #[DataProvider('coordinates')]
    public function test_create_and_update_send_numeric_coordinates_without_rounding(
        string|int|float $latitude,
        string|int|float $longitude,
        string $expectedLatitude,
        string $expectedLongitude
    ): void {
        $data = [
            'name' => '89.999999999999999999',
            'external_id' => '00123',
            'phones' => [['number' => '+380501234567']],
            'other' => ['latitude' => '89.999999999999999999'],
            'location' => ['latitude' => $latitude, 'longitude' => $longitude],
        ];
        $expected = '{"name":"89.999999999999999999","external_id":"00123",'
            . '"phones":[{"number":"+380501234567"}],"other":{"latitude":"89.999999999999999999"},'
            . '"location":{"latitude":' . $expectedLatitude . ',"longitude":' . $expectedLongitude . '}}';
        $api = $this->fakeApi($expected);

        $api->create($data);
        $api->update('division-uuid', $data);
        $api->update(null, $data);

        Http::assertSentCount(3);
    }

    public static function optionalLocations(): array
    {
        return [
            'absent' => [['name' => 'Division'], '{"name":"Division"}'],
            'null' => [['location' => null], '{"location":null}'],
            'empty coordinates' => [
                ['location' => ['latitude' => null, 'longitude' => null]],
                '{"location":{"latitude":null,"longitude":null}}',
            ],
        ];
    }

    #[DataProvider('optionalLocations')]
    public function test_preserves_optional_location_values(array $data, string $expected): void
    {
        $this->fakeApi($expected)->create($data);

        Http::assertSentCount(1);
    }

    private function fakeApi(string $expected): Division
    {
        config(['ehealth.api.domain' => 'https://ehealth.example']);
        Http::fake(function (Request $request) use ($expected) {
            $this->assertSame($expected, $request->body());
            $this->assertTrue(json_validate($request->body()));
            $this->assertContains('application/json', $request->header('Content-Type'));

            return Http::response(['data' => []]);
        });

        $factory = Http::getFacadeRoot();
        $api = new Division($factory);
        $api->stub((function () {
            return $this->stubCallbacks;
        })->call($factory));
        $api->preventStrayRequests();

        return $api;
    }
}
