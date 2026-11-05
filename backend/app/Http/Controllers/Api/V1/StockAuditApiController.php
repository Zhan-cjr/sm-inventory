<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StockAuditInspection;
use App\Models\StockOpnameRack;
use App\Services\StockAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockAuditApiController extends Controller
{
    protected StockAuditService $service;

    public function __construct(StockAuditService $service)
    {
        $this->service = $service;
    }

    /**
     * Pastikan user memiliki izin sidak audit stok
     */
    protected function authorizeAudit(Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $role = strtoupper($user->role ?? '');
        $isManagerOrAdmin = in_array($role, ['MANAGER', 'ADMIN', 'SUPERVISOR', 'SPV', 'EDP', 'SUPERADMIN', 'SUPER_ADMIN', 'AUDITOR']);
        $hasAuth = $user->hasCustomAuthorization('ACCESS_STOCK_AUDIT');

        if (!$isManagerOrAdmin && !$hasAuth) {
            return response()->json(['error' => 'Akses Ditolak: Anda tidak memiliki izin Sidak Audit Stok.'], 403);
        }

        return null;
    }

    /**
     * Ambil riwayat / sesi sidak yang sedang aktif di cabang
     */
    public function index(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeAudit($request)) return $deny;

        $user = $request->user();
        $branchId = $request->query('branch_id') ?: $user->branch_id;
        if (!$branchId) {
            $branchId = \App\Models\Branch::where('organization_id', $user->organization_id)->value('id')
                ?? \App\Models\Branch::first()?->id;
        }

        $inspections = StockAuditInspection::with(['auditor'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return response()->json(['data' => $inspections]);
    }

    /**
     * Buat Sesi Sidak Baru (Free Scan atau Undian Acak)
     */
    public function store(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeAudit($request)) return $deny;

        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'branch_id'       => 'nullable|uuid',
            'inspection_mode' => 'nullable|in:FREE_SCAN,RANDOM_LOTTERY,RISK_BASED',
            'target_count'    => 'nullable|integer|min:1|max:200',
            'rack_ids'        => 'nullable|array',
            'category_ids'    => 'nullable|array',
            'notes'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $data = $request->all();
        $data['branch_id'] = $request->input('branch_id') ?: $user->branch_id;
        $data['organization_id'] = $user->organization_id;

        if (!$data['branch_id']) {
            $data['branch_id'] = \App\Models\Branch::where('organization_id', $user->organization_id)->value('id')
                ?? \App\Models\Branch::first()?->id;
        }

        $inspection = $this->service->createInspection($data, $user);

        return response()->json([
            'message' => 'Sesi Sidak Audit berhasil dibuat',
            'data'    => $inspection->load(['items.product', 'auditor']),
        ], 201);
    }

    /**
     * Detail Sesi Sidak
     */
    public function show(Request $request, string $id): JsonResponse
    {
        if ($deny = $this->authorizeAudit($request)) return $deny;

        $inspection = StockAuditInspection::with(['items.product', 'items.rack', 'auditor', 'adjustment'])
            ->findOrFail($id);

        return response()->json(['data' => $inspection]);
    }

    /**
     * Cari produk untuk sidak (Barcode / SKU / Nama)
     */
    public function searchProduct(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeAudit($request)) return $deny;

        $queryStr = $request->query('query', '');
        $user = $request->user();
        $branchId = $request->query('branch_id') ?: $user->branch_id;
        if (!$branchId) {
            $branchId = \App\Models\Branch::where('organization_id', $user->organization_id)->value('id')
                ?? \App\Models\Branch::first()?->id;
        }

        $results = $this->service->searchProduct($queryStr, $branchId);

        return response()->json(['data' => $results]);
    }

    /**
     * Tetapkan produk katalog global ke cabang saat sidak
     */
    public function assignBranchProduct(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeAudit($request)) return $deny;

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|uuid',
            'branch_id'  => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $stock = $this->service->assignProductToBranch($request->product_id, $request->branch_id);
        $product = $stock->product;

        return response()->json([
            'message' => 'Produk berhasil ditetapkan ke cabang ini.',
            'data'    => [
                'id'                    => $product->id,
                'name'                  => $product->name,
                'sku'                   => $product->sku,
                'barcode'               => $product->barcode,
                'unit'                  => $product->unit ?? 'pcs',
                'cost_price'            => (float) ($stock->cost_price_tax ?: $stock->cost_price ?: $product->cost_price_tax ?: $product->cost_price ?: 0),
                'stock'                 => (float) $stock->salable_quantity,
                'is_assigned_to_branch' => true,
                'branch_id'             => $stock->branch_id,
                'racks'                 => '-',
                'rack_id'               => null,
            ]
        ]);
    }

    /**
     * Scan / Input hitung fisik produk ke sesi sidak
     */
    public function recordItem(Request $request, string $id): JsonResponse
    {
        if ($deny = $this->authorizeAudit($request)) return $deny;

        $validator = Validator::make($request->all(), [
            'product_id'        => 'required|uuid',
            'physical_quantity' => 'required|numeric|min:0',
            'rack_id'           => 'nullable|uuid',
            'notes'             => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $inspection = StockAuditInspection::findOrFail($id);
        if (in_array($inspection->status, ['ADJUSTED', 'CLOSED'])) {
            return response()->json(['error' => 'Sesi sidak ini sudah ditutup.'], 422);
        }

        $item = $this->service->recordItemCount(
            $inspection,
            $request->product_id,
            (float) $request->physical_quantity,
            $request->rack_id,
            $request->notes
        );

        return response()->json([
            'message' => 'Hitungan fisik berhasil dicatat',
            'item'    => $item->load(['product', 'rack']),
            'summary' => [
                'total_items_checked'     => $inspection->fresh()->total_items_checked,
                'total_items_discrepancy' => $inspection->fresh()->total_items_discrepancy,
                'total_loss_nominal'      => $inspection->fresh()->total_loss_nominal,
                'total_gain_nominal'      => $inspection->fresh()->total_gain_nominal,
            ],
        ]);
    }

    /**
     * Hapus item dari sesi sidak
     */
    public function removeItem(Request $request, string $id, string $itemId): JsonResponse
    {
        if ($deny = $this->authorizeAudit($request)) return $deny;

        $inspection = StockAuditInspection::findOrFail($id);
        $this->service->removeItem($inspection, $itemId);

        return response()->json([
            'message' => 'Item berhasil dihapus dari sesi sidak',
            'summary' => [
                'total_items_checked'     => $inspection->fresh()->total_items_checked,
                'total_items_discrepancy' => $inspection->fresh()->total_items_discrepancy,
                'total_loss_nominal'      => $inspection->fresh()->total_loss_nominal,
                'total_gain_nominal'      => $inspection->fresh()->total_gain_nominal,
            ],
        ]);
    }

    /**
     * Selesaikan Sesi Sidak (Simpan Temuan atau Adjust Langsung)
     */
    public function finish(Request $request, string $id): JsonResponse
    {
        if ($deny = $this->authorizeAudit($request)) return $deny;

        $validator = Validator::make($request->all(), [
            'action'          => 'required|in:INVESTIGATION,ADJUST,CLOSE',
            'acknowledged_by' => 'nullable|string|max:100',
            'notes'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $inspection = StockAuditInspection::findOrFail($id);
        $updated = $this->service->finishInspection(
            $inspection,
            $request->action,
            $request->acknowledged_by,
            $request->notes,
            $request->user()
        );

        return response()->json([
            'message' => $request->action === 'ADJUST' 
                ? 'Sidak selesai dan koreksi stok berhasil diterapkan ke sistem.' 
                : 'Laporan temuan sidak berhasil disimpan untuk investigasi.',
            'data'    => $updated,
        ]);
    }

    /**
     * Eksekusi Koreksi Stok untuk Sidak yang Berstatus INVESTIGATION_PENDING
     */
    public function applyAdjustment(Request $request, string $id): JsonResponse
    {
        if ($deny = $this->authorizeAudit($request)) return $deny;

        $inspection = StockAuditInspection::findOrFail($id);
        if ($inspection->status === 'ADJUSTED') {
            return response()->json(['error' => 'Sesi sidak ini sudah disesuaikan stoknya sebelumnya.'], 422);
        }

        $adjustment = $this->service->applyStockAdjustment($inspection, $request->user());

        return response()->json([
            'message'    => 'Penyesuaian stok berhasil diterapkan!',
            'adjustment' => $adjustment,
            'inspection' => $inspection->fresh(),
        ]);
    }

    /**
     * Ambil daftar rak aktif cabang (untuk filter undian acak)
     */
    public function getActiveRacks(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeAudit($request)) return $deny;

        $user = $request->user();
        $branchId = $request->query('branch_id') ?: $user->branch_id;
        if (!$branchId) {
            $branchId = \App\Models\Branch::where('organization_id', $user->organization_id)->value('id')
                ?? \App\Models\Branch::first()?->id;
        }

        $racks = StockOpnameRack::where('branch_id', $branchId)
            ->where('is_active', true)
            ->select('id', 'rack_name', 'rack_code')
            ->orderBy('rack_name')
            ->get();

        return response()->json(['data' => $racks]);
    }
}

