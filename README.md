# motolinker-api

Backend API dla katalogu produktów branży motoryzacyjnej. 

## Stack Technologiczny

- **Framework:** Symfony 7.4
- **Język:** PHP 8.2
- **Baza danych:** MySQL
- **Autentykacja:** JWT (LexikJWTAuthenticationBundle)
- **Komunikacja w czasie rzeczywistym:** Symfony Mercure
- **Kolejkowanie zadań:** Symfony Messenger
- **Dokumentacja API:** Swagger/OpenAPI (NelmioApiDocBundle)

## Instalacja ze wsparciem Docker

Wymagane zainstalowane narzędzia Docker oraz Docker Compose.

### Środowisko deweloperskie

W projekcie znajdują się gotowe skrypty przygotowujące środowisko. Konfigurują one pliki środowiskowe, uruchamiają kontenery, instalują pakiety i generują klucze dostępowe.

Dla systemów Linux / macOS:
```bash
./install-dev.sh
```

Dla systemu Windows (PowerShell):
```powershell
.\install-dev.ps1
```

Aplikacja deweloperska po zakończeniu działania skryptu będzie wystawiona pod adresem http://localhost:8080.

### Środowisko produkcyjne

1. Skopiuj podstawowy plik środowiskowy i skonfiguruj odpowiednie i docelowe hasła oraz ustaw `APP_ENV=prod`:
   ```bash
   cp docker/env.docker .env.local
   ```
2. Przeglądnij również plik `docker/docker-compose.prod.yml` i dostosuj go do swoich potrzeb. Pamiętaj że aplikacja buduje kontener ale nie obsługuje domeny, więc musisz przekierować ruch na odpowiedni port. Zbuduj i uruchom kontenery środowiska produkcyjnego:
   ```bash
   docker compose -f docker/docker-compose.prod.yml up -d --build
   ```
3. Zainstaluj zależności omijając systemy deweloperskie i optymalizując pliki:
   ```bash
   docker compose exec app composer install --no-dev --optimize-autoloader
   ```
4. Przeprowadź migracje bazy danych:
   ```bash
   docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
   ```
5. Wygeneruj parę kluczy JWT niezbędnych do logowania i zabezpieczeń tras:
   ```bash
   docker compose exec app php bin/console lexik:jwt:generate-keypair
   ```

## Instalacja bez wsparcia Docker (Lokalnie)

Wymagane zainstalowane narzędzia PHP (wersja >= 8.2), Composer oraz serwer bazy danych MySQL.

### Środowisko deweloperskie

1. Wejdź do katalogu projektu i pobierz zależności:
   ```bash
   composer install
   ```
2. Skopiuj plik ze zmiennymi i skonfiguruj parametry połączenia z bazą, upewnij się czy serwer www obsługuje zasady mod_rewrite (dla Apache):
   ```bash
   cp docker/env.docker .env.local
   ```
3. Przygotuj bazę danych i załaduj schematy migracji:
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```
4. Utwórz klucze autentykacji dla JWT:
   ```bash
   php bin/console lexik:jwt:generate-keypair
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
