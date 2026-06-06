@echo off
title Build React Frontend (POS & Ecommerce)
echo ==============================================
echo 1. Mem-build POS Kasir...
echo ==============================================
cd /d "d:\APLIKASI PROJECT\sminventory\frontend"
call npm install
call npm run build

echo.
echo ==============================================
echo 2. Mem-build Ecommerce...
echo ==============================================
cd /d "d:\APLIKASI PROJECT\sminventory\ecommerce-frontend"
call npm install
call npm run build

echo.
echo ==============================================
echo [SELESAI] Build aplikasi berhasil!
echo Sekarang Anda bisa menjalankan start_react.bat
echo ==============================================
pause
