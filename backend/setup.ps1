# setup.ps1 - Self-Healing Local Environment Launcher for Cracker Demo (Backend Specific)
# Elevate Security Protocols
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

$toolsDir = Join-Path $PSScriptRoot ".tools"
$phpDir = Join-Path $toolsDir "php"
$phpZipPath = Join-Path $toolsDir "php.zip"
$composerPath = Join-Path $toolsDir "composer.phar"

# Create Tools directory
if (-not (Test-Path $toolsDir)) {
    New-Item -ItemType Directory -Path $toolsDir | Out-Null
    Write-Host "Created tools directory inside backend." -ForegroundColor Green
}

# 1. Download & Extract PHP
if (-not (Test-Path (Join-Path $phpDir "php.exe"))) {
    Write-Host "Downloading portable PHP 8.2..." -ForegroundColor Yellow
    try {
        cmd.exe /c "node download.js php"
        Write-Host "PHP download completed." -ForegroundColor Green
        
        Write-Host "Extracting PHP..." -ForegroundColor Yellow
        if (-not (Test-Path $phpDir)) {
            New-Item -ItemType Directory -Path $phpDir | Out-Null
        }
        Expand-Archive -Path $phpZipPath -DestinationPath $phpDir -Force
        Remove-Item $phpZipPath -Force
        Write-Host "PHP extracted successfully." -ForegroundColor Green
    } catch {
        Write-Error "Failed to download/extract PHP: $_"
        exit 1
    }
} else {
    Write-Host "Portable PHP already set up." -ForegroundColor Green
}

# 2. Configure php.ini
$phpIniPath = Join-Path $phpDir "php.ini"
if (-not (Test-Path $phpIniPath)) {
    Write-Host "Configuring php.ini..." -ForegroundColor Yellow
    
    $iniContent = @"
[PHP]
engine = On
short_open_tag = Off
precision = 14
output_buffering = 4096
zlib.output_compression = Off
implicit_flush = Off
unserialize_callback_func =
serialize_precision = -1
disable_functions =
disable_classes =
zend.enable_gc = On
expose_php = On
max_execution_time = 30
max_input_time = 60
memory_limit = 512M
error_reporting = E_ALL
display_errors = On
display_startup_errors = On
log_errors = On
ignore_repeated_errors = Off
ignore_repeated_source = Off
report_memleaks = On
html_errors = On
variables_order = "GPCS"
request_order = "GP"
register_argc_argv = Off
auto_globals_jit = On
post_max_size = 50M
default_mimetype = "text/html"
default_charset = "UTF-8"
doc_root =
user_dir =
enable_dl = Off
file_uploads = On
upload_max_filesize = 50M
max_file_uploads = 20
allow_url_fopen = On
allow_url_include = Off
default_socket_timeout = 60

# Extensions Directory
extension_dir = "ext"

# Core Extensions
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_sqlite
extension=sqlite3
extension=pdo_mysql
extension=mysqli


[CLI Server]
cli_server.color = On

[Date]
date.timezone = UTC
"@
    Set-Content -Path $phpIniPath -Value $iniContent
    Write-Host "php.ini configured." -ForegroundColor Green
}

# 3. Download Composer
if (-not (Test-Path $composerPath)) {
    Write-Host "Downloading Composer..." -ForegroundColor Yellow
    try {
        cmd.exe /c "node download.js composer"
        Write-Host "Composer downloaded successfully." -ForegroundColor Green
    } catch {
        Write-Error "Failed to download Composer: $_"
        exit 1
    }
} else {
    Write-Host "Composer already set up." -ForegroundColor Green
}

# 4. Create Helper Batch Files
Write-Host "Creating helper batch files in backend..." -ForegroundColor Yellow

$phpBat = @"
@echo off
"%~dp0.tools\php\php.exe" -c "%~dp0.tools\php\php.ini" %*
"@
Set-Content -Path (Join-Path $PSScriptRoot "php.bat") -Value $phpBat

$composerBat = @"
@echo off
"%~dp0.tools\php\php.exe" -c "%~dp0.tools\php\php.ini" "%~dp0.tools\composer.phar" %*
"@
Set-Content -Path (Join-Path $PSScriptRoot "composer.bat") -Value $composerBat

$artisanBat = @"
@echo off
"%~dp0.tools\php\php.exe" -c "%~dp0.tools\php\php.ini" "%~dp0artisan" %*
"@
Set-Content -Path (Join-Path $PSScriptRoot "artisan.bat") -Value $artisanBat

$runBat = @"
@echo off
echo Starting Cracker Demo local development environment...
echo Opening browser...
start http://127.0.0.1:9000
"%~dp0.tools\php\php.exe" -c "%~dp0.tools\php\php.ini" "%~dp0artisan" serve --port=9000
"@
Set-Content -Path (Join-Path $PSScriptRoot "run.bat") -Value $runBat

Write-Host "Helper batch files created in backend." -ForegroundColor Green

# 5. Initialize Laravel Project Dependencies
if (-not (Test-Path (Join-Path $PSScriptRoot "vendor"))) {
    Write-Host "Installing composer dependencies... This may take a minute..." -ForegroundColor Yellow
    cmd.exe /c "composer.bat install --no-interaction"
}

# 6. Initialize Environment file
if (-not (Test-Path (Join-Path $PSScriptRoot ".env"))) {
    Write-Host "Creating environment file..." -ForegroundColor Yellow
    if (Test-Path (Join-Path $PSScriptRoot ".env.example")) {
        Copy-Item -Path (Join-Path $PSScriptRoot ".env.example") -Destination (Join-Path $PSScriptRoot ".env")
    } else {
        $envContent = @"
APP_NAME="Cracker Demo"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Asia/Kolkata
APP_URL=http://127.0.0.1:9000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crackers2
DB_USERNAME=root
DB_PASSWORD=tiger

SESSION_DRIVER=file
SESSION_LIFETIME=120

CACHE_STORE=database
CACHE_PREFIX=

QUEUE_CONNECTION=database

"@
        Set-Content -Path (Join-Path $PSScriptRoot ".env") -Value $envContent
    }
}

# 8. Generate Application Key if not set
Write-Host "Generating application key..." -ForegroundColor Yellow
cmd.exe /c "artisan.bat key:generate"

# 9. Run Migrations & Seeders
Write-Host "Running database migrations and seeders..." -ForegroundColor Yellow
cmd.exe /c "artisan.bat migrate:fresh --seed"

Write-Host "`nSetup Completed Successfully!" -ForegroundColor Green
Write-Host "You can run the application by double-clicking 'run.bat' inside the backend directory." -ForegroundColor Green
