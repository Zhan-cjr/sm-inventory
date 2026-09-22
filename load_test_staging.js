import http from "k6/http";
import { check, sleep, group } from "k6";

// =========================================================================
// PENGATURAN SKENARIO UJI BEBAN SINTETIS (STAGING)
// =========================================================================
export let options = {
    thresholds: {
        http_req_failed: ["rate<0.05"], // Tingkat error maksimal di bawah 5%
        http_req_duration: ["p(90)<1000", "p(95)<1500"], // 95% request harus selesai di bawah 1.5 detik
    },
    stages: [
        { duration: "15s", target: 10 },  // Tahap 1: Pemanasan (10 Kasir Virtual)
        { duration: "30s", target: 30 },  // Tahap 2: Beban Normal (30 Kasir Aktif)
        { duration: "30s", target: 75 },  // Tahap 3: Beban Jam Sibuk (75 Kasir Antre)
        { duration: "15s", target: 0 },   // Tahap 4: Pendinginan (Ramp-down)
    ],
};

const BASE_URL = "https://adminstg.toserbaselamat.id";
const DEVICE_UUID = "4f6d26b9-5ee6-4ef1-9c5b-ae25c4086583";

// Dapatkan token saat inisialisasi atau gunakan token aktif
const TOKEN = "395|UdP1kWFbAirJvEgEF8XCqmH5rUf98k0Nc3jdBQKf5d4860d5";

export default function () {
    const headers = {
        "Authorization": `Bearer ${TOKEN}`,
        "Accept": "application/json",
        "Content-Type": "application/json",
        "X-Device-UUID": DEVICE_UUID,
    };

    // SKENARIO 1: Uji Pencarian Produk & Katalog Kasir (Read Heavy)
    group("1. Kasir Scan / Cari Produk", function () {
        let resProducts = http.get(`${BASE_URL}/api/v1/products?limit=20`, { headers: headers });
        check(resProducts, {
            "Katalog Produk - Status 200": (r) => r.status === 200,
            "Katalog Produk - Latency < 1000ms": (r) => r.timings.duration < 1000,
        });
    });

    sleep(Math.random() * 1.5 + 0.5); // Jeda kasir scan barang berikutnya

    // SKENARIO 2: Uji Analitik Cross-Selling (AI Apriori Rekomendasi Kasir)
    group("2. Rekomendasi Cross-Selling Kasir", function () {
        let resApriori = http.get(`${BASE_URL}/api/v1/bi/apriori`, { headers: headers });
        check(resApriori, {
            "Rekomendasi Kasir - Status 200": (r) => r.status === 200 || r.status === 404,
        });
    });

    sleep(Math.random() * 1.0 + 0.5);

    // SKENARIO 3: Uji Katalog E-Commerce Publik
    group("3. Katalog E-Commerce Publik", function () {
        let resEcom = http.get(`${BASE_URL}/api/v1/ecommerce/products?limit=15`, {
            headers: { "Accept": "application/json" }
        });
        check(resEcom, {
            "E-Commerce Products - Status 200": (r) => r.status === 200,
        });
    });

    sleep(1);
}
