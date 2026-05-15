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
        
        // Check if there is an active shift for this user or this terminal
        $shift = Shift::where('status', 'OPEN')
            ->where(function($query) use ($terminalId, $userId) {
                $query->where('terminal_id', $terminalId)
                      ->orWhere('user_id', $userId);
            })
            ->first();
            
        return response()->json([
            'shift' => $shift
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
        
        // Check if the user already has an open shift anywhere
        $userActive = Shift::where('user_id', $user->id)
            ->where('status', 'OPEN')
            ->first();
            
        if ($userActive) {
            return response()->json([
                'message' => "Anda masih memiliki shift aktif di terminal {$userActive->terminal->name}. Silakan tutup shift tersebut terlebih dahulu.",
                'shift' => $userActive
            ], 422);
        }

        // Check if there is already an open shift for this terminal by someone else
        $terminalActive = Shift::where('terminal_id', $validated['terminal_id'])
            ->where('status', 'OPEN')
            ->first();
            
        if ($terminalActive) {
            return response()->json([
                'message' => "Terminal ini sedang digunakan oleh {$terminalActive->user->name}. Tunggu hingga shift tersebut ditutup.",
                'shift' => $terminalActive
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
