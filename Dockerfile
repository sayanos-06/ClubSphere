FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql \
    && php -m | grep -E 'pdo_pgsql|pgsql' \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/ClubSphere

RUN sed -ri 's!/var/www/html!/var/www/ClubSphere/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's!/var/www/!/var/www/ClubSphere/public!g' /etc/apache2/apache2.conf \
    && chown -R www-data:www-data /var/www/ClubSphere

EXPOSE 80
