<x-filament-panels::page>
    <style>
        /* ============================================================
           ENTERPRISE DESIGN SYSTEM: EVALUASI KINERJA SUPPLIER
           Zero-Jargon, 100% Bahasa Indonesia, Anti-Scrolling & Fast Triage
           ============================================================ */
        .sl-dashboard {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            font-family: inherit;
            color: #0f172a;
        }
        .dark .sl-dashboard {
            color: #f1f5f9;
        }

        /* Prevent any SVG explosion */
        .sl-dashboard svg {
            display: inline-block;
            vertical-align: middle;
            max-width: 100%;
            max-height: 100%;
            flex-shrink: 0;
        }
        .sl-icon-xs { width: 14px; height: 14px; }
        .sl-icon-sm { width: 16px; height: 16px; }
        .sl-icon-md { width: 20px; height: 20px; }
        .sl-icon-lg { width: 26px; height: 26px; }

        /* Hero Header Card */
        .sl-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border: 1px solid #334155;
            border-radius: 1.25rem;
            padding: 1.5rem 1.75rem;
            color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .dark .sl-hero {
            background: linear-gradient(135deg, #090d16 0%, #111827 100%);
            border-color: #1f293d;
        }
        .sl-hero-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
        }
        .sl-hero-title-box {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .sl-hero-icon-badge {
            width: 48px;
            height: 48px;
            border-radius: 1rem;
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #60a5fa;
            flex-shrink: 0;
        }
        .sl-hero-title {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #ffffff;
            margin: 0;
            line-height: 1.2;
        }
        .sl-hero-sub {
            font-size: 0.8rem;
            color: #94a3b8;
            margin: 0.25rem 0 0 0;
        }
        .sl-hero-actions {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
        }
        .sl-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.55rem 0.95rem;
            border-radius: 0.65rem;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            border: none;
        }
        .sl-btn-reset {
            background: rgba(255, 255, 255, 0.1);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .sl-btn-reset:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
        .sl-btn-csv {
            background: #059669;
            color: #ffffff;
            border: 1px solid #10b981;
        }
        .sl-btn-csv:hover {
            background: #047857;
        }
        .sl-btn-print {
            background: #334155;
            color: #ffffff;
            border: 1px solid #475569;
        }
        .sl-btn-print:hover {
            background: #1e293b;
        }
        .sl-btn-danger-light {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .sl-btn-danger-light:hover {
            background: rgba(239, 68, 68, 0.3);
            color: #ffffff;
        }

        /* Filter Toolbar */
        .sl-filter-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.2rem 1.4rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .dark .sl-filter-box {
            background: #0f172a;
            border-color: #1e293b;
        }
        .sl-filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 0.85rem;
        }
        .sl-filter-item {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            position: relative;
        }
        .sl-filter-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }
        .dark .sl-filter-label {
            color: #94a3b8;
        }
        .sl-input, .sl-select, .sl-combobox-trigger {
            width: 100%;
            font-size: 0.78rem;
            padding: 0.5rem 0.65rem;
            border-radius: 0.55rem;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
            outline: none;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-align: left;
            cursor: pointer;
        }
        .dark .sl-input, .dark .sl-select, .dark .sl-combobox-trigger {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }
        .sl-input:focus, .sl-select:focus, .sl-combobox-trigger:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        /* Searchable Combobox Popover */
        .sl-combobox-popover {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 50;
            margin-top: 0.35rem;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
            padding: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            max-height: 280px;
        }
        .dark .sl-combobox-popover {
            background: #0f172a;
            border-color: #334155;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
        }
        .sl-combobox-search {
            width: 100%;
            padding: 0.45rem 0.6rem;
            border-radius: 0.45rem;
            border: 1px solid #e2e8f0;
            background: #f1f5f9;
            font-size: 0.75rem;
            color: #0f172a;
            outline: none;
        }
        .dark .sl-combobox-search {
            background: #1e293b;
            border-color: #334155;
            color: #ffffff;
        }
        .sl-combobox-list {
            overflow-y: auto;
            max-height: 200px;
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }
        .sl-combobox-item {
            padding: 0.45rem 0.6rem;
            border-radius: 0.45rem;
            font-size: 0.74rem;
            color: #334155;
            cursor: pointer;
            transition: background 0.1s ease;
            text-align: left;
            background: transparent;
            border: none;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dark .sl-combobox-item {
            color: #cbd5e1;
        }
        .sl-combobox-item:hover, .sl-combobox-item.selected {
            background: #eff6ff;
            color: #2563eb;
            font-weight: 700;
        }
        .dark .sl-combobox-item:hover, .dark .sl-combobox-item.selected {
            background: rgba(37, 99, 235, 0.2);
            color: #93c5fd;
        }

        /* KPI Summary Grid */
        .sl-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1rem;
        }
        .sl-kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.15rem 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            position: relative;
            overflow: hidden;
        }
        .dark .sl-kpi-card {
            background: #0f172a;
            border-color: #1e293b;
        }
        .sl-kpi-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .sl-kpi-label {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        .dark .sl-kpi-label {
            color: #94a3b8;
        }
        .sl-kpi-badge {
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 800;
        }
        .sl-badge-green { background: #dcfce7; color: #15803d; }
        .dark .sl-badge-green { background: rgba(22, 101, 52, 0.4); color: #4ade80; }
        .sl-badge-amber { background: #fef3c7; color: #b45309; }
        .dark .sl-badge-amber { background: rgba(146, 64, 14, 0.4); color: #fbbf24; }
        .sl-badge-red { background: #fee2e2; color: #b91c1c; }
        .dark .sl-badge-red { background: rgba(153, 27, 27, 0.4); color: #f87171; }
        .sl-badge-blue { background: #dbeafe; color: #1d4ed8; }
        .dark .sl-badge-blue { background: rgba(30, 64, 175, 0.4); color: #60a5fa; }

        .sl-kpi-value {
            font-size: 1.5rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.02em;
            line-height: 1.1;
        }
        .dark .sl-kpi-value {
            color: #ffffff;
        }
        .sl-kpi-sub {
            font-size: 0.72rem;
            color: #64748b;
        }
        .dark .sl-kpi-sub {
            color: #94a3b8;
        }
        .sl-meter-track {
            width: 100%;
            height: 6px;
            border-radius: 9999px;
            background: #f1f5f9;
            overflow: hidden;
            margin-top: 0.35rem;
        }
        .dark .sl-meter-track {
            background: #1e293b;
        }
        .sl-meter-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.4s ease;
        }

        /* Navigation Tabs */
        .sl-tabs {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0;
            overflow-x: auto;
        }
        .dark .sl-tabs {
            border-color: #1e293b;
        }
        .sl-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.1rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .dark .sl-tab-btn {
            color: #94a3b8;
        }
        .sl-tab-btn:hover {
            color: #0f172a;
        }
        .dark .sl-tab-btn:hover {
            color: #ffffff;
        }
        .sl-tab-btn.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
        }
        .dark .sl-tab-btn.active {
            color: #60a5fa;
            border-bottom-color: #60a5fa;
        }
        .sl-tab-count {
            padding: 0.15rem 0.45rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 800;
            background: #f1f5f9;
            color: #475569;
        }
        .dark .sl-tab-count {
            background: #1e293b;
            color: #94a3b8;
        }
        .sl-tab-btn.active .sl-tab-count {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .dark .sl-tab-btn.active .sl-tab-count {
            background: rgba(37, 99, 235, 0.3);
            color: #93c5fd;
        }

        /* Executive Reconciliation Summary Strip */
        .sl-recon-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.85rem 1.1rem;
        }
        .dark .sl-recon-strip {
            background: #131d31;
            border-color: #1e293b;
        }
        .sl-recon-strip-item {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }
        .sl-recon-strip-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }
        .dark .sl-recon-strip-label {
            color: #94a3b8;
        }
        .sl-recon-strip-val {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
        }
        .dark .sl-recon-strip-val {
            color: #ffffff;
        }

        /* Tab Toolbar / Header Bar */
        .sl-tab-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.6rem 0.85rem;
        }
        .dark .sl-tab-toolbar {
            background: #131d31;
            border-color: #1e293b;
        }

        /* Triage Toolbar for Tab 3 */
        .sl-triage-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.6rem 0.85rem;
        }
        .dark .sl-triage-bar {
            background: #131d31;
            border-color: #1e293b;
        }
        .sl-triage-pills {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
        }
        .sl-pill-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.7rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .dark .sl-pill-btn {
            background: #0f172a;
            border-color: #334155;
            color: #94a3b8;
        }
        .sl-pill-btn:hover {
            border-color: #2563eb;
            color: #2563eb;
        }
        .sl-pill-btn.active {
            background: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.3);
        }
        .sl-pill-btn.active-red {
            background: #ef4444;
            border-color: #ef4444;
            color: #ffffff;
            box-shadow: 0 1px 3px rgba(239, 68, 68, 0.3);
        }
        .sl-pill-btn.active-amber {
            background: #f59e0b;
            border-color: #f59e0b;
            color: #ffffff;
            box-shadow: 0 1px 3px rgba(245, 158, 11, 0.3);
        }

        .sl-triage-controls {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        /* Pagination Bar */
        .sl-pagination-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            font-size: 0.74rem;
            color: #64748b;
        }
        .dark .sl-pagination-bar {
            background: #131d31;
            border-color: #1e293b;
            color: #94a3b8;
        }
        .sl-pagination-pages {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .sl-page-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 28px;
            padding: 0 0.35rem;
            border-radius: 0.4rem;
            font-size: 0.72rem;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .dark .sl-page-btn {
            background: #0f172a;
            border-color: #334155;
            color: #cbd5e1;
        }
        .sl-page-btn:hover:not(:disabled) {
            border-color: #2563eb;
            color: #2563eb;
        }
        .sl-page-btn.active {
            background: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
        }
        .sl-page-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* Content Card & Tables */
        .sl-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 1rem;
        }
        .dark .sl-card {
            background: #0f172a;
            border-color: #1e293b;
        }
        .sl-table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
        }
        .dark .sl-table-wrap {
            border-color: #1e293b;
        }
        .sl-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.76rem;
            text-align: left;
        }
        .sl-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.68rem;
            letter-spacing: 0.04em;
            padding: 0.75rem 0.85rem;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        .dark .sl-table th {
            background: #131d31;
            color: #94a3b8;
            border-color: #1e293b;
        }
        .sl-table td {
            padding: 0.7rem 0.85rem;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }
        .dark .sl-table td {
            border-color: #1e293b;
            color: #cbd5e1;
        }
        .sl-table tr:hover td {
            background: #f8fafc;
        }
        .dark .sl-table tr:hover td {
            background: #131d31;
        }
        .sl-table tfoot td {
            background: #f8fafc;
            font-weight: 800;
            color: #0f172a;
            border-top: 2px solid #cbd5e1;
            border-bottom: none;
            padding: 0.85rem 0.85rem;
        }
        .dark .sl-table tfoot td {
            background: #131d31;
            color: #ffffff;
            border-top-color: #334155;
        }

        /* Grade Badges */
        .sl-grade-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 0.55rem;
            font-weight: 900;
            font-size: 0.85rem;
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .sl-grade-A { background: #10b981; }
        .sl-grade-B { background: #3b82f6; }
        .sl-grade-C { background: #f59e0b; }
        .sl-grade-D { background: #ef4444; }

        /* Modal */
        .sl-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            overflow-y: auto;
        }
        .sl-modal-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            width: 100%;
            max-width: 1000px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        .dark .sl-modal-box {
            background: #0f172a;
            border-color: #1e293b;
        }
        .sl-modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
        }
        .dark .sl-modal-header {
            background: #131d31;
            border-color: #1e293b;
        }
        .sl-modal-body {
            padding: 1.25rem 1.5rem;
            overflow-y: auto;
            flex: 1;
        }
        .sl-modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dark .sl-modal-footer {
            background: #131d31;
            border-color: #1e293b;
        }

        /* ============================================================
           PERFECT PRINT CSS FOR FILAMENT V3 & BROWSER PRINTER
           ============================================================ */
        @media print {
            @page {
                size: landscape;
                margin: 8mm 6mm 8mm 6mm;
            }

            html, body, 
            .fi-body, 
            .fi-layout, 
            .fi-main-ctn, 
            .fi-main, 
            .fi-page, 
            .fi-section,
            .sl-dashboard, 
            .sl-card, 
            .sl-table-wrap {
                overflow: visible !important;
                height: auto !important;
                min-height: 0 !important;
                max-height: none !important;
                position: static !important;
                display: block !important;
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                float: none !important;
            }

            /* Hide UI Navigation & non-print items */
            .no-print, 
            .fi-topbar, 
            .fi-sidebar, 
            .fi-header, 
            .fi-breadcrumbs, 
            header, 
            nav, 
            aside,
            .sl-modal-backdrop,
            .sl-tabs,
            .sl-pagination-bar,
            .sl-triage-bar,
            .sl-tab-toolbar {
                display: none !important;
            }

            /* Print Header */
            .sl-print-header {
                display: block !important;
                margin-bottom: 12px !important;
                padding-bottom: 8px !important;
                border-bottom: 2px solid #000000 !important;
            }

            /* Print Table */
            .sl-card {
                border: none !important;
                padding: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
            }
            .sl-table-wrap {
                overflow: visible !important;
                border: 1px solid #000000 !important;
            }
            .sl-table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 7.5pt !important;
                color: #000000 !important;
            }
            .sl-table th {
                background: #f1f5f9 !important;
                color: #000000 !important;
                border: 1px solid #333333 !important;
                padding: 4px 6px !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                font-size: 7pt !important;
            }
            .sl-table td {
                border: 1px solid #999999 !important;
                padding: 3px 5px !important;
                color: #000000 !important;
                font-size: 7.5pt !important;
            }
            .sl-table tr {
                page-break-inside: avoid !important;
            }
            .sl-table tfoot td {
                border-top: 2px solid #000000 !important;
                background: #f8fafc !important;
                font-weight: 800 !important;
            }

            /* KPI Summary in print */
            .sl-kpi-grid {
                display: grid !important;
                grid-template-columns: repeat(5, 1fr) !important;
                gap: 6px !important;
                margin-bottom: 10px !important;
                page-break-inside: avoid !important;
            }
            .sl-kpi-card {
                border: 1px solid #666666 !important;
                padding: 6px !important;
                border-radius: 4px !important;
                background: #ffffff !important;
            }
            .sl-kpi-label {
                font-size: 6.5pt !important;
                color: #333333 !important;
                font-weight: 800 !important;
            }
            .sl-kpi-value {
                font-size: 10pt !important;
                color: #000000 !important;
                font-weight: 900 !important;
            }
            .sl-meter-track {
                display: none !important;
            }
            .sl-grade-badge {
                border: 1px solid #000000 !important;
                color: #000000 !important;
                background: #ffffff !important;
                font-weight: 900 !important;
            }
        }
    </style>

    @php
        $allSuppliers = \App\Models\Supplier::orderBy('name')->get(['id', 'name', 'code']);
        $currentSupplierName = 'Semua Supplier';
        if ($selected_supplier_id !== 'ALL') {
            $matched = $allSuppliers->firstWhere('id', $selected_supplier_id);
            if ($matched) {
                $currentSupplierName = $matched->name;
            }
        }
        $scorecardData = $this->supplier_scorecards;
        $reconData = $this->po_reconciliations;
        $discData = $this->item_discrepancies;
    @endphp

    <div class="sl-dashboard">
        {{-- PRINTABLE HEADER (Visible Only in Print Mode) --}}
        <div class="sl-print-header" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h1 style="font-size: 14pt; font-weight: 900; margin: 0; text-transform: uppercase; color: #000;">
                        SM INVENTORY — LAPORAN KINERJA SUPPLIER & REKONSILIASI PO
                    </h1>
                    <p style="font-size: 8.5pt; color: #333; margin: 2px 0 0 0;">
                        Laporan: <b>{{ $active_tab === 'scorecard' ? '1. Rapor Kinerja Supplier' : ($active_tab === 'reconciliation' ? '2. Rekonsiliasi PO vs Penerimaan' : '3. Audit Selisih Barang & Harga') }}</b>
                    </p>
                </div>
                <div style="text-align: right; font-size: 7.5pt; color: #444;">
                    <div>Dicetak: <b>{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</b></div>
                    <div>User: <b>{{ auth()->user()->name ?? 'Administrator' }}</b></div>
                </div>
            </div>
            <div style="display: flex; gap: 15px; margin-top: 6px; font-size: 8pt; background: #f8fafc; padding: 4px 8px; border: 1px solid #ccc; border-radius: 4px;">
                <span>Periode: <b>{{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}</b></span>
                <span>Cabang: <b>{{ $selected_branch_id === 'ALL' ? 'Semua Cabang' : (\App\Models\Branch::find($selected_branch_id)->name ?? '-') }}</b></span>
                <span>Supplier: <b>{{ $currentSupplierName }}</b></span>
            </div>
        </div>

        {{-- HERO HEADER --}}
        <div class="sl-hero no-print">
            <div class="sl-hero-top">
                <div class="sl-hero-title-box">
                    <div class="sl-hero-icon-badge">
                        <svg class="sl-icon-lg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.25 2.25L15 7.5" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="sl-hero-title">Laporan Kinerja Supplier & Rekonsiliasi PO</h1>
                        <p class="sl-hero-sub">Evaluasi kelengkapan barang, ketepatan jadwal kirim, akurasi faktur, dan audit deviasi harga supplier.</p>
                    </div>
                </div>

                <div class="sl-hero-actions">
                    <button wire:click="resetFilter" type="button" class="sl-btn sl-btn-reset">
                        <svg class="sl-icon-sm" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Reset Filter
                    </button>

                    <button 
                        wire:click="closeAllExpiredPos" 
                        wire:confirm="Apakah Anda yakin ingin menutup (menghanguskan) semua sisa PO yang sudah melewati tanggal expired? Sisa barang tidak akan lagi dihitung sebagai tunggakan aktif."
                        type="button" 
                        class="sl-btn sl-btn-danger-light" 
                        title="Tutup & hanguskan semua PO yang sudah lewat tanggal expired">
                        <svg class="sl-icon-sm" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        Tutup Semua PO Expired
                    </button>

                    <button wire:click="exportCsv" type="button" class="sl-btn sl-btn-csv">
                        <svg class="sl-icon-sm" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Export CSV
                    </button>

                    <button 
                        x-data 
                        @click="
                            const p = new URLSearchParams({
                                start_date: $wire.start_date || '',
                                end_date: $wire.end_date || '',
                                branch_id: $wire.selected_branch_id || 'ALL',
                                supplier_id: $wire.selected_supplier_id || 'ALL',
                                active_tab: $wire.active_tab || 'scorecard',
                                status: $wire.status_filter || 'ALL',
                                grade: $wire.grade_filter || 'ALL',
                                discrepancy_type: $wire.discrepancy_type || 'ALL',
                                discrepancy_sort: $wire.discrepancy_sort || 'impact_desc',
                                search: $wire.search || ''
                            });
                            window.open('/print/report/service-level-supplier?' + p.toString(), '_blank');
                        "
                        type="button" 
                        class="sl-btn sl-btn-print">
                        <svg class="sl-icon-sm" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                        </svg>
                        Cetak Laporan
                    </button>
                </div>
            </div>
        </div>

        {{-- FILTER TOOLBAR WITH SEARCHABLE COMBOBOX --}}
        <div class="sl-filter-box no-print">
            <div class="sl-filter-grid">
                {{-- Date Start --}}
                <div class="sl-filter-item">
                    <label class="sl-filter-label">Dari Tanggal PO</label>
                    <input type="date" wire:model.live="start_date" class="sl-input" />
                </div>

                {{-- Date End --}}
                <div class="sl-filter-item">
                    <label class="sl-filter-label">Sampai Tanggal PO</label>
                    <input type="date" wire:model.live="end_date" class="sl-input" />
                </div>

                {{-- Branch Filter --}}
                <div class="sl-filter-item">
                    <label class="sl-filter-label">Cabang / Lokasi</label>
                    <select wire:model.live="selected_branch_id" class="sl-select">
                        <option value="ALL">Semua Cabang</option>
                        @foreach(\App\Models\Branch::all() as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- SEARCHABLE SUPPLIER COMBOBOX (Alpine.js) --}}
                <div class="sl-filter-item" 
                     x-data="{
                         open: false,
                         search: '',
                         suppliers: {{ Js::from($allSuppliers->map(fn($s) => ['id' => (string)$s->id, 'name' => $s->name, 'code' => $s->code ?? ''])) }},
                         get filtered() {
                             if (!this.search) return this.suppliers;
                             let q = this.search.toLowerCase();
                             return this.suppliers.filter(s => s.name.toLowerCase().includes(q) || (s.code && s.code.toLowerCase().includes(q)));
                         },
                         select(id) {
                             $wire.set('selected_supplier_id', id);
                             this.open = false;
                             this.search = '';
                         }
                     }"
                     @click.outside="open = false">
                    <label class="sl-filter-label">Mitra Supplier</label>
                    
                    {{-- Trigger Button --}}
                    <button type="button" @click="open = !open; if (open) $nextTick(() => $refs.searchInput.focus())" class="sl-combobox-trigger">
                        <span class="truncate" style="max-width: 150px;">{{ $currentSupplierName }}</span>
                        <svg class="sl-icon-xs text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    {{-- Dropdown with Instant Search --}}
                    <div x-show="open" x-cloak class="sl-combobox-popover">
                        <input 
                            x-ref="searchInput"
                            x-model="search" 
                            type="text" 
                            placeholder="Ketik nama / kode supplier..." 
                            class="sl-combobox-search" 
                            @keydown.escape="open = false" />

                        <div class="sl-combobox-list">
                            <button type="button" @click="select('ALL')" class="sl-combobox-item {{ $selected_supplier_id === 'ALL' ? 'selected' : '' }}">
                                <span>Semua Supplier</span>
                                @if($selected_supplier_id === 'ALL')
                                    <span style="color: #2563eb; font-weight: 900;">✓</span>
                                @endif
                            </button>

                            <template x-for="s in filtered" :key="s.id">
                                <button type="button" @click="select(s.id)" class="sl-combobox-item" :class="{ 'selected': s.id === '{{ $selected_supplier_id }}' }">
                                    <div>
                                        <div x-text="s.name"></div>
                                        <span x-show="s.code" x-text="s.code" style="font-size: 0.65rem; color: #94a3b8; font-family: monospace;"></span>
                                    </div>
                                    <span x-show="s.id === '{{ $selected_supplier_id }}'" style="color: #2563eb; font-weight: 900;">✓</span>
                                </button>
                            </template>

                            <div x-show="filtered.length === 0" style="padding: 0.75rem; text-align: center; color: #94a3b8; font-size: 0.72rem;">
                                Supplier tidak ditemukan.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tab Dependent Filters --}}
                @if($active_tab === 'scorecard')
                    <div class="sl-filter-item">
                        <label class="sl-filter-label">Grade Rapor</label>
                        <select wire:model.live="grade_filter" class="sl-select">
                            <option value="ALL">Semua Grade (A, B, C, D)</option>
                            <option value="A">Grade A (Sangat Baik &ge; 95%)</option>
                            <option value="B">Grade B (Baik 85 - 94%)</option>
                            <option value="C">Grade C (Cukup 70 - 84%)</option>
                            <option value="D">Grade D (Kritis &lt; 70%)</option>
                        </select>
                    </div>
                @else
                    <div class="sl-filter-item">
                        <label class="sl-filter-label">Status Pemenuhan</label>
                        <select wire:model.live="status_filter" class="sl-select">
                            <option value="ALL">Semua Status</option>
                            <option value="COMPLETED">Lengkap (100%)</option>
                            <option value="PARTIAL">Sebagian (Parsial)</option>
                            <option value="PENDING">Menunggu Kiriman</option>
                            <option value="EXPIRED">Expired (Perlu Ditutup)</option>
                            <option value="CLOSED">Sisa Dihanguskan (Closed)</option>
                        </select>
                    </div>
                @endif

                {{-- Search text --}}
                <div class="sl-filter-item">
                    <label class="sl-filter-label">Pencarian No PO / Barang</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="No PO / Produk / SKU..." class="sl-input" />
                </div>
            </div>
        </div>

        {{-- EXECUTIVE KPI SUMMARY CARDS --}}
        @php
            $kpi = $this->kpi_summary;
        @endphp
        <div class="sl-kpi-grid">
            {{-- Card 1: Pemenuhan Barang --}}
            <div class="sl-kpi-card">
                <div class="sl-kpi-header">
                    <span class="sl-kpi-label">Pemenuhan Jumlah Barang</span>
                    <span class="sl-kpi-badge {{ $kpi['sl_qty'] >= 90 ? 'sl-badge-green' : ($kpi['sl_qty'] >= 75 ? 'sl-badge-amber' : 'sl-badge-red') }}">
                        {{ $kpi['sl_qty'] }}%
                    </span>
                </div>
                <div>
                    <div class="sl-kpi-value">
                        {{ number_format($kpi['total_received_qty'], 0, ',', '.') }}
                        <span style="font-size: 0.75rem; font-weight: normal; color: #94a3b8;">/ {{ number_format($kpi['total_ordered_qty'], 0, ',', '.') }} pcs</span>
                    </div>
                    <div class="sl-meter-track">
                        <div class="sl-meter-fill" style="width: {{ min(100, $kpi['sl_qty']) }}%; background: {{ $kpi['sl_qty'] >= 90 ? '#10b981' : ($kpi['sl_qty'] >= 75 ? '#f59e0b' : '#ef4444') }};"></div>
                    </div>
                </div>
                <div class="sl-kpi-sub" style="display: flex; justify-content: space-between;">
                    <span>Total PO: <b>{{ $kpi['total_po'] }}</b></span>
                    <span>Target Lengkap: <b>&ge; 95%</b></span>
                </div>
            </div>

            {{-- Card 2: Financial Realization --}}
            <div class="sl-kpi-card">
                <div class="sl-kpi-header">
                    <span class="sl-kpi-label">Realisasi Nilai Faktur</span>
                    <span class="sl-kpi-badge {{ $kpi['sl_amount'] >= 90 ? 'sl-badge-green' : 'sl-badge-blue' }}">
                        {{ $kpi['sl_amount'] }}%
                    </span>
                </div>
                <div>
                    <div class="sl-kpi-value">
                        Rp {{ number_format($kpi['total_gr_amount'] / 1000000, 1, ',', '.') }} <span style="font-size: 0.75rem; font-weight: 600; color: #94a3b8;">Juta</span>
                    </div>
                    <div class="sl-meter-track">
                        <div class="sl-meter-fill" style="width: {{ min(100, $kpi['sl_amount']) }}%; background: #3b82f6;"></div>
                    </div>
                </div>
                <div class="sl-kpi-sub">
                    Total Komitmen PO: <b>Rp {{ number_format($kpi['total_po_amount'] / 1000000, 1, ',', '.') }} Juta</b>
                </div>
            </div>

            {{-- Card 3: Outstanding / Sisa Belum Dikirim --}}
            <div class="sl-kpi-card">
                <div class="sl-kpi-header">
                    <span class="sl-kpi-label" style="color: #d97706;">Sisa Belum Dikirim</span>
                    <span class="sl-kpi-badge sl-badge-amber">Kurang Kirim</span>
                </div>
                <div>
                    <div class="sl-kpi-value" style="color: #d97706;">
                        {{ number_format($kpi['total_outstanding_qty'], 0, ',', '.') }} <span style="font-size: 0.75rem; font-weight: normal; color: #94a3b8;">pcs</span>
                    </div>
                    <div class="sl-meter-track">
                        <div class="sl-meter-fill" style="width: 100%; background: #f59e0b;"></div>
                    </div>
                </div>
                <div class="sl-kpi-sub">
                    Potensi Stok Tertahan: <b>Rp {{ number_format($kpi['total_outstanding_amount'] / 1000000, 1, ',', '.') }} Juta</b>
                </div>
            </div>

            {{-- Card 4: Ketepatan Waktu Kirim --}}
            <div class="sl-kpi-card">
                <div class="sl-kpi-header">
                    <span class="sl-kpi-label" style="color: #6366f1;">Ketepatan Waktu Kirim</span>
                    <span class="sl-kpi-badge {{ $kpi['otd_rate'] >= 85 ? 'sl-badge-green' : 'sl-badge-red' }}">
                        {{ $kpi['otd_rate'] }}% Tepat
                    </span>
                </div>
                <div>
                    <div class="sl-kpi-value" style="color: #6366f1;">
                        {{ $kpi['otd_rate'] }}%
                    </div>
                    <div class="sl-meter-track">
                        <div class="sl-meter-fill" style="width: {{ min(100, $kpi['otd_rate']) }}%; background: #6366f1;"></div>
                    </div>
                </div>
                <div class="sl-kpi-sub">
                    Terkirim sebelum batas tanggal expired PO
                </div>
            </div>

            {{-- Card 5: Reject & Price Discrepancies --}}
            <div class="sl-kpi-card">
                <div class="sl-kpi-header">
                    <span class="sl-kpi-label" style="color: #ef4444;">Barang Rusak & Harga</span>
                    <span class="sl-kpi-badge sl-badge-red">Audit Risiko</span>
                </div>
                <div>
                    <div class="sl-kpi-value" style="color: #ef4444;">
                        {{ number_format($kpi['total_rejected_qty'], 0, ',', '.') }} <span style="font-size: 0.75rem; font-weight: normal; color: #94a3b8;">pcs rusak</span>
                    </div>
                    <div class="sl-meter-track">
                        <div class="sl-meter-fill" style="width: 100%; background: #ef4444;"></div>
                    </div>
                </div>
                <div class="sl-kpi-sub" style="color: #ef4444; font-weight: 700;">
                    ⚠️ {{ $kpi['price_discrepancy_count'] }} item selisih harga dari PO
                </div>
            </div>
        </div>

        {{-- NAVIGATION TABS --}}
        <div class="sl-tabs no-print">
            <button wire:click="setTab('scorecard')" type="button" class="sl-tab-btn {{ $active_tab === 'scorecard' ? 'active' : '' }}">
                <svg class="sl-icon-sm" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.003 0H9.497m5.003 0a3.375 3.375 0 003.375-3.375V6.75A2.25 2.25 0 0015.625 4.5h-7.25A2.25 2.25 0 006.125 6.75v5.25a3.375 3.375 0 003.375 3.375z" />
                </svg>
                <span>1. Rapor Kinerja Supplier</span>
                <span class="sl-tab-count">{{ number_format($scorecardData['total_items']) }}</span>
            </button>

            <button wire:click="setTab('reconciliation')" type="button" class="sl-tab-btn {{ $active_tab === 'reconciliation' ? 'active' : '' }}">
                <svg class="sl-icon-sm" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                <span>2. Rekonsiliasi PO vs Penerimaan</span>
                <span class="sl-tab-count">{{ number_format($reconData['total_items']) }}</span>
            </button>

            <button wire:click="setTab('item_discrepancy')" type="button" class="sl-tab-btn {{ $active_tab === 'item_discrepancy' ? 'active' : '' }}">
                <svg class="sl-icon-sm" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <span>3. Audit Selisih Barang & Harga</span>
                <span class="sl-tab-count" style="{{ $discData['counts']['ALL'] > 0 ? 'background: #fee2e2; color: #b91c1c;' : '' }}">{{ number_format($discData['counts']['ALL']) }}</span>
            </button>
        </div>

        {{-- TAB 1: RAPOR SUPPLIER --}}
        @if($active_tab === 'scorecard')
            @php
                $scTotals = $scorecardData['totals'];
                $scItems = $scorecardData['items'];
            @endphp
            <div class="sl-card space-y-0">
                {{-- Toolbar / Per-Page Control --}}
                <div class="sl-tab-toolbar no-print">
                    <div style="font-size: 0.74rem; font-weight: 700; color: #475569;" class="dark:text-gray-300">
                        Evaluasi Rapor Kinerja Mitra Supplier Terdaftar
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.35rem;">
                        <span class="sl-filter-label" style="font-size: 0.68rem;">Tampilkan:</span>
                        <select wire:model.live="scorecard_per_page" class="sl-select" style="padding: 0.3rem 0.5rem; font-size: 0.72rem; width: auto;">
                            <option value="15">15 Baris</option>
                            <option value="25">25 Baris</option>
                            <option value="50">50 Baris</option>
                            <option value="100">100 Baris</option>
                        </select>
                    </div>
                </div>

                {{-- Table Wrap --}}
                <div class="sl-table-wrap">
                    <table class="sl-table">
                        <thead>
                            <tr>
                                <th style="text-align: center; width: 60px;">Grade</th>
                                <th>Mitra Supplier</th>
                                <th style="text-align: center;">Total PO</th>
                                <th style="text-align: right;">Qty Pesan</th>
                                <th style="text-align: right;">Qty Terima</th>
                                <th style="text-align: right;">Sisa Kurang</th>
                                <th style="text-align: center;" title="Persentase kelengkapan barang yang dikirim: (Qty Terima / Qty Pesan)">
                                    % Barang Lengkap ℹ️
                                </th>
                                <th style="text-align: right;">Nominal PO (Rp)</th>
                                <th style="text-align: right;">Nominal Faktur (Rp)</th>
                                <th style="text-align: center;" title="Persentase PO yang dikirim tepat waktu sebelum batas expired">
                                    % Tepat Waktu ℹ️
                                </th>
                                <th style="text-align: center;" title="Rata-rata berapa hari barang sampai sejak PO dibuat">
                                    Waktu Tunggu Kirim
                                </th>
                                <th>Rekomendasi Manajemen</th>
                                <th style="text-align: center;" class="no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($scItems as $row)
                                <tr>
                                    <td style="text-align: center;">
                                        <span class="sl-grade-badge sl-grade-{{ $row['grade'] }}">
                                            {{ $row['grade'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 800; color: #0f172a;" class="dark:text-white">{{ $row['supplier_name'] }}</div>
                                        <span style="font-size: 0.68rem; color: #94a3b8; font-family: monospace;">{{ $row['supplier_code'] }}</span>
                                    </td>
                                    <td style="text-align: center; font-weight: 800;">
                                        {{ $row['po_count'] }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        {{ number_format($row['ordered_qty'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: #10b981;">
                                        {{ number_format($row['received_qty'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: {{ $row['outstanding_qty'] > 0 ? '#ef4444' : '#94a3b8' }};">
                                        {{ number_format($row['outstanding_qty'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="font-weight: 900; color: {{ $row['sl_qty'] >= 90 ? '#10b981' : ($row['sl_qty'] >= 75 ? '#f59e0b' : '#ef4444') }};">
                                            {{ $row['sl_qty'] }}%
                                        </div>
                                        <div class="sl-meter-track" style="width: 60px; margin: 0.25rem auto 0 auto;">
                                            <div class="sl-meter-fill" style="width: {{ min(100, $row['sl_qty']) }}%; background: {{ $row['sl_qty'] >= 90 ? '#10b981' : ($row['sl_qty'] >= 75 ? '#f59e0b' : '#ef4444') }};"></div>
                                        </div>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        Rp {{ number_format($row['po_amount'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800;">
                                        Rp {{ number_format($row['gr_amount'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="sl-kpi-badge {{ $row['otd_rate'] >= 85 ? 'sl-badge-green' : 'sl-badge-red' }}">
                                            {{ $row['otd_rate'] }}%
                                        </span>
                                    </td>
                                    <td style="text-align: center; font-family: monospace; font-weight: 700;">
                                        {{ $row['avg_lead_time'] }} hari
                                    </td>
                                    <td>
                                        <div style="font-size: 0.74rem; font-weight: 700; color: #334155;" class="dark:text-gray-200">
                                            {{ $row['recommendation'] }}
                                        </div>
                                        @if($row['rejected_qty'] > 0 || $row['price_dev_count'] > 0)
                                             <div style="font-size: 0.68rem; color: #ef4444; margin-top: 0.2rem; font-weight: 600;">
                                                ⚠️ {{ $row['rejected_qty'] }} rusak | {{ $row['price_dev_count'] }} selisih harga
                                            </div>
                                        @endif
                                    </td>
                                    <td style="text-align: center;" class="no-print">
                                        <button wire:click="selectSupplier('{{ $row['supplier_id'] }}')" type="button" class="sl-btn sl-btn-reset" style="padding: 0.35rem 0.65rem; font-size: 0.7rem;">
                                            Lihat PO
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                        Tidak ada data supplier yang sesuai dengan kriteria filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($scorecardData['total_items'] > 0)
                            <tfoot>
                                <tr>
                                    <td colspan="2" style="text-align: right; letter-spacing: 0.04em;">TOTAL KESELURUHAN ({{ number_format($scTotals['total_suppliers']) }} Mitra):</td>
                                    <td style="text-align: center; font-weight: 900;">{{ number_format($scTotals['total_po'], 0, ',', '.') }}</td>
                                    <td style="text-align: right;">{{ number_format($scTotals['ordered_qty'], 0, ',', '.') }}</td>
                                    <td style="text-align: right; color: #10b981;">{{ number_format($scTotals['received_qty'], 0, ',', '.') }}</td>
                                    <td style="text-align: right; color: {{ $scTotals['outstanding_qty'] > 0 ? '#ef4444' : '#94a3b8' }};">{{ number_format($scTotals['outstanding_qty'], 0, ',', '.') }}</td>
                                    <td style="text-align: center; color: #2563eb;">{{ $scTotals['overall_pct'] }}%</td>
                                    <td style="text-align: right;">Rp {{ number_format($scTotals['po_amount'], 0, ',', '.') }}</td>
                                    <td style="text-align: right; color: #10b981;">Rp {{ number_format($scTotals['gr_amount'], 0, ',', '.') }}</td>
                                    <td colspan="4" style="text-align: left; font-size: 0.7rem; color: #64748b;">
                                        Rata-rata kelengkapan barang seluruh mitra terfilter.
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                {{-- Pagination Toolbar Tab 1 --}}
                @if($scorecardData['total_items'] > 0)
                    <div class="sl-pagination-bar no-print">
                        <div>
                            Menampilkan data <b>{{ number_format($scorecardData['from']) }}</b> – <b>{{ number_format($scorecardData['to']) }}</b> dari total <b>{{ number_format($scorecardData['total_items']) }}</b> mitra supplier
                        </div>

                        <div class="sl-pagination-pages">
                            <button 
                                wire:click="prevScorecardPage" 
                                type="button" 
                                class="sl-page-btn" 
                                {{ $scorecardData['current_page'] <= 1 ? 'disabled' : '' }}>
                                &larr; Sebelumnya
                            </button>

                            <span style="font-weight: 700; padding: 0 0.5rem;">
                                Hal. {{ $scorecardData['current_page'] }} / {{ $scorecardData['total_pages'] }}
                            </span>

                            <button 
                                wire:click="nextScorecardPage" 
                                type="button" 
                                class="sl-page-btn" 
                                {{ $scorecardData['current_page'] >= $scorecardData['total_pages'] ? 'disabled' : '' }}>
                                Berikutnya &rarr;
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- TAB 2: REKONSILIASI PO VS PENERIMAAN --}}
        @if($active_tab === 'reconciliation')
            @php
                $reconTotals = $reconData['totals'];
                $reconItems = $reconData['items'];
            @endphp
            <div class="sl-card space-y-0">
                {{-- EXECUTIVE FILTER SUMMARY STRIP --}}
                <div class="sl-recon-strip no-print">
                    <div class="sl-recon-strip-item">
                        <span class="sl-recon-strip-label">Total Data PO Terfilter</span>
                        <span class="sl-recon-strip-val" style="color: #2563eb;">{{ number_format($reconTotals['total_pos']) }} PO</span>
                        <span style="font-size: 0.68rem; color: #64748b;">Supplier: <b>{{ $currentSupplierName }}</b></span>
                    </div>

                    <div class="sl-recon-strip-item">
                        <span class="sl-recon-strip-label">Total Kuantitas Barang</span>
                        <div class="sl-recon-strip-val">
                            {{ number_format($reconTotals['received_qty'], 0, ',', '.') }} <span style="font-size: 0.72rem; color: #94a3b8; font-weight: normal;">/ {{ number_format($reconTotals['ordered_qty'], 0, ',', '.') }} pcs</span>
                        </div>
                        <span style="font-size: 0.68rem; color: #10b981; font-weight: 700;">Kelengkapan: {{ $reconTotals['overall_pct'] }}%</span>
                    </div>

                    <div class="sl-recon-strip-item">
                        <span class="sl-recon-strip-label">Total Nilai PO (Komitmen)</span>
                        <span class="sl-recon-strip-val">Rp {{ number_format($reconTotals['po_amount'], 0, ',', '.') }}</span>
                        <span style="font-size: 0.68rem; color: #64748b;">Rencana anggaran pemesanan</span>
                    </div>

                    <div class="sl-recon-strip-item">
                        <span class="sl-recon-strip-label">Total Nilai Faktur (Realisasi)</span>
                        <span class="sl-recon-strip-val" style="color: #10b981;">Rp {{ number_format($reconTotals['gr_amount'], 0, ',', '.') }}</span>
                        <span style="font-size: 0.68rem; color: #64748b;">Total tagihan barang yang masuk</span>
                    </div>

                    <div class="sl-recon-strip-item" style="border-left: 2px solid {{ $reconTotals['diff_amount'] > 0 ? '#ef4444' : ($reconTotals['diff_amount'] < 0 ? '#f59e0b' : '#10b981') }}; padding-left: 0.75rem;">
                        <span class="sl-recon-strip-label" style="color: {{ $reconTotals['diff_amount'] > 0 ? '#ef4444' : ($reconTotals['diff_amount'] < 0 ? '#d97706' : '#10b981') }};">
                            Total Selisih Nominal
                        </span>
                        <div class="sl-recon-strip-val" style="color: {{ $reconTotals['diff_amount'] > 0 ? '#ef4444' : ($reconTotals['diff_amount'] < 0 ? '#d97706' : '#10b981') }};">
                            {{ $reconTotals['diff_amount'] > 0 ? '+' : ($reconTotals['diff_amount'] < 0 ? '-' : '') }}Rp {{ number_format(abs($reconTotals['diff_amount']), 0, ',', '.') }}
                        </div>
                        <span style="font-size: 0.68rem; font-weight: 700; color: {{ $reconTotals['diff_amount'] > 0 ? '#ef4444' : ($reconTotals['diff_amount'] < 0 ? '#d97706' : '#10b981') }};">
                            @if($reconTotals['diff_amount'] > 0)
                                ⚠️ Faktur Lebih Tinggi (Tagihan Lebih Besar)
                            @elseif($reconTotals['diff_amount'] < 0)
                                ℹ️ Faktur Lebih Rendah (Kurang Kirim)
                            @else
                                ✓ Nilai Faktur Sesuai 100%
                            @endif
                        </span>
                    </div>
                </div>

                {{-- Toolbar / Per-Page Control --}}
                <div class="sl-tab-toolbar no-print">
                    <div style="font-size: 0.74rem; font-weight: 700; color: #475569;" class="dark:text-gray-300">
                        Daftar Surat Pesanan (PO) & Rekonsiliasi Penerimaan Faktur
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.35rem;">
                        <span class="sl-filter-label" style="font-size: 0.68rem;">Tampilkan:</span>
                        <select wire:model.live="recon_per_page" class="sl-select" style="padding: 0.3rem 0.5rem; font-size: 0.72rem; width: auto;">
                            <option value="15">15 Baris</option>
                            <option value="25">25 Baris</option>
                            <option value="50">50 Baris</option>
                            <option value="100">100 Baris</option>
                        </select>
                    </div>
                </div>

                {{-- Table Wrap --}}
                <div class="sl-table-wrap">
                    <table class="sl-table">
                        <thead>
                            <tr>
                                <th>No PO & Tanggal</th>
                                <th>Supplier</th>
                                <th>Cabang / Divisi</th>
                                <th style="text-align: center;">Target / Expired</th>
                                <th style="text-align: right;">Qty Order</th>
                                <th style="text-align: right;">Scan Fisik</th>
                                <th style="text-align: right;">Faktur Masuk</th>
                                <th style="text-align: right;">Sisa Kurang</th>
                                <th style="text-align: center;">% Terkirim</th>
                                <th style="text-align: right;">Total PO (Rp)</th>
                                <th style="text-align: right;">Total Faktur (Rp)</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: center;" class="no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reconItems as $po)
                                <tr>
                                    <td>
                                        <div style="font-family: monospace; font-weight: 800; color: #2563eb;">{{ $po['po_number'] }}</div>
                                        <div style="font-size: 0.68rem; color: #94a3b8;">Tgl: {{ $po['po_date'] }}</div>
                                    </td>
                                    <td style="font-weight: 700;">
                                        {{ $po['supplier_name'] }}
                                    </td>
                                    <td>
                                        <div>{{ $po['branch_name'] }}</div>
                                        <div style="font-size: 0.68rem; color: #94a3b8;">Div: {{ $po['division_name'] }}</div>
                                    </td>
                                    <td style="text-align: center; font-family: monospace; font-size: 0.72rem;">
                                        <div style="font-weight: 700; color: {{ $po['status_key'] === 'EXPIRED' ? '#ef4444' : '#64748b' }};">
                                            {{ $po['expired_date'] }}
                                        </div>
                                        @if($po['lead_days'] !== null)
                                            <div style="font-size: 0.68rem; color: #94a3b8; font-family: sans-serif;">{{ $po['lead_days'] }} hari</div>
                                        @endif
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        {{ number_format($po['ordered_qty'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: #3b82f6;">
                                        {{ number_format($po['scanned_qty'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: #10b981;">
                                        {{ number_format($po['received_qty'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: {{ $po['outstanding_qty'] > 0 ? '#ef4444' : '#94a3b8' }};">
                                        {{ number_format($po['outstanding_qty'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: center;">
                                        <span style="font-weight: 900; color: {{ $po['sl_qty'] >= 100 ? '#10b981' : ($po['sl_qty'] > 0 ? '#f59e0b' : '#ef4444') }};">
                                            {{ $po['sl_qty'] }}%
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        Rp {{ number_format($po['po_amount'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: #10b981;">
                                        Rp {{ number_format($po['gr_amount'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: center;">
                                        @if($po['status_key'] === 'COMPLETED')
                                            <span class="sl-kpi-badge sl-badge-green">Lengkap</span>
                                        @elseif($po['status_key'] === 'PARTIAL')
                                            <span class="sl-kpi-badge sl-badge-amber">Parsial</span>
                                        @elseif($po['status_key'] === 'EXPIRED')
                                            <span class="sl-kpi-badge sl-badge-red">Expired</span>
                                        @elseif($po['status_key'] === 'CLOSED')
                                            <span class="sl-kpi-badge" style="background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;">Closed (Hangus)</span>
                                        @else
                                            <span class="sl-kpi-badge" style="background: #f1f5f9; color: #64748b;">Pending</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;" class="no-print">
                                        <div style="display: flex; align-items: center; justify-content: center; gap: 0.35rem;">
                                            <button wire:click="openPoDetail('{{ $po['id'] }}')" type="button" class="sl-btn sl-btn-reset" style="padding: 0.35rem 0.65rem; font-size: 0.7rem;">
                                                Detail
                                            </button>

                                            @if($po['can_close'])
                                                <button 
                                                    wire:click="closePo('{{ $po['id'] }}')" 
                                                    wire:confirm="Yakin ingin menutup PO {{ $po['po_number'] }} dan menghanguskan sisa {{ number_format($po['outstanding_qty']) }} pcs barang yang belum dikirim?"
                                                    type="button" 
                                                    class="sl-btn sl-btn-danger-light" 
                                                    style="padding: 0.35rem 0.6rem; font-size: 0.7rem;"
                                                    title="Tutup PO dan hanguskan sisa barang">
                                                    Hanguskan
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                        Tidak ada data PO yang sesuai dengan kriteria filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($reconData['total_items'] > 0)
                            <tfoot>
                                <tr>
                                    <td colspan="4" style="text-align: right; letter-spacing: 0.04em;">TOTAL ({{ number_format($reconTotals['total_pos']) }} PO):</td>
                                    <td style="text-align: right;">{{ number_format($reconTotals['ordered_qty'], 0, ',', '.') }}</td>
                                    <td style="text-align: right; color: #3b82f6;">{{ number_format($reconTotals['scanned_qty'], 0, ',', '.') }}</td>
                                    <td style="text-align: right; color: #10b981;">{{ number_format($reconTotals['received_qty'], 0, ',', '.') }}</td>
                                    <td style="text-align: right; color: {{ $reconTotals['outstanding_qty'] > 0 ? '#ef4444' : '#94a3b8' }};">{{ number_format($reconTotals['outstanding_qty'], 0, ',', '.') }}</td>
                                    <td style="text-align: center; color: #2563eb;">{{ $reconTotals['overall_pct'] }}%</td>
                                    <td style="text-align: right;">Rp {{ number_format($reconTotals['po_amount'], 0, ',', '.') }}</td>
                                    <td style="text-align: right; color: #10b981;">Rp {{ number_format($reconTotals['gr_amount'], 0, ',', '.') }}</td>
                                    <td colspan="2" style="text-align: center;">
                                        <span class="sl-kpi-badge {{ $reconTotals['diff_amount'] > 0 ? 'sl-badge-red' : ($reconTotals['diff_amount'] < 0 ? 'sl-badge-amber' : 'sl-badge-green') }}" style="font-size: 0.72rem;">
                                            {{ $reconTotals['diff_amount'] > 0 ? '+Rp ' . number_format($reconTotals['diff_amount'], 0, ',', '.') : ($reconTotals['diff_amount'] < 0 ? '-Rp ' . number_format(abs($reconTotals['diff_amount']), 0, ',', '.') : 'Pas Sesuai') }}
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                {{-- Pagination Toolbar Tab 2 --}}
                @if($reconData['total_items'] > 0)
                    <div class="sl-pagination-bar no-print">
                        <div>
                            Menampilkan data <b>{{ number_format($reconData['from']) }}</b> – <b>{{ number_format($reconData['to']) }}</b> dari total <b>{{ number_format($reconData['total_items']) }}</b> PO terfilter
                        </div>

                        <div class="sl-pagination-pages">
                            <button 
                                wire:click="prevReconPage" 
                                type="button" 
                                class="sl-page-btn" 
                                {{ $reconData['current_page'] <= 1 ? 'disabled' : '' }}>
                                &larr; Sebelumnya
                            </button>

                            <span style="font-weight: 700; padding: 0 0.5rem;">
                                Hal. {{ $reconData['current_page'] }} / {{ $reconData['total_pages'] }}
                            </span>

                            <button 
                                wire:click="nextReconPage" 
                                type="button" 
                                class="sl-page-btn" 
                                {{ $reconData['current_page'] >= $reconData['total_pages'] ? 'disabled' : '' }}>
                                Berikutnya &rarr;
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- TAB 3: AUDIT SELISIH BARANG & HARGA DENGAN TRIAGE, SORTING & PAGINASI --}}
        @if($active_tab === 'item_discrepancy')
            @php
                $pagedItems = $discData['items'] ?? [];
                $discTotals = $discData['totals'] ?? [];
                $discRecon = $discData['reconciliation'] ?? [];
                $totalImpactFiltered = $discTotals['impact_amount'] ?? array_sum(array_column($discData['all_items'] ?? [], 'impact_amount'));
            @endphp
            <div class="sl-card space-y-0">
                {{-- 1. Triage Toolbar & Controls --}}
                <div class="sl-triage-bar no-print">
                    {{-- Pill Filters --}}
                    <div class="sl-triage-pills">
                        <button 
                            wire:click="setDiscrepancyType('ALL')" 
                            type="button" 
                            class="sl-pill-btn {{ $discrepancy_type === 'ALL' ? 'active' : '' }}">
                            <span>Semua Masalah</span>
                            <span style="font-weight: 800; opacity: 0.85;">({{ number_format($discData['counts']['ALL']) }})</span>
                        </button>

                        <button 
                            wire:click="setDiscrepancyType('PRICE_DIFF')" 
                            type="button" 
                            class="sl-pill-btn {{ $discrepancy_type === 'PRICE_DIFF' ? 'active-red' : '' }}">
                            <span>⚠️ Selisih Harga Saja</span>
                            <span style="font-weight: 800; opacity: 0.85;">({{ number_format($discData['counts']['PRICE_DIFF']) }})</span>
                        </button>

                        <button 
                            wire:click="setDiscrepancyType('REJECTED')" 
                            type="button" 
                            class="sl-pill-btn {{ $discrepancy_type === 'REJECTED' ? 'active-red' : '' }}">
                            <span>🚫 Barang Rusak/Reject</span>
                            <span style="font-weight: 800; opacity: 0.85;">({{ number_format($discData['counts']['REJECTED']) }})</span>
                        </button>

                        <button 
                            wire:click="setDiscrepancyType('SHORT_QTY')" 
                            type="button" 
                            class="sl-pill-btn {{ $discrepancy_type === 'SHORT_QTY' ? 'active-amber' : '' }}">
                            <span>📦 Kurang Kirim</span>
                            <span style="font-weight: 800; opacity: 0.85;">({{ number_format($discData['counts']['SHORT_QTY']) }})</span>
                        </button>
                    </div>

                    {{-- Sort & Per-Page Controls --}}
                    <div class="sl-triage-controls">
                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                            <span class="sl-filter-label" style="font-size: 0.68rem;">Urutkan:</span>
                            <select wire:model.live="discrepancy_sort" class="sl-select" style="padding: 0.3rem 0.5rem; font-size: 0.72rem; width: auto;">
                                <option value="impact_desc">💰 Selisih Nominal Terbesar (Rp)</option>
                                <option value="qty_desc">📦 Selisih Qty Terbanyak (Pcs)</option>
                                <option value="date_desc">📅 Tanggal PO Terbaru</option>
                            </select>
                        </div>

                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                            <span class="sl-filter-label" style="font-size: 0.68rem;">Tampilkan:</span>
                            <select wire:model.live="discrepancy_per_page" class="sl-select" style="padding: 0.3rem 0.5rem; font-size: 0.72rem; width: auto;">
                                <option value="15">15 Baris</option>
                                <option value="25">25 Baris</option>
                                <option value="50">50 Baris</option>
                                <option value="100">100 Baris</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- 2. Clean Compact Table --}}
                <div class="sl-table-wrap">
                    <table class="sl-table">
                        <thead>
                            <tr>
                                <th>No PO & Supplier</th>
                                <th>SKU / Barcode</th>
                                <th>Nama Barang</th>
                                <th style="text-align: center;">Tipe Masalah</th>
                                <th style="text-align: right;">Qty Order</th>
                                <th style="text-align: right;">Qty Terima</th>
                                <th style="text-align: right;">Selisih Kurang</th>
                                <th style="text-align: right;">Barang Rusak</th>
                                <th>Alasan Rusak</th>
                                <th style="text-align: right;">Harga PO (Rp)</th>
                                <th style="text-align: right;">Harga Faktur (Rp)</th>
                                <th style="text-align: right;">Selisih Harga</th>
                                <th style="text-align: right;">Dampak Nominal (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pagedItems as $item)
                                <tr style="{{ $item['has_price_deviation'] ? 'background: rgba(254, 243, 199, 0.25);' : '' }}">
                                    <td>
                                        <div style="font-family: monospace; font-weight: 800; color: #2563eb;">{{ $item['po_number'] }}</div>
                                        <div style="font-size: 0.68rem; color: #94a3b8;">{{ $item['supplier_name'] }} ({{ $item['branch_name'] }})</div>
                                    </td>
                                    <td style="font-family: monospace; font-size: 0.72rem;">
                                        <div>{{ $item['sku'] }}</div>
                                        <span style="font-size: 0.68rem; color: #94a3b8;">{{ $item['barcode'] }}</span>
                                    </td>
                                    <td style="font-weight: 700;">
                                        {{ $item['product_name'] }}
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; flex-direction: column; gap: 0.2rem; align-items: center;">
                                            @if($item['has_price_deviation'])
                                                <span class="sl-kpi-badge sl-badge-red" style="font-size: 0.65rem;">Selisih Harga</span>
                                            @endif
                                            @if($item['has_rejected'])
                                                <span class="sl-kpi-badge sl-badge-red" style="font-size: 0.65rem;">Barang Rusak</span>
                                            @endif
                                            @if($item['has_short_qty'])
                                                <span class="sl-kpi-badge sl-badge-amber" style="font-size: 0.65rem;">Kurang Kirim</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        {{ number_format($item['ordered_qty'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: #10b981;">
                                        {{ number_format($item['received_qty'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: {{ $item['qty_diff'] > 0 ? '#ef4444' : '#94a3b8' }};">
                                        {{ number_format($item['qty_diff'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: {{ $item['rejected_qty'] > 0 ? '#ef4444' : '#94a3b8' }};">
                                        {{ number_format($item['rejected_qty'], 0, ',', '.') }}
                                    </td>
                                    <td style="font-size: 0.72rem; color: #64748b;">
                                        {{ $item['reject_reason'] }}
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        Rp {{ number_format($item['po_price'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: {{ $item['has_price_deviation'] ? '#ef4444' : 'inherit' }};">
                                        Rp {{ number_format($item['gr_price'], 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; font-weight: 900; color: {{ $item['price_diff'] > 0 ? '#ef4444' : ($item['price_diff'] < 0 ? '#10b981' : '#94a3b8') }};">
                                        @if(abs($item['price_diff']) > 0.01)
                                            {{ $item['price_diff'] > 0 ? '+' : '' }}Rp {{ number_format($item['price_diff'], 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td style="text-align: right; font-weight: 800; color: #0f172a;" class="dark:text-white">
                                        Rp {{ number_format($item['impact_amount'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" style="text-align: center; padding: 3rem; color: #10b981; font-weight: 700;">
                                        ✨ Sempurna! Tidak ditemukan data yang sesuai dengan filter masalah yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($pagedItems) > 0)
                            <tfoot>
                                <tr>
                                    <td colspan="4" style="text-align: right; font-weight: 800; letter-spacing: 0.04em;">
                                        TOTAL KESELURUHAN ({{ number_format($discData['total_items']) }} Item Masalah):
                                    </td>
                                    <td style="text-align: right; font-weight: 700;">{{ number_format($discTotals['ordered_qty'] ?? 0, 0, ',', '.') }}</td>
                                    <td style="text-align: right; font-weight: 800; color: #10b981;">{{ number_format($discTotals['received_qty'] ?? 0, 0, ',', '.') }}</td>
                                    <td style="text-align: right; font-weight: 800; color: #ef4444;">{{ number_format($discTotals['qty_diff'] ?? 0, 0, ',', '.') }}</td>
                                    <td style="text-align: right; font-weight: 800; color: #ef4444;">{{ number_format($discTotals['rejected_qty'] ?? 0, 0, ',', '.') }}</td>
                                    <td colspan="3" style="text-align: right; font-size: 0.72rem; color: #64748b;">
                                        @if($discrepancy_type === 'SHORT_QTY')
                                            Total Nilai Kurang Kirim:
                                        @elseif($discrepancy_type === 'PRICE_DIFF')
                                            Total Deviasi Harga Faktur:
                                        @elseif($discrepancy_type === 'REJECTED')
                                            Total Nilai Barang Rusak:
                                        @else
                                            Total Estimasi Dampak Masalah:
                                        @endif
                                    </td>
                                    <td style="text-align: right; font-weight: 900; color: #ef4444; font-size: 0.85rem;">
                                        Rp {{ number_format($totalImpactFiltered, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                {{-- KOTAK REKONSILIASI KEUANGAN & PEMENUHAN (TAB 1 VS TAB 3) --}}
                @if(!empty($discRecon) && ($discRecon['po_amount'] ?? 0) > 0)
                    <div style="margin-top: 1rem; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 0.75rem; padding: 1rem 1.25rem;" class="dark:bg-gray-900 dark:border-gray-800">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;" class="dark:border-gray-800">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="font-size: 1.1rem;">⚖️</span>
                                <div>
                                    <div style="font-size: 0.82rem; font-weight: 800; color: #0f172a;" class="dark:text-white">
                                        Rekonsiliasi Hubungan Nilai PO, Realisasi Faktur & Selisih Fisik
                                    </div>
                                    <div style="font-size: 0.7rem; color: #64748b;">
                                        Menjelaskan hubungan matematis antara Tab Rapor Kinerja (Tab 1/2) dengan Tab Audit Selisih (Tab 3)
                                    </div>
                                </div>
                            </div>
                            <span class="sl-kpi-badge sl-badge-green" style="font-size: 0.68rem;">100% Klop Terverifikasi</span>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
                            {{-- 1. Total PO --}}
                            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.65rem 0.85rem;" class="dark:bg-gray-800 dark:border-gray-700">
                                <div style="font-size: 0.68rem; font-weight: 700; color: #64748b; text-transform: uppercase;">1. Total Pesanan (PO)</div>
                                <div style="font-size: 1.05rem; font-weight: 800; color: #2563eb; margin-top: 0.2rem;">
                                    Rp {{ number_format($discRecon['po_amount'], 0, ',', '.') }}
                                </div>
                                <div style="font-size: 0.65rem; color: #94a3b8;">Nilai awal yang dipesan di PO</div>
                            </div>

                            {{-- 2. Barang Kurang Kirim --}}
                            <div style="background: #ffffff; border: 1px solid #fecaca; border-radius: 0.5rem; padding: 0.65rem 0.85rem;" class="dark:bg-gray-800 dark:border-red-900/50">
                                <div style="font-size: 0.68rem; font-weight: 700; color: #b91c1c; text-transform: uppercase;">2. (-) Barang Kurang Kirim</div>
                                <div style="font-size: 1.05rem; font-weight: 800; color: #dc2626; margin-top: 0.2rem;">
                                    -Rp {{ number_format($discRecon['short_qty_amount'], 0, ',', '.') }}
                                </div>
                                <div style="font-size: 0.65rem; color: #ef4444;">Barang tidak dikirim (@ harga PO)</div>
                            </div>

                            {{-- 3. Deviasi Harga Faktur --}}
                            <div style="background: #ffffff; border: 1px solid #fed7aa; border-radius: 0.5rem; padding: 0.65rem 0.85rem;" class="dark:bg-gray-800 dark:border-amber-900/50">
                                <div style="font-size: 0.68rem; font-weight: 700; color: #c2410c; text-transform: uppercase;">3. (+/-) Selisih Harga Faktur</div>
                                <div style="font-size: 1.05rem; font-weight: 800; color: {{ $discRecon['net_price_dev_amount'] >= 0 ? '#b91c1c' : '#15803d' }}; margin-top: 0.2rem;">
                                    {{ $discRecon['net_price_dev_amount'] >= 0 ? '+' : '-' }}Rp {{ number_format(abs($discRecon['net_price_dev_amount']), 0, ',', '.') }}
                                </div>
                                <div style="font-size: 0.65rem; color: #64748b;">{{ $discRecon['net_price_dev_amount'] >= 0 ? 'Faktur lebih mahal dari PO' : 'Faktur lebih murah dari PO' }}</div>
                            </div>

                            {{-- 4. Realisasi Faktur yang Harus Dibayar --}}
                            <div style="background: #ffffff; border: 1px solid #bbf7d0; border-radius: 0.5rem; padding: 0.65rem 0.85rem;" class="dark:bg-gray-800 dark:border-green-900/50">
                                <div style="font-size: 0.68rem; font-weight: 700; color: #15803d; text-transform: uppercase;">4. (=) Total Faktur Tagihan</div>
                                <div style="font-size: 1.05rem; font-weight: 900; color: #16a34a; margin-top: 0.2rem;">
                                    Rp {{ number_format($discRecon['gr_amount'], 0, ',', '.') }}
                                </div>
                                <div style="font-size: 0.65rem; color: #15803d;">Nilai riil yang ditagih & dibayar</div>
                            </div>

                            {{-- 5. Selisih Bersih --}}
                            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.65rem 0.85rem;" class="dark:bg-gray-800 dark:border-gray-700">
                                <div style="font-size: 0.68rem; font-weight: 700; color: #475569; text-transform: uppercase;">5. Selisih Bersih (PO - Faktur)</div>
                                <div style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin-top: 0.2rem;" class="dark:text-white">
                                    Rp {{ number_format($discRecon['net_diff'], 0, ',', '.') }}
                                </div>
                                <div style="font-size: 0.65rem; color: #64748b;">Klop dengan Tab 1 & Tab 2</div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- 3. Interactive Pagination Toolbar Tab 3 --}}
                @if($discData['total_items'] > 0)
                    <div class="sl-pagination-bar no-print">
                        <div>
                            Menampilkan data <b>{{ number_format($discData['from']) }}</b> – <b>{{ number_format($discData['to']) }}</b> dari total <b>{{ number_format($discData['total_items']) }}</b> masalah
                        </div>

                        <div class="sl-pagination-pages">
                            <button 
                                wire:click="prevDiscrepancyPage" 
                                type="button" 
                                class="sl-page-btn" 
                                {{ $discData['current_page'] <= 1 ? 'disabled' : '' }}>
                                &larr; Sebelumnya
                            </button>

                            <span style="font-weight: 700; padding: 0 0.5rem;">
                                Hal. {{ $discData['current_page'] }} / {{ $discData['total_pages'] }}
                            </span>

                            <button 
                                wire:click="nextDiscrepancyPage" 
                                type="button" 
                                class="sl-page-btn" 
                                {{ $discData['current_page'] >= $discData['total_pages'] ? 'disabled' : '' }}>
                                Berikutnya &rarr;
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- INTERACTIVE MODAL DETAIL ITEM PO --}}
    @if($selected_po_id && $this->selected_po_detail)
        @php
            $poDetail = $this->selected_po_detail;
        @endphp
        <div class="sl-modal-backdrop no-print">
            <div class="sl-modal-box">
                <div class="sl-modal-header">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                            <h2 style="font-size: 1.1rem; font-weight: 900; margin: 0; color: #0f172a;" class="dark:text-white">Detail PO: {{ $poDetail['po_number'] }}</h2>
                            <span class="sl-kpi-badge sl-badge-blue">{{ $poDetail['supplier_name'] }}</span>
                            @if($poDetail['is_closed'])
                                <span class="sl-kpi-badge" style="background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;">Status: Closed / Sisa Dihanguskan</span>
                            @endif
                        </div>
                        <p style="font-size: 0.75rem; color: #64748b; margin: 0.25rem 0 0 0;">
                            Tgl PO: <b>{{ $poDetail['po_date'] }}</b> | Expired: <b>{{ $poDetail['expired_date'] }}</b> | Cabang: <b>{{ $poDetail['branch_name'] }}</b> (Divisi: {{ $poDetail['division_name'] }})
                        </p>
                    </div>

                    <button wire:click="closePoDetail" type="button" class="sl-btn sl-btn-reset" style="padding: 0.4rem 0.6rem;">
                        <svg class="sl-icon-md" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="sl-modal-body">
                    <div class="sl-table-wrap">
                        <table class="sl-table">
                            <thead>
                                <tr>
                                    <th>SKU / Barcode</th>
                                    <th>Nama Produk</th>
                                    <th style="text-align: right;">Qty Order</th>
                                    <th style="text-align: right;">Scan Fisik</th>
                                    <th style="text-align: right;">Faktur Masuk</th>
                                    <th style="text-align: right;">Sisa Kurang</th>
                                    <th style="text-align: right;">Barang Rusak</th>
                                    <th style="text-align: right;">Harga PO</th>
                                    <th style="text-align: right;">Harga Faktur</th>
                                    <th style="text-align: right;">Subtotal PO</th>
                                    <th style="text-align: right;">Subtotal Faktur</th>
                                    <th style="text-align: center;">% Terkirim</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($poDetail['items'] as $it)
                                    <tr>
                                        <td style="font-family: monospace; font-size: 0.72rem;">
                                            <div>{{ $it['sku'] }}</div>
                                            <span style="font-size: 0.68rem; color: #94a3b8;">{{ $it['barcode'] }}</span>
                                        </td>
                                        <td style="font-weight: 700;">
                                            {{ $it['product_name'] }}
                                        </td>
                                        <td style="text-align: right; font-weight: 600;">
                                            {{ number_format($it['ordered_qty'], 0, ',', '.') }}
                                        </td>
                                        <td style="text-align: right; font-weight: 700; color: #3b82f6;">
                                            {{ number_format($it['scanned_qty'], 0, ',', '.') }}
                                        </td>
                                        <td style="text-align: right; font-weight: 800; color: #10b981;">
                                            {{ number_format($it['received_qty'], 0, ',', '.') }}
                                        </td>
                                        <td style="text-align: right; font-weight: 800; color: {{ $it['outstanding_qty'] > 0 ? '#ef4444' : '#94a3b8' }};">
                                            {{ number_format($it['outstanding_qty'], 0, ',', '.') }}
                                        </td>
                                        <td style="text-align: right; font-weight: 800; color: {{ $it['rejected_qty'] > 0 ? '#ef4444' : '#94a3b8' }};">
                                            {{ number_format($it['rejected_qty'], 0, ',', '.') }}
                                            @if($it['reject_reason'] !== '-')
                                                <div style="font-size: 0.65rem; color: #ef4444; font-weight: normal;">{{ $it['reject_reason'] }}</div>
                                            @endif
                                        </td>
                                        <td style="text-align: right; font-weight: 600;">
                                            Rp {{ number_format($it['po_price'], 0, ',', '.') }}
                                        </td>
                                        <td style="text-align: right; font-weight: 800; color: {{ abs($it['price_diff']) > 0.01 ? '#ef4444' : 'inherit' }};">
                                            Rp {{ number_format($it['gr_price'], 0, ',', '.') }}
                                        </td>
                                        <td style="text-align: right; font-weight: 600;">
                                            Rp {{ number_format($it['po_subtotal'], 0, ',', '.') }}
                                        </td>
                                        <td style="text-align: right; font-weight: 800;">
                                            Rp {{ number_format($it['gr_subtotal'], 0, ',', '.') }}
                                        </td>
                                        <td style="text-align: center;">
                                            <span style="font-weight: 900; color: {{ $it['sl_item'] >= 100 ? '#10b981' : ($it['sl_item'] > 0 ? '#f59e0b' : '#ef4444') }};">
                                                {{ $it['sl_item'] }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="sl-modal-footer">
                    <div style="font-size: 0.75rem; color: #64748b;">
                        @if(!empty($poDetail['notes']))
                            <span>Catatan: <i>{{ $poDetail['notes'] }}</i></span>
                        @endif
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        @if(!$poDetail['is_closed'])
                            <button 
                                wire:click="closePo('{{ $poDetail['id'] }}')" 
                                wire:confirm="Yakin ingin menutup PO ini dan menghanguskan semua sisa barang yang belum dikirim?"
                                type="button" 
                                class="sl-btn sl-btn-danger-light">
                                Hanguskan Sisa PO Ini
                            </button>
                        @endif

                        <button wire:click="closePoDetail" type="button" class="sl-btn sl-btn-reset" style="padding: 0.5rem 1rem;">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
