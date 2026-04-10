#!/usr/bin/env pwsh
# install-all-deps.ps1
# Installs all npm and Composer dependencies for every project in the monorepo.

$root = Split-Path $PSScriptRoot -Parent

# Ensure XAMPP PHP is on PATH (needed when composer.bat lives in C:\xampp\php)
$xamppPhp = "C:\xampp\php"
if ((Test-Path "$xamppPhp\php.exe") -and ($env:PATH -notlike "*$xamppPhp*")) {
    $env:PATH = "$xamppPhp;" + $env:PATH
}

$errors = @()

function Run-Install {
    param(
        [string]$Label,
        [string]$Path,
        [string]$Command
    )

    Write-Host ""
    Write-Host "──────────────────────────────────────────" -ForegroundColor Cyan
    Write-Host "  $Label" -ForegroundColor Cyan
    Write-Host "  Path: $Path" -ForegroundColor DarkGray
    Write-Host "  Cmd : $Command" -ForegroundColor DarkGray
    Write-Host "──────────────────────────────────────────" -ForegroundColor Cyan

    if (-not (Test-Path $Path)) {
        Write-Host "  [SKIP] Directory not found." -ForegroundColor Yellow
        return
    }

    Push-Location $Path
    try {
        Invoke-Expression $Command
        if ($LASTEXITCODE -ne 0) {
            throw "Exit code $LASTEXITCODE"
        }
        Write-Host "  [OK] Done." -ForegroundColor Green
    } catch {
        Write-Host "  [FAIL] $_" -ForegroundColor Red
        $script:errors += "$Label - $_"
    } finally {
        Pop-Location
    }
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Magenta
Write-Host "   StoreForge - Install All Dependencies  " -ForegroundColor Magenta
Write-Host "==========================================" -ForegroundColor Magenta

# ── Composer ──────────────────────────────────────────────────────────────────
# Try local composer first; fall back to Docker container if available
if (Get-Command composer -ErrorAction SilentlyContinue) {
    Run-Install -Label "Backend (Composer)" -Path "$root\platform\backend" -Command "composer install"
} else {
    Write-Host ""
    Write-Host "──────────────────────────────────────────" -ForegroundColor Cyan
    Write-Host "  Backend (Composer)" -ForegroundColor Cyan
    Write-Host "──────────────────────────────────────────" -ForegroundColor Cyan
    Write-Host "  [INFO] 'composer' not found locally - trying Docker..." -ForegroundColor DarkYellow

    # Check if Docker daemon is reachable
    $dockerOk = docker info 2>&1 | Select-String -Quiet "Server Version"
    if ($dockerOk) {
        Run-Install -Label "Backend (Composer via Docker)" -Path "$root\platform\backend" `
            -Command "docker compose -f `"$root\docker-compose.yml`" exec -T backend composer install"
    } else {
        Write-Host "  [SKIP] Docker is not running and 'composer' is not installed locally." -ForegroundColor Yellow
        Write-Host "         To fix, do ONE of the following:" -ForegroundColor Yellow
        Write-Host "           Option A: Start Docker Desktop, then re-run this script." -ForegroundColor Yellow
        Write-Host "           Option B: Install PHP + Composer locally from https://getcomposer.org" -ForegroundColor Yellow
        $script:errors += "Backend (Composer) - Docker not running and composer not installed locally"
    }
}

# ── npm ───────────────────────────────────────────────────────────────────────
# Admin panel uses React 19 which conflicts with react-helmet-async peer dep declaration
Run-Install -Label "Admin Panel (npm)"    -Path "$root\platform\admin-panel"                         -Command "npm install --legacy-peer-deps"
Run-Install -Label "Backend (npm)"        -Path "$root\platform\backend"                             -Command "npm install"
Run-Install -Label "MCP Browser Service"  -Path "$root\platform\mcp-services\mcp-browser-service"    -Command "npm install"
Run-Install -Label "MCP Asset Service"    -Path "$root\platform\mcp-services\mcp-asset-service"      -Command "npm install"
Run-Install -Label "Storefront Template"  -Path "$root\storefront-template"                          -Command "npm install"
Run-Install -Label "Client: Honey Bee"    -Path "$root\client-honey-bee"                             -Command "npm install"

# ── Summary ───────────────────────────────────────────────────────────────────
Write-Host ""
Write-Host "==========================================" -ForegroundColor Magenta
if ($errors.Count -eq 0) {
    Write-Host "  All dependencies installed successfully!" -ForegroundColor Green
} else {
    Write-Host "  Completed with $($errors.Count) error(s):" -ForegroundColor Yellow
    foreach ($e in $errors) {
        Write-Host "    - $e" -ForegroundColor Red
    }
}
Write-Host "==========================================" -ForegroundColor Magenta
Write-Host ""
