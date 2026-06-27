<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BiteshipService
{
    private $apiKey;
    private $baseUrl = 'https://api.biteship.com/v1';

    public function __construct()
    {
        $org = Organization::first();
        $this->apiKey = $org ? $org->biteship_api_key : null;
    }

    public function isConfigured()
    {
        return !empty($this->apiKey);
    }

    public function getRates($originLat, $originLon, $destLat, $destLon, $weightInGrams, $destAreaId = null, $couriers = "jne,jnt,sicepat,anteraja,grab,gojek")
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Biteship API Key belum dikonfigurasi di Pengaturan Admin.'];
        }

        try {
            $payload = [
                'origin_latitude' => (float) $originLat,
                'origin_longitude' => (float) $originLon,
                'couriers' => $couriers,
                'items' => [
                    [
                        'name' => 'Pesanan E-Commerce',
                        'value' => 50000, // Nilai default asuransi dasar
                        'weight' => (int) $weightInGrams,
                        'quantity' => 1
                    ]
                ]
            ];

            // If area ID is provided, use it. Otherwise fallback to lat/lon
            if ($destAreaId) {
                $payload['destination_area_id'] = $destAreaId;
            } else {
                $payload['destination_latitude'] = (float) $destLat;
                $payload['destination_longitude'] = (float) $destLon;
            }

            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Content-Type'  => 'application/json'
            ])->post($this->baseUrl . '/rates/couriers', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $rates = $data['pricing'] ?? [];
                
                // Aplikasikan fitur rahasia "Markup Ongkir"
                $org = Organization::first();
                $markupType = $org->logistics_markup_type ?? 'NONE';
                $markupValue = (float) ($org->logistics_markup_value ?? 0);

                foreach ($rates as &$rate) {
                    $originalPrice = $rate['price'];
                    $newPrice = $originalPrice;

                    if ($markupType === 'FIXED') {
                        $newPrice += $markupValue;
                    } elseif ($markupType === 'PERCENTAGE') {
                        $newPrice += ($originalPrice * $markupValue / 100);
                    }

                    $rate['price'] = $newPrice; // Harga ini yang akan dilihat oleh frontend
                    // Harga asli dihapus dari respons agar tidak bocor ke sisi klien
                }

                return ['success' => true, 'rates' => $rates];
            }

            Log::error('Biteship Get Rates Error: ' . $response->body());
            return ['success' => false, 'message' => 'Gagal mendapatkan tarif dari Biteship.'];

        } catch (\Exception $e) {
            Log::error('Biteship Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem saat menghubungi Biteship.'];
        }
    }

    public function searchArea($query)
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Biteship API Key belum dikonfigurasi.'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->get($this->baseUrl . '/maps/areas', [
                'countries' => 'ID',
                'input' => $query,
                'type' => 'single'
            ]);

            if ($response->successful()) {
                return ['success' => true, 'areas' => $response->json('areas') ?? []];
            }

            Log::error('Biteship Search Area Error: ' . $response->body());
            return ['success' => false, 'message' => 'Gagal mencari area dari Biteship.'];

        } catch (\Exception $e) {
            Log::error('Biteship Search Area Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem saat menghubungi Biteship.'];
        }
    }
    public function createOrder($payload)
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Biteship API Key belum dikonfigurasi.'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Content-Type'  => 'application/json'
            ])->post($this->baseUrl . '/orders', $payload);

            if ($response->successful()) {
                return ['success' => true, 'order' => $response->json()];
            }

            Log::error('Biteship Create Order Error: ' . $response->body());
            return ['success' => false, 'message' => 'Gagal membuat pesanan (pickup) di Biteship.'];

        } catch (\Exception $e) {
            Log::error('Biteship Create Order Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem saat menghubungi Biteship.'];
        }
    }
}
