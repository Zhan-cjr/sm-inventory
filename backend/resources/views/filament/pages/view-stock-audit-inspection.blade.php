<x-filament-panels::page>
@php
    $record = $this->record;
    $record->load(['items.product', 'items.rack', 'branch', 'auditor', 'adjustment']);
    
    $totalChecked = $record->items->count();
    $matchItems = $record->items->where('status', 'MATCH');
    $minusItems = $record->items->where('status', 'MINUS');
    $plusItems = $record->items->where('status', 'PLUS');
    
    $matchCount = $matchItems->count();
    $minusCount = $minusItems->count();
    $plusCount = $plusItems->count();
    $discCount = $minusCount + $plusCount;
    
    $accuracyRate = $totalChecked > 0 ? round(($matchCount / $totalChecked) * 100, 1) : 0;
    
    $totalSystemQty = $record->items->sum('system_quantity');
    $totalPhysicalQty = $record->items->sum('physical_quantity');
    $totalDiscrepancyQty = $record->items->sum('discrepancy_quantity');
    $netNominal = $record->total_gain_nominal - $record->total_loss_nominal;
@endphp

<div class="sa-wrapper" x-data="{
    activeFilter: 'all',
    searchQuery: '',
    basePrintUrl: '{{ route('stock-audits.print-bap', ['record' => $record->id]) }}',
    showReviseModal: false,
    reviseItemId: '',
    reviseProductName: '',
    reviseSystemQty: 0,
    revisePhysicalQty: 0,
    reviseNotes: '',
    isRevising: false,
    openReviseModal(id, name, sysQty, physQty, notes) {
        this.reviseItemId = id;
        this.reviseProductName = name;
        this.reviseSystemQty = sysQty;
        this.revisePhysicalQty = physQty;
        this.reviseNotes = notes || '';
        this.showReviseModal = true;
    },
    async submitRevise() {
        if (this.revisePhysicalQty === '' || isNaN(this.revisePhysicalQty)) {
            alert('Masukkan jumlah fisik yang valid');
            return;
        }
        this.isRevising = true;
        try {
            await $wire.reviseItem(this.reviseItemId, parseFloat(this.revisePhysicalQty), this.reviseNotes);
            this.showReviseModal = false;
        } finally {
            this.isRevising = false;
        }
    },
    getPrintUrl() {
        let params = new URLSearchParams();
        if (this.activeFilter && this.activeFilter !== 'all') {
            params.set('filter', this.activeFilter);
        }
        if (this.searchQuery && this.searchQuery.trim() !== '') {
            params.set('q', this.searchQuery.trim());
        }
        const qs = params.toString();
        return qs ? `${this.basePrintUrl}?${qs}` : this.basePrintUrl;
    },
    getFilterLabel() {
        if (this.activeFilter === 'discrepancy') return 'Selisih Saja';
        if (this.activeFilter === 'minus') return 'Minus Saja';
        if (this.activeFilter === 'plus') return 'Plus Saja';
        if (this.activeFilter === 'match') return 'Cocok Saja';
        return 'Semua Item';
    },
    syncHeaderPrintBtn() {
        const url = this.getPrintUrl();
        document.querySelectorAll('a').forEach(el => {
            if (el.href && el.href.includes('print-bap')) {
                el.setAttribute('href', url);
            }
        });
    },
    matchesFilter(itemStatus, itemName, itemBarcode, itemSku) {
        const q = this.searchQuery.toLowerCase().trim();
        const matchesSearch = !q || 
            (itemName && itemName.toLowerCase().includes(q)) || 
            (itemBarcode && itemBarcode.toLowerCase().includes(q)) || 
            (itemSku && itemSku.toLowerCase().includes(q));
            
        if (!matchesSearch) return false;
        
        if (this.activeFilter === 'all') return true;
        if (this.activeFilter === 'discrepancy') return itemStatus !== 'MATCH';
        if (this.activeFilter === 'minus') return itemStatus === 'MINUS';
        if (this.activeFilter === 'plus') return itemStatus === 'PLUS';
        if (this.activeFilter === 'match') return itemStatus === 'MATCH';
        return true;
    }
}" x-init="$watch('activeFilter', () => syncHeaderPrintBtn()); $watch('searchQuery', () => syncHeaderPrintBtn());">
    <style>
        .sa-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            font-family: inherit;
        }
        .sa-wrapper svg {
            width: 1.25rem !important;
            height: 1.25rem !important;
            max-width: 1.5rem !important;
            max-height: 1.5rem !important;
            display: inline-block !important;
            vertical-align: middle !important;
            flex-shrink: 0 !important;
        }

        /* Grids */
        .sa-grid-3 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
        }
        @media (min-width: 768px) {
            .sa-grid-3 {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .sa-grid-4 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        @media (min-width: 640px) {
            .sa-grid-4 {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (min-width: 1024px) {
            .sa-grid-4 {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Info Cards */
        .sa-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.25rem 1.35rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .dark .sa-card {
            background: #111827;
            border-color: #1f2937;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .sa-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.85rem;
        }
        .sa-card-head-left {
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }
        .sa-icon-box {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sa-icon-box.indigo { background: #eef2ff; color: #4f46e5; border: 1px solid #e0e7ff; }
        .dark .sa-icon-box.indigo { background: rgba(79, 70, 229, 0.15); color: #818cf8; border-color: rgba(99, 102, 241, 0.3); }

        .sa-icon-box.sky { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; }
        .dark .sa-icon-box.sky { background: rgba(2, 132, 199, 0.15); color: #38bdf8; border-color: rgba(14, 165, 233, 0.3); }

        .sa-icon-box.purple { background: #faf5ff; color: #9333ea; border: 1px solid #e9d5ff; }
        .dark .sa-icon-box.purple { background: rgba(147, 51, 234, 0.15); color: #c084fc; border-color: rgba(168, 85, 247, 0.3); }

        .sa-card-label {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
        }
        .dark .sa-card-label { color: #94a3b8; }

        .sa-doc-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            letter-spacing: -0.02em;
            margin-bottom: 0.75rem;
        }
        .dark .sa-doc-title { color: #f8fafc; }

        .sa-branch-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }
        .dark .sa-branch-title { color: #f8fafc; }

        .sa-info-list {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
            border-top: 1px dashed #e2e8f0;
            padding-top: 0.75rem;
        }
        .dark .sa-info-list { border-top-color: #1f2937; }

        .sa-info-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8rem;
            gap: 0.5rem;
        }
        .sa-info-key {
            color: #64748b;
            font-weight: 500;
        }
        .dark .sa-info-key { color: #94a3b8; }

        .sa-info-val {
            color: #1e293b;
            font-weight: 700;
            text-align: right;
        }
        .dark .sa-info-val { color: #e2e8f0; }

        /* Status Pills */
        .sa-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .sa-status-pill.draft { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .dark .sa-status-pill.draft { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border-color: rgba(245, 158, 11, 0.3); }

        .sa-status-pill.pending { background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; }
        .dark .sa-status-pill.pending { background: rgba(14, 165, 233, 0.15); color: #38bdf8; border-color: rgba(14, 165, 233, 0.3); }

        .sa-status-pill.adjusted { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .dark .sa-status-pill.adjusted { background: rgba(16, 185, 129, 0.15); color: #4ade80; border-color: rgba(16, 185, 129, 0.3); }

        .sa-status-pill.closed { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .dark .sa-status-pill.closed { background: #1e293b; color: #94a3b8; border-color: #334155; }

        .sa-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .sa-dot.amber { background: #f59e0b; }
        .sa-dot.sky { background: #0ea5e9; }
        .sa-dot.emerald { background: #10b981; }
        .sa-dot.gray { background: #64748b; }

        /* Notes & IRA Progress */
        .sa-notes-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.65rem 0.85rem;
            font-size: 0.78rem;
            color: #475569;
            font-style: italic;
            min-height: 52px;
            margin-bottom: 0.75rem;
            line-height: 1.45;
        }
        .dark .sa-notes-box {
            background: #1e293b;
            border-color: #334155;
            color: #cbd5e1;
        }

        .sa-ira-wrap {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .sa-ira-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
        }
        .sa-progress-bg {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 9999px;
            overflow: hidden;
        }
        .dark .sa-progress-bg { background: #1f2937; }
        .sa-progress-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.4s ease;
        }
        .sa-progress-fill.high { background: linear-gradient(90deg, #10b981, #059669); }
        .sa-progress-fill.med  { background: linear-gradient(90deg, #f59e0b, #d97706); }
        .sa-progress-fill.low  { background: linear-gradient(90deg, #ef4444, #dc2626); }

        /* KPI Summary Cards */
        .sa-kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.15rem 1.25rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .dark .sa-kpi-card {
            background: #111827;
            border-color: #1f2937;
        }
        .sa-kpi-card.border-emerald { border-left: 4px solid #10b981; }
        .sa-kpi-card.border-rose    { border-left: 4px solid #ef4444; }
        .sa-kpi-card.border-indigo  { border-left: 4px solid #6366f1; }
        .sa-kpi-card.border-amber   { border-left: 4px solid #f59e0b; }

        .sa-kpi-label {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        .dark .sa-kpi-label { color: #94a3b8; }

        .sa-kpi-val {
            font-size: 1.75rem;
            font-weight: 900;
            line-height: 1.15;
            margin-top: 0.25rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            color: #0f172a;
        }
        .dark .sa-kpi-val { color: #f8fafc; }
        .sa-kpi-val.emerald { color: #059669; }
        .dark .sa-kpi-val.emerald { color: #34d399; }
        .sa-kpi-val.rose { color: #dc2626; }
        .dark .sa-kpi-val.rose { color: #f87171; }

        .sa-kpi-sub {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            flex-wrap: wrap;
        }
        .dark .sa-kpi-sub { color: #94a3b8; }

        .sa-kpi-icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sa-kpi-icon.slate   { background: #f1f5f9; color: #475569; }
        .dark .sa-kpi-icon.slate { background: #1e293b; color: #cbd5e1; }
        .sa-kpi-icon.emerald { background: #ecfdf5; color: #059669; }
        .dark .sa-kpi-icon.emerald { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .sa-kpi-icon.rose    { background: #fef2f2; color: #dc2626; }
        .dark .sa-kpi-icon.rose { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .sa-kpi-icon.amber   { background: #fffbeb; color: #d97706; }
        .dark .sa-kpi-icon.amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }

        /* Table Card */
        .sa-table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }
        .dark .sa-table-card {
            background: #111827;
            border-color: #1f2937;
        }

        .sa-table-header {
            padding: 1rem 1.25rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .dark .sa-table-header {
            background: #1e293b;
            border-bottom-color: #334155;
        }

        .sa-filter-list {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            flex-wrap: wrap;
        }

        .sa-filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.42rem 0.85rem;
            border-radius: 0.6rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            transition: all 0.15s ease;
        }
        .dark .sa-filter-btn {
            background: #0f172a;
            border-color: #334155;
            color: #cbd5e1;
        }
        .sa-filter-btn:hover {
            border-color: #94a3b8;
        }
        .sa-filter-btn.active-all {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #ffffff;
            font-weight: 700;
        }
        .sa-filter-btn.active-disc {
            background: #d97706;
            border-color: #d97706;
            color: #ffffff;
            font-weight: 700;
        }
        .sa-filter-btn.active-minus {
            background: #dc2626;
            border-color: #dc2626;
            color: #ffffff;
            font-weight: 700;
        }
        .sa-filter-btn.active-plus {
            background: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
            font-weight: 700;
        }
        .sa-filter-btn.active-match {
            background: #059669;
            border-color: #059669;
            color: #ffffff;
            font-weight: 700;
        }

        .sa-filter-count {
            padding: 0.05rem 0.4rem;
            border-radius: 9999px;
            font-size: 0.68rem;
            background: rgba(0, 0, 0, 0.08);
        }
        .dark .sa-filter-count { background: rgba(255, 255, 255, 0.15); }
        .sa-filter-btn[class*="active-"] .sa-filter-count {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        /* Search Input */
        .sa-search-box {
            position: relative;
            width: 100%;
            max-width: 280px;
        }
        .sa-search-input {
            width: 100%;
            padding: 0.45rem 2rem 0.45rem 2.1rem;
            font-size: 0.78rem;
            border-radius: 0.65rem;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            outline: none;
        }
        .dark .sa-search-input {
            background: #0f172a;
            border-color: #334155;
            color: #f8fafc;
        }
        .sa-search-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
        }
        .sa-search-icon {
            position: absolute;
            left: 0.65rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            display: flex;
            align-items: center;
        }
        .sa-search-clear {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            padding: 2px;
        }

        .sa-toolbar-right {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
        }
        .sa-print-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.9rem;
            border-radius: 0.65rem;
            font-size: 0.76rem;
            font-weight: 700;
            text-decoration: none;
            background: #0f172a;
            color: #ffffff !important;
            border: 1px solid #0f172a;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .sa-print-btn:hover {
            background: #1e293b;
        }
        .dark .sa-print-btn {
            background: #334155;
            border-color: #475569;
        }
        .dark .sa-print-btn:hover {
            background: #475569;
        }

        /* Data Table */
        .sa-table-scroll {
            width: 100%;
            overflow-x: auto;
        }
        .sa-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.8rem;
        }
        .sa-table thead tr {
            background: #1e293b;
            color: #f8fafc;
            border-bottom: 1px solid #334155;
        }
        .sa-table th {
            padding: 0.85rem 0.9rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }
        .sa-table td {
            padding: 0.85rem 0.9rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .dark .sa-table td {
            border-bottom-color: #1f2937;
        }
        .sa-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .dark .sa-table tbody tr:nth-child(even) {
            background: rgba(30, 41, 59, 0.35);
        }
        .sa-table tbody tr:hover {
            background: #eef2ff;
        }
        .dark .sa-table tbody tr:hover {
            background: rgba(79, 70, 229, 0.12);
        }

        .sa-prod-name {
            font-weight: 700;
            color: #0f172a;
            font-size: 0.82rem;
            line-height: 1.35;
        }
        .dark .sa-prod-name { color: #f1f5f9; }

        .sa-barcode-tag {
            display: inline-block;
            margin-top: 0.25rem;
            padding: 0.1rem 0.45rem;
            border-radius: 0.35rem;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.7rem;
            font-weight: 700;
            color: #475569;
        }
        .dark .sa-barcode-tag {
            background: #1e293b;
            border-color: #334155;
            color: #cbd5e1;
        }

        .sa-rack-badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 0.4rem;
            font-size: 0.7rem;
            font-weight: 700;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            white-space: nowrap;
        }
        .dark .sa-rack-badge {
            background: rgba(37, 99, 235, 0.15);
            color: #60a5fa;
            border-color: rgba(59, 130, 246, 0.3);
        }

        .sa-qty-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            min-width: 62px;
            padding: 0.22rem 0.6rem;
            border-radius: 9999px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.75rem;
            font-weight: 800;
        }
        .sa-qty-badge.minus {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .dark .sa-qty-badge.minus {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.35);
        }
        .sa-qty-badge.plus {
            background: #dbeafe;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .dark .sa-qty-badge.plus {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border-color: rgba(59, 130, 246, 0.35);
        }
        .sa-qty-badge.match {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .dark .sa-qty-badge.match {
            background: rgba(16, 185, 129, 0.2);
            color: #4ade80;
            border-color: rgba(16, 185, 129, 0.35);
        }

        .sa-table tfoot tr {
            background: #f1f5f9;
            border-top: 2px solid #cbd5e1;
            font-weight: 800;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            color: #0f172a;
        }
        .dark .sa-table tfoot tr {
            background: #1e293b;
            border-top-color: #334155;
            color: #f8fafc;
        }
        .sa-table tfoot td {
            padding: 0.9rem;
            font-size: 0.8rem;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .text-rose { color: #dc2626; font-weight: 700; }
        .dark .text-rose { color: #f87171; }
        .text-blue { color: #2563eb; font-weight: 700; }
        .dark .text-blue { color: #60a5fa; }
        .text-emerald { color: #059669; font-weight: 700; }
        .dark .text-emerald { color: #34d399; }
        .text-muted { color: #64748b; }
        .dark .text-muted { color: #94a3b8; }
    </style>

    {{-- 1. CARD INFORMASI DOKUMEN, CABANG, DAN CATATAN --}}
    <div class="sa-grid-3">
        
        {{-- Card 1: Dokumen Sidak --}}
        <div class="sa-card">
            <div>
                <div class="sa-card-head">
                    <div class="sa-card-head-left">
                        <div class="sa-icon-box indigo">
                            <x-heroicon-o-document-text style="width: 18px; height: 18px;" />
                        </div>
                        <span class="sa-card-label">Dokumen Sidak</span>
                    </div>

                    @if($record->status === 'DRAFT')
                        <span class="sa-status-pill draft">
                            <span class="sa-dot amber"></span> Sedang Berjalan
                        </span>
                    @elseif($record->status === 'INVESTIGATION_PENDING')
                        <span class="sa-status-pill pending">
                            <span class="sa-dot sky"></span> Menunggu Klarifikasi
                        </span>
                    @elseif($record->status === 'ADJUSTED')
                        <span class="sa-status-pill adjusted">
                            <span class="sa-dot emerald"></span> Stok Disesuaikan
                        </span>
                    @else
                        <span class="sa-status-pill closed">
                            <span class="sa-dot gray"></span> Ditutup
                        </span>
                    @endif
                </div>

                <div class="sa-doc-title">{{ $record->audit_number }}</div>
            </div>

            <div class="sa-info-list">
                <div class="sa-info-row">
                    <span class="sa-info-key">Tanggal Sidak</span>
                    <span class="sa-info-val">
                        {{ $record->inspection_date?->translatedFormat('d F Y') ?: $record->created_at->format('d/m/Y') }}
                    </span>
                </div>
                <div class="sa-info-row">
                    <span class="sa-info-key">Metode Inspeksi</span>
                    <span class="sa-info-val" style="color: #4f46e5;">
                        @if($record->inspection_mode === 'FREE_SCAN')
                            Spot Check (Scan Bebas)
                        @elseif($record->inspection_mode === 'RANDOM_LOTTERY')
                            Undian Acak Sistem
                        @else
                            Berbasis Risiko Tinggi
                        @endif
                    </span>
                </div>
                <div class="sa-info-row">
                    <span class="sa-info-key">Waktu Dibuat</span>
                    <span class="sa-info-val">{{ $record->created_at->format('H:i') }} WIB</span>
                </div>
            </div>
        </div>

        {{-- Card 2: Lokasi Cabang & Pelaksana --}}
        <div class="sa-card">
            <div>
                <div class="sa-card-head">
                    <div class="sa-card-head-left">
                        <div class="sa-icon-box sky">
                            <x-heroicon-o-building-storefront style="width: 18px; height: 18px;" />
                        </div>
                        <span class="sa-card-label">Cabang & Pelaksana</span>
                    </div>
                </div>

                <div class="sa-branch-title">{{ $record->branch?->name ?: 'Semua Cabang' }}</div>
            </div>

            <div class="sa-info-list">
                <div class="sa-info-row">
                    <span class="sa-info-key">Pemeriksa / Auditor</span>
                    <span class="sa-info-val">{{ $record->auditor?->name ?: '-' }}</span>
                </div>
                <div class="sa-info-row">
                    <span class="sa-info-key">Saksi Toko / PIC</span>
                    <span class="sa-info-val">{{ $record->acknowledged_by ?: '-' }}</span>
                </div>
                <div class="sa-info-row">
                    <span class="sa-info-key">No. Dokumen Koreksi</span>
                    @if($record->adjustment)
                        <span class="sa-info-val text-emerald font-mono">{{ $record->adjustment->adjustment_number }}</span>
                    @else
                        <span class="sa-info-val text-muted">Belum Ada Koreksi</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card 3: Catatan & Akurasi Stok (IRA) --}}
        <div class="sa-card">
            <div>
                <div class="sa-card-head">
                    <div class="sa-card-head-left">
                        <div class="sa-icon-box purple">
                            <x-heroicon-o-clipboard-document-check style="width: 18px; height: 18px;" />
                        </div>
                        <span class="sa-card-label">Catatan & Akurasi Stok</span>
                    </div>
                </div>

                <div class="sa-notes-box">
                    {{ $record->notes ?: 'Tidak ada catatan tambahan yang dicantumkan saat inspeksi.' }}
                </div>
            </div>

            <div class="sa-ira-wrap">
                <div class="sa-ira-head">
                    <span class="sa-info-key">Akurasi Stok (IRA)</span>
                    <span class="font-mono" style="font-weight: 800; color: {{ $accuracyRate >= 95 ? '#059669' : ($accuracyRate >= 80 ? '#d97706' : '#dc2626') }};">
                        {{ $accuracyRate }}% ({{ $matchCount }}/{{ $totalChecked }} Cocok)
                    </span>
                </div>
                <div class="sa-progress-bg">
                    <div 
                        class="sa-progress-fill {{ $accuracyRate >= 95 ? 'high' : ($accuracyRate >= 80 ? 'med' : 'low') }}" 
                        style="width: {{ min(100, max(0, $accuracyRate)) }}%;"
                    ></div>
                </div>
            </div>
        </div>

    </div>

    {{-- 2. CARD RANGKUMAN METRIK (KPI) --}}
    <div class="sa-grid-4">
        
        {{-- KPI 1: Jumlah Item Dicek --}}
        <div class="sa-kpi-card border-indigo">
            <div>
                <div class="sa-kpi-label">Jumlah Item Dicek</div>
                <div class="sa-kpi-val">{{ number_format($totalChecked, 0) }}</div>
                <div class="sa-kpi-sub">
                    <span>Fisik: <strong>{{ number_format($totalPhysicalQty, 0) }}</strong></span>
                    <span>&bull;</span>
                    <span>Sistem: <strong>{{ number_format($totalSystemQty, 0) }}</strong></span>
                </div>
            </div>
            <div class="sa-kpi-icon slate">
                <x-heroicon-o-cube style="width: 22px; height: 22px;" />
            </div>
        </div>

        {{-- KPI 2: Item Cocok (Match) --}}
        <div class="sa-kpi-card border-emerald">
            <div>
                <div class="sa-kpi-label">Item Sesuai (Match)</div>
                <div class="sa-kpi-val emerald">{{ number_format($matchCount, 0) }}</div>
                <div class="sa-kpi-sub">
                    <strong class="text-emerald">{{ $accuracyRate }}%</strong> produk akurat 100%
                </div>
            </div>
            <div class="sa-kpi-icon emerald">
                <x-heroicon-o-check-badge style="width: 22px; height: 22px;" />
            </div>
        </div>

        {{-- KPI 3: Jumlah Selisih --}}
        <div class="sa-kpi-card {{ $discCount > 0 ? 'border-rose' : 'border-emerald' }}">
            <div>
                <div class="sa-kpi-label">Jumlah Item Selisih</div>
                <div class="sa-kpi-val {{ $discCount > 0 ? 'rose' : 'emerald' }}">{{ number_format($discCount, 0) }}</div>
                <div class="sa-kpi-sub">
                    <span class="text-rose">{{ $minusCount }} Minus</span>
                    <span>&bull;</span>
                    <span class="text-blue">{{ $plusCount }} Plus</span>
                </div>
            </div>
            <div class="sa-kpi-icon {{ $discCount > 0 ? 'rose' : 'emerald' }}">
                <x-heroicon-o-exclamation-triangle style="width: 22px; height: 22px;" />
            </div>
        </div>

        {{-- KPI 4: Dampak Finansial --}}
        <div class="sa-kpi-card border-amber">
            <div>
                <div class="sa-kpi-label">Kerugian (Selisih Minus)</div>
                <div class="sa-kpi-val rose" style="font-size: 1.35rem;">
                    -Rp {{ number_format($record->total_loss_nominal, 0, ',', '.') }}
                </div>
                <div class="sa-kpi-sub">
                    <span class="text-emerald">Lebih: +Rp {{ number_format($record->total_gain_nominal, 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="sa-kpi-icon amber">
                <x-heroicon-o-banknotes style="width: 22px; height: 22px;" />
            </div>
        </div>

    </div>

    {{-- 3. TABEL RINCIAN ITEM YANG DIPERIKSA --}}
    <div class="sa-table-card">
        
        {{-- Toolbar Filter & Pencarian --}}
        <div class="sa-table-header">
            <div class="sa-filter-list">
                <button
                    type="button"
                    class="sa-filter-btn"
                    @click="activeFilter = 'all'"
                    :class="{ 'active-all': activeFilter === 'all' }"
                >
                    <span>Semua Item</span>
                    <span class="sa-filter-count">{{ $totalChecked }}</span>
                </button>

                <button
                    type="button"
                    class="sa-filter-btn"
                    @click="activeFilter = 'discrepancy'"
                    :class="{ 'active-disc': activeFilter === 'discrepancy' }"
                >
                    <span class="sa-dot amber"></span>
                    <span>Selisih Saja</span>
                    <span class="sa-filter-count">{{ $discCount }}</span>
                </button>

                <button
                    type="button"
                    class="sa-filter-btn"
                    @click="activeFilter = 'minus'"
                    :class="{ 'active-minus': activeFilter === 'minus' }"
                >
                    <span class="sa-dot" style="background: #ef4444;"></span>
                    <span>Minus (Kurang)</span>
                    <span class="sa-filter-count">{{ $minusCount }}</span>
                </button>

                <button
                    type="button"
                    class="sa-filter-btn"
                    @click="activeFilter = 'plus'"
                    :class="{ 'active-plus': activeFilter === 'plus' }"
                >
                    <span class="sa-dot sky"></span>
                    <span>Plus (Lebih)</span>
                    <span class="sa-filter-count">{{ $plusCount }}</span>
                </button>

                <button
                    type="button"
                    class="sa-filter-btn"
                    @click="activeFilter = 'match'"
                    :class="{ 'active-match': activeFilter === 'match' }"
                >
                    <span class="sa-dot emerald"></span>
                    <span>Cocok (Match)</span>
                    <span class="sa-filter-count">{{ $matchCount }}</span>
                </button>
            </div>

            <div class="sa-toolbar-right">
                <div class="sa-search-box">
                    <span class="sa-search-icon">
                        <x-heroicon-m-magnifying-glass style="width: 15px; height: 15px;" />
                    </span>
                    <input
                        type="text"
                        x-model="searchQuery"
                        placeholder="Cari nama produk / barcode / SKU..."
                        class="sa-search-input"
                    />
                    <button
                        type="button"
                        x-show="searchQuery"
                        @click="searchQuery = ''"
                        class="sa-search-clear"
                    >
                        <x-heroicon-m-x-mark style="width: 15px; height: 15px;" />
                    </button>
                </div>

                <a
                    :href="getPrintUrl()"
                    target="_blank"
                    class="sa-print-btn"
                    title="Cetak BAP sesuai filter tab yang sedang aktif"
                >
                    <x-heroicon-o-printer style="width: 15px; height: 15px;" />
                    <span>Cetak BAP (<span x-text="getFilterLabel()">Semua Item</span>)</span>
                </a>
            </div>
        </div>

        {{-- Tabel Data --}}
        <div class="sa-table-scroll">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 48px;">No</th>
                        <th style="min-width: 250px;">Produk & Barcode</th>
                        <th class="text-center" style="min-width: 110px;">Lokasi Rak</th>
                        <th class="text-right" style="min-width: 95px;">Stok Sistem</th>
                        <th class="text-right" style="min-width: 95px;">Fisik Dihitung</th>
                        <th class="text-center" style="min-width: 105px;">Selisih Qty</th>
                        <th class="text-right" style="min-width: 105px;">HPP Satuan</th>
                        <th class="text-right" style="min-width: 130px;">Total Selisih (Rp)</th>
                        <th style="min-width: 150px;">Keterangan</th>
                        <th class="text-center" style="width: 80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($record->items as $idx => $it)
                        @php
                            $prod = $it->product;
                            $pName = addslashes($prod?->name ?? 'Produk Dihapus');
                            $pBarcode = addslashes($prod?->barcode ?? '');
                            $pSku = addslashes($prod?->sku ?? '');
                            $status = $it->status;
                        @endphp
                        <tr x-show="matchesFilter('{{ $status }}', '{{ $pName }}', '{{ $pBarcode }}', '{{ $pSku }}')">
                            <td class="text-center font-mono text-muted">{{ $idx + 1 }}</td>

                            <td>
                                <div class="sa-prod-name">{{ $prod?->name ?? 'Produk Tidak Ditemukan' }}</div>
                                <div>
                                    <span class="sa-barcode-tag">
                                        {{ $prod?->barcode ?: ($prod?->sku ?: '-') }}
                                    </span>
                                    @if($prod?->barcode && $prod?->sku && $prod->barcode !== $prod->sku)
                                        <span class="text-muted font-mono" style="font-size: 0.7rem; margin-left: 4px;">
                                            / {{ $prod->sku }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="text-center">
                                @if($it->rack)
                                    <span class="sa-rack-badge">{{ $it->rack->rack_name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td class="text-right font-mono" style="font-weight: 600;">
                                {{ number_format($it->system_quantity, 0) }}
                            </td>

                            <td class="text-right font-mono" style="font-weight: 800; font-size: 0.88rem;">
                                {{ number_format($it->physical_quantity, 0) }}
                            </td>

                            <td class="text-center">
                                @if($it->discrepancy_quantity < 0)
                                    <span class="sa-qty-badge minus">
                                        {{ number_format($it->discrepancy_quantity, 0) }}
                                    </span>
                                @elseif($it->discrepancy_quantity > 0)
                                    <span class="sa-qty-badge plus">
                                        +{{ number_format($it->discrepancy_quantity, 0) }}
                                    </span>
                                @else
                                    <span class="sa-qty-badge match">
                                        <x-heroicon-m-check style="width: 13px; height: 13px;" /> 0
                                    </span>
                                @endif
                            </td>

                            <td class="text-right font-mono text-muted">
                                Rp {{ number_format($it->unit_cost, 0, ',', '.') }}
                            </td>

                            <td class="text-right font-mono">
                                @if($it->discrepancy_cost < 0)
                                    <span class="text-rose">
                                        -Rp {{ number_format(abs($it->discrepancy_cost), 0, ',', '.') }}
                                    </span>
                                @elseif($it->discrepancy_cost > 0)
                                    <span class="text-blue">
                                        +Rp {{ number_format($it->discrepancy_cost, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-muted">Rp 0</span>
                                @endif
                            </td>

                            <td class="text-muted" style="font-size: 0.75rem;">
                                {{ $it->clarification_notes ?: '-' }}
                            </td>

                            <td class="text-center">
                                @if($record->status !== 'ADJUSTED')
                                    <button 
                                        type="button"
                                        @click="openReviseModal('{{ $it->id }}', '{{ $pName }}', {{ (float)$it->system_quantity }}, {{ (float)$it->physical_quantity }}, '{{ addslashes($it->clarification_notes ?? '') }}')"
                                        style="padding: 4px 8px; font-size: 0.73rem; font-weight: 700; border-radius: 6px; background: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe; cursor: pointer; display: inline-flex; align-items: center; gap: 3px;"
                                        title="Revisi Qty Fisik"
                                    >
                                        <x-heroicon-m-pencil-square style="width: 13px; height: 13px;" /> Revisi
                                    </button>
                                @else
                                    <span class="text-muted font-mono" style="font-size: 0.75rem;">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted" style="padding: 3rem 1rem;">
                                Belum ada item yang diperiksa dalam sesi sidak ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right" style="text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.72rem; color: #64748b;">
                            Total Rekapitulasi:
                        </td>
                        <td class="text-right">{{ number_format($totalSystemQty, 0) }}</td>
                        <td class="text-right">{{ number_format($totalPhysicalQty, 0) }}</td>
                        <td class="text-center">
                            @if($totalDiscrepancyQty < 0)
                                <span class="text-rose">{{ number_format($totalDiscrepancyQty, 0) }}</span>
                            @elseif($totalDiscrepancyQty > 0)
                                <span class="text-blue">+{{ number_format($totalDiscrepancyQty, 0) }}</span>
                            @else
                                <span class="text-emerald">0</span>
                            @endif
                        </td>
                        <td></td>
                        <td class="text-right">
                            @if($netNominal < 0)
                                <span class="text-rose">-Rp {{ number_format(abs($netNominal), 0, ',', '.') }}</span>
                            @elseif($netNominal > 0)
                                <span class="text-blue">+Rp {{ number_format($netNominal, 0, ',', '.') }}</span>
                            @else
                                <span class="text-emerald">Rp 0</span>
                            @endif
                        </td>
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Modal Revisi Qty Fisik --}}
    <div 
        x-show="showReviseModal" 
        x-cloak
        style="position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); padding: 1rem;"
    >
        <div 
            @click.away="if (!isRevising) showReviseModal = false"
            style="background: #ffffff; border-radius: 1rem; width: 100%; max-width: 480px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid #e2e8f0; overflow: hidden; display: flex; flex-direction: column;"
            class="dark:bg-gray-900 dark:border-gray-800"
        >
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;" class="dark:border-gray-800">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center;">
                        <x-heroicon-m-pencil-square style="width: 18px; height: 18px;" />
                    </div>
                    <h3 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0;" class="dark:text-white">
                        Revisi Qty Fisik Sidak
                    </h3>
                </div>
                <button 
                    type="button" 
                    @click="showReviseModal = false" 
                    style="border: none; background: transparent; cursor: pointer; color: #94a3b8; font-size: 1.25rem;"
                >&times;</button>
            </div>

            <div style="padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 2px;">PRODUK</div>
                    <div style="font-size: 0.95rem; font-weight: 800; color: #0f172a;" class="dark:text-white" x-text="reviseProductName"></div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; background: #f8fafc; padding: 0.85rem; border-radius: 0.75rem; border: 1px solid #e2e8f0;" class="dark:bg-gray-800 dark:border-gray-700">
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; color: #64748b;">STOK SISTEM</div>
                        <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a; font-family: monospace;" class="dark:text-white" x-text="reviseSystemQty"></div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; color: #64748b;">SELISIH REVISI</div>
                        <div 
                            style="font-size: 1.1rem; font-weight: 800; font-family: monospace;"
                            :style="(revisePhysicalQty - reviseSystemQty) < 0 ? 'color: #e11d48;' : (revisePhysicalQty - reviseSystemQty) > 0 ? 'color: #2563eb;' : 'color: #10b981;'"
                            x-text="(revisePhysicalQty - reviseSystemQty) > 0 ? '+' + (revisePhysicalQty - reviseSystemQty) : (revisePhysicalQty - reviseSystemQty)"
                        ></div>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 0.35rem;" class="dark:text-gray-300">
                        Qty Fisik Sebenarnya (Hasil Hitung Ulang) <span style="color: #e11d48;">*</span>
                    </label>
                    <input 
                        type="number" 
                        step="1" 
                        x-model="revisePhysicalQty" 
                        style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 0.6rem; border: 1.5px solid #cbd5e1; font-size: 1rem; font-weight: 700; font-family: monospace;" 
                        class="dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                        placeholder="0"
                        autofocus
                    />
                </div>

                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 0.35rem;" class="dark:text-gray-300">
                        Catatan Klarifikasi / Alasan Revisi
                    </label>
                    <textarea 
                        x-model="reviseNotes" 
                        rows="2" 
                        style="width: 100%; padding: 0.65rem 0.85rem; border-radius: 0.6rem; border: 1.5px solid #cbd5e1; font-size: 0.85rem;" 
                        class="dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                        placeholder="Contoh: Ditemukan 1 pcs terselip di kolong display toko"
                    ></textarea>
                </div>
            </div>

            <div style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 0.65rem;" class="dark:bg-gray-800/50 dark:border-gray-800">
                <button 
                    type="button" 
                    @click="showReviseModal = false"
                    :disabled="isRevising"
                    style="padding: 0.55rem 1rem; border-radius: 0.6rem; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-size: 0.85rem; font-weight: 700; cursor: pointer;"
                    class="dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600"
                >
                    Batal
                </button>
                <button 
                    type="button" 
                    @click="submitRevise()"
                    :disabled="isRevising"
                    style="padding: 0.55rem 1.25rem; border-radius: 0.6rem; border: none; background: #4f46e5; color: #ffffff; font-size: 0.85rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;"
                >
                    <span x-show="!isRevising">Simpan Revisi Qty</span>
                    <span x-show="isRevising">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
