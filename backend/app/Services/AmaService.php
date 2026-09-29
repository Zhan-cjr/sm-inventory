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
        $this->userId = env('AMA_USER_ID', '262711139');
        $this->apiKey = env('AMA_PIN', '352ee5');
        $this->baseUrl = rtrim(env('AMA_API_URL', 'https://202.43.189.122:13008/clientapi/v2/json'), '/');
    }

    private function getHttpClient()
    {
        return Http::withoutVerifying()
            ->withOptions([
                'curl' => [
                    CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=0',
                    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_0,
                ],
                'timeout' => 60,
            ]);
    }

    private function getTransactionUrl(): string
    {
        if (str_ends_with($this->baseUrl, '.json') || str_contains($this->baseUrl, '/clientapi/')) {
            return $this->baseUrl;
        }
        return $this->baseUrl . '/transaction';
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
        return ['data' => ['status' => 'success', 'balance' => 0]];
    }

    public function getPriceList()
    {
        // Based on PDF: <url_endpoint_product>?userid=<userid>
        // with X-API-KEY header: HMAC256(userid, eKEY)
        $sign = hash_hmac('sha256', $this->userId, $this->apiKey);
        $productUrl = str_ends_with($this->baseUrl, '.json')
            ? str_replace('/json', '/product', $this->baseUrl)
            : $this->baseUrl . '/product';

        try {
            $response = $this->getHttpClient()->withHeaders([
                'X-API-KEY' => $sign
            ])->get($productUrl, [
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
        $payload = $this->buildRequestPayload('topUpRequest', $skuCode, $customerNo, $refId, $additionalInfo);
        
        $payloadJson = json_encode($payload);
        $sign = $this->generateSign($payloadJson);

        try {
            $response = $this->getHttpClient()->withHeaders([
                'Content-Type' => 'application/json',
                'X-API-KEY' => $sign
            ])->withBody($payloadJson, 'application/json')->post($this->getTransactionUrl());
            
            $res = $response->json();
            return $this->mapResponseToStandard($res);
        } catch (\Exception $e) {
            Log::error('AMA Topup Error: ' . $e->getMessage());
            return ['data' => ['status' => 'Gagal', 'message' => 'Exception: ' . $e->getMessage()]];
        }
    }

    public function checkStatus(string $skuCode, string $customerNo, string $refId)
    {
        // For AMA, using 'INQ' method or status inquiry
        $payload = $this->buildRequestPayload('INQ', $skuCode, $customerNo, $refId, []);
        $payloadJson = json_encode($payload);
        $sign = $this->generateSign($payloadJson);

        try {
            $response = $this->getHttpClient()->withHeaders([
                'Content-Type' => 'application/json',
                'X-API-KEY' => $sign
            ])->withBody($payloadJson, 'application/json')->post($this->getTransactionUrl());
            
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
        $status = (string) ($gwRs['status'] ?? '');
        
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
                'sn' => $gwRs['serialnumber'] ?? $gwRs['sn'] ?? '',
                'message' => $gwRs['message'] ?? '',
                'price' => $gwRs['price'] ?? 0,
            ]
        ];
    }
}
