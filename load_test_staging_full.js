import http from "k6/http";
import { check, sleep, group } from "k6";
import { uuidv4 } from "https://jslib.k6.io/k6-utils/1.4.0/index.js";

// =========================================================================
// PENGATURAN SKENARIO UJI BEBAN SINTETIS REALISTIS (FULL STAGING TRANSAKSI)
// =========================================================================
export let options = {
    thresholds: {
        http_req_failed: ["rate<0.05"], // Error rate di bawah 5%
        http_req_duration: ["p(90)<1000", "p(95)<1500"],
    },
    stages: [
        { duration: "10s", target: 10 }, // Pemanasan: 10 Kasir
        { duration: "25s", target: 30 }, // Beban Normal: 30 Kasir Aktif
        { duration: "20s", target: 50 }, // Jam Sibuk: 50 Kasir Serentak Checkout
        { duration: "10s", target: 0 },  // Pendinginan: Turun ke 0
    ],
};

const BASE_URL = "https://adminstg.toserbaselamat.id";
const DEVICE_UUID = "4f6d26b9-5ee6-4ef1-9c5b-ae25c4086583";
const BRANCH_ID = "01a09dd8-929a-72a5-a88f-9ff80536718d";
const TOKEN = "395|UdP1kWFbAirJvEgEF8XCqmH5rUf98k0Nc3jdBQKf5d4860d5";

// Sample valid product IDs from staging database
const SAMPLE_PRODUCTS = [
    { id: "019f188a-98e8-700e-8cb9-a39540e71895", price: 7500 }, // A&W 330
    { id: "019f188a-985c-712e-90ea-60b28036c131", price: 6500 }, // 5DAYS PAND.SAR
];

export default function () {
    const headers = {
        "Authorization": `Bearer ${TOKEN}`,
        "Accept": "application/json",
        "Content-Type": "application/json",
        "X-Device-UUID": DEVICE_UUID,
    };

    // 1. Kasir Scan Barcode
    group("1. Scan Barcode", function () {
        let resPromo = http.get(`${BASE_URL}/api/v1/promotions`, { headers: headers });
        check(resPromo, {
            "Promosi (200 OK)": (r) => r.status === 200,
        });
    });

    sleep(Math.random() * 0.8 + 0.3);

    // 2. Checkout Transaksi Nyata (Tercatat di Database & Arsip Transaksi Admin)
    group("2. Checkout Penjualan Kasir", function () {
        const localId = "SYNTH-" + uuidv4().substr(0, 18);
        const prod = SAMPLE_PRODUCTS[Math.floor(Math.random() * SAMPLE_PRODUCTS.length)];
        const qty = Math.floor(Math.random() * 3) + 1;
        const total = qty * prod.price;
        const received = Math.ceil(total / 10000) * 10000;
        const change = received - total;

        const payload = JSON.stringify({
            deviceId: BRANCH_ID,
            branchId: BRANCH_ID,
            transactions: [
                {
                    localId: localId,
                    totalAmount: total,
                    discountAmount: 0,
                    finalAmount: total,
                    paymentMethod: "CASH",
                    receivedAmount: received,
                    changeAmount: change,
                    transactionDate: new Date().toISOString(),
                    items: [
                        {
                            productId: prod.id,
                            quantity: qty,
                            unitPrice: prod.price,
                            discountPerItem: 0,
                        }
                    ]
                }
            ]
        });

        let resCheckout = http.post(`${BASE_URL}/api/v1/transactions/batch-sync`, payload, { headers: headers });
        let isSuccess = false;
        try {
            let body = JSON.parse(resCheckout.body);
            isSuccess = body.success === true && body.syncedCount > 0;
        } catch (e) {
            isSuccess = false;
        }

        check(resCheckout, {
            "Transaksi Masuk DB (200 OK)": (r) => r.status === 200,
            "Transaksi Berhasil Dicatat": () => isSuccess,
        });
    });

    sleep(Math.random() * 1.2 + 0.5);
}
