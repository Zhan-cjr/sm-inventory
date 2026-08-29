<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

Route::get('/print/transaction/{transaction}', function (\App\Models\Transaction $transaction) {
    return view('print.transaction-receipt', compact('transaction'));
})->name('print.transaction')->middleware('web');

Route::get('/print/report/{type}', [\App\Http\Controllers\ReportPrintController::class, 'print'])->name('print.report')->middleware('web');

Route::get('/print/document/{type}', [\App\Http\Controllers\DocumentPrintController::class, 'print'])->name('print.document')->middleware(['web', 'auth']);

Route::get('/print/eod/{shift}', [\App\Http\Controllers\Api\V1\ShiftController::class, 'printEod'])->name('print.eod')->middleware('web');

// ============================================================
// Stok Opname — Halaman Cetak QR (perlu session Filament admin)
// ============================================================
Route::middleware(['web', 'auth'])->group(function () {
    // Cetak semua QR rak dalam satu sesi
    Route::get('/opname/print/{sessionId}/all',
        [\App\Http\Controllers\OpnamePublicController::class, 'printQr'])
        ->name('opname.print-qr');

    // Cetak QR satu rak
    Route::get('/opname/print/rack/{rackSessionId}',
        [\App\Http\Controllers\OpnamePublicController::class, 'printQrSingle'])
        ->name('opname.print-qr-single');

    // Cetak laporan Final Check
    Route::get('/opname/print/{sessionId}/final-check', function (string $sessionId) {
        $session = \App\Models\StockOpnameSession::with([
            'branch',
            'items.product',
            'items.rackSession.rack',
        ])->findOrFail($sessionId);

        // Sertakan item DISCREPANCY dan FINAL_DONE untuk laporan lengkap
        $allDiscItems = $session->items()
            ->whereIn('status', ['DISCREPANCY', 'FINAL_DONE'])
            ->with(['product', 'rackSession.rack'])
            ->get();

        $grouped = [];
        foreach ($allDiscItems as $item) {
            $pid = $item->product_id;
            if (!isset($grouped[$pid])) {
                $allItemsForProduct = $session->items()
                    ->where('product_id', $pid)->get();

                $grouped[$pid] = [
                    'product_name' => $item->product?->name,
                    'product_sku'  => $item->product?->sku,
                    'system_qty'   => $item->system_quantity,
                    'total_count1' => $allItemsForProduct->sum('count1_quantity'),
                    'total_count2' => $allItemsForProduct->sum('count2_quantity'),
                    'racks'        => [],
                ];
            }
            $grouped[$pid]['racks'][] = [
                'item_id'         => $item->id,
                'rack_code'       => $item->rackSession?->rack?->rack_code,
                'rack_name'       => $item->rackSession?->rack?->rack_name,
                'count1_quantity' => $item->count1_quantity,
                'count2_quantity' => $item->count2_quantity,
                'discrepancy'     => $item->count2_quantity - $item->count1_quantity,
                'final_quantity'  => $item->final_quantity,
                'final_notes'     => $item->final_notes,
            ];
        }
        $groups = array_values($grouped);

        return view('print.opname-final-check', compact('session', 'groups'));
    })->name('opname.print-final-check');
});

// ============================================================
// Stok Opname — Halaman Publik (tanpa login)
// ============================================================
Route::prefix('opname')->name('opname.')->middleware('web')->group(function () {

    // Penghitung 1: scan QR rak → isi qty fisik
    Route::get('/hitung/{rackToken}',  [\App\Http\Controllers\OpnamePublicController::class, 'showCount1'])
        ->name('hitung');
    Route::post('/hitung/{rackToken}', [\App\Http\Controllers\OpnamePublicController::class, 'submitCount1'])
        ->name('hitung.submit');

    // Pengecek 2: scan QR sesi → pilih rak yang siap dicek
    Route::get('/cek/{sessionToken}',  [\App\Http\Controllers\OpnamePublicController::class, 'showCheck2List'])
        ->name('cek');

    // Pengecek 2: form input untuk rak tertentu
    Route::get('/cek/{sessionToken}/rak/{rackId}',  [\App\Http\Controllers\OpnamePublicController::class, 'showCheck2Form'])
        ->name('cek.form');
    Route::post('/cek/{sessionToken}/rak/{rackId}', [\App\Http\Controllers\OpnamePublicController::class, 'submitCount2'])
        ->name('cek.submit');

    // Halaman selesai
    Route::get('/selesai', [\App\Http\Controllers\OpnamePublicController::class, 'done'])
        ->name('done');

    // Pencarian produk dinamis via barcode (AJAX)
    Route::get('/search-product', [\App\Http\Controllers\OpnamePublicController::class, 'searchProduct'])
        ->name('search-product');

    // ★ PORTAL UTAMA — scan QR sesi, pilih peran (penghitung atau pengecek)
    //   QR code ini yang dicetak admin dan ditempel di papan pengumuman / dibagikan ke tim
    //   (Ditempatkan paling bawah agar tidak membayangi route statis seperti /selesai)
    Route::get('/{sessionToken}', [\App\Http\Controllers\OpnamePublicController::class, 'showPortal'])
        ->name('portal');
});

// ============================================================
// Pengecekan Penerimaan Gudang (Mobile)
// ============================================================
Route::prefix('warehouse/receive')->name('warehouse.receive.')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [\App\Http\Controllers\WarehouseCheckController::class, 'index'])->name('index');
    Route::post('/search', [\App\Http\Controllers\WarehouseCheckController::class, 'search'])->name('search');
    Route::get('/scan/{po_id}', [\App\Http\Controllers\WarehouseCheckController::class, 'scan'])->name('scan');
    Route::post('/submit/{po_id}', [\App\Http\Controllers\WarehouseCheckController::class, 'submit'])->name('submit');
});

Route::get('/admin/logout-get', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/admin/login');
})->name('admin.logout-get')->middleware('web');

Route::get('/print/stock-card/{productId}/{branchId}', function ($productId, $branchId) {
    $product = \App\Models\Product::findOrFail($productId);
    $branch = \App\Models\Branch::findOrFail($branchId);
    $logs = \App\Models\InventoryLog::where('product_id', $productId)
        ->where('branch_id', $branchId)
        ->latest('id')
        ->get();
        
    return view('print.documents.stock-card-print', compact('product', 'branch', 'logs'));
})->name('print.stock-card')->middleware(['web', 'auth']);

// Print Barcodes
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/print/barcodes/label', [\App\Http\Controllers\BarcodePrintController::class, 'printLabel'])->name('print.barcode.label');
    Route::get('/print/barcodes/pricecard', [\App\Http\Controllers\BarcodePrintController::class, 'printPricecard'])->name('print.barcode.pricecard');
});

// ============================================================
// Wireless Scanner Gun (HP jadi Scanner Tembak ke PC)
// ============================================================
Route::get('/scanner-gun', [\App\Http\Controllers\ScannerGunController::class, 'index'])->name('scanner.gun');
Route::get('/scanner-gun/qr', [\App\Http\Controllers\ScannerGunController::class, 'qr'])->name('scanner.qr');
Route::post('/scanner-gun/push', [\App\Http\Controllers\ScannerGunController::class, 'push'])->name('scanner.push');
Route::get('/scanner-gun/poll', [\App\Http\Controllers\ScannerGunController::class, 'poll'])->name('scanner.poll');
Route::post('/scanner-gun/heartbeat', [\App\Http\Controllers\ScannerGunController::class, 'heartbeat'])->name('scanner.heartbeat');



