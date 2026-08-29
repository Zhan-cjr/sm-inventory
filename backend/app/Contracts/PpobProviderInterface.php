<?php

namespace App\Contracts;

interface PpobProviderInterface
{
    /**
     * Check provider balance.
     * 
     * @return array|null
     */
    public function checkBalance();

    /**
     * Get price list from provider.
     * 
     * @return array|null
     */
    public function getPriceList();

    /**
     * Process topup/transaction.
     * 
     * @param string $skuCode Provider SKU code
     * @param string $customerNo Target customer number / destination
     * @param string $refId Unique transaction reference ID
     * @param array $additionalInfo Any additional info required by the provider
     * @return array Response data
     */
    public function topup(string $skuCode, string $customerNo, string $refId, array $additionalInfo = []);

    /**
     * Check transaction status.
     * 
     * @param string $skuCode Provider SKU code
     * @param string $customerNo Target customer number
     * @param string $refId Unique transaction reference ID
     * @return array Response data in unified format
     */
    public function checkStatus(string $skuCode, string $customerNo, string $refId);
}
