# motolinker-api-local
MotolinkerApi przy użyciu PHP i MySQL, bez konteneryzacji docker

Wymaga PHP

<h1>Instrukcja Instalacji: </h1>
<ol>
<li>Pobierz repozytorium za pomocą git clone</li>
<li>W środku wykonaj instalacje "composer install"</li>
<li>Aktywuj mod_rewrite za pomocą komendy "sudo a2enmod rewrite"</li>
<li>W pliku /etc/apache2/sites-enabled/000-default.conf dodaj linijke:</li>
    <Directory /var/www/html>
    AllowOverride All
    </Directory>
</ol>


