<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Exception;

class EFakturService
{
    /**
     * Fetch and parse DJP e-Faktur QR XML from given URL
     *
     * @param string $url
     * @return array
     * @throws Exception
     */
    public function fetchAndParse(string $url): array
    {
        $url = trim($url);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Format URL QR Code tidak valid.');
        }

        try {
            $response = Http::timeout(12)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/xml,text/xml,*/*',
                ])
                ->get($url);
        } catch (Exception $e) {
            throw new Exception('Gagal menghubungi server DJP e-Faktur: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            throw new Exception('Server DJP mengembalikan status error: HTTP ' . $response->status());
        }

        $body = trim($response->body());
        if (empty($body)) {
            throw new Exception('Respon dari server DJP kosong.');
        }

        // Disable entity loader for security
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xml === false) {
            throw new Exception('Gagal membaca format XML dari respon server DJP.');
        }

        // Parse Header
        $nomorFaktur = (string) ($xml->nomorFaktur ?? '');
        $kdJenis = (string) ($xml->kdJenisTransaksi ?? '01');
        $fgPengganti = (string) ($xml->fgPengganti ?? '0');
        
        // Full standard 16 digit format if needed
        $fullNomorFaktur = $nomorFaktur;
        if (strlen($nomorFaktur) === 16) {
            $fullNomorFaktur = substr($nomorFaktur, 0, 3) . '.' . substr($nomorFaktur, 3, 3) . '-' . substr($nomorFaktur, 6, 2) . '.' . substr($nomorFaktur, 8);
        }

        $rawTanggal = (string) ($xml->tanggalFaktur ?? '');
        $tanggalFaktur = null;
        $masaPajak = '';
        if (!empty($rawTanggal)) {
            try {
                // Usually DD/MM/YYYY or DD-MM-YYYY
                $cleanDate = str_replace('/', '-', $rawTanggal);
                $carbonDate = Carbon::parse($cleanDate);
                $tanggalFaktur = $carbonDate->format('Y-m-d');
                $masaPajak = $carbonDate->format('m-Y');
            } catch (Exception $e) {
                $tanggalFaktur = now()->format('Y-m-d');
                $masaPajak = now()->format('m-Y');
            }
        }

        $npwpPenjual = preg_replace('/[^0-9]/', '', (string) ($xml->npwpPenjual ?? ''));
        $namaPenjual = trim((string) ($xml->namaPenjual ?? ''));
        $alamatPenjual = trim((string) ($xml->alamatPenjual ?? ''));

        $npwpLawan = preg_replace('/[^0-9]/', '', (string) ($xml->npwpLawanTransaksi ?? ''));
        $namaLawan = trim((string) ($xml->namaLawanTransaksi ?? ''));
        $alamatLawan = trim((string) ($xml->alamatLawanTransaksi ?? ''));

        $jumlahDpp = (float) ($xml->jumlahDpp ?? 0);
        $jumlahPpn = (float) ($xml->jumlahPpn ?? 0);
        $jumlahPpnbm = (float) ($xml->jumlahPpnbm ?? 0);
        $statusApproval = (string) ($xml->statusApproval ?? '');
        $statusFaktur = (string) ($xml->statusFaktur ?? '');

        // Parse Items
        $items = [];
        if (isset($xml->detailTransaksi)) {
            foreach ($xml->detailTransaksi as $item) {
                $items[] = [
                    'name' => (string) ($item->nama ?? ''),
                    'harga_satuan' => (float) ($item->hargaSatuan ?? 0),
                    'jumlah_barang' => (float) ($item->jumlahBarang ?? 1),
                    'harga_total' => (float) ($item->hargaTotal ?? 0),
                    'diskon' => (float) ($item->diskon ?? 0),
                    'dpp' => (float) ($item->dpp ?? 0),
                    'ppn' => (float) ($item->ppn ?? 0),
                    'ppnbm' => (float) ($item->ppnbm ?? 0),
                ];
            }
        }

        return [
            'success' => true,
            'header' => [
                'nomor_faktur' => $nomorFaktur,
                'full_nomor_faktur' => $fullNomorFaktur,
                'tanggal_faktur' => $tanggalFaktur,
                'masa_pajak' => $masaPajak,
                'npwp_penjual' => $npwpPenjual,
                'nama_penjual' => $namaPenjual,
                'alamat_penjual' => $alamatPenjual,
                'npwp_lawan' => $npwpLawan,
                'nama_lawan' => $namaLawan,
                'alamat_lawan' => $alamatLawan,
                'dpp' => $jumlahDpp,
                'ppn' => $jumlahPpn,
                'ppnbm' => $jumlahPpnbm,
                'status_approval' => $statusApproval,
                'status_faktur' => $statusFaktur,
            ],
            'items' => $items,
        ];
    }
}
