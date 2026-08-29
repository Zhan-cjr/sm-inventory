<?php

namespace App\Services;

use App\Contracts\PpobProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigiflazzService implements PpobProviderInterface
{
    protected $username;
    protected $apiKey;
    protected $baseUrl = 'https://api.digiflazz.com/v1';

    public function __construct()
    {
        $this->username = env('DIGIFLAZZ_USERNAME');
        $mode = env('DIGIFLAZZ_MODE', 'development');
        $this->apiKey = $mode === 'production' ? env('DIGIFLAZZ_PROD_KEY') : env('DIGIFLAZZ_DEV_KEY');
    }

    private function generateSign($command)
    {
        return md5($this->username . $this->apiKey . $command);
    }

    public function checkBalance()
    {
        $payload = [
            'cmd' => 'deposit',
            'username' => $this->username,
            'sign' => $this->generateSign('depo')
        ];

        try {
            $response = Http::post($this->baseUrl . '/cek-saldo', $payload);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Digiflazz Check Balance Error: ' . $e->getMessage());
            return null;
        }
    }

    public function getPriceList()
    {
        $payload = [
            'cmd' => 'prepaid',
            'username' => $this->username,
            'sign' => $this->generateSign('pricelist')
        ];

        try {
            $response = Http::post($this->baseUrl . '/price-list', $payload);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Digiflazz Price List Error: ' . $e->getMessage());
            return null;
        }
    }

    public function topup(string $buyerSkuCode, string $customerNo, string $refId, array $additionalInfo = [])
    {
        $payload = [
            'username' => $this->username,
            'buyer_sku_code' => $buyerSkuCode,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $this->generateSign($refId)
        ];

        try {
            $response = Http::post($this->baseUrl . '/transaction', $payload);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Digiflazz Topup Error: ' . $e->getMessage());
            return [
                'data' => [
                    'status' => 'Gagal',
                    'message' => 'Exception: ' . $e->getMessage()
                ]
            ];
        }
    }

    public function checkStatus(string $skuCode, string $customerNo, string $refId)
    {
        // Digiflazz check status uses the exact same payload as topup
        return $this->topup($skuCode, $customerNo, $refId);
    }
}
