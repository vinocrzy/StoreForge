# Create New Client Storefront
# Usage: .\scripts\create-client-store.ps1 "Client Name" store_id

param(
    [Parameter(Mandatory=$true, Position=0)]
    [string]$ClientName,
    
    [Parameter(Mandatory=$true, Position=1)]
    [int]$StoreId
)

# Convert client name to folder name (lowercase, hyphen-separated)
$FolderName = "client-" + ($ClientName.ToLower() -replace '\s+', '-' -replace '[^a-z0-9-]', '')

# Navigate to project root
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RootDir = Split-Path -Parent $ScriptDir
Set-Location $RootDir

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "Creating New Client Storefront" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Client Name:  $ClientName" -ForegroundColor White
Write-Host "Folder Name:  $FolderName" -ForegroundColor White
Write-Host "Store ID:     $StoreId" -ForegroundColor White
Write-Host ""

# Check if storefront template exists
if (-not (Test-Path "storefront-template")) {
    Write-Host "ERROR: storefront-template not found!" -ForegroundColor Red
    Write-Host "Please ensure the template exists at: $RootDir\storefront-template" -ForegroundColor Yellow
    exit 1
}

# Check if client folder already exists
if (Test-Path $FolderName) {
    Write-Host "ERROR: $FolderName already exists!" -ForegroundColor Red
    Write-Host "Please choose a different client name or delete the existing folder." -ForegroundColor Yellow
    exit 1
}

Write-Host "Step 1/7: Copying storefront template..." -ForegroundColor Green
# Copy the entire template directory
Copy-Item -Path "storefront-template" -Destination $FolderName -Recurse -Force

Write-Host "Step 2/7: Removing template git history..." -ForegroundColor Green
Set-Location $FolderName
if (Test-Path ".git") {
    Remove-Item -Path ".git" -Recurse -Force
}

Write-Host "Step 3/7: Initializing new git repository..." -ForegroundColor Green
git init | Out-Null

Write-Host "Step 4/7: Creating .env.local configuration..." -ForegroundColor Green
$EnvContent = @"
# Store Configuration
NEXT_PUBLIC_STORE_ID=$StoreId
NEXT_PUBLIC_STORE_NAME=$ClientName
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1

# Optional: Override in production
# NEXT_PUBLIC_API_URL=https://api.yourplatform.com/v1
# NEXT_PUBLIC_STRIPE_KEY=pk_live_xxxxx
# NEXT_PUBLIC_GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX
"@

$EnvContent | Out-File -FilePath ".env.local" -Encoding UTF8

Write-Host "Step 5/7: Updating package.json..." -ForegroundColor Green
$PackageJson = Get-Content "package.json" -Raw | ConvertFrom-Json
$PackageJson.name = $FolderName
$PackageJson.description = "$ClientName storefront"
$PackageJson | ConvertTo-Json -Depth 10 | Out-File "package.json" -Encoding UTF8

Write-Host "Step 6/7: Creating initial git commit..." -ForegroundColor Green
git add . | Out-Null
git commit -m "chore: initialize $ClientName storefront (Store ID: $StoreId)" | Out-Null

Write-Host "Step 7/7: Creating development branches..." -ForegroundColor Green
git branch development | Out-Null
git branch staging | Out-Null

Set-Location $RootDir

Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "SUCCESS! Client storefront created: $FolderName" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Navigate to the storefront:" -ForegroundColor White
Write-Host "   cd $FolderName" -ForegroundColor Yellow
Write-Host ""
Write-Host "2. Customize the theme:" -ForegroundColor White
Write-Host "   - Edit src/config/theme.ts for branding (colors, fonts, logo)" -ForegroundColor Gray
Write-Host "   - Update public/favicon.ico" -ForegroundColor Gray
Write-Host "   - Add logo images to public/images/" -ForegroundColor Gray
Write-Host ""
Write-Host "3. Install dependencies:" -ForegroundColor White
Write-Host "   npm install" -ForegroundColor Yellow
Write-Host ""
Write-Host "4. Start development server:" -ForegroundColor White
Write-Host "   npm run dev" -ForegroundColor Yellow
Write-Host ""
Write-Host "5. Test the storefront:" -ForegroundColor White
Write-Host "   Open http://localhost:3000" -ForegroundColor Gray
Write-Host ""
Write-Host "6. Setup remote repository (when ready):" -ForegroundColor White
Write-Host "   git remote add origin <client-repo-url>" -ForegroundColor Yellow
Write-Host "   git push -u origin main" -ForegroundColor Yellow
Write-Host "   git push origin development staging" -ForegroundColor Yellow
Write-Host ""
Write-Host "7. Before production deployment:" -ForegroundColor White
Write-Host "   - Update NEXT_PUBLIC_API_URL in .env.local" -ForegroundColor Gray
Write-Host "   - Add Stripe key for payments" -ForegroundColor Gray
Write-Host "   - Configure analytics tracking" -ForegroundColor Gray
Write-Host "   - Build and test: npm run build && npm start" -ForegroundColor Gray
Write-Host ""
Write-Host "Configuration file: $FolderName\.env.local" -ForegroundColor Cyan
Write-Host ""
