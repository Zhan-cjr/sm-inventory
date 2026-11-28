<?php

namespace App\Services;

use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    /**
     * Resolve normalized uppercase branch code from branch ID, defaulting to PST.
     */
    public static function resolveBranchCode(?string $branchId = null): string
    {
        if (!empty($branchId)) {
            $code = Branch::where('id', $branchId)->value('code');
            if (!empty($code)) {
                $cleaned = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
                if ($cleaned !== '') {
                    return $cleaned;
                }
            }
        }

        return 'PST';
    }

    /**
     * Generate a collision-free sequential document number with branch code:
     * Format: {PREFIX}-{BRANCH_CODE}-{YYMMDD}-{SEQ4}
     * Example: GR-WRK-261128-0001, AUD-BS-WRK-261128-0001
     */
    public static function generate(
        string $prefix,
        string $table,
        string $column,
        ?string $branchId = null,
        ?string $date = null
    ): string {
        $cleanPrefix = strtoupper(trim($prefix, '-'));
        $branchCode = self::resolveBranchCode($branchId);
        $dateStr = $date ? Carbon::parse($date)->format('ymd') : now()->format('ymd');
        $fullPrefix = "{$cleanPrefix}-{$branchCode}-{$dateStr}-";

        $lockKey = "doc_seq_{$table}_{$cleanPrefix}_{$branchCode}_{$dateStr}";

        return Cache::lock($lockKey, 10)->block(5, function () use ($table, $column, $fullPrefix) {
            $lastDoc = DB::table($table)
                ->where($column, 'LIKE', $fullPrefix . '%')
                ->orderByRaw("CAST(SUBSTRING_INDEX({$column}, '-', -1) AS UNSIGNED) DESC")
                ->value($column);

            $nextSeq = 1;
            if ($lastDoc) {
                $parts = explode('-', (string) $lastDoc);
                $nextSeq = ((int) end($parts)) + 1;
            }

            do {
                $candidate = $fullPrefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
                $exists = DB::table($table)->where($column, $candidate)->exists();
                if ($exists) {
                    $nextSeq++;
                }
            } while ($exists);

            return $candidate;
        });
    }

    /**
     * Generate branch-coded reference number for Stock Quarantine (BS) movements.
     */
    public static function generateQuarantineMovementNumber(?string $branchId = null, ?string $date = null): string
    {
        return self::generate('BS', 'stock_quarantine_movements', 'reference_doc_id', $branchId, $date);
    }
}
