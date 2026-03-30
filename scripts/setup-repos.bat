@echo off
REM Setup Multi-Repository Structure for E-Commerce Platform
REM Windows Batch Script Version

setlocal enabledelayedexpansion

echo ===========================================
echo E-Commerce Platform Repository Setup
echo ===========================================
echo.
echo This will create:
echo   1. platform\ (Backend + Admin Panel)
echo   2. storefront-template\ (Base template)
echo.

set /p CONTINUE="Continue? (y/n): "
if /i not "%CONTINUE%"=="y" (
    echo Setup cancelled.
    exit /b 1
)

REM Get the root directory (parent of scripts folder)
set "ROOT_DIR=%~dp0.."
cd /d "%ROOT_DIR%"

REM ============================================
REM Step 1: Initialize Platform Repository
REM ============================================

echo.
echo [Step 1] Initializing Platform Repository...
echo.

set "PLATFORM_DIR=%ROOT_DIR%\platform"
if not exist "%PLATFORM_DIR%" mkdir "%PLATFORM_DIR%"
cd /d "%PLATFORM_DIR%"

REM Initialize git if not already initialized
if not exist ".git" (
    git init
    echo [OK] Git initialized in platform\
) else (
    echo [OK] Git already initialized in platform\
)

REM Create .gitignore
(
echo # Backend (Laravel^)
echo /backend/.env
echo /backend/.env.backup
echo /backend/vendor/
echo /backend/node_modules/
echo /backend/storage/*.key
echo /backend/storage/logs/
echo /backend/bootstrap/cache/
echo /backend/public/hot
echo.
echo # Admin Panel (React^)
echo /admin-panel/node_modules/
echo /admin-panel/.env
echo /admin-panel/.env.local
echo /admin-panel/dist/
echo /admin-panel/build/
echo.
echo # IDE
echo .vscode/
echo .idea/
echo.
echo # OS
echo .DS_Store
echo Thumbs.db
) > .gitignore

echo [OK] Created .gitignore

REM Create README
(
echo # E-Commerce Platform Core
echo.
echo Multi-tenant e-commerce platform with shared backend and admin panel.
echo.
echo ## Components
echo.
echo - **backend/** - Laravel 11 REST API
echo - **admin-panel/** - React 18 Admin Dashboard
echo.
echo ## Quick Start
echo.
echo ### Backend Setup
echo ```bash
echo cd backend
echo composer install
echo copy .env.example .env
echo php artisan key:generate
echo php artisan migrate
echo php artisan serve
echo ```
echo.
echo ### Admin Panel Setup
echo ```bash
echo cd admin-panel
echo npm install
echo npm run dev
echo ```
echo.
echo ## Documentation
echo.
echo See `/docs` folder in root for complete documentation.
) > README.md

echo [OK] Created README.md

REM Initial commit
git add .
git diff-index --quiet HEAD 2>nul || git commit -m "chore: initialize platform repository structure"

echo [OK] Platform repository initialized

REM ============================================
REM Step 2: Initialize Storefront Template
REM ============================================

echo.
echo [Step 2] Initializing Storefront Template...
echo.

set "TEMPLATE_DIR=%ROOT_DIR%\storefront-template"
if not exist "%TEMPLATE_DIR%" mkdir "%TEMPLATE_DIR%"
cd /d "%TEMPLATE_DIR%"

REM Initialize git if not already initialized
if not exist ".git" (
    git init
    echo [OK] Git initialized in storefront-template\
) else (
    echo [OK] Git already initialized in storefront-template\
)

REM Create .gitignore
(
echo # Dependencies
echo node_modules/
echo.
echo # Next.js
echo /.next/
echo /out/
echo.
echo # Environment
echo .env
echo .env.local
echo .env*.local
echo.
echo # Debug
echo npm-debug.log*
echo.
echo # IDE
echo .vscode/
echo .idea/
echo.
echo # OS
echo .DS_Store
echo Thumbs.db
) > .gitignore

echo [OK] Created .gitignore

REM Create README
(
echo # E-Commerce Storefront Template
echo.
echo Base template for client storefronts using Next.js 14.
echo.
echo ## Quick Start for New Client
echo.
echo 1. Clone this template
echo 2. Configure .env.local
echo 3. Customize theme/config.ts
echo 4. Run: npm install ^&^& npm run dev
echo.
echo See CUSTOMIZATION.md for detailed guide.
) > README.md

echo [OK] Created README.md

REM Create .env.template
(
echo # Store Configuration
echo NEXT_PUBLIC_STORE_ID=
echo NEXT_PUBLIC_STORE_NAME=
echo.
echo # API Configuration
echo NEXT_PUBLIC_API_URL=https://api.yourplatform.com/v1
echo.
echo # Payment Gateway
echo NEXT_PUBLIC_STRIPE_KEY=
) > .env.template

echo [OK] Created .env.template

REM Initial commit
git add .
git diff-index --quiet HEAD 2>nul || git commit -m "chore: initialize storefront template"

echo [OK] Storefront template initialized

REM ============================================
REM Step 3: Create Helper Scripts
REM ============================================

echo.
echo [Step 3] Creating helper scripts...
echo.

set "SCRIPTS_DIR=%ROOT_DIR%\scripts"
if not exist "%SCRIPTS_DIR%" mkdir "%SCRIPTS_DIR%"

REM Create client store creation script (Windows batch version)
(
echo @echo off
echo REM Create New Client Storefront
echo REM Usage: create-client-store.bat "Client Name" store_id
echo.
echo setlocal enabledelayedexpansion
echo.
echo if "%%~1"=="" goto usage
echo if "%%~2"=="" goto usage
echo.
echo set "CLIENT_NAME=%%~1"
echo set "STORE_ID=%%~2"
echo.
echo REM Convert client name to folder name (lowercase, replace spaces with hyphens^)
echo set "FOLDER_NAME=client-!CLIENT_NAME: =-!"
echo set "FOLDER_NAME=!FOLDER_NAME:~0,50!"
echo call :lowercase FOLDER_NAME
echo.
echo set "ROOT_DIR=%%~dp0.."
echo set "TEMPLATE_DIR=!ROOT_DIR!\storefront-template"
echo set "CLIENT_DIR=!ROOT_DIR!\!FOLDER_NAME!"
echo.
echo echo ============================================
echo echo Creating Client Storefront
echo echo ============================================
echo echo.
echo echo Client: !CLIENT_NAME!
echo echo Store ID: !STORE_ID!
echo echo Directory: !FOLDER_NAME!
echo echo.
echo.
echo REM Check if template exists
echo if not exist "!TEMPLATE_DIR!" (
echo     echo [ERROR] Template not found at !TEMPLATE_DIR!
echo     exit /b 1
echo ^)
echo.
echo REM Check if client directory already exists
echo if exist "!CLIENT_DIR!" (
echo     echo [ERROR] !CLIENT_DIR! already exists
echo     exit /b 1
echo ^)
echo.
echo REM Copy template
echo echo Copying template...
echo xcopy "!TEMPLATE_DIR!" "!CLIENT_DIR!\" /E /I /Q
echo cd /d "!CLIENT_DIR!"
echo.
echo REM Remove template git history
echo if exist ".git" rd /s /q ".git"
echo.
echo REM Initialize fresh git repo
echo git init
echo.
echo REM Create .env.local
echo echo Creating .env.local...
echo ^(
echo echo # Store Configuration
echo echo NEXT_PUBLIC_STORE_ID=!STORE_ID!
echo echo NEXT_PUBLIC_STORE_NAME=!CLIENT_NAME!
echo echo.
echo echo # API Configuration
echo echo NEXT_PUBLIC_API_URL=https://api.yourplatform.com/v1
echo echo.
echo echo # Payment Gateway
echo echo NEXT_PUBLIC_STRIPE_KEY=
echo ^) ^> .env.local
echo.
echo REM Initial commit
echo git add .
echo git commit -m "chore: initialize !CLIENT_NAME! storefront"
echo.
echo echo.
echo echo [SUCCESS] Client storefront created!
echo echo.
echo echo Location: !CLIENT_DIR!
echo echo.
echo echo Next steps:
echo echo   1. cd !FOLDER_NAME!
echo echo   2. Edit theme\config.ts
echo echo   3. Add Stripe key to .env.local
echo echo   4. npm install
echo echo   5. npm run dev
echo echo.
echo goto :eof
echo.
echo :usage
echo echo Usage: %%~nx0 "Client Name" store_id
echo echo Example: %%~nx0 "Fashion Store" 1
echo exit /b 1
echo.
echo :lowercase
echo for %%L in ^(A B C D E F G H I J K L M N O P Q R S T U V W X Y Z^) do set "%%~1=!%%~1:%%L=%%L!"
echo goto :eof
) > "%SCRIPTS_DIR%\create-client-store.bat"

echo [OK] Created create-client-store.bat

REM ============================================
REM Summary
REM ============================================

echo.
echo ===========================================
echo [SUCCESS] Setup Complete!
echo ===========================================
echo.
echo Repository structure created:
echo   [OK] platform\ (Backend + Admin^)
echo   [OK] storefront-template\ (Template^)
echo   [OK] scripts\ (Helper scripts^)
echo.
echo Next steps:
echo.
echo 1. Set up Backend:
echo    cd platform
echo    composer create-project laravel/laravel backend
echo.
echo 2. Set up Admin Panel:
echo    cd platform
echo    npm create vite@latest admin-panel -- --template react-ts
echo.
echo 3. Set up Storefront Template:
echo    cd storefront-template
echo    npx create-next-app@latest . --typescript --tailwind --app
echo.
echo 4. Create your first client storefront:
echo    scripts\create-client-store.bat "Test Store" 1
echo.
echo See docs\15-repository-structure.md for detailed guide
echo.

pause
