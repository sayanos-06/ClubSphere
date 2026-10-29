#!/bin/sh
set -eu
php /var/www/clubsphere/scripts/setup.php
php /var/www/clubsphere/scripts/check.php
sed -i "s/Listen 80/Listen ${PORT:-8080}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT:-8080}>/" /etc/apache2/sites-available/000-default.conf
exec apache2-foreground
