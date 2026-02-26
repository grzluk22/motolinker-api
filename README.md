# Motolinker API

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
2. Zbuduj i uruchom kontenery środowiska produkcyjnego:
   ```bash
   docker compose -f compose.yaml up -d --build
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

### Środowisko produkcyjne

1. Upewnij się, że DocumentRoot serwera (na przykład Nginx czy Apache) kieruje na wewnętrzny katalog `public`.
2. Zdefiniuj w `.env.local` docelowe zmienne bezpieczeństwa oraz popraw parametry połączeniowe. Koniecznie zmień wartość `APP_ENV=prod`.
3. Dokonaj instalacji wyłącznie bibliotek docelowych:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
4. Zmigruj bazę i wygeneruj asymetryczne klucze JWT:
   ```bash
   php bin/console doctrine:migrations:migrate --no-interaction
   php bin/console lexik:jwt:generate-keypair
   ```
