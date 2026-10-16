@php
    $currentPath = request()->path();
    $currentMenuKey = \App\Services\UserGuideService::detectMenuFromUrl($currentPath);
@endphp
<div style="display: inline-flex; align-items: center; margin-left: 6px;">
    <button type="button" 
            id="page-help-button-trigger"
            onclick="Livewire.dispatch('open-user-guide', { menu: '{{ $currentMenuKey }}', url: window.location.pathname })" 
            title="Petunjuk Penggunaan & SOP Halaman Ini (F1)"
            style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 9px; border: 1px solid rgba(16, 185, 129, 0.4); background: #ffffff; cursor: pointer; color: #10b981; transition: all 0.15s ease;"
            class="dark:bg-slate-800 dark:border-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-slate-700 shadow-sm">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
            <line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
    </button>
</div>
