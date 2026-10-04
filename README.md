# Reservas de Recinto Ferial

<p>
  <a href="https://reservas-corferias.blueocean-86680030.eastus.azurecontainerapps.io/"><img src="docs/demo-badge.svg" alt="Abrir la demo en vivo" height="32"></a>
  <a href="https://frontend-nine-topaz-99.vercel.app"><img src="https://portafolio-status.onrender.com/api/status/reservas-corferias/badge.svg" alt="Estado en vivo del proyecto" height="32"></a>
  <a href="https://d4i3vsgw7xwmh.cloudfront.net"><img src="https://portafolio-status.onrender.com/api/status/reservas-corferias/qa-badge.svg" alt="Fecha y resultado de la última prueba E2E" height="32"></a>
</p>

![PHP](https://img.shields.io/badge/PHP_8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel_12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Azure SQL](https://img.shields.io/badge/Azure_SQL-0078D4?style=for-the-badge&logo=microsoftazure&logoColor=white)
![Azure Container Apps](https://img.shields.io/badge/Azure_Container_Apps-0078D4?style=for-the-badge&logo=microsoftazure&logoColor=white)

Aplicación web para reservar escenarios de un centro de convenciones y consultar
la agenda de eventos. Los visitantes revisan disponibilidad y solicitan reservas;
el personal las gestiona desde un panel administrativo.

Reconstrucción sobre Laravel 12 de un proyecto académico originalmente escrito en
Lumen 5.8.

### En pocas palabras

- **Qué hace:** cualquiera puede ver los escenarios del recinto (salones,
  pabellones), su capacidad, tarifa y fechas ocupadas, y pedir una reserva. Al
  hacerlo recibe un comprobante con código único y QR.
- **Para el personal:** un panel para confirmar o cancelar reservas y
  administrar escenarios, eventos y usuarios; cada cambio de estado avisa por
  correo.
- **Cómo probarlo:** entra a la [demo](https://reservas-corferias.blueocean-86680030.eastus.azurecontainerapps.io/),
  elige un escenario y solicita una reserva. Para correrlo en tu equipo, ve a
  [Instalación](#instalación) y [Puesta en marcha](#puesta-en-marcha), o usa
  [Docker](#docker).

## Demo en vivo

**Aplicación:** [abrir la demo en vivo](https://reservas-corferias.blueocean-86680030.eastus.azurecontainerapps.io/)

## Funcionalidades

**Sitio público**
- Catálogo de escenarios con capacidad, tarifa, galería y fechas ocupadas
- Agenda de eventos
- Solicitud de reserva con validación de disponibilidad
- Comprobante con código único y QR
- Suscripción a la agenda por correo

**Cuenta de cliente**
- Registro con verificación de correo
- Historial de reservas propias
- Cancelación de reservas propias

**Panel de gestión**
- Tablero con métricas
- Reservas: filtros, confirmación y cancelación
- CRUD de escenarios, eventos y usuarios
- Envío de avisos de eventos a la lista de suscriptores

## Stack

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.3, Laravel 12 |
| Base de datos | SQL Server (Azure SQL) |
| Frontend | Blade, Bootstrap 5, CSS propio |
| Mapa | Leaflet + OpenStreetMap |
| Códigos QR | bacon/bacon-qr-code |
| Pruebas | PHPUnit 11 sobre SQLite en memoria |
| CI | GitHub Actions |

## Conexiones externas

| Servicio | Uso | Obligatorio |
|---|---|---|
| SQL Server | Persistencia | Sí |
| Brevo (API HTTP) | Notificaciones por correo | No — en desarrollo se escriben en el log; en producción se envían por Brevo |
| OpenStreetMap | Teselas del mapa | No (sin registro ni clave) |

## Arquitectura

<p align="center">
  <img src="docs/arquitectura.svg" alt="Diagrama de arquitectura: Laravel 12 en Azure Container Apps con sitio público, cuentas, panel de gestión, servicios de dominio y observers; SQL Server en Azure SQL, correos con Brevo y mapa con OpenStreetMap" width="100%">
</p>

- **Azure Container Apps** corre la aplicación Laravel 12 en una imagen Docker
  propia: el sitio público, las cuentas de cliente y el panel de gestión usan
  los mismos servicios de dominio (disponibilidad, estados y QR).
- **Azure SQL Database** guarda escenarios, eventos, reservas, suscriptores y
  usuarios.
- Cada cambio de estado de una reserva dispara un **observer** que encola el
  correo; **Brevo** lo entrega por su API HTTP.
- El mapa del recinto usa **Leaflet + OpenStreetMap**, sin clave de API.

## Estructura

```
app/
├── Console/Commands/    Comandos de diagnóstico
├── Exceptions/          Excepciones de dominio
├── Http/
│   ├── Controllers/     Público, Auth y Admin
│   ├── Middleware/      Control de roles y cabeceras de seguridad
│   └── Requests/        Validación de formularios
├── Listeners/
├── Mail/                Plantillas de notificación
├── Models/              Escenario, Evento, Reserva, Suscriptor, User
├── Observers/           Disparo de correos por cambio de estado
├── Providers/
└── Services/            Disponibilidad, estados, QR, imágenes, difusión
database/
├── factories/
├── migrations/
└── seeders/
resources/views/
├── layout/              Plantillas base pública y de panel
├── components/          Componentes Blade reutilizables
├── admin/               Vistas del panel
└── emails/              Plantillas de correo
public/
├── css/estilos.css
├── js/reserva.js
└── images/
tests/
├── Unit/                Lógica de disponibilidad
└── Feature/             Flujo HTTP, panel, seguridad y vistas
```

## Requisitos

- PHP 8.3 con las extensiones `sqlsrv`, `pdo_sqlsrv`, `pdo_sqlite`, `sqlite3`,
  `mbstring`, `openssl`, `curl`, `fileinfo`, `zip`, `intl` y `gd`
- Composer 2
- ODBC Driver 17 o 18 para SQL Server
- Una instancia de SQL Server accesible

`pdo_sqlite` y `sqlite3` son necesarias aunque no se use SQLite en producción: la
suite de pruebas corre sobre SQLite en memoria.

## Instalación

```bash
git clone https://github.com/lariasca1994/reservas-corferias.git
cd reservas-corferias
composer install
cp .env.example .env
php artisan key:generate
```

Editar `.env` con los datos de conexión, el servidor de correo y las contraseñas
de las cuentas iniciales. La plantilla indica qué variable corresponde a cada cosa.

Si la base está en un servicio en la nube, hay que habilitar el acceso desde la IP
del equipo en sus reglas de red.

## Puesta en marcha

```bash
php artisan db:ping        # verifica la conexión
php artisan storage:link   # habilita las imágenes subidas
php artisan migrate --seed # crea las tablas y carga datos de ejemplo
php artisan serve
```

La aplicación queda en `http://localhost:8000` y el panel en `/ingresar`.

En Windows, `storage:link` crea un enlace simbólico y puede requerir una consola
con permisos de administrador.

### Diagnóstico

```bash
php artisan proyecto:revisar
```

Comprueba imágenes, enlace de almacenamiento, datos cargados y cuentas del panel,
e indica qué comando ejecutar si algo falta.

## Docker

Alternativa que evita instalar PHP y el driver ODBC:

```bash
docker compose build
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose up -d
docker compose exec app php artisan migrate --seed
```

## Pruebas

No requieren base de datos ni conexión a internet.

```bash
php artisan test
vendor/bin/pint --test
```

## Correos

Con `MAIL_MAILER=log` los mensajes se escriben en `storage/logs/laravel.log` sin
enviarse. Para verlos renderizados en local se puede usar Mailpit apuntando el
mailer a SMTP en `127.0.0.1:1025`.

Con `QUEUE_CONNECTION=database` los correos se encolan y salen al ejecutar
`php artisan queue:work`. Con `sync` salen de inmediato.

En producción se usa `MAIL_MAILER=brevo`: los correos salen por la API HTTP de
Brevo con una sola variable, `BREVO_API_KEY`, sin usuario ni contraseña SMTP
(`app/Mail/Transport/BrevoApiTransport.php`). El `MAIL_FROM_ADDRESS` debe ser un
remitente verificado en Brevo, o la API rechaza el envío.

Si en Brevo está activado el bloqueo de IPs no autorizadas, también aplica a
las claves API: con la IP de salida variable de Azure Container Apps hay que
desactivarlo o autorizar esas IPs.

## Despliegue

La aplicación corre en Azure Container Apps (imagen Docker propia), con la base
de datos en Azure SQL Database y el envío de correo en producción
a través de la API de Brevo. Las variables de `.env` se definen como variables y
secrets de la Container App.

El despliegue es continuo: cada push a `main` ejecuta pruebas y estilo en
GitHub Actions y, si pasan, publica la imagen en GitHub Container Registry
(paquete público) y crea una revisión nueva de la Container App
etiquetada con el commit. Antes de actualizarla se verifica que la imagen se
pueda descargar sin credenciales, así la demo nunca queda caída. GitHub se
autentica en Azure por OIDC, sin contraseñas guardadas en el repositorio.

## Autor

**Luis Felipe Arias Carriazo**
[GitHub](https://github.com/lariasca1994) · [LinkedIn](https://linkedin.com/in/lfac1)

## Licencia

MIT