# Docker Helper Scripts for Windows PowerShell
# Convenient shortcuts for common Docker commands

function Show-Help {
    Write-Host "==================================================" -ForegroundColor Cyan
    Write-Host "   E-Commerce Platform - Docker Helper Commands  " -ForegroundColor Cyan
    Write-Host "==================================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Usage: .\docker-helper.ps1 <command>" -ForegroundColor White
    Write-Host ""
    Write-Host "Commands:" -ForegroundColor Yellow
    Write-Host "  setup      - Initial project setup" -ForegroundColor Green
    Write-Host "  start      - Start all containers" -ForegroundColor Green
    Write-Host "  stop       - Stop all containers" -ForegroundColor Green
    Write-Host "  restart    - Restart all containers" -ForegroundColor Green
    Write-Host "  logs       - View container logs" -ForegroundColor Green
    Write-Host "  status     - Show container status" -ForegroundColor Green
    Write-Host ""
    Write-Host "Backend:" -ForegroundColor Yellow
    Write-Host "  migrate    - Run database migrations" -ForegroundColor Green
    Write-Host "  seed       - Seed database with test data" -ForegroundColor Green
    Write-Host "  fresh      - Fresh migrate with seed" -ForegroundColor Green
    Write-Host "  tinker     - Access Laravel Tinker" -ForegroundColor Green
    Write-Host "  test       - Run backend tests" -ForegroundColor Green
    Write-Host "  artisan    - Run artisan command (pass command as argument)" -ForegroundColor Green
    Write-Host ""
    Write-Host "Database:" -ForegroundColor Yellow
    Write-Host "  mysql      - Access MySQL CLI" -ForegroundColor Green
    Write-Host "  backup     - Backup database" -ForegroundColor Green
    Write-Host "  redis      - Access Redis CLI" -ForegroundColor Green
    Write-Host ""
    Write-Host "Cleanup:" -ForegroundColor Yellow
    Write-Host "  clean      - Stop and remove all containers" -ForegroundColor Green
    Write-Host "  clean-all  - Remove containers and volumes (WARNING: deletes data)" -ForegroundColor Red
    Write-Host "  rebuild    - Rebuild all containers from scratch" -ForegroundColor Green
    Write-Host ""
}

param(
    [Parameter(Position=0)]
    [string]$Command,
    
    [Parameter(Position=1, ValueFromRemainingArguments=$true)]
    [string[]]$Args
)

switch ($Command) {
    "setup" {
        Write-Host "Setting up Docker environment..." -ForegroundColor Cyan
        
        # Copy environment file if it doesn't exist
        if (-not (Test-Path ".env")) {
            Write-Host "Copying .env.docker to .env..." -ForegroundColor Yellow
            Copy-Item .env.docker .env
        }
        
        # Start containers
        Write-Host "Starting containers..." -ForegroundColor Yellow
        docker-compose up -d
        
        # Wait for MySQL to be ready
        Write-Host "Waiting for MySQL to be ready..." -ForegroundColor Yellow
        Start-Sleep -Seconds 10
        
        # Generate app key
        Write-Host "Generating Laravel app key..." -ForegroundColor Yellow
        docker-compose exec -T backend php artisan key:generate
        
        # Run migrations
        Write-Host "Running migrations..." -ForegroundColor Yellow
        docker-compose exec -T backend php artisan migrate
        
        # Seed database
        Write-Host "Seeding database..." -ForegroundColor Yellow
        docker-compose exec -T backend php artisan db:seed
        
        Write-Host "Setup complete! ✅" -ForegroundColor Green
        Write-Host "Backend: http://localhost:8000" -ForegroundColor Cyan
        Write-Host "Admin: http://localhost:5173" -ForegroundColor Cyan
    }
    
    "start" {
        Write-Host "Starting all containers..." -ForegroundColor Cyan
        docker-compose up -d
        Write-Host "Containers started! ✅" -ForegroundColor Green
    }
    
    "stop" {
        Write-Host "Stopping all containers..." -ForegroundColor Cyan
        docker-compose down
        Write-Host "Containers stopped! ✅" -ForegroundColor Green
    }
    
    "restart" {
        Write-Host "Restarting all containers..." -ForegroundColor Cyan
        docker-compose restart
        Write-Host "Containers restarted! ✅" -ForegroundColor Green
    }
    
    "logs" {
        docker-compose logs -f
    }
    
    "status" {
        docker-compose ps
    }
    
    "migrate" {
        Write-Host "Running database migrations..." -ForegroundColor Cyan
        docker-compose exec backend php artisan migrate
    }
    
    "seed" {
        Write-Host "Seeding database..." -ForegroundColor Cyan
        docker-compose exec backend php artisan db:seed
    }
    
    "fresh" {
        Write-Host "Fresh migrating with seed..." -ForegroundColor Cyan
        docker-compose exec backend php artisan migrate:fresh --seed
    }
    
    "tinker" {
        docker-compose exec backend php artisan tinker
    }
    
    "test" {
        Write-Host "Running backend tests..." -ForegroundColor Cyan
        docker-compose exec backend php artisan test
    }
    
    "artisan" {
        if ($Args.Count -eq 0) {
            Write-Host "Usage: .\docker-helper.ps1 artisan <command>" -ForegroundColor Red
            exit 1
        }
        docker-compose exec backend php artisan @Args
    }
    
    "mysql" {
        Write-Host "Connecting to MySQL..." -ForegroundColor Cyan
        Write-Host "Password: root_secret" -ForegroundColor Yellow
        docker-compose exec mysql mysql -u root -p
    }
    
    "backup" {
        $BackupFile = "backup-$(Get-Date -Format 'yyyy-MM-dd-HHmmss').sql"
        Write-Host "Backing up database to $BackupFile..." -ForegroundColor Cyan
        docker-compose exec -T mysql mysqldump -u root -proot_secret ecommerce_platform > $BackupFile
        Write-Host "Backup complete! ✅" -ForegroundColor Green
    }
    
    "redis" {
        Write-Host "Connecting to Redis..." -ForegroundColor Cyan
        docker-compose exec redis redis-cli -a redis_secret
    }
    
    "clean" {
        Write-Host "Stopping and removing containers..." -ForegroundColor Cyan
        docker-compose down
        Write-Host "Cleanup complete! ✅" -ForegroundColor Green
    }
    
    "clean-all" {
        Write-Host "WARNING: This will delete all data!" -ForegroundColor Red
        $Confirm = Read-Host "Are you sure? (yes/no)"
        if ($Confirm -eq "yes") {
            Write-Host "Removing containers and volumes..." -ForegroundColor Cyan
            docker-compose down -v
            Write-Host "Full cleanup complete! ✅" -ForegroundColor Green
        } else {
            Write-Host "Cancelled." -ForegroundColor Yellow
        }
    }
    
    "rebuild" {
        Write-Host "Rebuilding all containers..." -ForegroundColor Cyan
        docker-compose down
        docker-compose build --no-cache
        docker-compose up -d
        Write-Host "Rebuild complete! ✅" -ForegroundColor Green
    }
    
    default {
        if ($Command) {
            Write-Host "Unknown command: $Command" -ForegroundColor Red
            Write-Host ""
        }
        Show-Help
    }
}
