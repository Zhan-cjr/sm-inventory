<?php

namespace App\Services;

use App\Contracts\PpobProviderInterface;
use Exception;
use Illuminate\Support\Facades\Log;

class PpobServiceManager
{
    /**
     * Resolve and return the correct PPOB provider instance.
     * 
     * @param string|null $providerName The provider name (digiflazz, ama, etc.)
     * @return PpobProviderInterface
     * @throws Exception
     */
    public static function make(?string $providerName = 'digiflazz'): PpobProviderInterface
    {
        $providerName = strtolower($providerName ?? 'digiflazz');

        // Check if provider is globally disabled based on Organization settings or .env fallback
        $organization = \App\Models\Organization::first();
        if ($organization && is_array($organization->active_ppob_providers) && count($organization->active_ppob_providers) > 0) {
            $activeProviders = $organization->active_ppob_providers;
        } else {
            // Fallback to .env if DB is not set
            $activeProvidersStr = env('PPOB_ACTIVE_PROVIDERS', 'digiflazz');
            $activeProviders = array_map('trim', explode(',', strtolower($activeProvidersStr)));
        }

        if (!in_array($providerName, $activeProviders)) {
            Log::error("PPOB Manager: Attempted to use disabled provider '{$providerName}'");
            throw new Exception("Provider {$providerName} is currently disabled in the system settings.");
        }

        switch ($providerName) {
            case 'ama':
                return new AmaService();
            case 'digiflazz':
            default:
                return new DigiflazzService();
        }
    }
}
