<div x-data="filamentMultiTabs()"
     x-init="initTabs()"
     @popstate.window="handleNavigation()"
     style="display: flex; flex-direction: row; align-items: center; width: 100%; max-width: 100%; box-sizing: border-box; overflow: hidden; position: relative; z-index: 15; margin-top: 0;"
     class="filament-multi-tabs-wrapper border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/95 dark:bg-slate-900/95 backdrop-blur-md px-3 py-1.5 transition-all">
    
    <div style="display: flex; flex-direction: row; align-items: center; justify-content: space-between; width: 100%; max-width: 100%; box-sizing: border-box; gap: 8px; min-width: 0;">
        <!-- Tabs List Container (Horizontal Scrollable Flex Row) -->
        <div id="filament-multi-tabs-scroll-container"
             style="display: flex; flex-direction: row; align-items: center; gap: 6px; overflow-x: auto; overflow-y: hidden; flex: 1 1 0%; min-width: 0; max-width: 100%; padding: 2px 0;" 
             class="scrollbar-none scroll-smooth">
            <template x-for="(tab, index) in tabs" :key="tab.url">
                <div :class="tab.active ? 'filament-tab-pill is-active' : 'filament-tab-pill'"
                     style="display: inline-flex; flex-direction: row; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; white-space: nowrap; flex-shrink: 0; cursor: pointer; transition: all 0.15s ease;"
                     @click="openTab(tab.url)">
                    
                    <!-- Tab Menu Icon (Extracted from Sidebar) -->
                    <span x-html="tab.iconSvg || getFallbackIcon()" 
                          style="display: inline-flex; align-items: center; justify-content: center; width: 15px; height: 15px; flex-shrink: 0;"
                          :class="tab.active ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500'"></span>

                    <!-- Tab Title -->
                    <span x-text="tab.title" style="white-space: nowrap; max-width: 140px; overflow: hidden; text-overflow: ellipsis;"></span>

                    <!-- Close Button -->
                    <button type="button"
                            @click.stop.prevent="closeTab(index)"
                            title="Tutup Tab"
                            style="display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; border-radius: 4px; border: none; background: transparent; cursor: pointer; margin-left: 2px;"
                            class="tab-close-btn text-slate-400 hover:text-rose-600 hover:bg-rose-100 dark:hover:bg-rose-950/60 dark:hover:text-rose-400 transition">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width: 11px; height: 11px; flex-shrink: 0;">
                            <path d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </template>
        </div>

        <!-- Scroll Controls & Action Button (Tutup Lainnya) -->
        <div style="display: flex; flex-direction: row; align-items: center; gap: 4px; flex-shrink: 0; padding-left: 4px; z-index: 5;">
            <!-- Scroll Left Button -->
            <button type="button" 
                    @click="scrollTabs(-220)" 
                    title="Geser Kiri"
                    style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 6px; border: 1px solid #cbd5e1; background-color: #ffffff; color: #475569; cursor: pointer; flex-shrink: 0;"
                    class="hover:bg-slate-100 dark:hover:bg-slate-800 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 transition">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>

            <!-- Scroll Right Button -->
            <button type="button" 
                    @click="scrollTabs(220)" 
                    title="Geser Kanan"
                    style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 6px; border: 1px solid #cbd5e1; background-color: #ffffff; color: #475569; cursor: pointer; flex-shrink: 0;"
                    class="hover:bg-slate-100 dark:hover:bg-slate-800 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 transition">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 5l7 7-7 7"></path>
                </svg>
            </button>

            <!-- Tutup Lainnya Button -->
            <button type="button" 
                    x-show="tabs.length > 1" 
                    @click="closeOtherTabs()"
                    title="Tutup Tab Lainnya"
                    style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px; border: 1px solid #cbd5e1; background-color: #ffffff; color: #475569; cursor: pointer; white-space: nowrap; flex-shrink: 0;"
                    class="hover:border-rose-300 hover:text-rose-600 hover:bg-rose-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-rose-950/30 dark:hover:text-rose-400 transition">
                Tutup Lainnya
            </button>
        </div>
    </div>
</div>

<script>
function filamentMultiTabs() {
    return {
        tabs: [],
        pendingClosePath: null,
        storageKey: 'sm_filament_admin_tabs',

        isFormTab(path) {
            if (!path) return false;
            const clean = path.split('?')[0];
            return clean.includes('/edit') || 
                   clean.includes('/create') || 
                   clean.endsWith('-pos') || 
                   clean.includes('create-') ||
                   /\/edit(\/|$)/.test(clean) || 
                   /\/create(\/|$)/.test(clean);
        },

        initTabs() {
            this.loadTabsFromStorage();
            
            // Periodically sync URL query string changes
            setInterval(() => {
                this.updateActiveTabUrl();
            }, 400);

            // Listen for notificationSent event (e.g. after Edit form or POS save)
            window.addEventListener('notificationSent', (e) => {
                const notif = e.detail?.notification;
                if (notif && (notif.status === 'success' || notif.color === 'success')) {
                    const currentPath = window.location.pathname;
                    if (this.isFormTab(currentPath)) {
                        this.pendingClosePath = currentPath;
                        const activeIndex = this.tabs.findIndex(t => t.path === currentPath || (t.active && this.isFormTab(t.path)));
                        if (activeIndex !== -1) {
                            this.closeTab(activeIndex);
                        }
                    }
                }
            });

            // Global click listener for Batal / Cancel / Kembali buttons
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('a, button');
                if (!btn) return;
                const text = (btn.innerText || btn.textContent || '').trim().toLowerCase();
                if (text === 'batal' || text === 'cancel' || text === 'kembali') {
                    const currentPath = window.location.pathname;
                    if (this.isFormTab(currentPath)) {
                        this.pendingClosePath = currentPath;
                        const activeIndex = this.tabs.findIndex(t => t.path === currentPath || (t.active && this.isFormTab(t.path)));
                        if (activeIndex !== -1) {
                            this.closeTab(activeIndex);
                        }
                    }
                }
            });

            // Refresh icons for all existing stored tabs after DOM renders
            setTimeout(() => {
                this.refreshAllTabIcons();
                this.scrollToActiveTab();
            }, 300);
            setTimeout(() => this.refreshAllTabIcons(), 1000);

            document.addEventListener('livewire:navigated', () => {
                setTimeout(() => {
                    this.captureCurrentPage();
                    this.refreshAllTabIcons();
                    this.scrollToActiveTab();
                }, 100);
            });

            setTimeout(() => {
                this.captureCurrentPage();
                this.scrollToActiveTab();
            }, 300);
            setTimeout(() => this.captureCurrentPage(), 700);
        },

        scrollTabs(amount) {
            const container = document.getElementById('filament-multi-tabs-scroll-container');
            if (container) {
                container.scrollBy({ left: amount, behavior: 'smooth' });
            }
        },

        scrollToActiveTab() {
            setTimeout(() => {
                const activeTabEl = document.querySelector('.filament-tab-pill.is-active');
                if (activeTabEl) {
                    activeTabEl.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
                }
            }, 100);
        },

        updateActiveTabUrl() {
            const currentFullUrl = window.location.pathname + window.location.search;
            const currentPath = window.location.pathname;

            const activeTab = this.tabs.find(t => t.active);
            if (activeTab && activeTab.path === currentPath) {
                if (activeTab.url !== currentFullUrl) {
                    activeTab.url = currentFullUrl;
                    this.saveTabsToStorage();
                }
            }
        },

        refreshAllTabIcons() {
            let updated = false;
            this.tabs.forEach(t => {
                const newIcon = this.extractSidebarIcon(t.path);
                if (newIcon && t.iconSvg !== newIcon) {
                    t.iconSvg = newIcon;
                    updated = true;
                }
            });
            if (updated) {
                this.saveTabsToStorage();
            }
        },

        loadTabsFromStorage() {
            try {
                const stored = sessionStorage.getItem(this.storageKey);
                if (stored) {
                    this.tabs = JSON.parse(stored);
                }
            } catch (e) {
                this.tabs = [];
            }
        },

        saveTabsToStorage() {
            try {
                sessionStorage.setItem(this.storageKey, JSON.stringify(this.tabs));
            } catch (e) {}
        },

        cleanTitle(rawTitle) {
            if (!rawTitle) return 'Halaman';
            return rawTitle
                .replace(/\s*-\s*SM Inventory ERP.*$/i, '')
                .replace(/\s*-\s*Filament.*$/i, '')
                .replace(/\s*\|\s*.*$/i, '')
                .trim() || 'Halaman';
        },

        extractSidebarIcon(currentPath) {
            try {
                const cleanPath = currentPath.split('?')[0].replace(/\/$/, '');
                const sidebarLinks = document.querySelectorAll('.fi-sidebar-item a, .fi-sidebar-item-button, .fi-sidebar a, aside a');
                
                let bestSvg = null;
                let longestMatchLength = 0;

                for (let link of sidebarLinks) {
                    const linkPath = (link.pathname || '').replace(/\/$/, '');
                    if (!linkPath) continue;

                    if (linkPath === cleanPath) {
                        const svg = link.querySelector('svg');
                        if (svg) return this.formatSvg(svg);
                    }

                    if (linkPath !== '/admin' && cleanPath.startsWith(linkPath)) {
                        if (linkPath.length > longestMatchLength) {
                            longestMatchLength = linkPath.length;
                            const svg = link.querySelector('svg');
                            if (svg) bestSvg = svg;
                        }
                    }
                }

                if (bestSvg) {
                    return this.formatSvg(bestSvg);
                }
            } catch (e) {}
            return null;
        },

        formatSvg(svg) {
            const clone = svg.cloneNode(true);
            clone.setAttribute('width', '15');
            clone.setAttribute('height', '15');
            clone.setAttribute('style', 'width: 15px; height: 15px; flex-shrink: 0; min-width: 15px; min-height: 15px; display: inline-block;');
            return clone.outerHTML;
        },

        getFallbackIcon() {
            return `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 15px; height: 15px; flex-shrink: 0;"><path d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>`;
        },

        captureCurrentPage() {
            const currentUrl = window.location.pathname + window.location.search;
            const currentPath = window.location.pathname;

            if (currentPath.includes('/login') || currentPath.includes('/logout')) {
                return;
            }

            // Enforce removal of pending closed form tab
            if (this.pendingClosePath) {
                const targetToRemove = this.pendingClosePath;
                this.pendingClosePath = null;
                const removeIdx = this.tabs.findIndex(t => t.path === targetToRemove);
                if (removeIdx !== -1) {
                    this.tabs.splice(removeIdx, 1);
                }
                if (currentPath === targetToRemove) {
                    return;
                }
            }

            let pageTitle = this.cleanTitle(document.title);
            if (pageTitle === 'Halaman' || pageTitle === 'Dashboard') {
                const h1 = document.querySelector('h1.fi-header-heading, header h1, h1');
                if (h1 && h1.innerText.trim()) {
                    pageTitle = h1.innerText.trim();
                }
            }

            const iconSvg = this.extractSidebarIcon(currentPath);
            const existingIndex = this.tabs.findIndex(t => t.path === currentPath);

            // Save currently active tab as parentUrl for new form tab
            const currentActiveTab = this.tabs.find(t => t.active);
            const parentUrl = (currentActiveTab && currentActiveTab.path !== currentPath) ? currentActiveTab.url : null;

            this.tabs.forEach(t => t.active = false);

            if (existingIndex !== -1) {
                this.tabs[existingIndex].active = true;
                this.tabs[existingIndex].url = currentUrl;
                if (pageTitle && pageTitle !== 'Halaman') {
                    this.tabs[existingIndex].title = pageTitle;
                }
                if (iconSvg) {
                    this.tabs[existingIndex].iconSvg = iconSvg;
                }
            } else {
                this.tabs.push({
                    title: pageTitle,
                    url: currentUrl,
                    path: currentPath,
                    iconSvg: iconSvg,
                    parentUrl: parentUrl,
                    active: true
                });
            }

            this.saveTabsToStorage();
        },

        openTab(url) {
            this.updateActiveTabUrl();

            if (window.location.pathname + window.location.search === url) return;

            if (window.Livewire && typeof window.Livewire.navigate === 'function') {
                window.Livewire.navigate(url);
            } else {
                window.location.href = url;
            }
        },

        closeTab(index) {
            if (index < 0 || index >= this.tabs.length) return;

            const closingTab = this.tabs[index];
            const isClosingActive = closingTab.active;
            const parentUrl = closingTab ? closingTab.parentUrl : null;

            this.tabs.splice(index, 1);
            this.saveTabsToStorage();

            if (isClosingActive) {
                if (parentUrl) {
                    const parentTab = this.tabs.find(t => t.url === parentUrl || t.path === parentUrl.split('?')[0]);
                    if (parentTab) {
                        this.openTab(parentTab.url);
                        return;
                    }
                }

                if (this.tabs.length > 0) {
                    const fallbackTab = this.tabs.slice(0, index).reverse().find(t => !this.isFormTab(t.path)) || this.tabs[Math.max(0, index - 1)];
                    if (fallbackTab) {
                        this.openTab(fallbackTab.url);
                    } else {
                        this.openTab('/admin');
                    }
                } else {
                    this.openTab('/admin');
                }
            }
        },

        closeOtherTabs() {
            this.tabs = this.tabs.filter(t => t.active);
            this.saveTabsToStorage();
        },

        handleNavigation() {
            this.captureCurrentPage();
        }
    };
}
</script>
