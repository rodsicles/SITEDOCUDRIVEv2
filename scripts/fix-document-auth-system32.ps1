# Run as Administrator — applies view/download fixes to System32 deployment.
$ErrorActionPreference = 'Stop'
$src = 'C:\Users\rodwin\Documents\EMPLOYEEDASHBOARDV7'
$dst = 'C:\Windows\System32\EMmmPLOYEEDASHBOARDV7'

if (-not (Test-Path $dst)) { throw "Not found: $dst" }

Write-Host "Granting write access..."
takeown /f "$dst\app" /r /d y 2>&1 | Out-Null
icacls "$dst\app" /grant "${env:USERNAME}:(OI)(CI)M" /t 2>&1 | Out-Null

$files = @(
    'app\Support\UploadStorage.php',
    'app\Services\DocumentService.php',
    'app\Models\Document.php',
    'resources\views\coordinator\documents.blade.php',
    'app\Http\Controllers\TeachingGuideController.php'
)

foreach ($rel in $files) {
    $from = Join-Path $src $rel
    $to = Join-Path $dst $rel
    if (Test-Path $from) {
        Copy-Item $from $to -Force
        Write-Host "OK $rel"
    }
}

Write-Host 'Done. Restart php artisan serve / redeploy if needed.'
