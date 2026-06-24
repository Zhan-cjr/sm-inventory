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
            --card-bg: #ffffff;
            --border-color: #d1d5db;
            --primary-color: #d97706; /* Filament warning/primary */
        }

        html.dark {
            --bg-color: #020617; /* Slate 950 (Filament default bg) */
            --text-color: #f8fafc; /* Slate 50 */
            --card-bg: #0f172a; /* Slate 900 (Filament default card) */
            --border-color: #1e293b; /* Slate 800 */
            --primary-color: #fbbf24;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            transition: background-color 0.3s, color 0.3s;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s;
            margin-top: 20px;
        }
        .back-btn:hover {
            background-color: var(--bg-color);
            border-color: var(--primary-color);
            color: var(--primary-color);
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
    <div style="max-width: 600px; margin: 0 auto; padding: 0 20px;">
        <a href="{{ url('/admin/warehouse-checks') }}" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
            </svg>
            Kembali
        </a>
    </div>
    
    @yield('content')

    @stack('scripts')
</body>
</html>
