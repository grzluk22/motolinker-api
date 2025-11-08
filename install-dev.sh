#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="${SCRIPT_DIR}"
ENV_FILE="${PROJECT_ROOT}/.env.local"
COMPOSE_FILE="${PROJECT_ROOT}/docker/docker-compose.dev.yml"
COMPOSE_CMD=("docker" "compose" "-f" "${COMPOSE_FILE}")

info() { printf '\033[1;34m[INFO]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[WARN]\033[0m %s\n' "$*"; }
error() { printf '\033[1;31m[ERROR]\033[0m %s\n' "$*"; }

trap 'error "Instalacja przerwana (kod wyjścia $?)."' ERR

command -v docker >/dev/null 2>&1 || { error "Docker jest wymagany."; exit 1; }
docker compose version >/dev/null 2>&1 || { error "Wymagany Docker Compose (docker compose)."; exit 1; }

if ! command -v python3 >/dev/null 2>&1; then
    error "Wymagany jest python3 do aktualizacji plików konfiguracyjnych."
    exit 1
fi

cd "${PROJECT_ROOT}"
info "Instalacja w katalogu ${PROJECT_ROOT}"

if [ ! -f "${ENV_FILE}" ]; then
    info "Tworzenie pliku .env.local na podstawie docker/env.docker"
    cp docker/env.docker "${ENV_FILE}"
else
    info "Znaleziono istniejący plik .env.local (zostanie zaktualizowany)."
fi

set_env_var() {
    local key="$1"
    local value="$2"
    VALUE="${value}" python3 "${PROJECT_ROOT}/scripts/_set_env_var.py" "${ENV_FILE}" "${key}"
}

generate_random_secret() {
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -hex 32
    else
        python3 - <<'PY'
import secrets
print(secrets.token_hex(32))
PY
    fi
}

mkdir -p config/jwt

read -rp "APP_SECRET (ENTER aby wygenerować losowy): " input_secret
if [ -z "${input_secret}" ]; then
    input_secret="$(generate_random_secret)"
    info "Wygenerowano APP_SECRET."
fi
set_env_var "APP_SECRET" "${input_secret}"

read -rsp "JWT_PASSPHRASE (ENTER aby pozostawić domyślne 'changeit'): " input_passphrase
echo ""
if [ -z "${input_passphrase}" ]; then
    input_passphrase="changeit"
    info "Pozostawiono domyślne JWT_PASSPHRASE."
fi
set_env_var "JWT_PASSPHRASE" "${input_passphrase}"

read -rp "Czy zbudować obraz kontenera (y/N)? " rebuild_choice
if [[ "${rebuild_choice}" =~ ^[Yy]$ ]]; then
    info "Budowanie obrazu aplikacji..."
    "${COMPOSE_CMD[@]}" build app
else
    warn "Pominięto budowanie obrazu."
fi

info "Uruchamianie kontenerów (docker compose up -d)..."
"${COMPOSE_CMD[@]}" up -d

info "Instalacja zależności (composer install)..."
"${COMPOSE_CMD[@]}" run --rm app composer install

read -rp "Czy uruchomić migracje bazy danych (y/N)? " migrate_choice
if [[ "${migrate_choice}" =~ ^[Yy]$ ]]; then
    info "Wykonywanie migracji..."
    "${COMPOSE_CMD[@]}" exec app php bin/console doctrine:migrations:migrate --no-interaction
else
    warn "Migracje pominięte."
fi

read -rp "Czy wygenerować klucze JWT (y/N)? " jwt_choice
if [[ "${jwt_choice}" =~ ^[Yy]$ ]]; then
    info "Generowanie kluczy JWT..."
    "${COMPOSE_CMD[@]}" exec app php bin/console lexik:jwt:generate-keypair --overwrite --skip-if-exists
else
    warn "Generowanie kluczy JWT pominięte."
fi

info "Instalacja zakończona. Aplikacja powinna być dostępna pod adresem http://localhost:8080"
warn "Aby zatrzymać kontenery użyj: make docker-stop lub docker compose -f docker/docker-compose.dev.yml stop"

