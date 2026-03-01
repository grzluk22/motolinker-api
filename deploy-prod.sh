#!/bin/bash
set -e

# Kolory
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Funkcja pobierająca wartość z domyślną
ask() {
    local PROMPT=$1
    local DEFAULT=$2
    local VAR_NAME=$3
    read -p "$(echo -e ${YELLOW}$PROMPT [${DEFAULT}]: ${NC})" INPUT
    export $VAR_NAME="${INPUT:-$DEFAULT}"
}

echo -e "${BLUE}=== Przygotowanie wdrożenia produkcyjnego Motolinker API ===${NC}"
echo "Skrypt wygeneruje plik środowiskowy i zbuduje kontenery."
echo ""

# Sprawdz czy istnieje plik .env.prod.local
if [ -f ".env.prod.local" ]; then
    # Zapytaj czy usunąć
    read -p "$(echo -e "${YELLOW}Plik .env.prod.local już istnieje. Czy chcesz go usunąć i skonfigurować na nowo? Ostrzeżenie: Spowoduje to całkowity RESET BAZY DANYCH! (t/n): ${NC}")" INPUT
    if [ "$INPUT" = "t" ]; then
        echo -e "${YELLOW}Zatrzymywanie kontenerów i czyszczenie wolumenów danych...${NC}"
        docker compose -f docker-compose.prod.yml down -v || echo "Nie znaleziono kontenerów do zamknięcia."
        rm .env.prod.local
    else
        echo -e "${GREEN}Kontynuuję z istniejącym plikiem .env.prod.local${NC}"
    fi
fi

if [ ! -f ".env.prod.local" ]; then
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

    ask "Nazwa podpiętej sieci dockera dla proxy" "audiora_audiora-network" PROXY_NETWORK_NAME
    ask "Nazwa kontenera dockera z Nginx" "audiora-nginx" PROXY_CONTAINER_NAME

    echo -e "\n${BLUE}Tworzenie pliku .env.prod.local...${NC}"

    cat > .env.prod.local <<EOF
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=${APP_SECRET}
DATABASE_URL="mysql://${MYSQL_USER}:${MYSQL_PASSWORD}@db:3306/${MYSQL_DATABASE}?serverVersion=8.0.32&charset=utf8mb4"
MESSENGER_TRANSPORT_DSN="doctrine://default?auto_setup=0"

JWT_SECRET_KEY="%kernel.project_dir%/config/jwt/private.pem"
JWT_PUBLIC_KEY="%kernel.project_dir%/config/jwt/public.pem"
JWT_PASSPHRASE="${JWT_PASSPHRASE}"
DEBUG_DELAY_MS=0
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
PROXY_NETWORK_NAME=${PROXY_NETWORK_NAME}
PROXY_CONTAINER_NAME=${PROXY_CONTAINER_NAME}
EOF

    echo -e "${GREEN}Zapisano .env.prod.local!${NC}"
fi

# ZAWSZE wczytujemy zmienne ze skonfigurowanego pliku do środowiska skryptu
set -a
source .env.prod.local
set +a

# Docker-compose czyta m.in. z .env domyślnie przy parsowaniu ymla, 
# więc zawsze kopiujemy aktualny konfig na wszelki wypadek
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

echo -e "\n${BLUE}Generowanie dynamicznych plików konfiguracyjnych Nginx...${NC}"

cat > ${API_DOMAIN}.conf <<EOF
server {
    listen 80;
    server_name ${API_DOMAIN};

    location / {
        proxy_pass http://motolinker_backend_prod:80;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        
        proxy_read_timeout 60s;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
    }
}
EOF

cat > ${MERCURE_DOMAIN}.conf <<EOF
server {
    listen 80;
    server_name ${MERCURE_DOMAIN};

    location / {
        proxy_pass http://motolinker_mercure_prod:80;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        
        proxy_set_header Connection '';
        proxy_http_version 1.1;
        chunked_transfer_encoding off;
        proxy_buffering off;
        proxy_cache off;
    }
}
EOF

if [ -z "${API_DOMAIN}" ] || [ -z "${PROXY_CONTAINER_NAME}" ]; then
    echo -e "${RED}Błąd krytyczny skryptu: Zmienne sieciowe nie załadowały się. Przerywam kopiowanie do proxy.${NC}"
    exit 1
fi

echo -e "\n${BLUE}Wgrywanie konfiguracji do kontenera ${PROXY_CONTAINER_NAME}...${NC}"
docker cp ${API_DOMAIN}.conf ${PROXY_CONTAINER_NAME}:/etc/nginx/conf.d/${API_DOMAIN}.conf
docker cp ${MERCURE_DOMAIN}.conf ${PROXY_CONTAINER_NAME}:/etc/nginx/conf.d/${MERCURE_DOMAIN}.conf

echo -e "\n${BLUE}Restartowanie proxy...${NC}"
docker exec ${PROXY_CONTAINER_NAME} nginx -s reload || echo -e "${RED}Nie udało się przeładować Nginx. Upewnij się, że nazwa kontenera (${PROXY_CONTAINER_NAME}) jest poprawna.${NC}"

rm ${API_DOMAIN}.conf ${MERCURE_DOMAIN}.conf

echo -e "\n${GREEN}=== Wdrożenie Zakończone ===${NC}"
echo -e "Aplikacja podłączona pod sieć: ${PROXY_NETWORK_NAME}"
echo -e "Klucze konfiguracyjne zostały zaktualizowane w ${PROXY_CONTAINER_NAME}!"
