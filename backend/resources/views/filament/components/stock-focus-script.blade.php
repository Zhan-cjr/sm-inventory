<style>
/* ===== TABLE HEADER ===== */
.adj-table-header {
    display: grid;
    grid-template-columns: 44px 100px 120px 1fr 80px 110px 100px 40px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-bottom: 2px solid #cbd5e1;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #475569;
    margin-bottom: 0;
}
.adj-table-header > span {
    padding: 10px 8px;
    border-right: 1px solid #e2e8f0;
}
.adj-table-header > span:first-child { text-align: center; }
.adj-table-header > span:last-child  { border-right: none; }

/* ===== HIDE FILAMENT REPEATER, SHOW OUR TABLE ===== */
.cart-repeater { display: none !important; }

/* ===== CART BODY ===== */
#adj-cart-body {
    border: 1px solid #e2e8f0;
    border-top: none;
    border-radius: 0 0 8px 8px;
    overflow: hidden;
    background: #fff;
    min-height: 52px;
}

.adj-cart-empty {
    padding: 24px;
    text-align: center;
    color: #94a3b8;
    font-size: 0.875rem;
}

.adj-row {
    display: grid;
    grid-template-columns: 44px 100px 120px 1fr 80px 110px 100px 40px;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
    min-height: 50px;
    background: #fff;
    transition: background 0.12s;
}
.adj-row:last-child { border-bottom: none; }
.adj-row:hover      { background: #f8fafc; }

.adj-cell {
    padding: 5px 8px;
    font-size: 0.875rem;
    color: #334155;
    border-right: 1px solid #f1f5f9;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    height: 100%;
    display: flex;
    align-items: center;
}
.adj-cell:last-child { border-right: none; justify-content: center; }
.adj-cell-no         { justify-content: center; font-weight: 700; color: #94a3b8; }
.adj-cell-name       { font-weight: 500; }
.adj-cell-num        { justify-content: flex-end; }

.adj-qty {
    width: 100%;
    border: 2px solid #3b82f6;
    border-radius: 6px;
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 700;
    font-size: 0.95rem;
    text-align: center;
    padding: 5px 4px;
    outline: none;
}
.adj-qty:focus {
    border-color: #1d4ed8;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
}

.adj-del {
    border: none; background: transparent;
    color: #ef4444; cursor: pointer;
    padding: 5px; border-radius: 5px;
    display: flex; align-items: center; justify-content: center; width: 100%;
}
.adj-del:hover { background: #fee2e2; }

/* ===== SEARCH ===== */
.search-section { background: #f8fafc !important; border: 1px solid #e2e8f0 !important; border-radius: 10px !important; }
.search-section .fi-section-header { display: none !important; }
.search-section .fi-section-content { padding: 10px 12px !important; }

.enter-item-label {
    background: #1e3a5f; color: white;
    padding: 10px 16px; font-weight: 700; font-size: 0.85rem; letter-spacing: 1px;
    height: 100%; display: flex; align-items: center; border-radius: 8px 0 0 8px;
}

.adj-flash {
    padding: 6px 12px; border-radius: 6px; font-size: 0.8rem;
    display: none; margin-top: 4px;
}
.adj-flash.error   { background: #fee2e2; color: #dc2626; display: block; }
.adj-flash.success { background: #dcfce7; color: #16a34a; display: block; }
</style>

{{-- Header rendered once, right here inside the Section --}}
<div class="adj-table-header">
    <span>No</span><span>SKU</span><span>Barcode</span>
    <span>Nama Barang</span><span>Stok</span>
    <span>Qty Koreksi</span><span>Qty Akhir</span><span></span>
</div>
<div id="adj-cart-body">
    <div class="adj-cart-empty">Keranjang kosong — scan atau ketik nama barang lalu tekan Enter</div>
</div>
<div class="adj-flash" id="adj-flash"></div>

<script>
(function() {
    // Run after Livewire is ready
    function init() {
        const cartBody = document.getElementById('adj-cart-body');
        const flash    = document.getElementById('adj-flash');

        // ── Get search input ──────────────────────────────────
        const getInput = () => document.querySelector('#search-product-input input');

        // ── Show flash message ────────────────────────────────
        function showFlash(msg, type = 'error') {
            flash.textContent = msg;
            flash.className = 'adj-flash ' + type;
            setTimeout(() => { flash.className = 'adj-flash'; }, 3000);
        }

        // ── Read state from Filament's hidden repeater ─────────
        // Each fi-repeater-item has inputs in order: sku, barcode, name, prev_qty, adj_qty, new_qty
        function getItems() {
            const rows = [];
            document.querySelectorAll('.cart-repeater .fi-fo-repeater-item').forEach((item, idx) => {
                const ins = item.querySelectorAll('input:not([type=hidden]), select');
                if (ins.length < 5) return; // skip empty ghost rows
                rows.push({
                    idx,
                    sku:      (ins[0]?.value || '').trim(),
                    barcode:  (ins[1]?.value || '').trim(),
                    name:     (ins[2]?.value || '').trim(),
                    prev:     ins[3]?.value || '0',
                    adj:      ins[4]?.value || '1',
                    newq:     ins[5]?.value || '0',
                    filamentItem: item,
                });
            });
            // Skip rows that have no sku (empty rows Filament might render)
            return rows.filter(r => r.sku !== '');
        }

        // ── Render visual table ───────────────────────────────
        function render() {
            const items = getItems();
            cartBody.innerHTML = '';

            if (!items.length) {
                cartBody.innerHTML = '<div class="adj-cart-empty">Keranjang kosong — scan atau ketik nama barang lalu tekan Enter</div>';
                return;
            }

            items.forEach((row, visIdx) => {
                const div = document.createElement('div');
                div.className = 'adj-row';
                div.innerHTML = `
                    <div class="adj-cell adj-cell-no">${visIdx + 1}</div>
                    <div class="adj-cell">${row.sku}</div>
                    <div class="adj-cell">${row.barcode}</div>
                    <div class="adj-cell adj-cell-name" title="${row.name}">${row.name}</div>
                    <div class="adj-cell adj-cell-num">${row.prev}</div>
                    <div class="adj-cell" style="padding:4px 6px">
                        <input class="adj-qty" type="number" min="0" value="${row.adj}" data-idx="${row.idx}">
                    </div>
                    <div class="adj-cell adj-cell-num" data-newq="${row.idx}">${row.newq}</div>
                    <div class="adj-cell">
                        <button type="button" class="adj-del" data-idx="${row.idx}" title="Hapus">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                    </div>`;
                cartBody.appendChild(div);
            });

            // Qty input → sync to Filament + update new_qty display
            cartBody.querySelectorAll('.adj-qty').forEach(inp => {
                inp.addEventListener('input', e => {
                    const fidx = parseInt(e.target.dataset.idx);
                    const filamentItem = document.querySelectorAll('.cart-repeater .fi-fo-repeater-item')[fidx];
                    if (!filamentItem) return;
                    const ins = filamentItem.querySelectorAll('input:not([type=hidden])');
                    if (ins[4]) {
                        ins[4].value = e.target.value;
                        ins[4].dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    // Update new_qty display optimistically
                    setTimeout(() => {
                        if (ins[5]) {
                            const newqEl = cartBody.querySelector(`[data-newq="${fidx}"]`);
                            if (newqEl) newqEl.textContent = ins[5].value;
                        }
                    }, 400);
                });

                // Enter → back to search
                inp.addEventListener('keydown', e => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        e.stopPropagation();
                        qtyFocusPending = false; // Release guard immediately
                        const si = getInput();
                        if (si) {
                            si.value = '';
                            si.focus();
                            // Double check focus because of potential Livewire interference
                            setTimeout(() => si.focus(), 50);
                        }
                    }
                });
            });

            // Delete buttons
            cartBody.querySelectorAll('.adj-del').forEach(btn => {
                btn.addEventListener('click', () => {
                    const fidx = parseInt(btn.dataset.idx);
                    const filamentItem = document.querySelectorAll('.cart-repeater .fi-fo-repeater-item')[fidx];
                    if (!filamentItem) return;
                    const header = filamentItem.querySelector('.fi-fo-repeater-item-header');
                    if (header) {
                        header.style.cssText = 'display:block!important;position:fixed;top:-9999px;visibility:hidden';
                        const delBtn = header.querySelector('button');
                        if (delBtn) delBtn.click();
                        setTimeout(() => { header.style.cssText = ''; }, 100);
                    }
                });
            });
        }

        // ── Watch Filament repeater for DOM changes ───────────
        const observer = new MutationObserver(() => {
            clearTimeout(window._adjRenderTimer);
            window._adjRenderTimer = setTimeout(render, 250);
        });

        const startObserver = () => {
            const rep = document.querySelector('.cart-repeater');
            if (rep) observer.observe(rep, { childList: true, subtree: true, attributes: true });
        };

        // ── Search/barcode input ──────────────────────────────
        function setupInput() {
            const si = getInput();
            if (!si) return;
            si.removeEventListener('keydown', si._adjHandler);
            si._adjHandler = (e) => {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                const q = si.value.trim();
                if (!q) return;
                si.value = '';
                si.disabled = true;

                // Dispatch Livewire event
                Livewire.dispatch('add-product-by-search', { query: q });
                setTimeout(() => { si.disabled = false; }, 500); 
            };
            si.addEventListener('keydown', si._adjHandler);
            si.focus();
        }

        // ── Livewire response events ──────────────────────────
        window.addEventListener('product-added', () => {
            // Wait slightly longer to ensure Filament DOM update is finished
            setTimeout(() => {
                render();
                // Focus last qty
                const qtys = cartBody.querySelectorAll('.adj-qty');
                if (qtys.length) { 
                    qtyFocusPending = true;
                    const lastQty = qtys[qtys.length - 1];
                    lastQty.focus(); 
                    lastQty.select(); 
                    // Release guard after interaction
                    setTimeout(() => { qtyFocusPending = false; }, 1000);
                }
            }, 600);
        });

        window.addEventListener('product-not-found', () => {
            showFlash('⚠ Barang tidak ditemukan');
            getInput()?.focus();
        });

        window.addEventListener('product-already-in-cart', () => {
            showFlash('ℹ Barang sudah ada di keranjang', 'success');
            getInput()?.focus();
        });

        // ── Init ──────────────────────────────────────────────
        startObserver();
        setupInput();
        render();

        // Re-setup input after Livewire re-renders
        document.addEventListener('livewire:navigated', setupInput);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        setTimeout(init, 600);
    }
})();
</script>
