<?php

namespace App\Livewire\Traits;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;

trait HasPosDraft
{
    protected bool $isDraftCleared = false;
    protected bool $isSavingInProgress = false;

    protected function getPosDraftCacheKey(): string
    {
        return 'pos_draft_' . static::class . '_' . auth()->id();
    }

    protected function getLastSavedCartCacheKey(): string
    {
        return 'pos_saved_cart_' . static::class . '_' . auth()->id();
    }

    protected function computeRawCartHash(array $cart): string
    {
        return hash('sha256', json_encode($cart));
    }

    /**
     * Save current component state to cache
     */
    public function saveDraft()
    {
        // Don't save draft if it was just cleared or if a save operation is currently running
        if ($this->isDraftCleared || $this->isSavingInProgress) {
            return;
        }

        // Don't save draft if we are editing an existing record
        if (isset($this->purchaseOrder) || isset($this->goodsReceipt) || isset($this->purchaseReturn) || isset($this->stockAdjustment)) {
            return;
        }

        $draftData = [];
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $ignoredProperties = [
            'purchaseOrder', 'goodsReceipt', 'purchaseReturn', 'stockAdjustment',
            'po_number', 'adjustment_number', 'return_number', 'receipt_number',
            'searchResults', 'visibleColumns', 'faktur_image', 'existing_faktur_image',
            'scan_image', 'purchaseOrdersList', 'purchaseOrders',
            // Promo modal states & file uploads
            'promoFile', 'parsedPromoItems', 'excelSheets', 'excelColumns',
            'showPromoModal', 'promoErrorMessage', 'promoText',
            // Over-order & duplicate confirmation modal states
            'showOverOrderModal', 'overOrderProducts', 'bulkReasonCode', 'bulkReasonNote', 'overOrderReasonPresets',
            'showDuplicatePoModal', 'duplicatePoNumber', 'forceDuplicateSave',
        ];

        foreach ($properties as $prop) {
            if ($prop->isStatic()) {
                continue;
            }
            // Only save properties declared in the specific component (not Livewire internals)
            if ($prop->class === static::class) {
                $name = $prop->getName();
                // Ignore model instances, large arrays, and file uploads
                if (in_array($name, $ignoredProperties)) {
                    continue;
                }

                $val = $this->{$name};

                // Exclude any file uploads or resources or objects that cannot be serialized
                if ($this->isUnserializable($val)) {
                    continue;
                }

                $draftData[$name] = $val;
            }
        }

        try {
            Cache::put($this->getPosDraftCacheKey(), $draftData, now()->addDays(3));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('POS saveDraft failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if a value cannot or should not be serialized
     */
    protected function isUnserializable($value): bool
    {
        if (is_resource($value)) {
            return true;
        }

        if (is_object($value)) {
            if ($value instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
                || $value instanceof \Symfony\Component\HttpFoundation\File\UploadedFile
                || $value instanceof \Closure) {
                return true;
            }
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->isUnserializable($item)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Load draft from cache
     */
    public function loadDraft()
    {
        // Only load if not editing an existing record
        if (isset($this->purchaseOrder) || isset($this->goodsReceipt) || isset($this->purchaseReturn) || isset($this->stockAdjustment)) {
            return;
        }

        $draft = Cache::get($this->getPosDraftCacheKey());

        if ($draft && is_array($draft) && !empty($draft['cart']) && is_array($draft['cart'])) {
            // Discard zombie draft if its exact cart was already committed recently
            $lastSavedCartHash = Cache::get($this->getLastSavedCartCacheKey());
            if ($lastSavedCartHash && hash_equals($lastSavedCartHash, $this->computeRawCartHash($draft['cart']))) {
                Cache::forget($this->getPosDraftCacheKey());
                return;
            }

            $ignoredProperties = [
                'purchaseOrder', 'goodsReceipt', 'purchaseReturn', 'stockAdjustment',
                'po_number', 'po_date',
                'adjustment_number', 'adjustment_date',
                'return_number', 'return_date',
                'receipt_number', 'receipt_date',
                'searchResults', 'visibleColumns', 'faktur_image', 'existing_faktur_image',
                'scan_image', 'purchaseOrdersList', 'purchaseOrders',
                'promoFile', 'parsedPromoItems', 'excelSheets', 'excelColumns',
                'showPromoModal', 'promoErrorMessage', 'promoText',
                'showOverOrderModal', 'overOrderProducts', 'bulkReasonCode', 'bulkReasonNote', 'overOrderReasonPresets',
                'showDuplicatePoModal', 'duplicatePoNumber', 'forceDuplicateSave',
            ];

            $reflection = new \ReflectionClass($this);
            foreach ($draft as $key => $value) {
                if (in_array($key, $ignoredProperties)) {
                    continue;
                }
                if ($reflection->hasProperty($key)) {
                    $prop = $reflection->getProperty($key);
                    if ($prop->isPublic() && !$prop->isStatic() && $prop->class === static::class) {
                        try {
                            $this->{$key} = $value;
                        } catch (\Throwable $e) {
                            // ignore unassignable property
                        }
                    }
                }
            }

            Notification::make()
                ->title('Draft Dimuat')
                ->body('Sistem berhasil mengembalikan data yang belum tersimpan dari sesi Anda sebelumnya.')
                ->info()
                ->send();
        }
    }

    /**
     * Clear draft from cache and record saved cart hash to prevent zombie draft resurrection
     */
    public function clearDraft()
    {
        if (!empty($this->cart) && is_array($this->cart)) {
            Cache::put($this->getLastSavedCartCacheKey(), $this->computeRawCartHash($this->cart), now()->addMinutes(30));
        }

        Cache::forget($this->getPosDraftCacheKey());
        $this->isDraftCleared = true;
    }

    /**
     * Acquire per-user active save lock to prevent rapid double-clicks / unstable network retries
     */
    protected function acquirePosSaveLock(string $moduleKey, int $seconds = 15): ?Lock
    {
        $this->isSavingInProgress = true;
        $userKey = auth()->id() ?: request()->ip();
        $lock = Cache::lock("pos_save_lock_{$moduleKey}_{$userKey}", $seconds);

        if (!$lock->get()) {
            $this->isSavingInProgress = false;
            Notification::make()
                ->title('Proses Simpan Sedang Berlangsung')
                ->body('Mohon tunggu sejenak, transaksi Anda sedang disimpan ke server.')
                ->warning()
                ->send();

            return null;
        }

        return $lock;
    }

    /**
     * Safely release per-user active save lock
     */
    protected function releasePosSaveLock(?Lock $lock): void
    {
        $this->isSavingInProgress = false;
        if ($lock) {
            try {
                $lock->release();
            } catch (\Throwable $e) {
                // ignore expired lock release
            }
        }
    }

    /**
     * Compute deterministic SHA-256 content fingerprint from header fields + sorted cart items.
     * Changing supplier, branch, item, quantity, or price produces a completely different fingerprint.
     */
    protected function computeCartFingerprint(string $moduleKey, array $headerContext, array $cartItems): string
    {
        $normalizedHeader = [];
        foreach ($headerContext as $k => $v) {
            $normalizedHeader[(string) $k] = trim((string) ($v ?? ''));
        }
        ksort($normalizedHeader);

        $normalizedItems = [];
        foreach ($cartItems as $item) {
            if (!is_array($item)) {
                continue;
            }
            $normalizedItems[] = implode('|', [
                trim((string) ($item['product_id'] ?? '')),
                number_format((float) ($item['qty'] ?? $item['qty_received'] ?? $item['qty_returned'] ?? $item['physical_qty'] ?? 0), 4, '.', ''),
                number_format((float) ($item['unit_cost'] ?? $item['unit_price'] ?? $item['price'] ?? $item['buy_price'] ?? 0), 2, '.', ''),
                number_format((float) ($item['subtotal'] ?? 0), 2, '.', ''),
                number_format((float) ($item['discount_1'] ?? 0), 2, '.', ''),
                number_format((float) ($item['discount_2'] ?? 0), 2, '.', ''),
                number_format((float) ($item['discount_3'] ?? 0), 2, '.', ''),
                trim((string) ($item['type'] ?? '')),
            ]);
        }
        sort($normalizedItems);

        return hash('sha256', json_encode([
            'module' => $moduleKey,
            'header' => $normalizedHeader,
            'items' => $normalizedItems,
        ]));
    }

    protected function getRecentSavedFingerprint(string $fingerprint): ?string
    {
        $userKey = auth()->id() ?: 'guest';
        return Cache::get("pos_saved_fp_{$userKey}_{$fingerprint}");
    }

    protected function recordSavedFingerprint(string $fingerprint, string $docNumber, int $ttlSeconds = 180): void
    {
        $userKey = auth()->id() ?: 'guest';
        Cache::put("pos_saved_fp_{$userKey}_{$fingerprint}", $docNumber, now()->addSeconds($ttlSeconds));
    }
}
