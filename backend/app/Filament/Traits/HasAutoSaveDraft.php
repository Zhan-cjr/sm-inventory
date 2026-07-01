<?php

namespace App\Filament\Traits;

use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;

trait HasAutoSaveDraft
{
    /**
     * Get the cache key for the draft.
     */
    protected function getDraftCacheKey(): string
    {
        return 'draft_' . static::class . '_' . auth()->id();
    }

    /**
     * Hook into Livewire's updated lifecycle.
     */
    public function updated($propertyName)
    {
        // Call parent if it exists (although traits don't usually have parent::updated, 
        // Filament's InteractsWithForms or CreateRecord doesn't have a public updated method natively, 
        // but we check just in case).
        if (method_exists(parent::class, 'updated')) {
            parent::updated($propertyName);
        }

        // Only save draft if the updated property is part of the form data
        if (str_starts_with($propertyName, 'data.')) {
            Cache::put($this->getDraftCacheKey(), $this->data, now()->addDays(3));
        }
    }

    /**
     * Hook into mount to load draft if it exists.
     */
    public function mount(): void
    {
        // Filament's CreateRecord has a mount() method, so we must call it first
        parent::mount();

        $draft = Cache::get($this->getDraftCacheKey());

        if ($draft && is_array($draft)) {
            // Fill the form with draft data
            $this->form->fill($draft);
            
            // Re-assign the loaded draft to $this->data to ensure Livewire state is updated
            $this->data = $draft;

            Notification::make()
                ->title('Draft Dimuat')
                ->body('Kami telah memuat data yang belum sempat tersimpan dari sesi Anda sebelumnya.')
                ->info()
                ->send();
        }
    }

    /**
     * Hook into afterCreate to clear the draft when successfully saved.
     */
    protected function afterCreate(): void
    {
        if (method_exists(parent::class, 'afterCreate')) {
            parent::afterCreate();
        }

        Cache::forget($this->getDraftCacheKey());
    }

    /**
     * Optional: Action to clear draft manually.
     */
    public function clearDraft(): void
    {
        Cache::forget($this->getDraftCacheKey());
        
        // Reset form
        $this->form->fill();
        
        Notification::make()
            ->title('Draft Dihapus')
            ->body('Draft sebelumnya telah dihapus dan form telah dikosongkan.')
            ->success()
            ->send();
    }
}
