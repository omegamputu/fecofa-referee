# AVANT (probable)
# FROM php:8.2-fpm

# APRES
FROM php:8.3-fpm

# 1. Paquets système
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libonig-dev libzip-dev libpng-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring bcmath zip gd \
    && rm -rf /var/lib/apt/lists/*

# Optionnel mais utile pour éviter l’avertissement "dubious ownership"
RUN git config --global --add safe.directory /var/www/html

# 2. Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 3. Dossier de travail
WORKDIR /var/www/html

# 4. Copier le code de l'application
COPY . .

# 5. Installer les dépendances PHP
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# 6. Données d'écriture Laravel

RUN chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache


CMD ["php-fpm"]
