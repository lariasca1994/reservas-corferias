FROM php:8.3-cli-bookworm

# Dependencias del sistema y repositorio de Microsoft para el driver ODBC
RUN apt-get update && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS ca-certificates curl gnupg2 git unzip \
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
# Se compilan desde su codigo en GitHub (versiones fijas) y no con
# "pecl install": cuando pecl.php.net no responde, la imagen no se podia
# construir y el despliegue fallaba.
ARG MSPHPSQL_VERSION=5.13.3
ARG IMAGICK_VERSION=3.8.1
RUN mkdir -p /tmp/ext && cd /tmp/ext \
    && curl -fsSL "https://github.com/microsoft/msphpsql/archive/refs/tags/v${MSPHPSQL_VERSION}.tar.gz" | tar xz \
    && for ext in sqlsrv pdo_sqlsrv; do \
         cp -r "msphpsql-${MSPHPSQL_VERSION}/source/shared" "msphpsql-${MSPHPSQL_VERSION}/source/${ext}/shared" \
         && (cd "msphpsql-${MSPHPSQL_VERSION}/source/${ext}" && phpize && ./configure && make -j"$(nproc)" && make install) || exit 1; \
       done \
    && curl -fsSL "https://github.com/Imagick/imagick/archive/refs/tags/${IMAGICK_VERSION}.tar.gz" | tar xz \
    && (cd "imagick-${IMAGICK_VERSION}" && phpize && ./configure && make -j"$(nproc)" && make install) \
    && cd / && rm -rf /tmp/ext \
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

# En un contenedor el log debe ir a la salida estandar: un archivo dentro
# de storage/logs no se ve en los logs de Azure y se pierde en cada revision.
ENV LOG_CHANNEL=stderr     LOG_LEVEL=warning

EXPOSE 8000

# Pooling de conexiones del driver ODBC: abrir una conexion a Azure SQL
# (TLS + autenticacion) cuesta varios viajes hasta la base, que esta en
# otra region. Con Pooling=Yes el proceso reutiliza la conexion entre
# peticiones en vez de abrir una nueva cada vez.
RUN sed -i '1i [ODBC]\nPooling=Yes\n' /etc/odbcinst.ini \
    && sed -i '/^\[ODBC Driver 18 for SQL Server\]/a CPTimeout=120' /etc/odbcinst.ini

# migrate --force solo corre si MIGRAR_AL_ARRANCAR=1. La base es Azure SQL
# sin servidor con cuota gratuita: si cada arranque del contenedor (que
# escala a cero) la consultara, cualquier visita o revision de estado la
# despertaria y la cuota del mes se agotaria a mitad de mes. El workflow de
# despliegue activa la variable, despierta la app una vez para migrar y la
# vuelve a apagar (ver .github/workflows/ci.yml).
# Si la base no responde (pausada o sin cuota gratuita), el contenedor
# arranca igual y muestra la pagina de mantenimiento en vez de reiniciarse
# en bucle.
CMD if [ "$MIGRAR_AL_ARRANCAR" = "1" ]; then php artisan migrate --force || echo "migrate omitido: la base no responde"; fi; exec php artisan serve --host=0.0.0.0 --port=8000
