@echo off
title Monitor React Server (PM2)
echo ==============================================
echo Membuka PM2 Monitor...
echo (Gunakan tombol Panah Atas/Bawah untuk memilih log)
echo (Tekan CTRL + C atau huruf 'q' untuk keluar dari monitor)
echo ==============================================
call pm2 monit
