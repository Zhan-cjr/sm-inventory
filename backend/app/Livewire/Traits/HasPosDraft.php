<?php

namespace App\Livewire\Traits;

use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;

trait HasPosDraft
{
    protected bool $isDraftCleared = false;

    /**
     * Save current component state to cache
     */
    public function saveDraft()
    {
        // Don't save draft if it was just cleared in this request
        if ($this->isDraftCleared) {
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
            'searchResults', 'visibleColumns', 'faktur_image', 'existing_faktur_image',
            'scan_image', 'purchaseOrdersList', 'purchaseOrders',
            // Promo modal states & file uploads
            'promoFile', 'parsedPromoItems', 'excelSheets', 'excelColumns',
            'showPromoModal', 'promoErrorMessage', 'promoText'
        ];

        foreach ($properties as $prop) {
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
            Cache::put('pos_draft_' . static::class . '_' . auth()->id(), $draftData, now()->addDays(3));
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

        $draft = Cache::get('pos_draft_' . static::class . '_' . auth()->id());
        
        if ($draft && is_array($draft) && !empty($draft['cart'])) {
            $reflection = new \ReflectionClass($this);
            foreach ($draft as $key => $value) {
                if ($reflection->hasProperty($key)) {
                    $prop = $reflection->getProperty($key);
                    if ($prop->isPublic() && $prop->class === static::class) {
                        $this->{$key} = $value;
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
     * Clear draft from cache
     */
    public function clearDraft()
    {
        Cache::forget('pos_draft_' . static::class . '_' . auth()->id());
        $this->isDraftCleared = true;
    }
}
