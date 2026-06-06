@echo off
title Stop Servers (PM2)
echo ==============================================
echo Aplikasi React sudah dihandle oleh Laragon Nginx.
echo Mematikan Cloudflared Tunnel...
echo ==============================================

call pm2 stop cloudflared-tunnel

echo.
echo ==============================================
echo [BERHASIL] Tunnel berhasil dihentikan.
echo ==============================================
pause
