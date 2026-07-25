<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - SM INVENTORY GUDANG</title>
    
    <!-- Google Fonts & Modern Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #f8fafc;
            --text-color: #0f172a;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --primary-color: #10b981;
            --primary-hover: #059669;
            --primary-light: rgba(16, 185, 129, 0.1);
            --header-bg: #ffffff;
            --header-border: #cbd5e1;
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        }

        html.dark {
            --bg-color: #020617; /* Slate 950 */
            --text-color: #f8fafc; /* Slate 50 */
            --text-muted: #94a3b8; /* Slate 400 */
            --card-bg: #0f172a; /* Slate 900 */
            --border-color: #1e293b; /* Slate 800 */
            --primary-color: #10b981;
            --primary-hover: #34d399;
            --primary-light: rgba(16, 185, 129, 0.15);
            --header-bg: #0f172a;
            --header-border: #1e293b;
            --card-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            transition: background-color 0.3s, color 0.3s;
            -webkit-tap-highlight-color: transparent;
        }

        /* Top Header Bar */
        .wh-header-bar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            backdrop-filter: blur(12px);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .wh-header-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text-color);
        }

        .wh-brand-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            font-weight: 900;
            font-size: 0.85rem;
            padding: 6px 10px;
            border-radius: 12px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .wh-brand-title {
            font-weight: 800;
            font-size: 1rem;
            letter-spacing: -0.3px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }
        
        .back-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateX(-2px);
        }

        .wh-layout-wrapper {
            max-width: 650px;
            margin: 0 auto;
            padding: 16px;
            min-height: calc(100vh - 65px);
        }
    </style>
    @stack('styles')
    <script>
        // Check local storage for theme (matches Filament)
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>
<body>
    <!-- Navigation Top Bar -->
    <header class="wh-header-bar">
        <a href="{{ url('/admin/warehouse-checks') }}" class="wh-header-brand">
            <span class="wh-brand-badge">SM</span>
            <span class="wh-brand-title">Pengecekan Gudang</span>
        </a>
        <a href="{{ url('/admin/warehouse-checks') }}" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
            </svg>
            Kembali
        </a>
    </header>

    <div class="wh-layout-wrapper">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
