<?php

namespace App\Livewire\Traits;

use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;

trait HasPosDraft
{
    /**
     * Save current component state to cache
     */
    public function saveDraft()
    {
        // Don't save draft if we are editing an existing record
        if (isset($this->purchaseOrder) || isset($this->goodsReceipt)) {
            return;
        }

        $draftData = [];
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        foreach ($properties as $prop) {
            // Only save properties declared in the specific component (not Livewire internals)
            if ($prop->class === static::class) {
                $name = $prop->getName();
                // Ignore model instances, large arrays, and file uploads
                if (!in_array($name, ['purchaseOrder', 'goodsReceipt', 'searchResults', 'visibleColumns', 'faktur_image', 'existing_faktur_image'])) {
                    $draftData[$name] = $this->{$name};
                }
            }
        }

        Cache::put('pos_draft_' . static::class . '_' . auth()->id(), $draftData, now()->addDays(3));
    }

    /**
     * Load draft from cache
     */
    public function loadDraft()
    {
        // Only load if not editing an existing record
        if (isset($this->purchaseOrder) || isset($this->goodsReceipt)) {
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
    }
}
