<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Exception;

class EFakturService
{
    /**
     * Fetch and parse DJP e-Faktur QR from given URL or raw delimited QR string
     *
     * @param string $rawInput
     * @return array
     * @throws Exception
     */
    public function fetchAndParse(string $rawInput): array
    {
        $rawInput = trim($rawInput);

        if (empty($rawInput)) {
            throw new Exception('Data hasil scan kosong.');
        }

        // Format 1: Raw Delimited Text (Contoh: #0019907658406000#04002600189429075#18-05-2026#1.469.394,00#176.326,00#0,00#APPROVED)
        if (str_contains($rawInput, '#') || !str_starts_with($rawInput, 'http')) {
            return $this->parseRawDelimitedQr($rawInput);
        }

        // Format 2: URL DJP Validation
        return $this->fetchFromDjpUrl($rawInput);
    }

    /**
     * Parse raw delimited QR format from physical scanner gun
     */
    protected function parseRawDelimitedQr(string $raw): array
    {
        // Split by '#' or ';' or '|'
        $delimiter = str_contains($raw, '#') ? '#' : (str_contains($raw, ';') ? ';' : '|');
        $parts = array_values(array_filter(array_map('trim', explode($delimiter, $raw)), fn($v) => $v !== ''));

        if (count($parts) < 3) {
            throw new Exception('Format QR Code e-Faktur tidak dikenali.');
        }

        // Standard DJP Raw Hash Format:
        // [0] NPWP Penjual (15/16 digit)
        // [1] Nomor Faktur
        // [2] Tanggal Faktur (DD-MM-YYYY atau DD/MM/YYYY)
        // [3] DPP
        // [4] PPN
        // [5] PPNBM (opsional)
        // [6] Status Approval (opsional)
        $npwpPenjual = preg_replace('/[^0-9]/', '', $parts[0] ?? '');
        $nomorFaktur = trim($parts[1] ?? '');
        $rawTanggal = trim($parts[2] ?? '');
        $rawDpp = trim($parts[3] ?? '0');
        $rawPpn = trim($parts[4] ?? '0');
        $rawPpnbm = trim($parts[5] ?? '0');
        $statusApproval = trim($parts[6] ?? 'APPROVED');

        // Parse Date
        $tanggalFaktur = null;
        $masaPajak = '';
        if (!empty($rawTanggal)) {
            try {
                $cleanDate = str_replace('/', '-', $rawTanggal);
                $carbonDate = Carbon::parse($cleanDate);
                $tanggalFaktur = $carbonDate->format('Y-m-d');
                $masaPajak = $carbonDate->format('m-Y');
            } catch (Exception $e) {
                $tanggalFaktur = now()->format('Y-m-d');
                $masaPajak = now()->format('m-Y');
            }
        }

        // Parse Numbers (Indonesian currency format: 1.469.394,00 -> 1469394.00)
        $parseCurrency = function (string $val): float {
            $clean = preg_replace('/[^0-9,\.]/', '', $val);
            if (str_contains($clean, ',') && str_contains($clean, '.')) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } elseif (str_contains($clean, ',')) {
                $clean = str_replace(',', '.', $clean);
            }
            return (float) $clean;
        };

        $dpp = $parseCurrency($rawDpp);
        $ppn = $parseCurrency($rawPpn);
        $ppnbm = $parseCurrency($rawPpnbm);

        return [
            'success' => true,
            'header' => [
                'nomor_faktur' => $nomorFaktur,
                'full_nomor_faktur' => $nomorFaktur,
                'tanggal_faktur' => $tanggalFaktur,
                'masa_pajak' => $masaPajak,
                'npwp_penjual' => $npwpPenjual,
                'nama_penjual' => '',
                'alamat_penjual' => '',
                'npwp_lawan' => '',
                'nama_lawan' => '',
                'alamat_lawan' => '',
                'dpp' => $dpp,
                'ppn' => $ppn,
                'ppnbm' => $ppnbm,
                'status_approval' => $statusApproval ?: 'Faktur Valid',
                'status_faktur' => 'Valid',
            ],
            'items' => [],
        ];
    }

    /**
     * Fetch XML from DJP Validation URL
     */
    protected function fetchFromDjpUrl(string $url): array
    {
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
