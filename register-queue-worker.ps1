# register-queue-worker.ps1
# Jalankan sebagai Administrator

$taskName = "SMInventory-QueueWorker"
$batPath  = "d:\APLIKASI PROJECT\sminventory\queue-worker.bat"

# Hapus task lama jika ada
Unregister-ScheduledTask -TaskName $taskName -Confirm:$false -ErrorAction SilentlyContinue

# Buat task baru
$action   = New-ScheduledTaskAction -Execute "cmd.exe" -Argument "/c `"$batPath`""
$trigger  = New-ScheduledTaskTrigger -AtStartup
$settings = New-ScheduledTaskSettingsSet `
    -RestartCount 10 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -ExecutionTimeLimit ([TimeSpan]::Zero)

Register-ScheduledTask `
    -TaskName $taskName `
    -Action   $action `
    -Trigger  $trigger `
    -Settings $settings `
    -RunLevel Highest `
    -Force

Write-Host "✅ Task '$taskName' berhasil didaftarkan." -ForegroundColor Green
Write-Host "   Akan otomatis berjalan saat Windows startup." -ForegroundColor Cyan

# Jalankan sekarang juga
Start-ScheduledTask -TaskName $taskName
Write-Host "✅ Task sudah dijalankan sekarang." -ForegroundColor Green
