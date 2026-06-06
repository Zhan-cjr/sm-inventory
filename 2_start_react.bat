@echo off
title Start Backend & React Servers
echo ==============================================
echo Aplikasi React dan Backend dihosting oleh Laragon Nginx
echo ==============================================

cd /d "d:\APLIKASI PROJECT\sminventory"
call pm2 start ecosystem-cloudflared.config.js

echo.
call pm2 save
echo.
echo ==============================================
echo [BERHASIL] Cloudflared Tunnel berjalan di Background!
echo.
echo POS Kasir : http://localhost:3000
echo Ecommerce : http://localhost:3001
echo.
echo (Jendela ini sudah boleh ditutup, aplikasi tetap jalan)
echo ==============================================
pause
