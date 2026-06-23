import http from 'k6/http';
import { check, sleep } from 'k6';

// =========================================================================
// PENGATURAN LOAD TEST (SKENARIO)
// =========================================================================
export let options = {
    // Thresholds menentukan kriteria sukses/gagal dari test ini
    thresholds: {
        http_req_failed: ['rate<0.01'], // Error rate harus di bawah 1%
        http_req_duration: ['p(95)<500'], // 95% request harus selesai di bawah 500ms
    },
    stages: [
        { duration: '30s', target: 50 },  // Ramp-up: perlahan naik ke 50 kasir aktif dalam 30 detik
        { duration: '1m', target: 50 },   // Steady: tahan di 50 kasir selama 1 menit (Pemanasan)
        { duration: '30s', target: 150 }, // Spike: mendadak naik ke 150 kasir (Simulasi Jam Sibuk/Antrean Panjang)
        { duration: '1m', target: 150 },  // Steady: tahan beban puncak selama 1 menit
        { duration: '30s', target: 0 },   // Ramp-down: perlahan turun kembali ke 0
    ],
};

// =========================================================================
// KONFIGURASI SERVER
// =========================================================================
// Jika PowerEdge R630 berada di jaringan lokal (LAN), ganti dengan IP Address-nya, misal: 'http://192.168.1.100'
// Jika sudah diakses online, gunakan URL domain Anda.
const BASE_URL = 'http://100.114.176.71';

// [PENTING]: Ganti dengan Token Auth dari salah satu akun kasir Anda yang valid
// Cara dapatnya: Login ke POS Kasir, buka Inspect Element (F12) -> Application -> Local Storage -> cari 'pos_user' -> copy token-nya.
const TOKEN = '323|XJnf80wayOqOeImr0wz34elBauVnur9jTgH1YUWhac3791e3';

// =========================================================================
// SIMULASI PERILAKU KASIR
// =========================================================================
export default function () {
    const params = {
        headers: {
            'Host': 'admin.toserbaselamat.id',
            'Authorization': `Bearer ${TOKEN}`,
            'Accept': 'application/json',
            'X-Device-UUID': '4f6d26b9-5ee6-4ef1-9c5b-ae25c4086583',
        },
    };

    // Skenario 1: Kasir memuat daftar Produk & Promo saat aplikasi baru dibuka/sinkronisasi
    let resProducts = http.get(`${BASE_URL}/api/v1/products`, params);
    check(resProducts, {
        'Get Products - Status is 200': (r) => r.status === 200,
        'Get Products - Response < 800ms': (r) => r.timings.duration < 800,
    });

    // Kasir "diam" sejenak selama 1-2 detik (mensimulasikan kasir sedang melihat layar / men-scan barang)
    sleep(Math.random() * 2 + 1);

    // Skenario 2: Kasir memuat dashboard BI / AI Apriori
    let resApriori = http.get(`${BASE_URL}/api/v1/bi/apriori`, params);
    check(resApriori, {
        'Get Apriori - Status is 200': (r) => r.status === 200,
    });

    // Istirahat sebentar sebelum kasir berikutnya melakukan hal yang sama
    sleep(1);
}
