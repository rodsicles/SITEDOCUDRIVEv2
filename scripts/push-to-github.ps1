# Push EMPLOYEEDASHBOARDV7 to GitHub (run in PowerShell or Cursor terminal)
# Usage: .\scripts\push-to-github.ps1

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path $PSScriptRoot -Parent
Set-Location $RepoRoot

Write-Host ""
Write-Host "=== EMPLOYEEDASHBOARDV7 — push helper ===" -ForegroundColor Cyan
Write-Host "Folder: $RepoRoot"
Write-Host ""

git config --local credential.helper manager 2>$null | Out-Null
git config user.name 2>$null | Out-Null
if (-not (git config user.name)) {
    git config --local user.name "npmrodev"
    git config --local user.email "rodwinvicquerra@spup.edu.ph"
}

Write-Host "Fetching from GitHub..." -ForegroundColor Yellow
git fetch origin
if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "FETCH FAILED. Common fixes:" -ForegroundColor Red
    Write-Host "  1. Check internet."
    Write-Host "  2. Sign in when Git Credential Manager opens (browser)."
    Write-Host "  3. Or use PAT: https://github.com/settings/tokens (classic, repo scope)"
    Write-Host "     Username: npmrodev  |  Password: <paste token>"
    exit 1
}

$status = git status -sb
Write-Host $status
Write-Host ""

if ($status -match "ahead (\d+)") {
    $ahead = [int]$Matches[1]
    Write-Host "You have $ahead commit(s) to upload." -ForegroundColor Green
} elseif ($status -notmatch "ahead") {
    Write-Host "Nothing to push — already up to date with origin/main." -ForegroundColor Green
    git log --oneline -1 origin/main
    exit 0
}

if ($status -match "behind") {
    Write-Host "Branch is BEHIND origin. Pull first:" -ForegroundColor Red
    Write-Host "  git pull --rebase origin main"
    exit 1
}

Write-Host "Pushing to https://github.com/npmrodev/EMPLOYEEDASHBOARDV7 ..." -ForegroundColor Yellow
Write-Host "(A login window or browser may open — sign in as npmrodev)" -ForegroundColor DarkGray
Write-Host ""

git push -u origin main

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "SUCCESS. Next: Laravel Cloud -> Deploy" -ForegroundColor Green
    git log --oneline -3
    exit 0
}

Write-Host ""
Write-Host "PUSH FAILED." -ForegroundColor Red
Write-Host ""
Write-Host "Try this once:" -ForegroundColor Yellow
Write-Host "  1. Open https://github.com/settings/tokens"
Write-Host "  2. Generate new token (classic) -> check 'repo'"
Write-Host "  3. Run again: .\scripts\push-to-github.ps1"
Write-Host "  4. Username: npmrodev"
Write-Host "  5. Password: paste the token (not your GitHub password)"
Write-Host ""
Write-Host "Or sign in via browser:" -ForegroundColor Yellow
Write-Host "  git credential-manager github login"
exit 1
