# Spesifikasi Teknis Hypermarket Inventory & POS System

**Status**: Production-Ready Architecture  
**Version**: 1.0  
**Last Updated**: May 2026

---

## 1. ARSITEKTUR INTI

### 1.1 Technology Stack

```
Frontend (POS + Admin)
├─ React.js / Vue.js 3 (SPA)
├─ Service Workers (offline caching)
├─ IndexedDB (local transaction storage)
└─ PWA manifest (installable)

Backend API
├─ Laravel 11 (Stateless REST API)
├─ Laravel Passport (OAuth2 Token-based Auth)
├─ Laravel Scout (Search indexing)
└─ Laravel Horizon (Job queue)

Infrastructure
├─ PostgreSQL 15+ (Master-Slave Read/Write Split)
├─ Redis 7+ (Sessions, Cache, Queue)
├─ Meilisearch 1.x (Full-text search)
└─ Docker + Kubernetes (optional scalability)
```

### 1.2 Deployment Model

- **Stateless API Servers**: Dapat auto-scale horizontal
- **Shared Sessions**: Stored in Redis (sticky sessions tidak diperlukan)
- **Database**: Managed PostgreSQL dengan replication to read-only replicas
- **Message Queue**: Redis-backed untuk background jobs

---

## 2. DATABASE SCHEMA (DDL)

### 2.1 Core Tables

```sql
-- Organizations & Branches
CREATE TABLE organizations (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name VARCHAR(255) NOT NULL,
  code VARCHAR(50) UNIQUE NOT NULL,
  timezone VARCHAR(50) DEFAULT 'Asia/Jakarta',
  currency_code CHAR(3) DEFAULT 'IDR',
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE branches (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
  name VARCHAR(255) NOT NULL,
  code VARCHAR(50) NOT NULL,
  address TEXT,
  phone VARCHAR(20),
  manager_id UUID,
  is_active BOOLEAN DEFAULT TRUE,
  UNIQUE(organization_id, code),
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Products & Inventory
CREATE TABLE products (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  organization_id UUID NOT NULL REFERENCES organizations(id),
  sku VARCHAR(100) NOT NULL,
  barcode VARCHAR(100) UNIQUE,
  name VARCHAR(255) NOT NULL,
  category_id UUID,
  supplier_id UUID,
  cost_price DECIMAL(12, 2) NOT NULL,
  selling_price DECIMAL(12, 2) NOT NULL,
  unit_of_measure VARCHAR(50) DEFAULT 'pcs',
  reorder_point INT DEFAULT 10,
  reorder_qty INT DEFAULT 50,
  lead_time_days INT DEFAULT 5,
  is_active BOOLEAN DEFAULT TRUE,
  search_vector tsvector GENERATED ALWAYS AS (
    to_tsvector('indonesian', name || ' ' || sku || ' ' || COALESCE(barcode, ''))
  ) STORED,
  UNIQUE(organization_id, sku),
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_products_organization ON products(organization_id);
CREATE INDEX idx_products_sku ON products(sku);
CREATE INDEX idx_products_barcode ON products(barcode);
CREATE INDEX idx_products_search ON products USING GIN(search_vector);

-- Stock Management
CREATE TABLE stocks (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  branch_id UUID NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
  product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  quantity_on_hand INT NOT NULL DEFAULT 0,
  quantity_reserved INT NOT NULL DEFAULT 0,
  quantity_available INT GENERATED ALWAYS AS (quantity_on_hand - quantity_reserved) STORED,
  last_count_date DATE,
  min_qty INT DEFAULT 10,
  max_qty INT DEFAULT 500,
  version INT DEFAULT 1,  -- For optimistic locking
  updated_at TIMESTAMP DEFAULT NOW(),
  UNIQUE(branch_id, product_id),
  created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_stocks_branch_product ON stocks(branch_id, product_id);
CREATE INDEX idx_stocks_available ON stocks(quantity_available);

-- Transactions (Sales & Void)
CREATE TABLE transactions (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  organization_id UUID NOT NULL REFERENCES organizations(id),
  branch_id UUID NOT NULL REFERENCES branches(id),
  transaction_type VARCHAR(50) NOT NULL, -- 'SALES', 'RETURN', 'ADJUSTMENT'
  transaction_date TIMESTAMP NOT NULL DEFAULT NOW(),
  cashier_id UUID NOT NULL,
  total_amount DECIMAL(12, 2) NOT NULL,
  discount_amount DECIMAL(12, 2) DEFAULT 0,
  final_amount DECIMAL(12, 2) NOT NULL,
  payment_method VARCHAR(50), -- 'CASH', 'CARD', 'EWALLET'
  is_voided BOOLEAN DEFAULT FALSE,
  void_reason TEXT,
  void_date TIMESTAMP,
  voided_by UUID,
  sync_status VARCHAR(50) DEFAULT 'PENDING', -- 'PENDING', 'SYNCED', 'CONFLICT'
  local_transaction_id VARCHAR(100),  -- For offline POS reconciliation
  UNIQUE(organization_id, local_transaction_id),
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_transactions_branch_date ON transactions(branch_id, transaction_date);
CREATE INDEX idx_transactions_cashier ON transactions(cashier_id);
CREATE INDEX idx_transactions_sync_status ON transactions(sync_status);

-- Transaction Items
CREATE TABLE transaction_items (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  transaction_id UUID NOT NULL REFERENCES transactions(id) ON DELETE CASCADE,
  product_id UUID NOT NULL REFERENCES products(id),
  quantity INT NOT NULL,
  unit_price DECIMAL(12, 2) NOT NULL,
  discount_per_item DECIMAL(12, 2) DEFAULT 0,
  line_total DECIMAL(12, 2) GENERATED ALWAYS AS (
    (quantity * unit_price) - (quantity * discount_per_item)
  ) STORED,
  created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_transaction_items_transaction ON transaction_items(transaction_id);
CREATE INDEX idx_transaction_items_product ON transaction_items(product_id);

-- Promotions & Discount Rules
CREATE TABLE promotions (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  organization_id UUID NOT NULL REFERENCES organizations(id),
  name VARCHAR(255) NOT NULL,
  promo_type VARCHAR(50) NOT NULL,  -- 'PERCENTAGE', 'FIXED', 'BUNDLING', 'TIERED', 'FLASH'
  discount_value DECIMAL(10, 2) NOT NULL,
  min_purchase_amount DECIMAL(12, 2),
  applicable_to VARCHAR(50), -- 'ALL', 'CATEGORY', 'PRODUCT', 'MEMBER'
  target_ids UUID[] DEFAULT '{}',  -- Product/Category/Member IDs
  valid_from TIMESTAMP NOT NULL,
  valid_until TIMESTAMP NOT NULL,
  max_discount_per_transaction DECIMAL(12, 2),
  is_active BOOLEAN DEFAULT TRUE,
  promo_config JSONB, -- Flexible schema for complex rules
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_promotions_active ON promotions(is_active) WHERE is_active = TRUE;
CREATE INDEX idx_promotions_date ON promotions(valid_from, valid_until);

-- Inventory Audit Trail
CREATE TABLE inventory_logs (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  branch_id UUID NOT NULL REFERENCES branches(id),
  product_id UUID NOT NULL REFERENCES products(id),
  log_type VARCHAR(50) NOT NULL,  -- 'ADJUSTMENT', 'SALE', 'TRANSFER', 'COUNT', 'RETURN'
  quantity_change INT NOT NULL,
  reason_code VARCHAR(100),
  reference_doc_type VARCHAR(50), -- 'TRANSACTION', 'STOCK_OPNAME', 'TRANSFER'
  reference_doc_id UUID,
  recorded_by UUID NOT NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_inventory_logs_date ON inventory_logs(created_at);
CREATE INDEX idx_inventory_logs_product ON inventory_logs(product_id, branch_id);
CREATE INDEX idx_inventory_logs_type ON inventory_logs(log_type);

-- Stock Transfer (Inter-branch)
CREATE TABLE stock_transfers (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  organization_id UUID NOT NULL REFERENCES organizations(id),
  from_branch_id UUID NOT NULL REFERENCES branches(id),
  to_branch_id UUID NOT NULL REFERENCES branches(id),
  status VARCHAR(50) DEFAULT 'PENDING',  -- 'PENDING', 'SHIPPED', 'RECEIVED'
  transfer_date TIMESTAMP DEFAULT NOW(),
  created_by UUID NOT NULL,
  received_date TIMESTAMP,
  received_by UUID,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE stock_transfer_items (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  transfer_id UUID NOT NULL REFERENCES stock_transfers(id) ON DELETE CASCADE,
  product_id UUID NOT NULL REFERENCES products(id),
  quantity_requested INT NOT NULL,
  quantity_shipped INT,
  quantity_received INT,
  created_at TIMESTAMP DEFAULT NOW()
);

-- Users & Roles
CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  organization_id UUID NOT NULL REFERENCES organizations(id),
  username VARCHAR(100) UNIQUE NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  phone VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL,
  branch_id UUID REFERENCES branches(id),  -- NULL = head office
  role VARCHAR(50) NOT NULL,  -- 'ADMIN', 'MANAGER', 'CASHIER', 'INVENTORY_STAFF'
  is_active BOOLEAN DEFAULT TRUE,
  last_login TIMESTAMP,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_organization ON users(organization_id);

-- Offline Transaction Cache (untuk POS yang offline)
CREATE TABLE offline_transaction_cache (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  pos_device_id VARCHAR(100) NOT NULL,
  branch_id UUID NOT NULL REFERENCES branches(id),
  transaction_data JSONB NOT NULL,  -- Full transaction payload
  sync_attempt_count INT DEFAULT 0,
  last_sync_attempt TIMESTAMP,
  sync_error TEXT,
  is_synced BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_offline_cache_device ON offline_transaction_cache(pos_device_id, is_synced);
```

### 2.2 Performance Optimization Indexes

```sql
-- Read-heavy queries
CREATE INDEX idx_stocks_branch ON stocks(branch_id);
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_transactions_voided ON transactions(is_voided) WHERE is_voided = FALSE;
CREATE INDEX idx_promo_valid ON promotions(valid_from, valid_until) WHERE is_active = TRUE;

-- Partitioning untuk tables besar (opsional)
CREATE TABLE transactions_2026 PARTITION OF transactions
  FOR VALUES FROM ('2026-01-01') TO ('2027-01-01');
```

---

## 3. ALGORITMA SINKRONISASI DATA (Offline-First Logic)

### 3.1 Flow Diagram

```
POS OFFLINE (IndexedDB)
  ↓
Collect transactions locally
  ↓
[ONLINE CHECK] → No internet?
  ├─ Yes → Queue in IndexedDB + Service Worker
  └─ No → Proceed to sync

SYNC PROCESS (Batch Upload)
  ↓
1. Generate transaction checksum (idempotency key)
2. POST /api/transactions/batch-create
   - Include: [transaction_list, local_device_id, checksum]
3. Server validates & stores
4. Server returns: [synced_ids, conflicts, success_status]
5. Clear IndexedDB cache for synced transactions
6. Pull latest stock from server → update IndexedDB
7. Resolve conflicts (if any)

CONFLICT RESOLUTION
  ├─ Stock mismatch → Fetch latest from server
  ├─ Duplicate transaction → Idempotency key prevents re-entry
  └─ Price change → Use server-side price at sync time
```

### 3.2 Pseudocode: Client-Side (React/Vue)

```javascript
// POS Service Worker + IndexedDB Handler

class OfflineTransactionManager {
  
  constructor(deviceId, branchId) {
    this.deviceId = deviceId;
    this.branchId = branchId;
    this.db = null; // IndexedDB instance
  }

  // Initialization
  async init() {
    const request = indexedDB.open('PosDatabase', 1);
    request.onupgradeneeded = (e) => {
      const db = e.target.result;
      // Create object stores
      db.createObjectStore('transactions', { keyPath: 'localId' });
      db.createObjectStore('products', { keyPath: 'id' });
      db.createObjectStore('stocks', { keyPath: 'id' });
      db.createObjectStore('promos', { keyPath: 'id' });
    };
    this.db = await new Promise((resolve, reject) => {
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  // Create local transaction
  async createLocalTransaction(items, paymentMethod) {
    const transaction = {
      localId: `${this.deviceId}-${Date.now()}-${Math.random()}`,
      branchId: this.branchId,
      items: items,
      totalAmount: items.reduce((sum, item) => 
        sum + (item.quantity * item.unitPrice), 0),
      discountAmount: 0,
      paymentMethod: paymentMethod,
      timestamp: new Date().toISOString(),
      syncStatus: 'PENDING',
      checksum: null // Generated for idempotency
    };

    // Calculate checksum (SHA256 of transaction data)
    transaction.checksum = await this.generateChecksum(transaction);

    // Store in IndexedDB
    const store = this.db
      .transaction('transactions', 'readwrite')
      .objectStore('transactions');
    
    await new Promise((resolve, reject) => {
      const req = store.add(transaction);
      req.onsuccess = () => resolve(transaction);
      req.onerror = () => reject(req.error);
    });

    return transaction;
  }

  // Batch sync dengan server
  async syncPendingTransactions() {
    const pendingTx = await this.getPendingTransactions();
    
    if (pendingTx.length === 0) return { synced: 0, failed: 0 };

    try {
      const response = await fetch('/api/v1/transactions/batch-sync', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${this.authToken}`,
          'X-Device-ID': this.deviceId,
          'X-Idempotency-Key': this.generateBatchKey(pendingTx)
        },
        body: JSON.stringify({
          transactions: pendingTx,
          deviceId: this.deviceId,
          branchId: this.branchId,
          syncTimestamp: new Date().toISOString()
        })
      });

      if (!response.ok) {
        console.error('Sync failed:', response.status);
        return { synced: 0, failed: pendingTx.length };
      }

      const syncResult = await response.json();

      // Update local cache based on response
      for (const syncedId of syncResult.syncedIds) {
        await this.updateLocalTransactionStatus(syncedId, 'SYNCED');
      }

      // Handle conflicts
      if (syncResult.conflicts.length > 0) {
        await this.resolveConflicts(syncResult.conflicts);
      }

      // Fetch latest stock & promo data
      await this.updateCacheFromServer(syncResult.latestStocks, syncResult.latestPromos);

      return { synced: syncResult.syncedIds.length, failed: syncResult.conflicts.length };
    } catch (error) {
      console.error('Sync error:', error);
      return { synced: 0, failed: pendingTx.length };
    }
  }

  // Generate checksum untuk idempotency
  async generateChecksum(transaction) {
    const data = JSON.stringify({
      localId: transaction.localId,
      items: transaction.items.map(i => ({ productId: i.productId, qty: i.quantity })),
      total: transaction.totalAmount
    });
    const encoder = new TextEncoder();
    const buffer = await crypto.subtle.digest('SHA-256', encoder.encode(data));
    return Array.from(new Uint8Array(buffer))
      .map(b => b.toString(16).padStart(2, '0'))
      .join('');
  }

  // Resolve stock conflicts
  async resolveConflicts(conflicts) {
    for (const conflict of conflicts) {
      if (conflict.type === 'STOCK_MISMATCH') {
        // Fetch latest stock from server
        const latestStock = await this.fetchLatestStock(conflict.productId);
        await this.updateLocalStock(conflict.productId, latestStock);
        
        // Mark transaction as needing review
        await this.updateLocalTransactionStatus(
          conflict.transactionId, 
          'CONFLICT_RESOLVED'
        );
      }
    }
  }

  // Helper: Get pending transactions
  async getPendingTransactions() {
    const store = this.db
      .transaction('transactions', 'readonly')
      .objectStore('transactions');
    
    return await new Promise((resolve, reject) => {
      const index = store.index('syncStatus');
      const range = IDBKeyRange.only('PENDING');
      const req = index.getAll(range);
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error);
    });
  }
}
```

### 3.3 Server-Side Laravel Implementation

```php
namespace App\Http\Controllers\Api;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionSyncController extends Controller
{
    /**
     * Batch sync transactions dari offline POS
     * 
     * Request body:
     * {
     *   "transactions": [...],
     *   "deviceId": "device-123",
     *   "branchId": "branch-uuid",
     *   "syncTimestamp": "2026-05-11T10:30:00Z"
     * }
     */
    public function batchSync(Request $request)
    {
        $validated = $request->validate([
            'transactions' => 'required|array',
            'deviceId' => 'required|string',
            'branchId' => 'required|uuid',
            'syncTimestamp' => 'required|date_format:Y-m-d\TH:i:s\Z',
        ]);

        $idempotencyKey = $request->header('X-Idempotency-Key');
        
        // Prevent duplicate sync dengan idempotency key
        $existingSync = DB::table('offline_transaction_cache')
            ->where('idempotency_key', $idempotencyKey)
            ->where('is_synced', true)
            ->first();

        if ($existingSync) {
            return response()->json([
                'message' => 'Batch already synced',
                'syncedIds' => json_decode($existingSync->synced_ids),
                'conflicts' => []
            ]);
        }

        $syncedIds = [];
        $conflicts = [];
        $latestStocks = [];
        $latestPromos = [];

        // Process each transaction dalam transaction block
        DB::transaction(function () use (
            $validated, $idempotencyKey, &$syncedIds, &$conflicts,
            &$latestStocks, &$latestPromos
        ) {
            foreach ($validated['transactions'] as $txData) {
                try {
                    // Validate checksum (idempotency check)
                    $calculatedChecksum = $this->calculateChecksum($txData);
                    if ($calculatedChecksum !== $txData['checksum']) {
                        $conflicts[] = [
                            'localId' => $txData['localId'],
                            'type' => 'CHECKSUM_MISMATCH',
                            'message' => 'Transaction data mismatch'
                        ];
                        continue;
                    }

                    // Check if transaction already exists
                    $existing = Transaction::where(
                        'local_transaction_id', 
                        $txData['localId']
                    )->first();

                    if ($existing) {
                        $syncedIds[] = $txData['localId'];
                        continue;
                    }

                    // Create transaction
                    $transaction = new Transaction([
                        'organization_id' => auth()->user()->organization_id,
                        'branch_id' => $validated['branchId'],
                        'transaction_type' => 'SALES',
                        'transaction_date' => now(),
                        'cashier_id' => auth()->id(),
                        'total_amount' => $txData['totalAmount'],
                        'discount_amount' => $txData['discountAmount'] ?? 0,
                        'final_amount' => $txData['finalAmount'] ?? 
                            ($txData['totalAmount'] - ($txData['discountAmount'] ?? 0)),
                        'payment_method' => $txData['paymentMethod'],
                        'sync_status' => 'SYNCED',
                        'local_transaction_id' => $txData['localId'],
                    ]);
                    $transaction->save();

                    // Process items & deduct stock
                    foreach ($txData['items'] as $item) {
                        // Create transaction item
                        TransactionItem::create([
                            'transaction_id' => $transaction->id,
                            'product_id' => $item['productId'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unitPrice'],
                            'discount_per_item' => $item['discountPerItem'] ?? 0,
                        ]);

                        // Deduct stock (with version control for optimistic locking)
                        $updated = Stock::where('branch_id', $validated['branchId'])
                            ->where('product_id', $item['productId'])
                            ->where('version', Stock::where('branch_id', $validated['branchId'])
                                ->where('product_id', $item['productId'])
                                ->value('version'))
                            ->update([
                                'quantity_on_hand' => DB::raw('quantity_on_hand - ' . $item['quantity']),
                                'version' => DB::raw('version + 1'),
                                'updated_at' => now()
                            ]);

                        if ($updated === 0) {
                            // Stock conflict: version mismatch
                            $conflicts[] = [
                                'transactionId' => $transaction->id,
                                'type' => 'STOCK_MISMATCH',
                                'productId' => $item['productId'],
                                'message' => 'Stock was modified by another transaction'
                            ];
                            
                            // Mark transaction as conflict
                            $transaction->update(['sync_status' => 'CONFLICT']);
                            throw new \Exception('Stock version mismatch');
                        }

                        // Log inventory change
                        InventoryLog::create([
                            'branch_id' => $validated['branchId'],
                            'product_id' => $item['productId'],
                            'log_type' => 'SALE',
                            'quantity_change' => -$item['quantity'],
                            'reference_doc_type' => 'TRANSACTION',
                            'reference_doc_id' => $transaction->id,
                            'recorded_by' => auth()->id(),
                        ]);
                    }

                    $syncedIds[] = $txData['localId'];

                } catch (\Exception $e) {
                    $conflicts[] = [
                        'localId' => $txData['localId'],
                        'type' => 'SYNC_ERROR',
                        'message' => $e->getMessage()
                    ];
                }
            }
        });

        // Fetch latest stocks & promos untuk update cache
        $latestStocks = Stock::where('branch_id', $validated['branchId'])
            ->with('product')
            ->get();

        $latestPromos = Promotion::where('organization_id', auth()->user()->organization_id)
            ->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->get();

        // Cache sync result
        DB::table('offline_transaction_cache')->insert([
            'pos_device_id' => $validated['deviceId'],
            'branch_id' => $validated['branchId'],
            'transaction_data' => json_encode($validated['transactions']),
            'sync_attempt_count' => 1,
            'is_synced' => true,
            'synced_ids' => json_encode($syncedIds),
            'conflicts_count' => count($conflicts),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'syncedIds' => $syncedIds,
            'conflicts' => $conflicts,
            'latestStocks' => $latestStocks,
            'latestPromos' => $latestPromos,
            'message' => count($syncedIds) . ' transactions synced, ' . 
                         count($conflicts) . ' conflicts'
        ], 200);
    }

    private function calculateChecksum($transaction)
    {
        $data = json_encode([
            'localId' => $transaction['localId'],
            'items' => array_map(fn($i) => [
                'productId' => $i['productId'],
                'qty' => $i['quantity']
            ], $transaction['items']),
            'total' => $transaction['totalAmount']
        ]);
        return hash('sha256', $data);
    }
}
```

---

## 4. ADVANCED DISCOUNT ENGINE

### 4.1 Discount Types

```json
{
  "PERCENTAGE": {
    "discountValue": 15,
    "description": "Diskon 15% untuk semua produk"
  },
  "FIXED": {
    "discountValue": 50000,
    "description": "Diskon Rp50.000 per transaksi"
  },
  "BUNDLING": {
    "rules": [
      {
        "requiredItems": [
          {"productId": "prod-001", "minQty": 2},
          {"productId": "prod-002", "minQty": 1}
        ],
        "discountValue": 100000,
        "description": "Beli 2x Prod-001 + 1x Prod-002 dapat diskon Rp100.000"
      }
    ]
  },
  "TIERED": {
    "tiers": [
      {"minAmount": 500000, "maxAmount": 999999, "discountPercent": 5},
      {"minAmount": 1000000, "maxAmount": 4999999, "discountPercent": 10},
      {"minAmount": 5000000, "discountPercent": 15}
    ]
  },
  "MEMBER_BASED": {
    "memberTier": "GOLD",
    "discountPercent": 20
  },
  "FLASH_SALE": {
    "productIds": ["prod-001", "prod-002"],
    "discountPercent": 40,
    "maxDiscount": 200000,
    "validFrom": "2026-05-11T10:00:00Z",
    "validUntil": "2026-05-11T12:00:00Z"
  }
}
```

### 4.2 Client-Side Discount Calculator (JavaScript)

```javascript
class DiscountEngine {
  
  constructor(promos) {
    this.promos = promos;
  }

  /**
   * Kalkulasi total discount untuk transaction
   * @param {Array} items - Transaction items
   * @param {Object} customer - Customer info
   * @param {Number} subtotal - Total sebelum discount
   */
  calculateTotalDiscount(items, customer, subtotal) {
    let totalDiscount = 0;
    const appliedPromos = [];

    // Iterate setiap promo (priority by type)
    for (const promo of this.sortPromosByPriority(this.promos)) {
      
      // Check validity
      if (!this.isPromoValid(promo)) continue;

      let discount = 0;

      switch (promo.promoType) {
        case 'PERCENTAGE':
          discount = this.applyPercentageDiscount(subtotal, promo);
          break;

        case 'FIXED':
          discount = Math.min(promo.discountValue, subtotal - totalDiscount);
          break;

        case 'BUNDLING':
          discount = this.applyBundlingDiscount(items, promo);
          break;

        case 'TIERED':
          discount = this.applyTieredDiscount(subtotal - totalDiscount, promo);
          break;

        case 'MEMBER_BASED':
          if (this.isMemberTierEligible(customer, promo.memberTier)) {
            discount = this.applyPercentageDiscount(subtotal - totalDiscount, promo);
          }
          break;

        case 'FLASH_SALE':
          discount = this.applyFlashSaleDiscount(items, promo);
          break;
      }

      // Cap discount
      if (promo.maxDiscountPerTransaction) {
        discount = Math.min(discount, promo.maxDiscountPerTransaction);
      }

      if (discount > 0) {
        totalDiscount += discount;
        appliedPromos.push({
          promoId: promo.id,
          promoName: promo.name,
          discountAmount: discount
        });
      }
    }

    return {
      totalDiscount: Math.min(totalDiscount, subtotal),
      appliedPromos: appliedPromos
    };
  }

  applyPercentageDiscount(amount, promo) {
    return Math.round(amount * promo.discountValue / 100);
  }

  applyBundlingDiscount(items, promo) {
    // Check if all required items exist
    const matchedBundles = [];
    for (const bundleRule of promo.rules) {
      let bundleMatch = true;
      for (const required of bundleRule.requiredItems) {
        const item = items.find(i => i.productId === required.productId);
        if (!item || item.quantity < required.minQty) {
          bundleMatch = false;
          break;
        }
      }
      if (bundleMatch) {
        matchedBundles.push(bundleRule);
      }
    }
    
    return matchedBundles.reduce((sum, rule) => sum + rule.discountValue, 0);
  }

  applyTieredDiscount(amount, promo) {
    const applicableTier = promo.tiers.find(tier => 
      amount >= tier.minAmount && (!tier.maxAmount || amount <= tier.maxAmount)
    );
    
    return applicableTier 
      ? Math.round(amount * applicableTier.discountPercent / 100)
      : 0;
  }

  applyFlashSaleDiscount(items, promo) {
    const eligibleItems = items.filter(item => 
      promo.productIds.includes(item.productId)
    );
    
    const eligibleAmount = eligibleItems.reduce((sum, item) => 
      sum + (item.quantity * item.unitPrice), 0);
    
    return Math.min(
      Math.round(eligibleAmount * promo.discountPercent / 100),
      promo.maxDiscount || Infinity
    );
  }

  isPromoValid(promo) {
    const now = new Date();
    return promo.isActive && 
           new Date(promo.validFrom) <= now && 
           new Date(promo.validUntil) >= now;
  }

  isMemberTierEligible(customer, requiredTier) {
    const tierHierarchy = ['BRONZE', 'SILVER', 'GOLD', 'PLATINUM'];
    const customerIndex = tierHierarchy.indexOf(customer.memberTier);
    const requiredIndex = tierHierarchy.indexOf(requiredTier);
    return customerIndex >= requiredIndex;
  }

  sortPromosByPriority(promos) {
    const priorityMap = { 'FLASH_SALE': 1, 'BUNDLING': 2, 'TIERED': 3, 'PERCENTAGE': 4, 'FIXED': 5, 'MEMBER_BASED': 6 };
    return [...promos].sort((a, b) => 
      (priorityMap[a.promoType] || 999) - (priorityMap[b.promoType] || 999)
    );
  }
}

// Usage dalam POS transaction
const discountEngine = new DiscountEngine(cachedPromos);
const transaction = {
  items: [
    { productId: 'prod-001', quantity: 2, unitPrice: 100000 },
    { productId: 'prod-002', quantity: 1, unitPrice: 150000 }
  ],
  customer: { memberTier: 'GOLD' }
};

const subtotal = transaction.items.reduce((sum, item) => 
  sum + (item.quantity * item.unitPrice), 0);

const { totalDiscount, appliedPromos } = discountEngine.calculateTotalDiscount(
  transaction.items,
  transaction.customer,
  subtotal
);

console.log(`Subtotal: ${subtotal}, Discount: ${totalDiscount}, Final: ${subtotal - totalDiscount}`);
console.log('Applied Promos:', appliedPromos);
```

---

## 5. INVENTORY INTELLIGENCE (SUGGESTED ORDER)

### 5.1 Two Methods Implementation

#### Metode A: Min-Max Buffer Stock

```php
namespace App\Services;

class BufferStockOrderService
{
    /**
     * Calculate suggested order quantity based on min-max method
     */
    public function calculateSuggestedOrder(
        string $productId, 
        string $branchId
    ): array {
        $stock = Stock::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->with('product')
            ->firstOrFail();

        $product = $stock->product;
        
        $currentQty = $stock->quantity_on_hand;
        $minQty = $product->reorder_point ?? 10;
        $maxQty = $product->reorder_qty ?? 50;

        $suggestedQty = 0;
        $reason = '';

        // If current stock below minimum, order until maximum
        if ($currentQty < $minQty) {
            $suggestedQty = $maxQty - $currentQty;
            $reason = 'Stok di bawah minimum. Order hingga max qty.';
        } else {
            $suggestedQty = 0;
            $reason = 'Stok masih optimal.';
        }

        return [
            'productId' => $productId,
            'productName' => $product->name,
            'currentQty' => $currentQty,
            'minQty' => $minQty,
            'maxQty' => $maxQty,
            'suggestedQty' => $suggestedQty,
            'method' => 'MIN_MAX_BUFFER',
            'reason' => $reason,
            'estimatedCost' => $suggestedQty * $product->cost_price
        ];
    }
}
```

#### Metode B: Forecasting dengan Sales History

```php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ForecastingOrderService
{
    const LOOKBACK_DAYS = 90;  // 3 bulan terakhir
    const FORECAST_DAYS = 30;   // Forecast untuk 30 hari ke depan
    const FORECAST_METHOD = 'MOVING_AVERAGE'; // atau 'EXPONENTIAL_SMOOTHING'

    /**
     * Calculate suggested order based on sales forecast
     */
    public function calculateSuggestedOrder(
        string $productId,
        string $branchId
    ): array {
        $product = Product::findOrFail($productId);
        $stock = Stock::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->firstOrFail();

        // 1. Get historical sales
        $salesHistory = $this->getSalesHistory($productId, $branchId);
        
        // 2. Calculate average daily sales
        $avgDailySales = $this->calculateAverageDailySales($salesHistory);
        
        // 3. Forecast future demand
        $forecastedDemand = $avgDailySales * self::FORECAST_DAYS;
        
        // 4. Calculate safety stock (untuk buffer ketidakpastian)
        $safetyStock = $this->calculateSafetyStock(
            $salesHistory,
            $avgDailySales,
            $product->lead_time_days
        );
        
        // 5. Calculate reorder point
        $reorderPoint = ($avgDailySales * $product->lead_time_days) + $safetyStock;
        
        // 6. Calculate order quantity
        $suggestedQty = 0;
        $reason = '';
        
        if ($stock->quantity_on_hand <= $reorderPoint) {
            // EOQ = Economic Order Quantity
            $eoq = $this->calculateEOQ($product);
            $suggestedQty = max($eoq, $forecastedDemand);
            $reason = 'Reorder point tercapai. Order untuk memenuhi forecast demand.';
        } else {
            $suggestedQty = 0;
            $reason = 'Stok masih mencukupi untuk 30 hari ke depan.';
        }

        return [
            'productId' => $productId,
            'productName' => $product->name,
            'currentQty' => $stock->quantity_on_hand,
            'avgDailySales' => round($avgDailySales, 2),
            'forecastedDemand' => round($forecastedDemand, 2),
            'safetyStock' => round($safetyStock, 2),
            'reorderPoint' => round($reorderPoint, 2),
            'suggestedQty' => $suggestedQty,
            'method' => 'FORECASTING',
            'leadTimeDays' => $product->lead_time_days,
            'reason' => $reason,
            'estimatedCost' => $suggestedQty * $product->cost_price
        ];
    }

    /**
     * Get sales data dari 3 bulan terakhir
     */
    private function getSalesHistory(string $productId, string $branchId): array
    {
        $startDate = now()->subDays(self::LOOKBACK_DAYS);
        
        $sales = DB::table('transaction_items as ti')
            ->join('transactions as t', 'ti.transaction_id', '=', 't.id')
            ->where('ti.product_id', $productId)
            ->where('t.branch_id', $branchId)
            ->where('t.transaction_date', '>=', $startDate)
            ->where('t.is_voided', false)
            ->select(
                DB::raw('DATE(t.transaction_date) as sale_date'),
                DB::raw('SUM(ti.quantity) as daily_sales')
            )
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get()
            ->pluck('daily_sales', 'sale_date')
            ->toArray();

        // Fill missing dates dengan 0
        $dateRange = [];
        $current = $startDate->copy();
        $end = now();
        
        while ($current <= $end) {
            $dateKey = $current->format('Y-m-d');
            $dateRange[$dateKey] = $sales[$dateKey] ?? 0;
            $current->addDay();
        }

        return $dateRange;
    }

    /**
     * Calculate average daily sales (3-month moving average)
     */
    private function calculateAverageDailySales(array $salesHistory): float
    {
        if (empty($salesHistory)) return 0;
        
        $totalSales = array_sum($salesHistory);
        $daysWithData = count(array_filter($salesHistory, fn($s) => $s > 0));
        
        // Handle zero sales days
        return $daysWithData > 0 ? $totalSales / count($salesHistory) : 0;
    }

    /**
     * Calculate safety stock (buffer untuk volatilitas)
     * Rumus: SafetyStock = Z × σ × √(L)
     * Z = Service level (95% = 1.65)
     * σ = Standard deviation dari demand
     * L = Lead time
     */
    private function calculateSafetyStock(array $salesHistory, float $avgDailySales, int $leadTimeDays): float
    {
        // Service level 95%
        $serviceLevel = 1.65;
        
        // Calculate standard deviation
        $stdDev = $this->calculateStandardDeviation($salesHistory);
        
        return $serviceLevel * $stdDev * sqrt($leadTimeDays);
    }

    /**
     * Calculate standard deviation untuk demand variability
     */
    private function calculateStandardDeviation(array $salesHistory): float
    {
        $count = count($salesHistory);
        if ($count === 0) return 0;

        $mean = array_sum($salesHistory) / $count;
        
        $variance = array_reduce(
            $salesHistory,
            fn($sum, $value) => $sum + pow($value - $mean, 2),
            0
        ) / $count;

        return sqrt($variance);
    }

    /**
     * Economic Order Quantity (EOQ)
     * EOQ = √((2DS) / H)
     * D = Annual demand
     * S = Order cost per order
     * H = Holding cost per unit per year
     */
    private function calculateEOQ(Product $product): int
    {
        $annualDemand = 365 * $this->calculateAverageDailySales(
            $this->getSalesHistory($product->id, 'all')
        );
        
        $orderCostPerOrder = 50000; // Fixed cost per order (e.g., transport)
        $holdingCostPerYear = $product->cost_price * 0.25; // 25% dari cost price
        
        if ($holdingCostPerYear === 0) return 50; // Default
        
        $eoq = sqrt((2 * $annualDemand * $orderCostPerOrder) / $holdingCostPerYear);
        
        return (int) ceil($eoq);
    }
}
```

### 5.2 Batch Suggested Order API

```php
namespace App\Http\Controllers\Api;

use App\Services\BufferStockOrderService;
use App\Services\ForecastingOrderService;

class SuggestedOrderController extends Controller
{
    public function __construct(
        private BufferStockOrderService $bufferService,
        private ForecastingOrderService $forecastService
    ) {}

    /**
     * GET /api/v1/branches/{branchId}/suggested-orders
     * Query params:
     *   - method: MIN_MAX_BUFFER | FORECASTING | BOTH
     *   - minSuggestedQty: Filter order >= qty (e.g., 10)
     */
    public function index(string $branchId)
    {
        $method = request('method', 'FORECASTING');
        $minSuggestedQty = (int) request('minSuggestedQty', 0);

        $branch = Branch::findOrFail($branchId);
        $products = Product::where('organization_id', $branch->organization_id)
            ->where('is_active', true)
            ->get();

        $suggestions = [];

        foreach ($products as $product) {
            $suggestion = match ($method) {
                'MIN_MAX_BUFFER' => $this->bufferService->calculateSuggestedOrder(
                    $product->id, $branchId
                ),
                'FORECASTING' => $this->forecastService->calculateSuggestedOrder(
                    $product->id, $branchId
                ),
                'BOTH' => $this->compareMethods($product->id, $branchId),
                default => null
            };

            if ($suggestion && $suggestion['suggestedQty'] >= $minSuggestedQty) {
                $suggestions[] = $suggestion;
            }
        }

        // Sort by suggested quantity (desc)
        usort($suggestions, fn($a, $b) => $b['suggestedQty'] <=> $a['suggestedQty']);

        return response()->json([
            'branchId' => $branchId,
            'method' => $method,
            'totalSuggestions' => count($suggestions),
            'estimatedTotalCost' => array_sum(array_column($suggestions, 'estimatedCost')),
            'suggestions' => array_slice($suggestions, 0, 100) // Limit 100 items
        ]);
    }

    private function compareMethods(string $productId, string $branchId): array
    {
        $bufferResult = $this->bufferService->calculateSuggestedOrder($productId, $branchId);
        $forecastResult = $this->forecastService->calculateSuggestedOrder($productId, $branchId);

        return [
            ...$bufferResult,
            'bufferQty' => $bufferResult['suggestedQty'],
            'forecastQty' => $forecastResult['suggestedQty'],
            'suggestedQty' => max($bufferResult['suggestedQty'], $forecastResult['suggestedQty']),
            'method' => 'BOTH',
            'reason' => 'Menggunakan nilai terbesar dari buffer stock dan forecasting'
        ];
    }
}
```

---

## 6. SECURITY & VALIDATION PROTOCOL

### 6.1 API Security Layers

```php
namespace App\Http\Middleware;

class ApiSecurityMiddleware
{
    /**
     * 1. Token Validation (OAuth2 Passport)
     * 2. Rate Limiting
     * 3. Input Sanitization
     * 4. CORS Protection
     */
    public function handle($request, Closure $next)
    {
        // 1. Validate Bearer token
        if (!$request->bearerToken()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $token = $request->bearerToken();
        $user = \Laravel\Passport\Token::where('token', hash('sha256', $token))
            ->first();

        if (!$user || $user->revoked) {
            return response()->json(['error' => 'Invalid or revoked token'], 401);
        }

        // 2. Check token expiration
        if ($user->expires_at && $user->expires_at->isPast()) {
            return response()->json(['error' => 'Token expired'], 401);
        }

        // 3. Rate limiting (100 requests per minute per user)
        if ($this->isRateLimited($user->user_id)) {
            return response()->json(['error' => 'Too many requests'], 429);
        }

        // 4. Validate branch access (branch isolation)
        if ($request->has('branch_id')) {
            $requestedBranch = $request->input('branch_id');
            if (!$this->userCanAccessBranch($user->user_id, $requestedBranch)) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        }

        return $next($request);
    }

    private function isRateLimited($userId): bool
    {
        $key = "rate_limit:{$userId}";
        $count = Cache::increment($key);
        if ($count === 1) {
            Cache::expire($key, 60); // Reset per menit
        }
        return $count > 100;
    }

    private function userCanAccessBranch($userId, $branchId): bool
    {
        $user = User::find($userId);
        return !$user->branch_id || $user->branch_id === $branchId;
    }
}
```

### 6.2 Transaction Validation Server-Side

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransactionRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.productId' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:9999',
            'items.*.unitPrice' => 'required|numeric|min:0',
            'items.*.discountPerItem' => 'nullable|numeric|min:0',
            'paymentMethod' => 'required|in:CASH,CARD,EWALLET',
            'customerInfo' => 'nullable|array',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validasi stock tersedia
            foreach ($this->input('items') as $item) {
                $stock = Stock::where('product_id', $item['productId'])
                    ->where('branch_id', auth()->user()->branch_id)
                    ->first();

                if (!$stock || $stock->quantity_available < $item['quantity']) {
                    $validator->errors()->add(
                        'items',
                        "Insufficient stock for product: {$item['productId']}"
                    );
                }
            }

            // Validasi price tidak menyimpang lebih dari 10% dari master
            foreach ($this->input('items') as $item) {
                $product = Product::find($item['productId']);
                $priceDiff = abs($item['unitPrice'] - $product->selling_price);
                $tolerance = $product->selling_price * 0.1; // 10% tolerance

                if ($priceDiff > $tolerance) {
                    $validator->errors()->add(
                        'items',
                        "Price deviation too high for {$product->name}"
                    );
                }
            }
        });
    }
}
```

### 6.3 Void Transaction Audit

```php
namespace App\Http\Controllers\Api;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class VoidTransactionController extends Controller
{
    /**
     * POST /api/v1/transactions/{id}/void
     * 
     * Mekanisme:
     * 1. Validate authorization (cashier supervisor / manager)
     * 2. Log void reason & person
     * 3. Reverse stock adjustments
     * 4. Mark transaction as voided (soft delete pattern)
     * 5. Create reverse inventory logs
     */
    public function void(string $transactionId)
    {
        $request = request();
        $user = auth()->user();

        // Authorization check
        if (!in_array($user->role, ['MANAGER', 'SUPERVISOR'])) {
            return response()->json(['error' => 'Not authorized to void'], 403);
        }

        $transaction = Transaction::findOrFail($transactionId);

        // Validate transaction is voidable
        if ($transaction->is_voided) {
            return response()->json(['error' => 'Transaction already voided'], 400);
        }

        if ($transaction->transaction_date->diffInHours(now()) > 24) {
            return response()->json(['error' => 'Only transactions within 24h can be voided'], 400);
        }

        DB::transaction(function () use ($transaction, $user, $request) {
            // 1. Reverse stock
            foreach ($transaction->items as $item) {
                $stock = Stock::lockForUpdate()  // Pessimistic lock
                    ->where('product_id', $item->product_id)
                    ->where('branch_id', $transaction->branch_id)
                    ->first();

                if ($stock) {
                    $stock->update([
                        'quantity_on_hand' => $stock->quantity_on_hand + $item->quantity,
                        'version' => DB::raw('version + 1')
                    ]);

                    // Log reversal
                    InventoryLog::create([
                        'branch_id' => $transaction->branch_id,
                        'product_id' => $item->product_id,
                        'log_type' => 'VOID_REVERSAL',
                        'quantity_change' => $item->quantity,
                        'reference_doc_type' => 'TRANSACTION',
                        'reference_doc_id' => $transaction->id,
                        'recorded_by' => $user->id,
                        'notes' => "Void reason: {$request->input('reason')}"
                    ]);
                }
            }

            // 2. Mark transaction voided
            $transaction->update([
                'is_voided' => true,
                'void_reason' => $request->input('reason'),
                'void_date' => now(),
                'voided_by' => $user->id
            ]);

            // 3. Log void action untuk audit trail
            AuditLog::create([
                'action_type' => 'VOID_TRANSACTION',
                'actor_id' => $user->id,
                'target_type' => 'TRANSACTION',
                'target_id' => $transaction->id,
                'description' => "Voided transaction for reason: {$request->input('reason')}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaction voided successfully',
            'transaction' => $transaction->fresh()
        ]);
    }
}
```

### 6.4 Data Encryption at Rest

```php
// .env
ENCRYPTION_ALGORITHM=AES-256-CBC
SENSITIVE_FIELDS_ENCRYPTED=true

// App/Models/Traits/EncryptsAttributes.php
namespace App\Models\Traits;

trait EncryptsAttributes
{
    /**
     * Encrypt sensitive fields
     */
    protected $encryptable = [
        'customer_phone',
        'customer_id_number',
        'bank_account',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            foreach ($model->encryptable as $field) {
                if ($model->$field) {
                    $model->$field = encrypt($model->$field);
                }
            }
        });

        static::retrieved(function ($model) {
            foreach ($model->encryptable as $field) {
                if ($model->$field) {
                    $model->$field = decrypt($model->$field);
                }
            }
        });
    }
}
```

---

## 7. PERFORMANCE TUNING CHECKLIST

### 7.1 Database Optimization

- [x] Implement read/write splitting dengan PostgreSQL replication
- [x] Add composite indexes untuk frequently-queried columns
- [x] Use connection pooling (PgBouncer)
- [x] Implement query caching untuk read-heavy operations
- [x] Archive old transactions ke separate table (partitioning)
- [x] Use full-text search via Meilisearch (bukan database LIKE queries)

### 7.2 Application Layer

- [x] Implement Redis caching untuk session & config
- [x] Use Laravel's query optimization tools (eager loading, select specific columns)
- [x] Implement pagination untuk list endpoints (max 100 items per page)
- [x] Use queued jobs untuk heavy operations (reporting, notifications)
- [x] Implement API response caching dengan ETag headers

### 7.3 Frontend Optimization

- [x] Implement service worker untuk asset caching
- [x] Use IndexedDB untuk transaction cache (offline-first)
- [x] Lazy-load components & images
- [x] Implement virtual scrolling untuk long lists
- [x] Minimize bundle size (code splitting, tree-shaking)

---

## 8. MONITORING & LOGGING

```php
// Laravel Logging Config
'channels' => [
    'transactions' => [
        'driver' => 'daily',
        'path' => storage_path('logs/transactions.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 30,
    ],
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
    ],
    'inventory' => [
        'driver' => 'daily',
        'path' => storage_path('logs/inventory.log'),
        'level' => 'info',
    ],
    'sentry' => [
        'driver' => 'sentry',
        'level' => 'error',
    ],
]
```

---

## KESIMPULAN

Arsitektur ini dirancang untuk:
- **Skalabilitas**: Stateless backend, horizontal scaling
- **Reliability**: Offline-first POS, data sync dengan conflict resolution
- **Security**: OAuth2, encryption, audit trails
- **Performance**: Caching, search optimization, async job processing
- **Flexibility**: Modular discount engine, two inventory methods

Deployment dapat dimulai dengan environment single-server untuk development, scale out ke multi-server setup dengan load balancer & replicated database untuk production.
