$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$ScriptDir = $PSScriptRoot
$ProjectRoot = $ScriptDir
$EnvFile = Join-Path $ProjectRoot ".env.local"
$ComposeFile = Join-Path $ProjectRoot "docker\docker-compose.dev.yml"

function Info {
    param ([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor Blue
}

function Warn {
    param ([string]$Message)
    Write-Host "[WARN] $Message" -ForegroundColor Yellow
}

function Error {
    param ([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

trap {
    Error "Instalacja przerwana (kod bledu: $($_.Exception.Message))."
    exit 1
}

# Sprawdzenie docker
if (-not (Get-Command "docker" -ErrorAction SilentlyContinue)) {
    Error "Docker jest wymagany."
    exit 1
}

# Sprawdzenie docker compose
try {
    docker compose version | Out-Null
}
catch {
    Error "Wymagany Docker Compose (docker compose)."
    exit 1
}

# Sprawdzenie python
if (Get-Command "python" -ErrorAction SilentlyContinue) {
    $PythonCmd = "python"
}
elseif (Get-Command "python3" -ErrorAction SilentlyContinue) {
    $PythonCmd = "python3"
}
else {
    Error "Wymagany jest python (lub python3) do aktualizacji plikow konfiguracyjnych."
    exit 1
}

Set-Location -Path $ProjectRoot
Info "Instalacja w katalogu $ProjectRoot"

if (-not (Test-Path -Path $EnvFile)) {
    Info "Tworzenie pliku .env.local na podstawie docker/env.docker"
    if (Test-Path "docker\env.docker") {
        Copy-Item (Join-Path "docker" "env.docker") -Destination $EnvFile
    }
    else {
        # Fallback try forward slash logic if path building fails, or just warn
        Copy-Item "docker/env.docker" -Destination $EnvFile
    }
}
else {
    Info "Znaleziono istniejacy plik .env.local (zostanie zaktualizowany)."
}

function Set-EnvVar {
    param (
        [string]$Key,
        [string]$Value
    )
    # Przekazanie zmiennej srodowiskowej VALUE do procesu pythona
    $env:VALUE = $Value
    try {
        & $PythonCmd "$ProjectRoot/scripts/_set_env_var.py" "$EnvFile" "$Key"
    }
    finally {
        $env:VALUE = $null
    }
}

function Generate-RandomSecret {
    if (Get-Command "openssl" -ErrorAction SilentlyContinue) {
        return (openssl rand -hex 32)
    }
    else {
        $Code = "import secrets; print(secrets.token_hex(32))"
        # Uzycie -c w pythonie
        return (& $PythonCmd -c $Code)
    }
}

if (-not (Test-Path "config/jwt")) {
    New-Item -ItemType Directory -Path "config/jwt" -Force | Out-Null
}

$InputSecret = Read-Host "APP_SECRET (ENTER aby wygenerowac losowy)"
if ([string]::IsNullOrWhiteSpace($InputSecret)) {
    $InputSecret = Generate-RandomSecret
    Info "Wygenerowano APP_SECRET."
}
Set-EnvVar "APP_SECRET" $InputSecret

Write-Host "JWT_PASSPHRASE (ENTER aby pozostawic domyslne 'changeit'): " -NoNewline
# Uzycie AsSecureString aby ukryc wpisywanie hasla, podobnie jak -s w bashu
$SecurePass = Read-Host -AsSecureString
Write-Host ""

if ($SecurePass.Length -eq 0) {
    $InputPassphrase = ""
}
else {
    $BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($SecurePass)
    $InputPassphrase = [System.Runtime.InteropServices.Marshal]::PtrToStringBSTR($BSTR)
    [System.Runtime.InteropServices.Marshal]::ZeroFreeBSTR($BSTR)
}

if ([string]::IsNullOrWhiteSpace($InputPassphrase)) {
    $InputPassphrase = "changeit"
    Info "Pozostawiono domyslne JWT_PASSPHRASE."
}
Set-EnvVar "JWT_PASSPHRASE" $InputPassphrase

$RebuildChoice = Read-Host "Czy zbudowac obraz kontenera (y/N)?"
if ($RebuildChoice -match "^[Yy]$") {
    Info "Budowanie obrazu aplikacji..."
    docker compose -f $ComposeFile build app
}
else {
    Warn "Pomineto budowanie obrazu."
}

Info "Uruchamianie kontenerow (docker compose up -d)..."
docker compose -f $ComposeFile up -d

Info "Instalacja zaleznosci (composer install)..."
docker compose -f $ComposeFile run --rm app composer install

$MigrateChoice = Read-Host "Czy uruchomic migracje bazy danych (y/N)?"
if ($MigrateChoice -match "^[Yy]$") {
    Info "Wykonywanie migracji..."
    docker compose -f $ComposeFile run --rm app php bin/console doctrine:migrations:migrate --no-interaction
}
else {
    Warn "Migracje pominiete."
}

$JwtChoice = Read-Host "Czy wygenerowac klucze JWT (y/N)?"
if ($JwtChoice -match "^[Yy]$") {
    Info "Generowanie kluczy JWT..."
    docker compose -f $ComposeFile run --rm app php bin/console lexik:jwt:generate-keypair --overwrite
}
else {
    Warn "Generowanie kluczy JWT pominiete."
}

Info "Instalacja zakonczona. Aplikacja powinna byc dostepna pod adresem http://localhost:8080"
Warn "Aby zatrzymac kontenery uzyj: docker compose -f docker/docker-compose.dev.yml stop"