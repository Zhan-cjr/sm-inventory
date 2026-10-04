import { useState } from 'react';

export const usePosBarcodeSearch = ({
  dbProducts = [],
  isSubtotalMode,
  setIsSubtotalMode,
  barcodeInput,
  addItemToTransaction,
  setAlertMsg
}) => {
  const [inputValue, setInputValue] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [highlightedIndex, setHighlightedIndex] = useState(-1);

  const handleInputChange = (val) => {
    setInputValue(val);
    if (!isSubtotalMode && val.length > 1) {
      const lowerVal = val.toLowerCase();

      // Check for exact barcode or sku match first (isolated result)
      const exactMatches = dbProducts.filter(p =>
        p.barcode?.toLowerCase() === lowerVal ||
        p.sku.toLowerCase() === lowerVal ||
        (p.metadata && Array.isArray(p.metadata.additional_barcodes) && p.metadata.additional_barcodes.some(b => String(b).toLowerCase() === lowerVal))
      );

      if (exactMatches.length > 0) {
        setSearchResults(exactMatches);
        setHighlightedIndex(-1);
        return;
      }

      // If no exact barcode/sku, score and sort the results
      const scored = dbProducts.map(p => {
        let score = 0;
        const name = p.name.toLowerCase();
        const sku = p.sku.toLowerCase();
        const barcode = p.barcode?.toLowerCase() || '';
        const additionalBarcodes = p.metadata?.additional_barcodes || [];

        if (name === lowerVal) score = 100;
        else if (name.startsWith(lowerVal)) score = 80;
        else if (sku.startsWith(lowerVal)) score = 70;
        else if (barcode.startsWith(lowerVal)) score = 60;
        else if (Array.isArray(additionalBarcodes) && additionalBarcodes.some(b => String(b).toLowerCase().startsWith(lowerVal))) score = 55;
        else if (name.includes(lowerVal)) score = 40;
        else if (sku.includes(lowerVal)) score = 30;
        else if (barcode.includes(lowerVal)) score = 20;
        else if (Array.isArray(additionalBarcodes) && additionalBarcodes.some(b => String(b).toLowerCase().includes(lowerVal))) score = 15;

        return { product: p, score };
      }).filter(item => item.score > 0);

      // Sort descending by score
      scored.sort((a, b) => b.score - a.score);

      setSearchResults(scored.slice(0, 10).map(item => item.product));
      setHighlightedIndex(-1);
    } else {
      setSearchResults([]);
      setHighlightedIndex(-1);
    }
  };

  const handleClearInput = () => {
    setInputValue('');
    setSearchResults([]);
    setHighlightedIndex(-1);
    setIsSubtotalMode(false);
    barcodeInput.current?.focus();
  };

  const handleBarcodeScan = async (barcode) => {
    if (!barcode) return;
    try {
      let isScale = false;
      let scaleItemCode = '';
      let scaleQty = 1;

      // Extract scale barcode settings
      const userObj = JSON.parse(localStorage.getItem('pos_user')) || {};
      const scaleEnabled = userObj.scale_barcode_enabled === true;

      if (scaleEnabled) {
        const prefix = userObj.scale_barcode_prefix || '20';
        const itemCodeLen = parseInt(userObj.scale_barcode_item_code_length) || 5;
        const weightLen = parseInt(userObj.scale_barcode_weight_length) || 5;
        const weightDecimals = parseInt(userObj.scale_barcode_weight_decimal_places) || 3;
        const expectedLen = prefix.length + itemCodeLen + weightLen + 1; // +1 for checksum

        if (barcode.startsWith(prefix) && barcode.length === expectedLen) {
          isScale = true;
          scaleItemCode = barcode.substring(prefix.length, prefix.length + itemCodeLen);
          const weightStr = barcode.substring(prefix.length + itemCodeLen, prefix.length + itemCodeLen + weightLen);
          scaleQty = parseFloat(weightStr) / Math.pow(10, weightDecimals);
        }
      }

      const searchCode = isScale ? scaleItemCode : barcode;
      const product = dbProducts.find(p =>
        p.sku === searchCode ||
        p.barcode === searchCode ||
        (p.metadata && Array.isArray(p.metadata.additional_barcodes) && p.metadata.additional_barcodes.includes(searchCode))
      );

      if (product) {
        addItemToTransaction(product, isScale ? scaleQty : null);
        setSearchResults([]);
        setInputValue('');
      } else {
        setAlertMsg({ text: `Product "${barcode}" tidak ditemukan.`, type: 'error' });
        setTimeout(() => setAlertMsg(null), 2000);
      }
    } catch (error) {
      console.error('Product search failed:', error);
    }
  };

  return {
    inputValue,
    setInputValue,
    searchResults,
    setSearchResults,
    highlightedIndex,
    setHighlightedIndex,
    handleInputChange,
    handleClearInput,
    handleBarcodeScan
  };
};
