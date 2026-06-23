<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - SM INVENTORY</title>
    <style>
        :root {
            --bg-color: #f3f4f6;
            --text-color: #111827;
            --header-bg: #1e3a8a;
            --header-text: #fff;
            --card-bg: #fff;
            --border-color: #d1d5db;
        }

        html.dark {
            --bg-color: #1f2937;
            --text-color: #f3f4f6;
            --header-bg: #111827;
            --header-text: #e5e7eb;
            --card-bg: #374151;
            --border-color: #4b5563;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            transition: background-color 0.3s, color 0.3s;
        }
        .app-header {
            background-color: var(--header-bg);
            color: var(--header-text);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .app-header h1 {
            margin: 0;
            font-size: 1.2rem;
            color: var(--header-text);
        }
        .user-info {
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .theme-toggle {
            background: none;
            border: none;
            color: var(--header-text);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 5px;
            border-radius: 50%;
            transition: background-color 0.2s;
        }
        .theme-toggle:hover {
            background-color: rgba(255,255,255,0.1);
        }
    </style>
    @stack('styles')
    <script>
        // Check local storage for theme
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>
<body>
    <div class="app-header">
        <h1>SM INVENTORY - GUDANG</h1>
        <div class="user-info">
            {{ auth()->user()->name ?? 'Guest' }}
            <button id="theme-toggle-btn" class="theme-toggle" onclick="toggleTheme()" title="Ganti Tema">
                <span id="theme-icon">🌓</span>
            </button>
        </div>
    </div>
    
    @yield('content')

    <script>
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
