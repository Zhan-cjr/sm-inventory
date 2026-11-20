@php
    $isLogin = request()->routeIs('filament.admin.auth.login') || request()->is('*/login') || request()->is('login');
@endphp

@if($isLogin)
    <div style="display: flex; justify-content: center; align-items: center; width: 100%; height: 100%;">
        <img src="{{ asset('logo-sm-inventory.png') }}" alt="SM Inventory" style="height: 5rem; width: 5rem; border-radius: 16px; object-fit: contain; box-shadow: 0 8px 24px rgba(0,0,0,0.12); display: block;" />
    </div>
@else
    <div style="display: inline-flex; align-items: center; gap: 10px; text-align: left; vertical-align: middle;">
        <!-- Logo Image -->
        <img src="{{ asset('logo-sm-inventory.png') }}" alt="SM Inventory" style="width: 36px; height: 36px; min-width: 36px; min-height: 36px; border-radius: 8px; object-fit: contain; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.1);" />

        <!-- Text & Badge Container -->
        <div style="display: flex; flex-direction: column; justify-content: center; line-height: 1.2;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 16px; font-weight: 800; letter-spacing: -0.025em; color: currentColor; white-space: nowrap;">
                    SM Inventory
                </span>
                <span style="display: inline-flex; align-items: center; padding: 2px 6px; font-size: 10px; font-weight: 700; background-color: rgba(16, 185, 129, 0.15); color: #10b981; border-radius: 4px; border: 1px solid rgba(16, 185, 129, 0.3); line-height: 1;">
                    ERP
                </span>
            </div>
            <div style="margin-top: 2px; display: flex; align-items: center;">
                <a href="https://www.instagram.com/amn4ll?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 600; color: #94a3b8; text-decoration: none; transition: color 0.2s;"
                   onmouseover="this.style.color='#10b981'"
                   onmouseout="this.style.color='#94a3b8'">
                    <svg width="10" height="10" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 10px; height: 10px; border-radius: 2px; display: inline-block;">
                        <defs>
                            <linearGradient id="zGradBrandViewFix" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#10b981" />
                                <stop offset="100%" stop-color="#6366f1" />
                            </linearGradient>
                        </defs>
                        <rect width="100" height="100" rx="30" fill="url(#zGradBrandViewFix)" />
                        <path d="M30 30H70L30 70H70" stroke="white" stroke-width="12" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>Zhan_soft &copy; {{ date('Y') }}</span>
                </a>
            </div>
        </div>
    </div>
@endif
