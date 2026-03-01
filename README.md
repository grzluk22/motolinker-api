# motolinker-api-local

Motolinker API wykorzystujące PHP (Symfony) i MySQL.

## Najważniejsze ścieżki

- `/doc` – interfejs Swagger
- `/doc.json` – dokumentacja JSON
- `/register` – rejestracja (JSON z polami `email`, `password`)
- `/login_check` – logowanie (JSON z polami `login`, `password`, zwraca JWT i refresh token)
- `/token/refresh` – odświeżenie tokenu (JSON z polami `token`, `refresh_token`)

## Środowisko developerskie z Docker

### Szybki start (skrypt)

```bash
./install-dev.sh
```

Skrypt poprosi o wartości `APP_SECRET`, `JWT_PASSPHRASE`, zbuduje obraz (opcjonalnie), uruchomi kontenery, zainstaluje zależności, migracje i wygeneruje klucze JWT.

### Ręczny start z Docker

1. Skopiuj plik środowiskowy i dostosuj sekrety (np. `APP_SECRET`):
   ```bash
   cd motolinker-api-local
   cp docker/env.docker .env.local
   ```
2. Uruchom kontenery (PHP-Apache + MySQL):
   ```bash
   make docker-up
   ```
   Aplikacja będzie dostępna pod adresem `http://localhost:8080`.
3. Zainstaluj zależności PHP, korzystając z kontenera:
   ```bash
   make install
   ```
4. Wykonaj migracje bazy danych:
   ```bash
   make migrate
   ```
5. Wygeneruj parę kluczy JWT (przechowywane w `config/jwt/`):
   ```bash
   make jwt-keys
   ```
6. Pozostałe polecenia dostępne są w pliku `Makefile` (`make docker-stop`, `make docker-down`, `make console <cmd>` itp.).

### Przydatne informacje

- Katalog projektu jest montowany w kontenerze, więc zmiany w kodzie są widoczne natychmiast.
- Volumne `db_data` przechowuje dane MySQL; aby wyczyścić bazę, wykonaj `make docker-down` i usuń wolumen (`docker volume rm motolinker-api-local_db_data`).
- Komenda `make console doctrine:database:create` pozwala uruchomić dowolne polecenie `bin/console`.

## Ręczna instalacja (opcjonalnie, bez Docker)

1. `git clone` repozytorium i przejdź do katalogu projektu.
2. `composer install`
3. Włącz `mod_rewrite` według instrukcji: <https://gcore.com/learning/how-enable-apache-mod-rewrite/>
4. Skonfiguruj połączenie z bazą w `.env`.
5. `php bin/console doctrine:database:create`
6. `php bin/console doctrine:migrations:migrate`
7. `php bin/console lexik:jwt:generate-keypair`

## Wdrożenie na środowisko produkcyjne (Produkcja)

Aplikacja posiada zautomatyzowany skrypt do wdrożenia na produkcji przy użyciu Docker Compose i Nginx (jako reverse proxy z opcjonalną obsługą SSL).

### Wymagania wstępne (przygotowanie środowiska)
Przed uruchomieniem skryptu na serwerze produkcyjnym (np. VPS), upewnij się, że spełnione są następujące warunki:
1. Zainstalowany **Docker** oraz wtyczka **Docker Compose** (`docker compose`).
2. Zainstalowane narzędzie **Certbot** (jeśli planujesz automatyczne generowanie bezpłatnych certyfikatów SSL za pomocą Let's Encrypt, np. `apt install certbot`).
3. Wydelegowane domeny/subdomeny (np. dla API, serwera Mercure, Frontendu) na adres IP twojego serwera (ustawione rekordy DNS typu A).
4. Porty **80** i **443** na serwerze są otwarte w zaporze sieciowej (firewall) i nie są zablokowane ani używane przez inne procesy (np. zainstalowany niezależnie serwer Apache/Nginx).
5. Sklonowane repozytorium na serwerze.

### 🚀 Uruchomienie wdrożenia
Aby wdrożyć aplikację na produkcji, na serwerze przejdź do katalogu projektu i wykonaj kroki:

1. Nadaj uprawnienia do wykonywania skryptowi instalacyjnemu:
   ```bash
   chmod +x deploy-prod.sh
   ```
2. Uruchom skrypt instalacyjny:
   ```bash
   ./deploy-prod.sh
   ```
3. Postępuj zgodnie z instrukcjami wyświetlanymi w terminalu. Skrypt poprosi Cię o:
   - Podanie domen (API, Mercure, Frontend).
   - Skonfigurowanie haseł i sekretów dla JWT, Mercure oraz bazy danych (Domyślnie skrypt podpowie losowe, bezpieczne tokeny).
   - Podjęcie decyzji dotyczącej generowania certyfikatów SSL (HTTPS).

Skrypt automatycznie wygeneruje plik konfiguracyjny `.env.prod.local`, uruchomi kontenery w trybie produkcyjnym, wygeneruje klucze JWT, zresetuje system cache`u aplikacji i dokona automatycznych migracji bazy danych.

## Najczęstsze problemy

1. Podczas generowania pary kluczy JWT może pojawić się komunikat `error:80000003:system library::No such process`.
   - Zainstaluj OpenSSL.
   - Wygeneruj klucz prywatny:
     ```bash
     openssl genrsa -out config/jwt/private.pem -aes256 4096
     ```
     Podaj hasło takie, jak w zmiennej `JWT_PASSPHRASE`.
   - Wygeneruj klucz publiczny:
     ```bash
     openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
     ```

Ten projekt jest w fazie rozwoju. Przy jego tworzeniu korzystałem z pomocy narzędzi AI, natomiast większośc planowania i implementacji jest moja. Wszelkie sugestie i uwagi są mile widziane. 
