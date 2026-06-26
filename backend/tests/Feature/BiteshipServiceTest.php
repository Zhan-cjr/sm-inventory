<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Organization;
use App\Services\BiteshipService;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BiteshipServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Update organization pertama agar API key terbaca
        $org = Organization::first();
        if ($org) {
            $org->update([
                'biteship_api_key' => 'biteship_test_key_123',
                'logistics_markup_type' => 'FIXED',
                'logistics_markup_value' => 5000,
            ]);
        } else {
            Organization::create([
                'name' => 'Toserba Selamat',
                'code' => 'TS-' . rand(1000, 9999),
                'biteship_api_key' => 'biteship_test_key_123',
                'logistics_markup_type' => 'FIXED',
                'logistics_markup_value' => 5000,
            ]);
        }
    }

    public function test_search_area_returns_correct_data()
    {
        // Fake Biteship Response
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'areas' => [
                    ['id' => 'IDNP21', 'name' => 'Kemayoran'],
                    ['id' => 'IDNP22', 'name' => 'Kebon Jeruk']
                ]
            ], 200)
        ]);

        $service = new BiteshipService();
        $result = $service->searchArea('Kemayoran');

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['areas']);
        $this->assertEquals('IDNP21', $result['areas'][0]['id']);
    }

    public function test_get_rates_calculates_fixed_markup_correctly()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'pricing' => [
                    ['company' => 'jne', 'type' => 'reg', 'price' => 15000],
                ]
            ], 200)
        ]);

        $service = new BiteshipService();
        $result = $service->getRates(-6.2, 106.8, -6.3, 106.9, 1000);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['rates']);
        
        // Original price = 15000, fixed markup = 5000, total should be 20000
        $this->assertEquals(20000, $result['rates'][0]['price']);
    }

    public function test_get_rates_calculates_percentage_markup_correctly()
    {
        $org = Organization::first();
        $org->update([
            'logistics_markup_type' => 'PERCENTAGE',
            'logistics_markup_value' => 10, // 10%
        ]);

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'pricing' => [
                    ['company' => 'jne', 'type' => 'reg', 'price' => 15000],
                ]
            ], 200)
        ]);

        $service = new BiteshipService();
        $result = $service->getRates(-6.2, 106.8, -6.3, 106.9, 1000);

        $this->assertTrue($result['success']);
        
        // Original price = 15000, 10% markup = 1500, total should be 16500
        $this->assertEquals(16500, $result['rates'][0]['price']);
    }
}
