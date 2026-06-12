<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceipt;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\Auth;

class DocumentPrintController extends Controller
{
    public function print(Request $request, $type)
    {
        $ids = $request->input('ids', []);
        if ($request->has('id')) {
            $ids[] = $request->input('id');
        }
        
        if (empty($ids)) {
            abort(404, 'Tidak ada dokumen yang dipilih');
        }

        // We convert scalar id to array to handle single and bulk print unified
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $documents = [];
        $viewName = '';
        $title = '';

        switch ($type) {
            case 'po':
                $documents = PurchaseOrder::with(['supplier', 'branch', 'items.product', 'creator'])
                    ->whereIn('id', $ids)->get();
                foreach ($documents as $doc) {
                    if ($doc->expired_date && $doc->expired_date < now()->toDateString()) {
                        abort(403, 'PO ' . $doc->po_number . ' sudah kadaluwarsa dan tidak dapat dicetak.');
                    }
                }
                $viewName = 'print.documents.purchase-order';
                $title = 'Nota Pesanan Pembelian';
                break;
            case 'receipt':
                $documents = GoodsReceipt::with(['supplier', 'branch', 'items.product', 'purchaseOrder'])
                    ->whereIn('id', $ids)->get();
                $viewName = 'print.documents.goods-receipt';
                $title = 'Nota Penerimaan Barang';
                break;
            case 'purchase-return':
                $documents = \App\Models\PurchaseReturn::with(['supplier', 'branch', 'items.product', 'goodsReceipt'])
                    ->whereIn('id', $ids)->get();
                $viewName = 'print.documents.purchase-return';
                $title = 'Nota Retur Pembelian';
                break;
            case 'adjustment':
                $documents = StockAdjustment::with(['branch', 'adjustmentReason', 'items.product', 'recorder'])
                    ->whereIn('id', $ids)->get();
                $viewName = 'print.documents.stock-adjustment';
                $title = 'Nota Koreksi Stok';
                break;
            case 'transfer':
                $documents = StockTransfer::with(['fromBranch', 'toBranch', 'creator', 'items.product'])
                    ->whereIn('id', $ids)->get();
                $viewName = 'print.documents.stock-transfer';
                $title = 'Nota Stok Transfer';
                break;
            case 'expense':
                $documents = \App\Models\Expense::with(['branch', 'expenseAccount', 'paymentAccount', 'creator'])
                    ->whereIn('id', $ids)->get();
                $viewName = 'print.documents.expense';
                $title = 'Bukti Pengeluaran Kas';
                break;
            default:
                abort(404, 'Tipe dokumen tidak didukung');
        }

        if ($documents->isEmpty()) {
            abort(404, 'Dokumen tidak ditemukan');
        }

        return view($viewName, [
            'documents' => $documents,
            'title' => $title,
        ]);
    }
}
