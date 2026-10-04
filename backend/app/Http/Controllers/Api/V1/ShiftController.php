<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\CashMovement;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    public function getActiveShift(Request $request)
    {
        $terminalId = $request->query('terminal_id');
        $userId = auth()->id();
        $user = auth()->user();
        
        if ($terminalId) {
            $terminal = \App\Models\Terminal::find($terminalId);
            if ($terminal && $user->branch_id !== null && $terminal->branch_id !== $user->branch_id) {
                return response()->json([
                    'status' => 'FORBIDDEN',
                    'message' => 'Akun kasir Anda terdaftar di cabang yang berbeda dengan terminal/kassa ini. Anda tidak diperbolehkan membuka kassa atau melakukan transaksi di kassa milik cabang lain demi keamanan data.'
                ], 403);
            }
        }

        // Daftar shift yang sudah CLOSED hari ini (baik di terminal kassa ini ataupun oleh kasir ini)
        $todayStart = Carbon::today();
        $todayEnd = Carbon::tomorrow()->subSecond();

        $closedQuery = Shift::where('status', 'CLOSED')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->where(function ($q) use ($terminalId, $userId) {
                if ($terminalId) {
                    $q->where('terminal_id', $terminalId);
                }
                $q->orWhere('user_id', $userId);
            });
        $closedShifts = $closedQuery->pluck('shift_name')->unique()->values()->toArray();
        
        // 1. Check if the user has an open shift anywhere
        $userActive = Shift::where('user_id', $userId)
            ->where('status', 'OPEN')
            ->with(['terminal', 'user'])
            ->first();

        // 2. Check if the terminal has an open shift by ANY user
        $terminalActive = Shift::where('terminal_id', $terminalId)
            ->where('status', 'OPEN')
            ->with(['terminal', 'user'])
            ->first();

        if ($userActive && $terminalActive) {
            if ($userActive->id === $terminalActive->id) {
                // Ideal case: user is on their correct terminal with their active shift
                return response()->json([
                    'status' => 'OK',
                    'shift' => $userActive,
                    'closed_shifts' => $closedShifts
                ]);
            } else {
                // Complex conflict: user has shift on another terminal, and this terminal has a shift by someone else
                return response()->json([
                    'status' => 'USER_HAS_OTHER_SHIFT',
                    'message' => "Akses Terkunci: Anda masih memiliki shift aktif ({$userActive->shift_name}) di kassa {$userActive->terminal->name}. Silakan kembali ke kassa tersebut untuk menutup shift Anda terlebih dahulu.",
                    'shift' => null,
                    'closed_shifts' => $closedShifts
                ]);
            }
        }

        if ($userActive) {
            // User has a shift, but NOT on this terminal
            return response()->json([
                'status' => 'USER_HAS_OTHER_SHIFT',
                'message' => "Akses Terkunci: Anda masih memiliki shift aktif ({$userActive->shift_name}) di kassa {$userActive->terminal->name}. Silakan kembali ke kassa tersebut untuk menutup shift Anda terlebih dahulu.",
                'shift' => null,
                'closed_shifts' => $closedShifts
            ]);
        }

        if ($terminalActive) {
            // Terminal is in use by someone else
            return response()->json([
                'status' => 'TERMINAL_IN_USE',
                'message' => "Akses Terkunci: Terminal kassa ini sedang digunakan oleh {$terminalActive->user->name} pada shift {$terminalActive->shift_name}. Silakan tunggu hingga kasir tersebut menutup shift-nya, atau login di kassa lain.",
                'shift' => null,
                'closed_shifts' => $closedShifts
            ]);
        }

        // No active shifts for user or terminal -> Safe to open new shift
        return response()->json([
            'status' => 'NONE',
            'shift' => null,
            'closed_shifts' => $closedShifts
        ]);
    }

    public function openShift(Request $request)
    {
        $validated = $request->validate([
            'terminal_id' => 'required|uuid',
            'shift_name' => 'required|string|in:Shift 1,Shift 2',
            'starting_cash' => 'required|numeric|min:0',
        ], [
            'shift_name.in' => 'Pilihan shift hanya diperbolehkan Shift 1 atau Shift 2.'
        ]);

        $user = auth()->user();
        
        // Ensure cashier branch matches terminal branch
        $terminal = \App\Models\Terminal::find($validated['terminal_id']);
        if (!$terminal) {
            return response()->json(['message' => 'Terminal tidak ditemukan.'], 404);
        }
        if ($user->branch_id !== null && $terminal->branch_id !== $user->branch_id) {
            return response()->json([
                'message' => "Gagal: Akun kasir Anda terdaftar di cabang yang berbeda dengan terminal/kassa ini. Anda tidak diperbolehkan bertransaksi di kassa ini."
            ], 403);
        }

        // Rule 2: Check if the user already has an open shift anywhere
        $userActive = Shift::where('user_id', $user->id)
            ->where('status', 'OPEN')
            ->first();
            
        if ($userActive) {
            return response()->json([
                'message' => "Gagal Buka Shift: Anda tidak bisa membuka shift baru ({$validated['shift_name']}) karena shift sebelumnya ({$userActive->shift_name}) belum ditutup (CLOSED). Harap tutup shift aktif Anda terlebih dahulu.",
                'shift' => $userActive
            ], 422);
        }

        // Rule 2 (terminal-wide): Check if there is already an open shift for this terminal
        $terminalActive = Shift::where('terminal_id', $validated['terminal_id'])
            ->where('status', 'OPEN')
            ->first();
            
        if ($terminalActive) {
            return response()->json([
                'message' => "Gagal Buka Shift: Terminal ini masih memiliki shift aktif ({$terminalActive->shift_name}) yang sedang berjalan oleh {$terminalActive->user->name}. Harap tutup shift sebelumnya terlebih dahulu sebelum membuka shift baru.",
                'shift' => $terminalActive
            ], 422);
        }

        $todayStart = Carbon::today();
        $todayEnd = Carbon::tomorrow()->subSecond();

        // Rule 1: Check if this shift name has already been closed on this terminal today (Option 1: Per Kassa)
        $terminalClosedToday = Shift::where('terminal_id', $validated['terminal_id'])
            ->where('shift_name', $validated['shift_name'])
            ->where('status', 'CLOSED')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->with('user')
            ->first();

        if ($terminalClosedToday) {
            $closedByName = $terminalClosedToday->user ? $terminalClosedToday->user->name : 'kasir sebelumnya';
            return response()->json([
                'message' => "Gagal Buka Shift: {$validated['shift_name']} di kassa ini sudah ditutup hari ini (oleh {$closedByName}). Kassa ini tidak dapat membuka kembali shift yang sama hari ini demi integritas laporan keuangan."
            ], 422);
        }

        // Rule 1b: Also check if this cashier has already closed this shift name today elsewhere
        $cashierClosedToday = Shift::where('user_id', $user->id)
            ->where('shift_name', $validated['shift_name'])
            ->where('status', 'CLOSED')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->first();

        if ($cashierClosedToday) {
            return response()->json([
                'message' => "Gagal Buka Shift: Anda sudah menutup {$validated['shift_name']} hari ini. Sistem menolak pembukaan kembali shift yang sama pada hari yang sama demi integritas laporan keuangan."
            ], 422);
        }

        $shift = Shift::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'terminal_id' => $validated['terminal_id'],
            'shift_name' => $validated['shift_name'],
            'start_time' => now(),
            'starting_cash' => $validated['starting_cash'],
            'status' => 'OPEN'
        ]);

        return response()->json([
            'message' => 'Shift berhasil dibuka.',
            'shift' => $shift
        ]);
    }

    public function cashMovement(Request $request)
    {
        $validated = $request->validate([
            'shift_id' => 'required|uuid',
            'terminal_id' => 'required|uuid',
            'type' => 'required|in:CASH_IN,CASH_OUT',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
        ]);

        $shift = Shift::find($validated['shift_id']);
        if (!$shift || $shift->status !== 'OPEN') {
            return response()->json(['message' => 'Shift tidak aktif atau tidak ditemukan.'], 404);
        }

        DB::beginTransaction();
        try {
            $movement = CashMovement::create([
                'shift_id' => $shift->id,
                'user_id' => auth()->id(),
                'terminal_id' => $validated['terminal_id'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
            ]);

            if ($validated['type'] === 'CASH_IN') {
                $shift->total_cash_in += $validated['amount'];
            } else {
                $shift->total_cash_out += $validated['amount'];
            }
            $shift->save();

            DB::commit();

            return response()->json([
                'message' => 'Kas ' . ($validated['type'] === 'CASH_IN' ? 'Masuk' : 'Keluar') . ' berhasil dicatat.',
                'movement' => $movement,
                'shift' => $shift
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal mencatat pergerakan kas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function closeShift(Request $request)
    {
        try {
            $validated = $request->validate([
                'shift_id' => 'required|uuid',
                'actual_cash' => 'required|numeric|min:0',
                'notes' => 'nullable|string'
            ]);

            $shift = Shift::find($validated['shift_id']);
            
            if (!$shift) {
                return response()->json(['message' => 'Shift tidak ditemukan.'], 404);
            }
            
            if ($shift->status === 'CLOSED') {
                return response()->json(['message' => 'Shift sudah ditutup sebelumnya.'], 422);
            }

            $shiftData = $this->calculateShiftData($shift);

            $shift->end_time = now();
            $shift->total_cash_sales = $shiftData['cash_sales'];
            $shift->total_card_sales = $shiftData['card_sales'];
            $shift->total_voucher_sales = $shiftData['voucher_sales'];
            $shift->total_cash_returns = $shiftData['cash_returns'];
            $shift->total_card_returns = $shiftData['card_returns'];
            $shift->actual_cash = $validated['actual_cash'];
            
            // Expected cash physical in drawer: Start + Cash In - Cash Out + Cash Sales - Cash Returns
            $expectedCash = $shift->starting_cash + $shift->total_cash_sales - $shift->total_cash_returns + $shift->total_cash_in - $shift->total_cash_out;
            $shift->difference = $shift->actual_cash - $expectedCash;
            $shift->status = 'CLOSED';
            $shift->notes = $validated['notes'] ?? null;
            $shift->save();

            // Load cash movements and branch details for EOD report
            $shift->load(['user', 'terminal', 'cashMovements', 'branch.organization']);
            $shift->expected_cash = $expectedCash;
            $shift->card_sales_by_bank = $shiftData['formatted_card_sales'];
            $shift->returns_detail = $shiftData['return_items'];
            $shift->discount_details = $shiftData['discount_details'];

            return response()->json([
                'message' => 'Shift berhasil ditutup.',
                'shift' => $shift
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menutup shift: ' . $e->getMessage(),
                'error_detail' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function printEod(Shift $shift)
    {
        $shift->load(['user', 'terminal', 'cashMovements', 'branch.organization']);

        $shiftData = $this->calculateShiftData($shift);

        $expectedCash = $shift->starting_cash + $shiftData['cash_sales'] - $shiftData['cash_returns'] + $shift->total_cash_in - $shift->total_cash_out;

        $shift->expected_cash = $expectedCash;
        $shift->total_cash_sales = $shiftData['cash_sales'];
        $shift->total_card_sales = $shiftData['card_sales'];
        $shift->total_voucher_sales = $shiftData['voucher_sales'];
        $shift->total_cash_returns = $shiftData['cash_returns'];
        $shift->total_card_returns = $shiftData['card_returns'];
        $shift->card_sales_by_bank = $shiftData['formatted_card_sales'];
        $shift->returns_detail = $shiftData['return_items'];
        $shift->discount_details = $shiftData['discount_details'];

        return view('print.eod-receipt', compact('shift'));
    }

    /**
     * Efficiently calculate all shift transaction totals and returns with eager loaded relations.
     */
    private function calculateShiftData(Shift $shift)
    {
        $transactions = Transaction::with(['items.product', 'bank'])
            ->where('shift_id', $shift->id)
            ->where('is_voided', false)
            ->get();

        $cash_sales = 0;
        $card_sales = 0;
        $voucher_sales = 0;
        $cash_returns = 0;
        $card_returns = 0;
        $cardSalesByBank = [];
        $returnItems = [];

        foreach ($transactions as $tx) {
            $amount = $tx->final_amount;
            $tx_sales = 0;
            $tx_returns = 0;

            foreach ($tx->items as $item) {
                $itemDiscount = (float) ($item->discount_per_item ?? 0);
                $effectivePrice = max(0, (float) $item->unit_price - $itemDiscount);

                if ($item->quantity > 0) {
                    $tx_sales += ($item->quantity * $effectivePrice);
                } else {
                    $tx_returns += (abs($item->quantity) * $effectivePrice);
                    $returnItems[] = [
                        'product_name' => $item->product ? $item->product->name : 'Unknown Item',
                        'quantity' => abs($item->quantity),
                        'total' => abs($item->quantity * $effectivePrice)
                    ];
                }
            }
            $tx_sales -= ($tx->manual_discount + $tx->promo_discount);
            if ($tx_sales < 0) $tx_sales = 0;

            $method = strtoupper($tx->payment_method);
            
            if ($method === 'CASH' || $method === 'POINT') {
                $cash_sales += $tx_sales;
                $cash_returns += $tx_returns;
            } elseif ($method === 'CARD') {
                $card_sales += $tx_sales;
                $card_returns += $tx_returns;
                $bankName = $tx->bank ? $tx->bank->name : 'EDC';
                if (!isset($cardSalesByBank[$bankName])) $cardSalesByBank[$bankName] = 0;
                $cardSalesByBank[$bankName] += $amount;
            } elseif ($method === 'VOUCHER') {
                $voucher_sales += $tx_sales;
            } elseif ($method === 'MULTI') {
                $details = $tx->payment_details;
                if (is_string($details)) $details = json_decode($details, true);
                if (is_array($details)) {
                    $cash_amt = collect($details)->where('method', 'CASH')->sum('amount');
                    if ($cash_amt > 0) $cash_amt = max(0, $cash_amt - $tx->change_amount);
                    
                    $voucher_amt = collect($details)->where('method', 'VOUCHER')->sum('amount');
                    $voucher_sales += $voucher_amt;
                    
                    $cash_returns += $tx_returns;
                    $cash_sales += ($cash_amt + $tx_returns);
                    
                    $cardDetails = collect($details)->where('method', 'CARD');
                    foreach ($cardDetails as $c) {
                        $card_sales += $c['amount'];
                        $bankName = $c['label'] ?? 'EDC';
                        if (strpos($bankName, 'Card: ') === 0) $bankName = substr($bankName, 6);
                        if (!isset($cardSalesByBank[$bankName])) $cardSalesByBank[$bankName] = 0;
                        $cardSalesByBank[$bankName] += $c['amount'];
                    }
                }
            }
        }

        $formattedCardSales = [];
        foreach ($cardSalesByBank as $name => $total) {
            $formattedCardSales[] = (object)['name' => $name, 'total_amount' => $total];
        }

        $totalManualDiscount = $transactions->sum('manual_discount');
        $totalPromoDiscount = $transactions->sum('promo_discount');
        $totalPointDeduction = $transactions->sum(function ($tx) {
            $pointPayment = 0.0;
            if (!empty($tx->payment_details)) {
                $details = $tx->payment_details;
                if (is_string($details)) $details = json_decode($details, true);
                if (is_array($details)) {
                    $pointPayment = (float) collect($details)->where('method', 'POINT')->sum('amount');
                }
            } elseif (strtoupper($tx->payment_method) === 'POINT') {
                $pointPayment = (float) $tx->final_amount;
            }
            return $pointPayment;
        });

        return [
            'cash_sales' => $cash_sales,
            'card_sales' => $card_sales,
            'voucher_sales' => $voucher_sales,
            'cash_returns' => $cash_returns,
            'card_returns' => $card_returns,
            'formatted_card_sales' => $formattedCardSales,
            'return_items' => $returnItems,
            'discount_details' => [
                'manual_discount' => $totalManualDiscount,
                'promo_discount' => $totalPromoDiscount,
                'point_deduction' => $totalPointDeduction
            ]
        ];
    }
}
