@echo off
title Laravel Queue Worker - SM Inventory
cd /d "d:\APLIKASI PROJECT\sminventory\backend"

:start
echo [%date% %time%] Queue worker starting...
php artisan queue:work --timeout=300 --tries=3 --sleep=3
echo [%date% %time%] Stopped. Restarting in 5 seconds...
timeout /t 5 /nobreak >nul
goto start
