<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\Transaction;
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
                    'shift' => $userActive
                ]);
            } else {
                // Complex conflict: user has shift on another terminal, and this terminal has a shift by someone else
                return response()->json([
                    'status' => 'USER_HAS_OTHER_SHIFT',
                    'message' => "Akses Terkunci: Anda masih memiliki shift aktif ({$userActive->shift_name}) di kassa {$userActive->terminal->name}. Silakan kembali ke kassa tersebut untuk menutup shift Anda terlebih dahulu.",
                    'shift' => null
                ]);
            }
        }

        if ($userActive) {
            // User has a shift, but NOT on this terminal
            return response()->json([
                'status' => 'USER_HAS_OTHER_SHIFT',
                'message' => "Akses Terkunci: Anda masih memiliki shift aktif ({$userActive->shift_name}) di kassa {$userActive->terminal->name}. Silakan kembali ke kassa tersebut untuk menutup shift Anda terlebih dahulu.",
                'shift' => null
            ]);
        }

        if ($terminalActive) {
            // Terminal is in use by someone else
            return response()->json([
                'status' => 'TERMINAL_IN_USE',
                'message' => "Akses Terkunci: Terminal kassa ini sedang digunakan oleh {$terminalActive->user->name} pada shift {$terminalActive->shift_name}. Silakan tunggu hingga kasir tersebut menutup shift-nya, atau login di kassa lain.",
                'shift' => null
            ]);
        }

        // No active shifts for user or terminal -> Safe to open new shift
        return response()->json([
            'status' => 'NONE',
            'shift' => null
        ]);
    }

    public function openShift(Request $request)
    {
        $validated = $request->validate([
            'terminal_id' => 'required|uuid',
            'shift_name' => 'required|string',
            'starting_cash' => 'required|numeric|min:0',
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

        // Rule 1: Check if the cashier has already closed this shift name today
        $todayStart = Carbon::today();
        $todayEnd = Carbon::tomorrow()->subSecond();

        $alreadyClosedToday = Shift::where('user_id', $user->id)
            ->where('shift_name', $validated['shift_name'])
            ->where('status', 'CLOSED')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->first();

        if ($alreadyClosedToday) {
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

            // Calculate sales during this shift
            $sales = Transaction::where('shift_id', $shift->id)
                ->select(
                    DB::raw("SUM(CASE WHEN payment_method = 'CASH' THEN final_amount ELSE 0 END) as cash_sales"),
                    DB::raw("SUM(CASE WHEN payment_method = 'CARD' THEN final_amount ELSE 0 END) as card_sales")
                )->first();

            $shift->end_time = now();
            $shift->total_cash_sales = $sales->cash_sales ?? 0;
            $shift->total_card_sales = $sales->card_sales ?? 0;
            $shift->actual_cash = $validated['actual_cash'];
            
            $expectedCash = $shift->starting_cash + $shift->total_cash_sales;
            $shift->difference = $shift->actual_cash - $expectedCash;
            $shift->status = 'CLOSED';
            $shift->notes = $validated['notes'] ?? null;
            $shift->save();

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
}
