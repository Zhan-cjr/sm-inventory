<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Exception;

class EFakturService
{
    /**
     * Fetch and parse DJP e-Faktur QR from given URL or raw delimited QR string
     */
    public function fetchAndParse(string $rawInput): array
    {
        $rawInput = trim($rawInput);
        if (empty($rawInput)) {
            throw new Exception('Data hasil scan kosong.');
        }

        if (str_contains($rawInput, '#') || !str_starts_with($rawInput, 'http')) {
            return $this->parseRawDelimitedQr($rawInput);
        }

        return $this->fetchFromDjpUrl($rawInput);
    }

    /**
     * Parse raw delimited QR format from physical scanner gun or mobile
     */
    protected function parseRawDelimitedQr(string $raw): array
    {
        if (str_contains($raw, '<resValidateFaktur') || str_contains($raw, '</nomorFaktur>')) {
            return $this->parseXmlString($raw);
        }

        $delimiter = str_contains($raw, '#') ? '#' : (str_contains($raw, ';') ? ';' : (str_contains($raw, '|') ? '|' : "\t"));
        $parts = array_values(array_filter(array_map('trim', explode($delimiter, $raw)), fn($v) => $v !== ''));

        if (count($parts) < 2) {
            throw new Exception('Format QR Code e-Faktur tidak dikenali.');
        }

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

        $namaPenjual = '';
        $npwpPenjual = '';
        $namaLawan = '';
        $npwpLawan = '';
        $nomorFaktur = '';
        $tanggalFaktur = null;
        $masaPajak = '';
        $dpp = 0.0;
        $ppn = 0.0;
        $ppnbm = 0.0;
        $statusApproval = 'APPROVED';

        // Check if starts with [NamaPenjual]#[NPWPPenjual]#[NamaPembeli]#[NPWPPembeli]#[NoFaktur]
        if (count($parts) >= 4 && !is_numeric($parts[0]) && preg_match('/^\d{15,16}$/', preg_replace('/[^0-9]/', '', $parts[1]))) {
            $namaPenjual = $parts[0];
            $npwpPenjual = preg_replace('/[^0-9]/', '', $parts[1]);
            $namaLawan = $parts[2] ?? '';
            $npwpLawan = isset($parts[3]) ? preg_replace('/[^0-9]/', '', $parts[3]) : '';
            $nomorFaktur = $parts[4] ?? '';

            // Remaining tokens for date, amounts, status
            for ($i = 5; $i < count($parts); $i++) {
                $val = $parts[$i];
                if (preg_match('/^(\d{1,2})[-\/\.](\d{1,2})[-\/\.](\d{4})$/', $val) || preg_match('/^(\d{4})[-\/\.](\d{1,2})[-\/\.](\d{1,2})$/', $val)) {
                    try {
                        $cd = Carbon::parse(str_replace(['/', '.'], '-', $val));
                        $tanggalFaktur = $cd->format('Y-m-d');
                        $masaPajak = $cd->format('m-Y');
                    } catch (Exception $e) {}
                } elseif (in_array(strtoupper($val), ['APPROVED', 'REJECTED', 'VALID', 'SUDAH DIAPPROVE OLEH DJP'])) {
                    $statusApproval = $val;
                } elseif (str_contains($val, ',') || str_contains($val, '.') || is_numeric($val)) {
                    $num = $parseCurrency($val);
                    if ($dpp == 0.0) $dpp = $num;
                    elseif ($ppn == 0.0) $ppn = $num;
                    elseif ($ppnbm == 0.0) $ppnbm = $num;
                }
            }
        } else {
            // Find date index
            $dateIdx = -1;
            foreach ($parts as $idx => $part) {
                if (preg_match('/^(\d{1,2})[-\/\.](\d{1,2})[-\/\.](\d{4})$/', $part) || preg_match('/^(\d{4})[-\/\.](\d{1,2})[-\/\.](\d{1,2})$/', $part)) {
                    $dateIdx = $idx;
                    try {
                        $cd = Carbon::parse(str_replace(['/', '.'], '-', $part));
                        $tanggalFaktur = $cd->format('Y-m-d');
                        $masaPajak = $cd->format('m-Y');
                    } catch (Exception $e) {}
                    break;
                }
            }

            if ($dateIdx !== -1) {
                if ($dateIdx > 0) $nomorFaktur = preg_replace('/[^0-9A-Za-z\.\-]/', '', $parts[$dateIdx - 1]);
                if (isset($parts[$dateIdx + 1])) $dpp = $parseCurrency($parts[$dateIdx + 1]);
                if (isset($parts[$dateIdx + 2])) $ppn = $parseCurrency($parts[$dateIdx + 2]);
                if (isset($parts[$dateIdx + 3])) $ppnbm = $parseCurrency($parts[$dateIdx + 3]);
                if (isset($parts[$dateIdx + 4])) $statusApproval = trim($parts[$dateIdx + 4]);

                $prefixParts = array_slice($parts, 0, $dateIdx - 1);
                foreach ($prefixParts as $p) {
                    $digits = preg_replace('/[^0-9]/', '', $p);
                    if (strlen($digits) >= 15 && strlen($digits) <= 16 && empty($npwpPenjual)) {
                        $npwpPenjual = $digits;
                    } elseif (!preg_match('/^\d+$/', $p) && empty($namaPenjual)) {
                        $namaPenjual = trim($p);
                    }
                }
            } else {
                foreach ($parts as $p) {
                    $digits = preg_replace('/[^0-9]/', '', $p);
                    if (strlen($digits) >= 15 && strlen($digits) <= 16 && empty($npwpPenjual)) {
                        $npwpPenjual = $digits;
                    } elseif (strlen($digits) >= 13 && strlen($digits) <= 17 && empty($nomorFaktur)) {
                        $nomorFaktur = $p;
                    } elseif (!preg_match('/^\d+$/', $p) && empty($namaPenjual)) {
                        $namaPenjual = $p;
                    }
                }
            }
        }

        return [
            'success' => true,
            'header' => [
                'nomor_faktur' => $nomorFaktur,
                'full_nomor_faktur' => $nomorFaktur,
                'tanggal_faktur' => $tanggalFaktur ?: now()->format('Y-m-d'),
                'masa_pajak' => $masaPajak ?: now()->format('m-Y'),
                'npwp_penjual' => $npwpPenjual,
                'nama_penjual' => $namaPenjual,
                'alamat_penjual' => '',
                'npwp_lawan' => $npwpLawan,
                'nama_lawan' => $namaLawan,
                'alamat_lawan' => '',
                'dpp' => $dpp,
                'ppn' => $ppn,
                'ppnbm' => $ppnbm,
                'status_approval' => $statusApproval ?: 'APPROVED',
                'status_faktur' => 'Valid',
            ],
            'items' => [],
        ];
    }

    /**
     * Parse directly from raw XML string
     */
    protected function parseXmlString(string $xmlContent): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) throw new Exception('Gagal membaca XML dari QR code.');
        return $this->extractDataFromXmlObject($xml);
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
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'application/xml,text/xml,*/*',
                ])
                ->get($url);
        } catch (Exception $e) {
            throw new Exception('Gagal menghubungi server DJP: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            throw new Exception('Server DJP error: HTTP ' . $response->status());
        }

        $body = trim($response->body());
        if (empty($body)) throw new Exception('Respon dari server DJP kosong.');

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) throw new Exception('Gagal membaca format XML dari respon server DJP.');

        return $this->extractDataFromXmlObject($xml);
    }

    /**
     * Extract structured invoice data from SimpleXMLElement
     */
    protected function extractDataFromXmlObject(\SimpleXMLElement $xml): array
    {
        $nomorFaktur = (string) ($xml->nomorFaktur ?? '');
        $fullNomorFaktur = $nomorFaktur;
        if (strlen($nomorFaktur) === 16) {
            $fullNomorFaktur = substr($nomorFaktur, 0, 3) . '.' . substr($nomorFaktur, 3, 3) . '-' . substr($nomorFaktur, 6, 2) . '.' . substr($nomorFaktur, 8);
        }

        $rawTanggal = (string) ($xml->tanggalFaktur ?? '');
        $tanggalFaktur = null;
        $masaPajak = '';
        if (!empty($rawTanggal)) {
            try {
                $cd = Carbon::parse(str_replace(['/', '.'], '-', $rawTanggal));
                $tanggalFaktur = $cd->format('Y-m-d');
                $masaPajak = $cd->format('m-Y');
            } catch (Exception $e) {
                $tanggalFaktur = now()->format('Y-m-d');
                $masaPajak = now()->format('m-Y');
            }
        }

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
                'npwp_penjual' => preg_replace('/[^0-9]/', '', (string) ($xml->npwpPenjual ?? '')),
                'nama_penjual' => trim((string) ($xml->namaPenjual ?? '')),
                'alamat_penjual' => trim((string) ($xml->alamatPenjual ?? '')),
                'npwp_lawan' => preg_replace('/[^0-9]/', '', (string) ($xml->npwpLawanTransaksi ?? '')),
                'nama_lawan' => trim((string) ($xml->namaLawanTransaksi ?? '')),
                'alamat_lawan' => trim((string) ($xml->alamatLawanTransaksi ?? '')),
                'dpp' => (float) ($xml->jumlahDpp ?? 0),
                'ppn' => (float) ($xml->jumlahPpn ?? 0),
                'ppnbm' => (float) ($xml->jumlahPpnbm ?? 0),
                'status_approval' => (string) ($xml->statusApproval ?? 'APPROVED'),
                'status_faktur' => (string) ($xml->statusFaktur ?? 'Valid'),
            ],
            'items' => $items,
        ];
    }
}
