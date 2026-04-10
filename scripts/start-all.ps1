param(
    [switch]$DockerInfra,
    [switch]$Stop
)

$root = Split-Path $PSScriptRoot -Parent

# Ensure XAMPP PHP is on PATH
$xamppPhp = "C:\xampp\php"
if ((Test-Path "$xamppPhp\php.exe") -and ($env:PATH -notlike "*$xamppPhp*")) {
    $env:PATH = "$xamppPhp;" + $env:PATH
}

# ---------- Stop mode --------------------------------------------------------
if ($Stop) {
    Write-Host "Stopping all StoreForge service windows..." -ForegroundColor Yellow
    $titles = @(
        "SF: Laravel Backend",
        "SF: Admin Panel",
        "SF: Storefront Template",
        "SF: Honey Bee Storefront",
        "SF: MCP Browser Service",
        "SF: MCP Asset Service"
    )
    foreach ($title in $titles) {
        Get-Process powershell, pwsh -ErrorAction SilentlyContinue |
            Where-Object { $_.MainWindowTitle -like "*$title*" } |
            ForEach-Object {
                $_.Kill()
                Write-Host "  Stopped: $title" -ForegroundColor Red
            }
    }
    if ($DockerInfra) {
        Write-Host "  Stopping Docker infrastructure..." -ForegroundColor Red
        docker compose -f "$root\docker-compose.yml" stop mysql redis 2>&1 | Out-Null
    }
    Write-Host "Done." -ForegroundColor Green
    exit 0
}

# ---------- Helper: open a new PowerShell window for a service ---------------
function Start-Service {
    param(
        [string]$Title,
        [string]$WorkDir,
        [string]$Command
    )

    if (-not (Test-Path $WorkDir)) {
        Write-Host "  [SKIP] $Title - directory not found" -ForegroundColor Yellow
        return
    }

    # Write a temp script so quoting is never an issue
    $tmpFile = [System.IO.Path]::GetTempFileName() -replace '\.tmp$', '.ps1'
    $scriptContent = @"
`$host.UI.RawUI.WindowTitle = '$Title'
Set-Location '$WorkDir'
Write-Host ''
Write-Host '  $Title' -ForegroundColor Cyan
Write-Host '  Dir: $WorkDir' -ForegroundColor DarkGray
Write-Host ''
$Command
Write-Host ''
Write-Host 'Process exited. Press any key to close...' -ForegroundColor Yellow
`$null = `$host.UI.RawUI.ReadKey('NoEcho,IncludeKeyDown')
"@
    $scriptContent | Set-Content -Path $tmpFile -Encoding UTF8

    # Use pwsh (PS7) if available, fall back to powershell (PS5)
    $psExe = if (Get-Command pwsh -ErrorAction SilentlyContinue) { "pwsh" } else { "powershell" }
    Start-Process $psExe -ArgumentList @("-ExecutionPolicy", "Bypass", "-NoExit", "-File", $tmpFile) -WindowStyle Normal
    Write-Host "  [STARTED] $Title" -ForegroundColor Green
}

# ---------- Banner -----------------------------------------------------------
Write-Host ""
Write-Host "==========================================" -ForegroundColor Magenta
Write-Host "   StoreForge - Start All Services        " -ForegroundColor Magenta
Write-Host "==========================================" -ForegroundColor Magenta
Write-Host ""

# ---------- 1. Docker infrastructure (optional) ------------------------------
if ($DockerInfra) {
    Write-Host "Starting Docker infrastructure (MySQL + Redis)..." -ForegroundColor Cyan
    $dockerOk = docker info 2>&1 | Select-String -Quiet "Server Version"
    if ($dockerOk) {
        docker compose -f "$root\docker-compose.yml" up -d mysql redis
        Write-Host "  [STARTED] MySQL  >> localhost:3306" -ForegroundColor Green
        Write-Host "  [STARTED] Redis  >> localhost:6379" -ForegroundColor Green
    } else {
        Write-Host "  [SKIP] Docker Desktop is not running." -ForegroundColor Yellow
    }
    Write-Host ""
}

# ---------- 2. Laravel Backend -----------------------------------------------
if (Get-Command php -ErrorAction SilentlyContinue) {
    $backendDir = "$root\platform\backend"

    # Ensure vendor/ exists
    if (-not (Test-Path "$backendDir\vendor\autoload.php")) {
        Write-Host "  [INFO] Running composer install for backend..." -ForegroundColor DarkYellow
        Push-Location $backendDir
        composer install --no-interaction
        Pop-Location
    }

    # Ensure .env exists
    if (-not (Test-Path "$backendDir\.env")) {
        Copy-Item "$backendDir\.env.example" "$backendDir\.env"
        Write-Host "  [INFO] Created .env from .env.example" -ForegroundColor DarkYellow
        Push-Location $backendDir
        php artisan key:generate --quiet
        Pop-Location
        Write-Host "  [INFO] Generated APP_KEY" -ForegroundColor DarkYellow
    }

    Start-Service `
        -Title   "SF: Laravel Backend" `
        -WorkDir $backendDir `
        -Command "`$env:PATH = 'C:\xampp\php;' + `$env:PATH; php artisan serve --host=127.0.0.1 --port=8000"
} else {
    Write-Host "  [SKIP] Laravel Backend - php not found on PATH" -ForegroundColor Yellow
}

# ---------- 3. Admin Panel ---------------------------------------------------
Start-Service `
    -Title   "SF: Admin Panel" `
    -WorkDir "$root\platform\admin-panel" `
    -Command "npm run dev"

# ---------- 4. Storefront Template -------------------------------------------
Start-Service `
    -Title   "SF: Storefront Template" `
    -WorkDir "$root\storefront-template" `
    -Command "npm run dev -- --port 3000"

# ---------- 5. Client: Honey Bee ---------------------------------------------
Start-Service `
    -Title   "SF: Honey Bee Storefront" `
    -WorkDir "$root\client-honey-bee" `
    -Command "npm run dev -- --port 3001"

# ---------- 6. MCP Browser Service -------------------------------------------
Start-Service `
    -Title   "SF: MCP Browser Service" `
    -WorkDir "$root\platform\mcp-services\mcp-browser-service" `
    -Command "npm run dev"

# ---------- 7. MCP Asset Service ---------------------------------------------
Start-Service `
    -Title   "SF: MCP Asset Service" `
    -WorkDir "$root\platform\mcp-services\mcp-asset-service" `
    -Command "npm run dev"

# ---------- Summary ----------------------------------------------------------
Write-Host ""
Write-Host "==========================================" -ForegroundColor Magenta
Write-Host "  All services launched!" -ForegroundColor Green
Write-Host ""
Write-Host "  Laravel API         http://127.0.0.1:8000" -ForegroundColor White
Write-Host "  Admin Panel         http://localhost:5173"  -ForegroundColor White
Write-Host "  Storefront Tpl      http://localhost:3000"  -ForegroundColor White
Write-Host "  Honey Bee Client    http://localhost:3001"  -ForegroundColor White
if ($DockerInfra) {
    Write-Host "  MySQL               localhost:3306" -ForegroundColor White
    Write-Host "  Redis               localhost:6379" -ForegroundColor White
}
Write-Host ""
Write-Host "  Stop all:  .\scripts\start-all.ps1 -Stop" -ForegroundColor DarkGray
Write-Host "==========================================" -ForegroundColor Magenta
Write-Host ""
