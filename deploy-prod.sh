#!/bin/bash
set -e

# Kolory
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Przygotowanie wdrożenia produkcyjnego Motolinker API ===${NC}"
echo "Skrypt wygeneruje plik środowiskowy i zbuduje kontenery."
echo ""

# Funkcja pobierająca wartość z domyślną
ask() {
    local PROMPT=$1
    local DEFAULT=$2
    local VAR_NAME=$3
    read -p "$(echo -e ${YELLOW}$PROMPT [${DEFAULT}]: ${NC})" INPUT
    export $VAR_NAME="${INPUT:-$DEFAULT}"
}

# Generowanie losowych bezpiecznych ciągów
GEN_SALT=$(head -c 32 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c 32)
GEN_MERCURE=$(head -c 32 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c 32)
GEN_DB_PASS=$(head -c 24 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c 20)
GEN_DB_ROOT=$(head -c 24 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c 20)
GEN_JWT_PASS=$(head -c 24 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c 20)

echo -e "${GREEN}Podaj parametry, naciśnij ENTER aby użyć wartości domyślnej w [nawiasach].${NC}\n"

ask "Domena API (np. api.motolinker.pl)" "api.motolinker.pl" API_DOMAIN
ask "Domena Mercure (np. mercure.motolinker.pl)" "mercure.motolinker.pl" MERCURE_DOMAIN
ask "Domena Frontend (do CORS, np. motolinker.pl)" "motolinker.pl" FRONTEND_DOMAIN

ask "Nazwa produkcyjnej bazy danych" "motolinker_prod" MYSQL_DATABASE
ask "Użytkownik bazy danych" "motolinker_user" MYSQL_USER
ask "Hasło bazy danych" "$GEN_DB_PASS" MYSQL_PASSWORD
ask "Hasło ROOT bazy danych" "$GEN_DB_ROOT" MYSQL_ROOT_PASSWORD

ask "Sekret aplikacji Symfony (APP_SECRET)" "$GEN_SALT" APP_SECRET
ask "Sekret JWT dla Mercure" "$GEN_MERCURE" MERCURE_JWT_SECRET
ask "Hasło kluczy JWT (Lexik)" "$GEN_JWT_PASS" JWT_PASSPHRASE

echo -e "\n${BLUE}Tworzenie pliku .env.prod.local...${NC}"

cat > .env.prod.local <<EOF
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=${APP_SECRET}
DATABASE_URL="mysql://${MYSQL_USER}:${MYSQL_PASSWORD}@db:3306/${MYSQL_DATABASE}?serverVersion=8.0.32&charset=utf8mb4"
MESSENGER_TRANSPORT_DSN="doctrine://default?auto_setup=0"

JWT_PASSPHRASE="${JWT_PASSPHRASE}"
MERCURE_URL="http://mercure/.well-known/mercure"
MERCURE_PUBLIC_URL="https://${MERCURE_DOMAIN}/.well-known/mercure"
MERCURE_JWT_SECRET="${MERCURE_JWT_SECRET}"
CORS_ALLOW_ORIGIN='^https?://(${FRONTEND_DOMAIN}|${API_DOMAIN})(:[0-9]+)?$'

# Zmienne dla docker-compose
API_DOMAIN=${API_DOMAIN}
MERCURE_DOMAIN=${MERCURE_DOMAIN}
FRONTEND_DOMAIN=${FRONTEND_DOMAIN}
MYSQL_DATABASE=${MYSQL_DATABASE}
MYSQL_USER=${MYSQL_USER}
MYSQL_PASSWORD=${MYSQL_PASSWORD}
MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
EOF

echo -e "${GREEN}Zapisano .env.prod.local!${NC}"

# Docker-compose czyta m.in. z .env domyślnie przy parsowaniu ymla, 
# więc warto skopiować go również tam, żeby Docker podstawiał wartości przed budową.
cp .env.prod.local .env

echo -e "\n${BLUE}Budowanie i uruchamianie kontenerów dockera...${NC}"
# Używamy wygenerowanego pliku yaml
docker compose -f docker-compose.prod.yml up -d --build

echo -e "\n${BLUE}Oczekiwanie na wstanie bazy danych, włączanie w 15 sekund...${NC}"
sleep 15

echo -e "\n${BLUE}Generowanie kluczy JWT (jeśli nie istnieją)...${NC}"
docker exec -i motolinker_backend_prod php bin/console lexik:jwt:generate-keypair --skip-if-exists || echo -e "${YELLOW}Nie udało się wygenerować kluczy (być może już są). Omijanie...${NC}"

echo -e "\n${BLUE}Czyszczenie cache dziedziczonego...${NC}"
docker exec -i motolinker_backend_prod php bin/console cache:clear --env=prod || echo -e "${YELLOW}Omijanie czyszczenia cache...${NC}"

echo -e "\n${BLUE}Wykonywanie migracji bazy danych...${NC}"
docker exec -i motolinker_backend_prod php bin/console doctrine:migrations:migrate --no-interaction || echo -e "${RED}Błąd podczas migracji, sprawdź logi dockera!${NC}"

echo -e "\n${GREEN}=== Wdrożenie Zakończone ===${NC}"
echo -e "Wszystkie kontenery działają na środowisku PROD."
echo -e "Pamiętaj o uwzględnieniu sieci Twojego Nginx Proxy w pliku docker-compose.prod.yml (zakomentowane linie)."
