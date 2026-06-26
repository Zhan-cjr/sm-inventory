<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\CustomerAddress;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class EcommerceAddressTest extends TestCase
{
    use DatabaseTransactions;

    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::first();
        if (!$org) {
            $org = Organization::create([
                'name' => 'Toserba Selamat',
                'code' => 'TS-' . rand(1000, 9999),
            ]);
        }

        $this->customer = Customer::create([
            'id' => 'CST-TEST-01',
            'organization_id' => $org->id,
            'name' => 'John Doe',
            'phone' => '08123456789',
            'points' => 0,
        ]);
    }

    public function test_can_create_new_address_and_first_address_is_primary()
    {
        $payload = [
            'label' => 'Rumah',
            'recipient_name' => 'John',
            'recipient_phone' => '08123456789',
            'full_address' => 'Jl. Test No. 1',
            'biteship_area_id' => 'IDNP21',
        ];

        $response = $this->postJson('/api/v1/ecommerce/customers/addresses', $payload, [
            'X-Member-ID' => $this->customer->id
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $this->customer->id,
            'label' => 'Rumah',
            'is_primary' => true,
        ]);
    }

    public function test_can_get_customer_addresses()
    {
        CustomerAddress::create([
            'customer_id' => $this->customer->id,
            'label' => 'Rumah',
            'recipient_name' => 'John',
            'recipient_phone' => '08123456789',
            'full_address' => 'Jl. Test No. 1',
            'is_primary' => true,
        ]);

        $response = $this->getJson('/api/v1/ecommerce/customers/addresses', [
            'X-Member-ID' => $this->customer->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $this->assertEquals('Rumah', $response->json()[0]['label']);
    }

    public function test_can_set_primary_address_and_others_become_non_primary()
    {
        $addr1 = CustomerAddress::create([
            'customer_id' => $this->customer->id,
            'label' => 'Rumah',
            'recipient_name' => 'John',
            'recipient_phone' => '08123456789',
            'full_address' => 'Jl. Test No. 1',
            'is_primary' => true,
        ]);

        $addr2 = CustomerAddress::create([
            'customer_id' => $this->customer->id,
            'label' => 'Kantor',
            'recipient_name' => 'John Office',
            'recipient_phone' => '08123456789',
            'full_address' => 'Jl. Test No. 2',
            'is_primary' => false,
        ]);

        $response = $this->putJson("/api/v1/ecommerce/customers/addresses/{$addr2->id}/set-primary", [], [
            'X-Member-ID' => $this->customer->id
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('customer_addresses', [
            'id' => $addr2->id,
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('customer_addresses', [
            'id' => $addr1->id,
            'is_primary' => false,
        ]);
    }

    public function test_can_delete_address_and_reassign_primary()
    {
        $addr1 = CustomerAddress::create([
            'customer_id' => $this->customer->id,
            'label' => 'Rumah',
            'recipient_name' => 'John',
            'recipient_phone' => '08123456789',
            'full_address' => 'Jl. Test No. 1',
            'is_primary' => true,
        ]);

        $addr2 = CustomerAddress::create([
            'customer_id' => $this->customer->id,
            'label' => 'Kantor',
            'recipient_name' => 'John Office',
            'recipient_phone' => '08123456789',
            'full_address' => 'Jl. Test No. 2',
            'is_primary' => false,
        ]);

        $response = $this->deleteJson("/api/v1/ecommerce/customers/addresses/{$addr1->id}", [], [
            'X-Member-ID' => $this->customer->id
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('customer_addresses', [
            'id' => $addr1->id,
        ]);

        // Karena addr1 dihapus, addr2 otomatis harus jadi primary
        $this->assertDatabaseHas('customer_addresses', [
            'id' => $addr2->id,
            'is_primary' => true,
        ]);
    }
}
