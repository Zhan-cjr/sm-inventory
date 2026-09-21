@echo off
title SM Inventory - Local Launcher
echo ========================================================
echo   SM INVENTORY - LOCAL DEVELOPMENT LAUNCHER
echo ========================================================
echo.
echo [1/4] Menyalakan Database MariaDB...
wsl -u root service mariadb start

echo.
echo [2/4] Menyalakan Search Engine Meilisearch (Port 7700)...
wsl bash -c "nohup /usr/local/bin/meilisearch --http-addr 0.0.0.0:7700 --master-key \"KunciRahasiaGudangSminventory123!\" --db-path /home/ideapad/meili_data > /dev/null 2>&1 &"

echo.
echo [3/4] Menjalankan Backend Laravel (Port 8080)...
start "SM-Inventory Backend (8080)" wsl bash -c "cd /home/ideapad/sminventory/backend && php artisan serve --host=0.0.0.0 --port=8080"

echo.
echo [4/4] Menjalankan Frontend Kasir POS (Port 4173)...
start "SM-Inventory Frontend POS (4173)" wsl bash -c "cd /home/ideapad/sminventory/frontend && npm run dev -- --host 0.0.0.0"

echo.
echo ========================================================
echo   SEMUA SERVICE BERHASIL DIJALANKAN!
echo ========================================================
echo   - Filament Admin Panel : http://localhost:8080/admin
echo   - Frontend POS Kasir   : http://localhost:4173
echo   - Meilisearch Engine   : http://localhost:7700
echo ========================================================
timeout /t 5
