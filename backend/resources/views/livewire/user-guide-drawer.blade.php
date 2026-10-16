<div>
    <style>
        /* Backdrop & Panel */
        .guide-drawer-backdrop {
            position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
            z-index: 99990;
            transition: opacity 0.25s ease-out;
        }
        .guide-drawer-panel {
            position: fixed; top: 0; right: 0; bottom: 0;
            width: 100%; max-width: 680px;
            background: #ffffff;
            box-shadow: -15px 0 35px -5px rgba(0, 0, 0, 0.2);
            z-index: 99999;
            display: flex; flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .dark .guide-drawer-panel {
            background: #0f172a;
            color: #f1f5f9;
            border-left: 1px solid #1e293b;
        }
        .guide-drawer-panel.is-open {
            transform: translateX(0);
        }

        /* Typography & Markdown Prose Styling */
        .guide-prose {
            font-size: 14.5px;
            line-height: 1.75;
            color: #334155;
        }
        .dark .guide-prose {
            color: #cbd5e1;
        }
        .guide-prose h1 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-top: 0;
            margin-bottom: 1rem;
            color: #0f172a;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.6rem;
            letter-spacing: -0.02em;
        }
        .dark .guide-prose h1 {
            color: #f8fafc;
            border-color: #334155;
        }
        .guide-prose h2 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-top: 1.75rem;
            margin-bottom: 0.65rem;
            color: #065f46;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: -0.01em;
            border-left: 4px solid #10b981;
            padding-left: 10px;
        }
        .dark .guide-prose h2 {
            color: #34d399;
            border-left-color: #059669;
        }
        .guide-prose h3 {
            font-size: 1.05rem;
            font-weight: 600;
            margin-top: 1.25rem;
            margin-bottom: 0.45rem;
            color: #1e293b;
        }
        .dark .guide-prose h3 {
            color: #e2e8f0;
        }
        .guide-prose p {
            margin-top: 0.5rem;
            margin-bottom: 0.85rem;
        }
        .guide-prose ul, .guide-prose ol {
            margin-top: 0.5rem;
            margin-bottom: 1rem;
            padding-left: 1.35rem;
        }
        .guide-prose li {
            margin-bottom: 0.45rem;
        }

        /* Callouts / Alerts */
        .guide-prose blockquote {
            border-left: 4px solid #10b981;
            background: linear-gradient(to right, #ecfdf5, #f0fdf4);
            padding: 12px 18px;
            margin: 16px 0;
            border-radius: 0 10px 10px 0;
            font-size: 13.5px;
            color: #065f46;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .dark .guide-prose blockquote {
            background: linear-gradient(to right, rgba(16, 185, 129, 0.12), rgba(16, 185, 129, 0.05));
            color: #a7f3d0;
            border-left-color: #10b981;
        }

        /* Tables */
        .guide-prose table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 18px 0;
            font-size: 13px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .dark .guide-prose table {
            border-color: #334155;
        }
        .guide-prose table th {
            background: #f8fafc;
            color: #0f172a;
            font-weight: 700;
            text-align: left;
            padding: 10px 14px;
            border-bottom: 1px solid #e2e8f0;
        }
        .dark .guide-prose table th {
            background: #1e293b;
            color: #f8fafc;
            border-color: #334155;
        }
        .guide-prose table td {
            padding: 9px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }
        .dark .guide-prose table td {
            border-color: #1e293b;
        }
        .guide-prose table tr:last-child td {
            border-bottom: none;
        }
        .guide-prose table tr:nth-child(even) td {
            background: #fafbfc;
        }
        .dark .guide-prose table tr:nth-child(even) td {
            background: rgba(30, 41, 59, 0.4);
        }

        /* Code & Keyboard */
        .guide-prose code {
            background: #f1f5f9;
            color: #0f172a;
            padding: 2px 7px;
            border-radius: 5px;
            font-size: 12.5px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            border: 1px solid #e2e8f0;
        }
        .dark .guide-prose code {
            background: #1e293b;
            color: #e2e8f0;
            border-color: #334155;
        }
        .guide-prose kbd {
            display: inline-block;
            background: linear-gradient(to bottom, #ffffff, #f1f5f9);
            color: #1e293b;
            border: 1px solid #cbd5e1;
            border-bottom: 2px solid #94a3b8;
            padding: 2px 7px;
            border-radius: 5px;
            font-size: 11.5px;
            font-weight: 700;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
            font-family: inherit;
        }
        .dark .guide-prose kbd {
            background: linear-gradient(to bottom, #334155, #1e293b);
            color: #f8fafc;
            border-color: #475569;
            border-bottom-color: #0f172a;
        }
        .guide-prose hr {
            border: 0;
            border-top: 1px solid #e2e8f0;
            margin: 24px 0;
        }
        .dark .guide-prose hr {
            border-top-color: #334155;
        }

        /* Tour Guide Spotlight Overlay Styles */
        .tour-overlay {
            position: fixed; inset: 0;
            z-index: 999998;
            pointer-events: none;
        }
        .tour-highlight {
            position: fixed;
            border-radius: 8px;
            box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.75), 0 0 15px rgba(16, 185, 129, 0.85);
            border: 2.5px solid #10b981;
            pointer-events: auto;
            z-index: 999999;
            box-sizing: border-box;
            will-change: top, left, width, height;
        }
        .tour-card {
            position: fixed;
            background: #ffffff;
            color: #1e293b;
            border-radius: 14px;
            padding: 18px 22px;
            width: 360px;
            max-width: calc(100vw - 32px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3), 0 8px 10px -6px rgba(0,0,0,0.25);
            border: 1px solid #e2e8f0;
            z-index: 1000000;
            pointer-events: auto;
            box-sizing: border-box;
            will-change: top, left;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .dark .tour-card {
            background: #0f172a;
            color: #f8fafc;
            border-color: #334155;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.6), 0 8px 10px -6px rgba(0,0,0,0.4);
        }
    </style>

    {{-- Listener shortcut keyboard F1 --}}
    <div x-data="{}"
          @keydown.window.f1.prevent="$wire.isOpen ? $wire.close() : $wire.openGuide(null, window.location.pathname)"
          @keydown.window.escape="$wire.close()">
    </div>

    @if($isOpen)
        {{-- Backdrop --}}
        <div class="guide-drawer-backdrop" wire:click="close"></div>
    @endif

    {{-- Sliding Panel --}}
    <div class="guide-drawer-panel {{ $isOpen ? 'is-open' : '' }}" role="dialog" aria-modal="true" id="user-guide-drawer-container">
        
        {{-- Header Panel Modern --}}
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);" class="dark:border-slate-800 dark:bg-slate-900/80">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                {{-- Title & Info --}}
                <div style="display: flex; align-items: center; gap: 14px; min-width: 0;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; flex-shrink: 0; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                            <line x1="12" y1="17" x2="12.01" y2="17" />
                        </svg>
                    </div>
                    <div style="min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <h2 style="font-size: 16px; font-weight: 800; margin: 0; color: #0f172a; letter-spacing: -0.01em;" class="dark:text-white">
                                Panduan Pengguna & SOP
                            </h2>
                            <span style="font-size: 11px; padding: 2px 9px; border-radius: 9999px; font-weight: 700; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;" class="dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                {{ $guide['category'] }}
                            </span>
                        </div>
                        <p style="font-size: 12px; margin: 3px 0 0 0; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" class="dark:text-slate-400">
                            {{ $guide['title'] }} &bull; Tombol Cepat <kbd>F1</kbd>
                        </p>
                    </div>
                </div>

                {{-- Action Buttons Toolbar (Horizontal) --}}
                <div style="display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;">
                    {{-- Tombol Cetak SOP --}}
                    <button type="button" 
                            onclick="printUserGuide()"
                            title="Cetak Dokumen SOP ini ke Printer / PDF"
                            style="width: 36px; height: 36px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #cbd5e1; cursor: pointer; color: #475569; transition: all 0.15s ease;"
                            class="dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-emerald-600 shadow-xs">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                    </button>

                    {{-- Tombol Salin Teks --}}
                    <button type="button" 
                            onclick="copyUserGuide()"
                            title="Salin Teks Panduan Ini"
                            style="width: 36px; height: 36px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #cbd5e1; cursor: pointer; color: #475569; transition: all 0.15s ease;"
                            class="dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-emerald-600 shadow-xs">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                    </button>

                    {{-- Tombol Tutup X --}}
                    <button type="button" 
                            wire:click="close"
                            title="Tutup Panel (Esc)"
                            style="width: 36px; height: 36px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1px solid #cbd5e1; cursor: pointer; color: #64748b; transition: all 0.15s ease;"
                            class="dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 dark:hover:bg-rose-950/40 shadow-xs">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Toolbar Navigasi & Pencarian --}}
        <div style="padding: 14px 24px; border-bottom: 1px solid #f1f5f9; background: #ffffff;" class="dark:border-slate-800 dark:bg-slate-900">
            {{-- Search Bar --}}
            <div style="position: relative; display: flex; align-items: center; margin-bottom: 10px;">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="position: absolute; left: 14px; pointer-events: none;" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input type="text" 
                       wire:model.live.debounce.250ms="searchQuery" 
                       placeholder="Cari kata kunci topik panduan, rumus, atau SOP..."
                       style="width: 100%; padding: 9px 14px 9px 40px; font-size: 13.5px; border-radius: 10px; border: 1.5px solid #e2e8f0; outline: none; transition: border-color 0.15s ease, box-shadow 0.15s ease;"
                       class="dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
            </div>

            {{-- Quick Menu Switcher & Tour Button --}}
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 220px;">
                    <span style="font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap;" class="dark:text-slate-400">Pilih Menu:</span>
                    <select wire:change="selectMenu($event.target.value)" 
                            style="width: 100%; font-size: 12.5px; font-weight: 600; padding: 6px 12px; border-radius: 8px; border: 1.5px solid #e2e8f0; outline: none; cursor: pointer;"
                            class="dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-emerald-500">
                        @foreach($groupedMenus as $category => $items)
                            <optgroup label="📂 {{ $category }}">
                                @foreach($items as $menuKey => $m)
                                    <option value="{{ $menuKey }}" {{ $activeMenu === $menuKey ? 'selected' : '' }}>
                                        {{ $m['title'] }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                {{-- Tombol Tour Guide --}}
                <button type="button" 
                        onclick="startGuidedTour()"
                        title="Jalankan tur interaktif penunjuk elemen di layar halaman ini"
                        style="display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px; font-size: 12.5px; font-weight: 700; border-radius: 8px; border: none; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.35); flex-shrink: 0;"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.45)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(16, 185, 129, 0.35)'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
                    </svg>
                    <span>Mulai Tur Halaman</span>
                </button>
            </div>
        </div>

        {{-- Body: Konten Markdown yang Dirender --}}
        <div style="flex: 1; overflow-y: auto; padding: 26px 30px;" class="guide-scrollable">
            <div class="guide-prose">
                {!! $guide['html'] !!}
            </div>
        </div>

        {{-- Footer Panel --}}
        <div style="padding: 14px 24px; border-top: 1px solid #e2e8f0; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);" class="dark:border-slate-800 dark:bg-slate-900/80 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 50%; background: #ecfdf5; color: #10b981; font-weight: 700;">i</span>
                <span>Butuh panduan menu lain? Pilih di dropdown atas atau tekan <kbd>F1</kbd>.</span>
            </div>
            <button type="button" 
                    wire:click="close"
                    style="padding: 6px 16px; font-size: 12.5px; font-weight: 700; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; cursor: pointer; color: #334155; transition: all 0.15s ease;"
                    class="dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 shadow-xs">
                Tutup
            </button>
        </div>
    </div>

    {{-- Interactive Guided Tour Container (DOM) --}}
    <div id="tour-spotlight-layer" style="display: none;">
        <div class="tour-highlight" id="tour-highlight-box"></div>
        <div class="tour-card" id="tour-card-box">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <span id="tour-step-badge" style="font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 9999px; background: #ecfdf5; color: #059669; text-transform: uppercase;"></span>
                <button type="button" onclick="stopGuidedTour()" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 2px; font-size: 16px; line-height: 1;" title="Keluar dari Tur">&times;</button>
            </div>
            <h4 id="tour-title" style="margin: 0 0 6px 0; font-size: 15px; font-weight: 800; color: #0f172a;"></h4>
            <p id="tour-description" style="margin: 0 0 16px 0; font-size: 13px; line-height: 1.55; color: #475569;"></p>
            <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 12px; margin-top: 4px;">
                <button type="button" id="tour-btn-prev" onclick="prevTourStep()" style="font-size: 12px; font-weight: 700; color: #64748b; background: #f1f5f9; border: 1px solid #e2e8f0; padding: 5px 12px; border-radius: 6px; cursor: pointer;">&larr; Kembali</button>
                <button type="button" id="tour-btn-next" onclick="nextTourStep()" style="font-size: 12px; font-weight: 700; color: #ffffff; background: #10b981; border: none; padding: 6px 16px; border-radius: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);">Lanjut &rarr;</button>
            </div>
        </div>
    </div>

    {{-- Script Helper: Print, Copy SOP & Interactive Guided Tour --}}
    <script>
        // Print Helper
        function printUserGuide() {
            var content = document.querySelector('.guide-prose');
            if (!content) return;
            var w = window.open('', '_blank', 'width=850,height=950');
            if (!w) {
                alert('Pop-up terblokir oleh browser. Harap izinkan pop-up.');
                return;
            }
            w.document.title = 'Panduan & SOP - SM Inventory';
            w.document.body.style.fontFamily = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            w.document.body.style.lineHeight = '1.65';
            w.document.body.style.padding = '35px';
            w.document.body.style.color = '#1e293b';

            var header = document.createElement('div');
            header.style.borderBottom = '2.5px solid #10b981';
            header.style.paddingBottom = '12px';
            header.style.marginBottom = '25px';
            header.innerHTML = '<h2 style="margin:0;color:#10b981;font-size:22px;">SM INVENTORY ERP - BUKU PANDUAN & SOP RESMI</h2><small style="color:#64748b;font-size:13px;">Dokumen Prosedur Standar Operasional Toko</small>';
            
            w.document.body.appendChild(header);

            var bodyContent = document.createElement('div');
            bodyContent.innerHTML = content.innerHTML;
            w.document.body.appendChild(bodyContent);

            setTimeout(function() {
                w.print();
                w.close();
            }, 350);
        }

        // Copy Helper
        function copyUserGuide() {
            var content = document.querySelector('.guide-prose');
            if (!content) return;
            navigator.clipboard.writeText(content.innerText).then(function() {
                alert('✓ Teks Panduan & SOP Berhasil Disalin ke Clipboard!');
            }).catch(function(err) {
                console.error('Gagal menyalin:', err);
            });
        }

        // ==================== INTERACTIVE GUIDED TOUR ENGINE (PRECISION ADAPTIVE) ====================
        var currentTourStep = 0;
        var activeTourSteps = [];
        var isTourRunning = false;
        var tourRafId = null;

        // Helper: Pastikan elemen benar-benar tampak di layar (bukan 0x0, bukan hidden)
        function isElementVisible(el) {
            if (!el || !(el instanceof HTMLElement)) return false;
            if (el.offsetParent === null && el.style.position !== 'fixed') return false;
            var style = window.getComputedStyle(el);
            if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return false;
            var rect = el.getBoundingClientRect();
            return rect.width > 2 && rect.height > 2;
        }

        // Helper: Cari elemen visible pertama dari daftar selector atau elemen langsung
        function findFirstVisible(selectors) {
            for (var i = 0; i < selectors.length; i++) {
                var item = selectors[i];
                if (typeof item === 'string') {
                    var els = document.querySelectorAll(item);
                    for (var j = 0; j < els.length; j++) {
                        if (isElementVisible(els[j])) return els[j];
                    }
                } else if (item && isElementVisible(item)) {
                    return item;
                }
            }
            return null;
        }

        function startGuidedTour() {
            // Tutup drawer terlebih dahulu agar pengguna dapat melihat halaman penuh
            if (window.Livewire) {
                var component = Livewire.find(document.getElementById('user-guide-drawer-container')?.closest('[wire\\:id]')?.getAttribute('wire:id'));
                if (component) {
                    component.set('isOpen', false);
                }
            }

            setTimeout(function() {
                buildTourSteps();
                if (activeTourSteps.length === 0) {
                    alert('Halaman ini siap digunakan. Tidak ada elemen tur khusus yang perlu disorot.');
                    return;
                }
                currentTourStep = 0;
                isTourRunning = true;
                document.getElementById('tour-spotlight-layer').style.display = 'block';

                // Pasang listeners dengan passive & capture
                window.removeEventListener('scroll', repositionTourInstant, true);
                window.removeEventListener('resize', repositionTourInstant, true);
                window.addEventListener('scroll', repositionTourInstant, true);
                window.addEventListener('resize', repositionTourInstant, true);

                showTourStep(0);
            }, 300);
        }

        function buildTourSteps() {
            activeTourSteps = [];

            // 1. Judul Halaman & Breadcrumb
            var headerEl = findFirstVisible([
                '.fi-header-heading',
                '.fi-page-header h1',
                '.fi-header h1',
                'h1'
            ]);
            if (headerEl) {
                activeTourSteps.push({
                    element: headerEl,
                    title: '1. Judul & Halaman Kerja',
                    desc: 'Anda berada di halaman ' + (headerEl.innerText.trim() || 'Menu Ini') + '. Seluruh konfigurasi dan data menu ini dipantau dari sini.'
                });
            }

            // 2. Tab Navigasi Sub-Fitur (jika halaman memiliki tabs seperti Pengaturan POS, Laporan, dll.)
            var tabsEl = findFirstVisible([
                '.fi-tabs',
                '[role="tablist"]',
                '.fi-page-sub-navigation-tabs'
            ]);
            if (tabsEl) {
                activeTourSteps.push({
                    element: tabsEl,
                    title: '2. Tab Navigasi Fitur',
                    desc: 'Gunakan tab-tab ini untuk berpindah bagian konfigurasi seperti General Setting, Pengaturan Tombol, Struk, dan Timbangan.'
                });
            }

            // 3. Tombol Aksi Header (HANYA tombol di fi-header-actions, misal Buat Data / Aksi Halaman)
            var headerActionBtn = findFirstVisible([
                '.fi-header-actions .fi-btn',
                '.fi-header-actions button:not([title*="Petunjuk"]):not([title*="F1"])'
            ]);
            if (headerActionBtn && !headerActionBtn.closest('form')) {
                activeTourSteps.push({
                    element: headerActionBtn,
                    title: '3. Tombol Aksi Halaman',
                    desc: 'Gunakan tombol "' + headerActionBtn.innerText.trim() + '" ini untuk menambah data baru atau mengeksekusi aksi penting.'
                });
            }

            // 4. Bilah Pencarian & Filter Tabel (jika ada pada halaman index)
            var searchEl = findFirstVisible([
                '.fi-ta-search-field input',
                '.fi-ta-search-field',
                '.fi-ta-header-ctn input[type="search"]'
            ]);
            if (searchEl) {
                activeTourSteps.push({
                    element: searchEl,
                    title: '4. Pencarian Cepat & Filter',
                    desc: 'Ketik nama barang, nomor dokumen, atau scan barcode di sini untuk menyaring data dengan instan.'
                });
            }

            // 5. Area Kerja / Tabel Data / Formulir Utama
            var tableEl = findFirstVisible(['.fi-ta-table', '.fi-ta-content']);
            if (tableEl) {
                activeTourSteps.push({
                    element: tableEl,
                    title: '5. Tabel Rekapitulasi Data',
                    desc: 'Seluruh daftar rekaman data, riwayat transaksi, dan status ditampilkan di tabel utama ini.'
                });
            } else {
                var formSection = findFirstVisible([
                    '.fi-section:first-of-type',
                    '.fi-fo-component-ctn',
                    '.fi-form'
                ]);
                if (formSection) {
                    activeTourSteps.push({
                        element: formSection,
                        title: '5. Formulir & Rincian Data',
                        desc: 'Bagian ini memuat formulir input dan rincian parameter pengaturan yang dapat Anda sesuaikan.'
                    });
                }
            }

            // 6. Tombol Simpan Formulir (Khusus jika halaman formulir Create / Edit)
            var saveCandidate = null;
            var formButtons = document.querySelectorAll('.fi-form-actions button, form button[type="submit"], .fi-ac-btn-action');
            for (var i = 0; i < formButtons.length; i++) {
                var btn = formButtons[i];
                if (isElementVisible(btn)) {
                    var btnText = btn.innerText.toLowerCase();
                    if (btnText.includes('simpan') || btn.getAttribute('type') === 'submit') {
                        saveCandidate = btn;
                        break;
                    }
                }
            }
            if (saveCandidate) {
                activeTourSteps.push({
                    element: saveCandidate,
                    title: '6. Simpan Perubahan',
                    desc: 'Setelah selesai mengubah pengaturan atau mengisi formulir, klik tombol "' + (saveCandidate.innerText.trim() || 'Simpan') + '" ini untuk menyimpan perubahan.'
                });
            }

            // 7. Tombol Bantuan Kapan Saja (F1)
            var helpBtn = findFirstVisible([
                '#page-help-button-trigger',
                'button[title*="Petunjuk"]',
                'button[title*="F1"]'
            ]);
            if (helpBtn) {
                activeTourSteps.push({
                    element: helpBtn,
                    title: '7. Bantuan Selalu Siap (F1)',
                    desc: 'Kapan pun Anda ragu, cukup klik tombol icon ? ini atau tekan tombol F1 di keyboard untuk membuka buku panduan lengkap.'
                });
            }
        }

        function showTourStep(index) {
            if (index < 0 || index >= activeTourSteps.length) return;
            currentTourStep = index;
            var step = activeTourSteps[index];
            var el = step.element;

            // Scroll ke elemen target agar berada di tengah layar
            el.scrollIntoView({ behavior: 'auto', block: 'center' });

            // Update badge dan teks
            document.getElementById('tour-step-badge').innerText = 'Langkah ' + (index + 1) + ' dari ' + activeTourSteps.length;
            document.getElementById('tour-title').innerText = step.title;
            document.getElementById('tour-description').innerText = step.desc;

            // Atur tombol prev & next
            document.getElementById('tour-btn-prev').style.visibility = index === 0 ? 'hidden' : 'visible';
            document.getElementById('tour-btn-next').innerText = index === activeTourSteps.length - 1 ? 'Selesai ✓' : 'Lanjut →';

            var highlight = document.getElementById('tour-highlight-box');
            var card = document.getElementById('tour-card-box');
            
            // Aktifkan transisi halus saat berganti langkah
            if (highlight) highlight.style.transition = 'top 0.25s cubic-bezier(0.16, 1, 0.3, 1), left 0.25s cubic-bezier(0.16, 1, 0.3, 1), width 0.25s cubic-bezier(0.16, 1, 0.3, 1), height 0.25s cubic-bezier(0.16, 1, 0.3, 1)';
            if (card) card.style.transition = 'top 0.25s cubic-bezier(0.16, 1, 0.3, 1), left 0.25s cubic-bezier(0.16, 1, 0.3, 1)';

            // Sinkronkan posisi spotlight dan card
            repositionTour();

            // Jalankan loop RAF selama 350ms agar selama browser scroll/render, spotlight tetap pas di setiap frame
            if (tourRafId) cancelAnimationFrame(tourRafId);
            var startTime = performance.now();
            function followElement(now) {
                repositionTour();
                if (now - startTime < 350) {
                    tourRafId = requestAnimationFrame(followElement);
                } else {
                    // Setelah transisi langkah selesai, matikan transisi posisi agar saat user scroll, tidak ada delay/lag!
                    if (highlight) highlight.style.transition = 'none';
                    if (card) card.style.transition = 'none';
                }
            }
            tourRafId = requestAnimationFrame(followElement);
        }

        // Reposisi instan saat scroll atau resize (tanpa delay CSS transition)
        function repositionTourInstant() {
            var highlight = document.getElementById('tour-highlight-box');
            var card = document.getElementById('tour-card-box');
            if (highlight) highlight.style.transition = 'none';
            if (card) card.style.transition = 'none';
            repositionTour();
        }

        function repositionTour() {
            if (!isTourRunning || currentTourStep < 0 || currentTourStep >= activeTourSteps.length) return;
            var step = activeTourSteps[currentTourStep];
            if (!step || !step.element) return;

            var el = step.element;
            var rect = el.getBoundingClientRect();
            var highlight = document.getElementById('tour-highlight-box');
            var card = document.getElementById('tour-card-box');
            if (!highlight || !card) return;

            // 1. Kotak Highlight (Pas membungkus elemen target dengan padding 6px)
            var pad = 6;
            var hlTop = Math.max(0, rect.top - pad);
            var hlLeft = Math.max(0, rect.left - pad);
            var hlWidth = Math.max(20, rect.width + (pad * 2));
            var hlHeight = Math.max(20, rect.height + (pad * 2));

            highlight.style.top = Math.round(hlTop) + 'px';
            highlight.style.left = Math.round(hlLeft) + 'px';
            highlight.style.width = Math.round(hlWidth) + 'px';
            highlight.style.height = Math.round(hlHeight) + 'px';

            // 2. Kalkulasi Posisi Card Popover (Anti-Tabrakan & Adaptif di Semua Layar)
            var winW = window.innerWidth;
            var winH = window.innerHeight;
            var cardWidth = Math.min(360, winW - 32);
            card.style.width = cardWidth + 'px';
            var cardHeight = card.offsetHeight || 200;

            var cardTop = 20;
            var cardLeft = 20;
            var gap = 14;

            var spaceAbove = hlTop;
            var spaceBelow = winH - (hlTop + hlHeight);
            var spaceRight = winW - (hlLeft + hlWidth);
            var spaceLeft = hlLeft;

            // Prioritas penempatan vertikal:
            // Jika elemen berada di separuh bawah layar, taruh di ATAS elemen
            if (rect.bottom > winH * 0.60 && spaceAbove >= cardHeight + gap) {
                cardTop = hlTop - cardHeight - gap;
            } else if (spaceBelow >= cardHeight + gap) {
                // Ada ruang luas di bawah elemen -> Taruh di BAWAH elemen
                cardTop = hlTop + hlHeight + gap;
            } else if (spaceAbove >= cardHeight + gap) {
                // Taruh di ATAS elemen
                cardTop = hlTop - cardHeight - gap;
            } else {
                // Elemen sangat tinggi (misal tabel penuh atau form panjang)
                if (spaceRight >= cardWidth + gap + 10) {
                    cardLeft = hlLeft + hlWidth + gap;
                    cardTop = Math.max(20, Math.min(rect.top, winH - cardHeight - 20));
                } else {
                    // Floating aman di pojok kanan atas viewport
                    cardTop = 24;
                    cardLeft = winW - cardWidth - 24;
                }
            }

            // Penempatan horizontal (jika card berada di atas atau di bawah elemen)
            if (!(spaceBelow < cardHeight + gap && spaceAbove < cardHeight + gap && spaceRight >= cardWidth + gap + 10)) {
                // Sejajarkan sisi kiri card dengan sisi kiri elemen target
                cardLeft = hlLeft;
                // Jangan sampai menabrak batas kanan layar
                if (cardLeft + cardWidth > winW - 16) {
                    cardLeft = winW - cardWidth - 16;
                }
                // Jangan sampai menabrak batas kiri layar
                if (cardLeft < 16) {
                    cardLeft = 16;
                }
            }

            // Pastikan batas vertikal aman dalam viewport
            if (cardTop < 16) cardTop = 16;
            if (cardTop + cardHeight > winH - 16) cardTop = winH - cardHeight - 16;

            card.style.top = Math.round(cardTop) + 'px';
            card.style.left = Math.round(cardLeft) + 'px';
        }

        function nextTourStep() {
            if (currentTourStep < activeTourSteps.length - 1) {
                showTourStep(currentTourStep + 1);
            } else {
                stopGuidedTour();
            }
        }

        function prevTourStep() {
            if (currentTourStep > 0) {
                showTourStep(currentTourStep - 1);
            }
        }

        function stopGuidedTour() {
            isTourRunning = false;
            if (tourRafId) cancelAnimationFrame(tourRafId);
            document.getElementById('tour-spotlight-layer').style.display = 'none';
            window.removeEventListener('scroll', repositionTourInstant, true);
            window.removeEventListener('resize', repositionTourInstant, true);
        }
    </script>
</div>
