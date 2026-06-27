<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BiteshipService;
use App\Models\Organization;
use Illuminate\Support\Facades\Http;

class BiteshipTestOrders extends Command
{
    protected $signature = 'biteship:generate-test-orders';
    protected $description = 'Generate 2 test orders for Biteship API Activation (one delivered, one cancelled)';

    public function handle()
    {
        $this->info("Creating 2 test orders in Biteship Sandbox...");
        $biteship = new BiteshipService();

        if (!$biteship->isConfigured()) {
            $this->error("Biteship API Key belum dikonfigurasi.");
            return;
        }

        $org = Organization::first();
        $apiKey = $org->biteship_api_key;

        $originPayload = [
            'contact_name' => 'Toserba Selamat',
            'contact_phone' => '081234567890',
            'address' => 'Jl. Kebon Sirih',
            'postal_code' => '10110',
            'coordinate' => [
                'latitude' => -6.182084,
                'longitude' => 106.831518,
            ],
            'collection_method' => 'drop_off',
        ];

        $payload = [
            'shipper_contact_name' => 'Toserba Selamat',
            'shipper_contact_phone' => '081234567890',
            'shipper_contact_email' => 'homepc.zhan@gmail.com',
            'origin' => $originPayload,
            'destination_contact_name' => 'Amnal Test',
            'destination_contact_phone' => '085861094485',
            'destination_address' => 'Jl. Sudirman',
            'destination_coordinate' => [
                'latitude' => -6.221469,
                'longitude' => 106.804961,
            ],
            'destination_postal_code' => '10270',
            'courier_company' => 'biteship',
            'courier_type' => 'test',
            'delivery_type' => 'later',
            'delivery_date' => date('Y-m-d'),
            'delivery_time' => date('H:i', strtotime('+1 hour')),
            'items' => [
                [
                    'name' => 'Test Item',
                    'value' => 10000,
                    'quantity' => 1,
                    'weight' => 500
                ]
            ]
        ];

        // 1. Create Delivered Order
        $this->info("Creating order 1 (for delivered)...");
        $res1 = $biteship->createOrder($payload);
        if (!$res1['success']) {
            $this->error("Failed to create order 1: " . json_encode($res1));
            return;
        }
        $order1Id = $res1['order']['id'];
        $this->info("Order 1 ID: " . $order1Id);
        
        // 2. Create Cancelled Order
        $this->info("Creating order 2 (for cancelled)...");
        $res2 = $biteship->createOrder($payload);
        if (!$res2['success']) {
            $this->error("Failed to create order 2: " . json_encode($res2));
            return;
        }
        $order2Id = $res2['order']['id'];
        $this->info("Order 2 ID: " . $order2Id);

        $this->info("--------------------------------------------------");
        $this->info("BERHASIL! Silakan gunakan ID berikut untuk form Aktivasi Biteship:");
        $this->info("ID Pesanan Terkirim (DELIVERED) : " . $order1Id);
        $this->info("ID Pesanan Dibatalkan (CANCELLED) : " . $order2Id);
        $this->info("");
        $this->info("PENTING: Jangan langsung dimasukkan ke form!");
        $this->info("1. Buka Dashboard Biteship (https://dashboard.biteship.com/orders)");
        $this->info("2. Cari Order ID pertama, buka detailnya, dan klik tombol 'Simulate' hingga statusnya DELIVERED.");
        $this->info("3. Cari Order ID kedua, buka detailnya, dan klik tombol 'Simulate' hingga statusnya CANCELLED.");
        $this->info("4. Setelah itu barulah masukkan ID-ID tersebut ke dalam formulir Aktivasi.");
    }
}
