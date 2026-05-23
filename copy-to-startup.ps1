# copy-to-startup.ps1
$src = "d:\APLIKASI PROJECT\sminventory\queue-worker.bat"
$dst = Join-Path $env:APPDATA "Microsoft\Windows\Start Menu\Programs\Startup\SMInventory-QueueWorker.bat"

Copy-Item $src -Destination $dst -Force
Write-Host "Berhasil didaftarkan ke Startup folder:"
Write-Host $dst
Write-Host ""
Write-Host "Queue worker akan otomatis berjalan saat Windows login."
