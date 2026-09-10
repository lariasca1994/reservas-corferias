FROM php:8.3-cli-bookworm

# Dependencias del sistema y repositorio de Microsoft para el driver ODBC
RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates curl gnupg2 git unzip \
        libicu-dev libzip-dev libonig-dev libxml2-dev \
        libmagickwand-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
        | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list \
        | sed 's|^deb |deb [signed-by=/usr/share/keyrings/microsoft-prod.gpg] |' \
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
EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
