import http from 'k6/http';
import { check, sleep } from 'k6';
import { uuidv4 } from 'https://jslib.k6.io/k6-utils/1.4.0/index.js';

export const options = {
  // Simulate 50 cashiers syncing simultaneously for 30 seconds
  stages: [
    { duration: '5s', target: 50 }, // Ramp up to 50 users
    { duration: '20s', target: 50 }, // Stay at 50 users
    { duration: '5s', target: 0 },  // Ramp down
  ],
};

const BASE_URL = 'http://127.0.0.1:8000/api/v1';

export function setup() {
  // 1. Authenticate to get a token before the load test starts
  const loginRes = http.post(`${BASE_URL}/login`, JSON.stringify({
    email: 'cashier@selamat.id',
    password: 'password',
  }), {
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
  });

  if (loginRes.status !== 200) {
    console.error(`Login failed: ${loginRes.status} ${loginRes.body}`);
  }

  return { token: loginRes.json('token') };
}

export default function (data) {
  const token = data.token;
  if (!token) {
    throw new Error('Authentication failed in setup()');
  }

  // 2. Generate 5 random offline transactions
  const transactions = [];
  for (let i = 0; i < 5; i++) {
    transactions.push({
      localId: uuidv4(),
      totalAmount: Math.floor(Math.random() * 50000) + 10000,
      paymentMethod: 'CASH',
      items: [
        {
          productId: '11111', // Dummy SKU from seeder
          quantity: Math.floor(Math.random() * 5) + 1,
          unitPrice: 3500,
        },
        {
          productId: '33333',
          quantity: 1,
          unitPrice: 12000,
        }
      ]
    });
  }

  const payload = JSON.stringify({
    branchId: 'JKT-01-0000-0000-000000000001',
    deviceId: '00000000-0000-0000-0000-000000000002',
    transactions: transactions
  });

  const params = {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${token}`
    },
  };

  // 3. Send the batch sync request
  const res = http.post(`${BASE_URL}/transactions/batch-sync`, payload, params);

  if (res.status !== 200) {
    console.error(`Sync failed: ${res.status} ${res.body}`);
  }

  // 4. Validate the response
  check(res, {
    'is status 200': (r) => r.status === 200,
    'sync was successful': (r) => r.json('syncedCount') === 5,
  });

  // Small delay between requests to simulate natural behavior
  sleep(1);
}
