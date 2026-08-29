<?php

namespace App\Services;

use App\Contracts\PpobProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmaService implements PpobProviderInterface
{
    protected $userId;
    protected $apiKey; // PIN
    protected $baseUrl;

    public function __construct()
    {
        $this->userId = env('AMA_USER_ID');
        $this->apiKey = env('AMA_PIN');
        $this->baseUrl = rtrim(env('AMA_API_URL', 'https://api.ama.com/v1'), '/'); // fallback url if not set
    }

    private function generateSign($payloadJson)
    {
        // PIN di encrypt menggunakan HMAC 256. Format: HMAC256(request_json_data,eKEY)
        return hash_hmac('sha256', $payloadJson, $this->apiKey);
    }

    private function buildRequestPayload($method, $skuCode, $customerNo, $refId, $additionalInfo = [])
    {
        $infoStr = '';
        if (!empty($additionalInfo)) {
            // For example: Kode Toko|Nama Kasir
            $infoStr = implode('|', $additionalInfo);
        }

        return [
            'gwRq' => [
                'method' => $method,
                'userid' => $this->userId,
                'trxid' => $refId,
                'id' => $customerNo,
                'productid' => $skuCode,
                'trxdate' => date('YmdHis'),
                'add_info' => $infoStr
            ]
        ];
    }

    public function checkBalance()
    {
        // Note: The provided API doc does not specify a check balance endpoint for AMA.
        // Assuming it will be documented later, returning a dummy success for now.
        return ['data' => ['status' => 'success', 'balance' => 0]];
    }

    public function getPriceList()
    {
        // Based on PDF: <url_endpoint_product>?userid=<userid>
        // with X-API-KEY header: HMAC256(userid, eKEY)
        
        $sign = hash_hmac('sha256', $this->userId, $this->apiKey);
        
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $sign
            ])->get($this->baseUrl . '/product', [
                'userid' => $this->userId
            ]);
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error('AMA Price List Error: ' . $e->getMessage());
            return null;
        }
    }

    public function topup(string $skuCode, string $customerNo, string $refId, array $additionalInfo = [])
    {
        // method depends on product. For standard topup: 'topUpRequest', 'INQ', or 'PAY'
        // Assuming 'topUpRequest' for regular transactions for now.
        $payload = $this->buildRequestPayload('topUpRequest', $skuCode, $customerNo, $refId, $additionalInfo);
        
        $payloadJson = json_encode($payload);
        $sign = $this->generateSign($payloadJson);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-API-KEY' => $sign
            ])->withBody($payloadJson, 'application/json')->post($this->baseUrl . '/transaction');
            
            $res = $response->json();
            return $this->mapResponseToStandard($res);
        } catch (\Exception $e) {
            Log::error('AMA Topup Error: ' . $e->getMessage());
            return ['data' => ['status' => 'Gagal', 'message' => 'Exception: ' . $e->getMessage()]];
        }
    }

    public function checkStatus(string $skuCode, string $customerNo, string $refId)
    {
        // For AMA, assuming 'INQ' method for status inquiry based on standard H2H (or we can use topUpRequest again)
        $payload = $this->buildRequestPayload('INQ', $skuCode, $customerNo, $refId, []);
        $payloadJson = json_encode($payload);
        $sign = $this->generateSign($payloadJson);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-API-KEY' => $sign
            ])->withBody($payloadJson, 'application/json')->post($this->baseUrl . '/transaction');
            
            $res = $response->json();
            return $this->mapResponseToStandard($res);
        } catch (\Exception $e) {
            Log::error('AMA CheckStatus Error: ' . $e->getMessage());
            return ['data' => ['status' => 'Pending', 'message' => 'Check status failed: ' . $e->getMessage()]];
        }
    }

    private function mapResponseToStandard($res)
    {
        if (!isset($res['gwRs'])) {
            return ['data' => ['status' => 'Pending', 'message' => 'Invalid response format from AMA']];
        }

        $gwRs = $res['gwRs'];
        $status = $gwRs['status'] ?? '';
        
        $mappedStatus = 'Pending';
        if ($status === '00') {
            $mappedStatus = 'Sukses';
        } elseif (in_array($status, ['03', '04', '05', '06', '63', '65', '67', '99'])) {
            $mappedStatus = 'Gagal';
        } elseif ($status === '68') {
            $mappedStatus = 'Pending';
        }

        return [
            'data' => [
                'status' => $mappedStatus,
                'rc' => $status,
                'sn' => $gwRs['sn'] ?? '',
                'message' => $gwRs['message'] ?? '',
                'price' => $gwRs['price'] ?? 0,
            ]
        ];
    }
}
