#!/bin/bash
set -e

cd /var/www/html

# Composer-Abhängigkeiten installieren (falls noch nicht vorhanden)
if [ ! -d "vendor" ]; then
    echo "==> Installiere Composer-Abhängigkeiten..."
    composer install --no-interaction --optimize-autoloader
fi

# Warte auf die Datenbank
echo "==> Warte auf MySQL..."
until php -r "try { new PDO('mysql:host=database;dbname=einkaufslisten', 'app_user', 'app_password'); echo 'OK'; } catch (Exception \$e) { exit(1); }" 2>/dev/null; do
    echo "   MySQL noch nicht bereit, warte 2 Sekunden..."
    sleep 2
done
echo ""
echo "==> MySQL ist bereit!"

# Migrations ausführen
echo "==> Führe Datenbank-Migrations aus..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true

# Cache leeren
echo "==> Leere den Cache..."
php bin/console cache:clear --no-interaction || true

# Berechtigungen sicherstellen
chown -R www-data:www-data /var/www/html/var 2>/dev/null || true

echo "==> Anwendung bereit unter http://localhost:8080"

# Original Command ausführen (PHP-FPM)
exec "$@"
