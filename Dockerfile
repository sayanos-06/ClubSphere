FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev && docker-php-ext-install pdo_pgsql && rm -rf /var/lib/apt/lists/*
COPY . /var/www/clubsphere
RUN sed -i 's|/var/www/html|/var/www/clubsphere/public|g' /etc/apache2/sites-available/000-default.conf && printf '\nServerTokens Prod\nServerSignature Off\n<Directory /var/www/clubsphere/public>\nOptions -Indexes\nAllowOverride None\nRequire all granted\n</Directory>\n' >> /etc/apache2/apache2.conf && printf 'expose_php=Off\ndisplay_errors=Off\nlog_errors=On\nsession.use_strict_mode=1\n' > /usr/local/etc/php/conf.d/production.ini && chmod +x /var/www/clubsphere/scripts/start.sh
EXPOSE 8080
CMD ["/var/www/clubsphere/scripts/start.sh"]
