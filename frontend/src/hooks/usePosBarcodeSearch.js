import { useState, useMemo, useRef, useEffect } from 'react';

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
  const searchDebounceRef = useRef(null);

  // Pre-build O(1) lookup map for barcode/SKU/additional_barcodes + pre-lowercased search index
  const { barcodeMap, indexedProducts } = useMemo(() => {
    const map = new Map();
    const addKey = (key, product) => {
      if (!key) return;
      const k = String(key).toLowerCase();
      const existing = map.get(k);
      if (existing) {
        if (!existing.includes(product)) existing.push(product);
      } else {
        map.set(k, [product]);
      }
    };

    const indexed = new Array(dbProducts.length);
    for (let i = 0; i < dbProducts.length; i++) {
      const p = dbProducts[i];
      const name = (p.name || '').toLowerCase();
      const sku = (p.sku || '').toLowerCase();
      const barcode = (p.barcode || '').toLowerCase();
      const rawAdditional = p.metadata?.additional_barcodes;
      const additionalBarcodes = Array.isArray(rawAdditional)
        ? rawAdditional.map((b) => String(b).toLowerCase())
        : [];

      addKey(barcode, p);
      addKey(sku, p);
      for (let j = 0; j < additionalBarcodes.length; j++) {
        addKey(additionalBarcodes[j], p);
      }

      indexed[i] = { product: p, name, sku, barcode, additionalBarcodes };
    }

    return { barcodeMap: map, indexedProducts: indexed };
  }, [dbProducts]);

  useEffect(() => {
    return () => {
      if (searchDebounceRef.current) clearTimeout(searchDebounceRef.current);
    };
  }, []);

  const handleInputChange = (val) => {
    setInputValue(val);
    if (searchDebounceRef.current) clearTimeout(searchDebounceRef.current);

    if (!isSubtotalMode && val.length > 1) {
      const lowerVal = val.trim().toLowerCase();

      // O(1) exact barcode/SKU match first (strict isolated result)
      const exactMatches = barcodeMap.get(lowerVal);
      if (exactMatches && exactMatches.length > 0) {
        setSearchResults(exactMatches);
        setHighlightedIndex(-1);
        return;
      }

      // Debounce fuzzy search across 29k+ products to keep typing smooth on cashier PCs
      searchDebounceRef.current = setTimeout(() => {
        const scored = [];
        for (let i = 0; i < indexedProducts.length; i++) {
          const item = indexedProducts[i];
          let score = 0;

          if (item.name === lowerVal) score = 100;
          else if (item.name.startsWith(lowerVal)) score = 80;
          else if (item.sku.startsWith(lowerVal)) score = 70;
          else if (item.barcode.startsWith(lowerVal)) score = 60;
          else if (item.additionalBarcodes.some((b) => b.startsWith(lowerVal))) score = 55;
          else if (item.name.includes(lowerVal)) score = 40;
          else if (item.sku.includes(lowerVal)) score = 30;
          else if (item.barcode.includes(lowerVal)) score = 20;
          else if (item.additionalBarcodes.some((b) => b.includes(lowerVal))) score = 15;

          if (score > 0) {
            scored.push({ product: item.product, score });
          }
        }

        scored.sort((a, b) => b.score - a.score);
        setSearchResults(scored.slice(0, 10).map((item) => item.product));
        setHighlightedIndex(-1);
      }, 100);
    } else {
      setSearchResults([]);
      setHighlightedIndex(-1);
    }
  };

  const handleClearInput = () => {
    if (searchDebounceRef.current) clearTimeout(searchDebounceRef.current);
    setInputValue('');
    setSearchResults([]);
    setHighlightedIndex(-1);
    setIsSubtotalMode(false);
    barcodeInput.current?.focus();
  };

  const handleBarcodeScan = async (barcode) => {
    if (!barcode) return;
    if (searchDebounceRef.current) clearTimeout(searchDebounceRef.current);
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

      const searchCode = (isScale ? scaleItemCode : barcode).trim().toLowerCase();
      const matchedList = barcodeMap.get(searchCode);
      const product = matchedList && matchedList.length > 0 ? matchedList[0] : null;

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
