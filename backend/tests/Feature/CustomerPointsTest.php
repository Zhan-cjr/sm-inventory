<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_points_update_during_sync()
    {
        // 1. Setup organization, branch, user, and customer
        $org = Organization::create([
            'id' => 'aa4fc3f6-0328-4d68-9e46-56ddcf21695e',
            'name' => 'Test Org',
            'point_conversion_rate' => 1000,
        ]);

        $branch = Branch::create([
            'id' => '00000000-0000-0000-0000-000000000002',
            'organization_id' => $org->id,
            'name' => 'Test Branch',
            'code' => 'TSB',
        ]);

        $user = User::create([
            'id' => 1,
            'name' => 'Cashier',
            'email' => 'cashier@test.com',
            'password' => bcrypt('password'),
            'organization_id' => $org->id,
            'branch_id' => $branch->id,
            'role' => 'CASHIER',
        ]);

        $customer = Customer::create([
            'id' => '019e24fa-6859-70e5-adef-1d4496404bad',
            'organization_id' => $org->id,
            'name' => 'Member Test',
            'points' => 0,
            'is_active' => true,
        ]);

        // 2. Mock batch sync request payload
        $payload = [
            'transactions' => [
                [
                    'localId' => 'local-tx-1',
                    'terminalId' => 'term-1',
                    'customerId' => $customer->id,
                    'totalAmount' => 50000,
                    'discountAmount' => 0,
                    'finalAmount' => 50000,
                    'paymentMethod' => 'CASH',
                    'items' => []
                ]
            ],
            'deviceId' => $branch->id,
            'branchId' => $branch->id,
        ];

        // 3. Authenticate and call batch-sync
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/transactions/batch-sync', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // 4. Assert transaction saved with customer_id
        $this->assertDatabaseHas('transactions', [
            'local_transaction_id' => 'local-tx-1',
            'customer_id' => $customer->id,
            'final_amount' => 50000,
        ]);

        // 5. Assert customer points updated: 50000 / 1000 = 50 points
        $customer->refresh();
        $this->assertEquals(50, $customer->points);
        $this->assertEquals('SILVER', $customer->member_tier);
    }

    public function test_login_returns_point_conversion_rate()
    {
        $org = Organization::create([
            'id' => 'aa4fc3f6-0328-4d68-9e46-56ddcf21695e',
            'name' => 'Test Org',
            'point_conversion_rate' => 500,
        ]);

        $user = User::create([
            'name' => 'Cashier',
            'email' => 'cashier@test.com',
            'password' => bcrypt('password'),
            'organization_id' => $org->id,
            'role' => 'CASHIER',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'cashier@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.point_conversion_rate', 500);
    }
}
