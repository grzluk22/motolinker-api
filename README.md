# motolinker-api-local
MotolinkerApi przy użyciu PHP i MySQL, bez konteneryzacji docker <br>
<h1>Najważniejsze ścieżki:</h1>
<h3>/doc - Dokumentacja Swagger</h3>
<h3>/doc.json - Dokumantacja JSON</h3>
<h3>/register - przyjmuje dane w postaci JSON z polami email oraz password i służy do rejestracji</h3>
<h3>/api_login_check - przyjmuje dane w postaci JSON z polami email oraz password i służy do zalogowania się i zwraca JWT Token</h3>
<h3>/api_refresh_token - przyjmuje dane w postaci JSON z polami token oraz token_refresh</h3>
Wymaga PHP

<h1>Instrukcja Instalacji: </h1>
<ol>
<li>Pobierz repozytorium za pomocą git clone</li>
<li>W środku wykonaj instalacje "composer install"</li>
<li>Aktywuj mod_rewrite według tej instrukcji: https://gcore.com/learning/how-enable-apache-mod-rewrite/ </li>
<li>Edytuj dane dostępowe do bazy danych w pliku .env</li>
<li>Utwórz bazę danych poleceniem php bin/console doctrine:database:create</li>
<li>Wykonaj migracje za pomocą polecenia php bin/console doctrine:migrations:migrate</li>
<li>Wygeneruj klucze JWT za pomocą polecenia: php bin/console lexik:jwt:generate-keypair</li>
</ol>


