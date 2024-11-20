# motolinker-api-local
MotolinkerApi przy użyciu PHP i MySQL, bez konteneryzacji docker <br>
<h1>Najważniejsze ścieżki:</h1>
<h5>/doc - Dokumentacja Swagger</h5>
<h5>/doc.json - Dokumantacja JSON</h5>
<h5>/register - przyjmuje dane w postaci JSON z polami email oraz password i służy do rejestracji</h5>
<h5>/login_check - przyjmuje dane w postaci JSON z polami login oraz password i służy do zalogowania się i zwraca JWT Token oraz refresh token</h5>
<h5>/token/refresh - przyjmuje dane w postaci JSON z polami token oraz refresh_token (takie same które zwraca endpoint /login_check </h5>

<h1>Instrukcja Instalacji: </h1>
<ol>
<li>Pobierz repozytorium za pomocą git clone</li>
<li>W środku wykonaj instalacje poleceniem "composer install"</li>
<li>Aktywuj mod_rewrite według tej instrukcji: https://gcore.com/learning/how-enable-apache-mod-rewrite/ </li>
<li>Edytuj dane dostępowe do bazy danych w pliku .env</li>
<li>Utwórz bazę danych poleceniem php "bin/console doctrine:database:create"</li>
<li>Wykonaj migracje za pomocą polecenia "php bin/console doctrine:migrations:migrate"</li>
<li>Wygeneruj klucze JWT za pomocą polecenia: "php bin/console lexik:jwt:generate-keypair"</li>
</ol>

<h1>Najczestsze problemy:</h1>
<ol>
  <li>Przy generowaniu pary kluczy JWT może wyskoczyć komunikat "error:80000003:system library::No such process"<br>
  Należy wtedy wygeneroować klucze ręcznie:<br>
    -Zainstaluj OpenSSL<br>
    -Wygeneruj klucz prywatny poleceniem "openssl genrsa -out config/jwt/private.pem -aes256 4096" oraz podaj passphrase (musi być zgodny z tym podanym w pliku .env)<br>
    -Wygeneruj klucz publiczny poleceniem "openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem"
  </li>
</ol>


