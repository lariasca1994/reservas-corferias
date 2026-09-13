FROM php:8.3-cli-bookworm

# Dependencias del sistema y repositorio de Microsoft para el driver ODBC
RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates curl gnupg2 git unzip \
        libicu-dev libzip-dev libonig-dev libxml2-dev \
        libmagickwand-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
        | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && echo "deb [arch=amd64,arm64,armhf signed-by=/usr/share/keyrings/microsoft-prod.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" \
        > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18 unixodbc-dev \
    && rm -rf /var/lib/apt/lists/*

# sqlsrv y pdo_sqlsrv son las extensiones que hablan con SQL Server.
# imagick se usa para exportar el codigo QR en PNG dentro de los correos.
RUN pecl install sqlsrv pdo_sqlsrv imagick \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv imagick \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl zip mbstring bcmath gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# A diferencia del Dockerfile original (pensado para desarrollo local con
# docker-compose montando el codigo como volumen), aqui SI copiamos el
# codigo y corremos composer install, porque en Azure Container Apps no hay
# ningun volumen: la imagen tiene que traer todo listo para correr sola.
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && php artisan storage:link \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

# migrate --force corre en cada arranque del contenedor: en el primer
# arranque crea las tablas, en los siguientes no hace nada si no hay
# migraciones nuevas (Laravel las controla por su propia tabla de
# control). Asi no hay que correr un paso aparte a mano tras cada deploy.
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000
