# Reservas Corferias

![PHP](https://img.shields.io/badge/PHP_8.3-777BB4?style=flat&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel_12-FF2D20?style=flat&logo=laravel&logoColor=white)
![Azure SQL](https://img.shields.io/badge/Azure_SQL-0078D4?style=flat&logo=microsoftazure&logoColor=white)
![Azure Container Apps](https://img.shields.io/badge/Azure_Container_Apps-0078D4?style=flat&logo=microsoftazure&logoColor=white)

Aplicación web para reservar escenarios de un centro de convenciones y consultar
la agenda de eventos. Los visitantes revisan disponibilidad y solicitan reservas;
el personal las gestiona desde un panel administrativo.

Reconstrucción sobre Laravel 12 de un proyecto académico originalmente escrito en
Lumen 5.8.

## Demo en vivo

**Aplicación:** [reservas-corferias.blueocean-86680030.eastus.azurecontainerapps.io](https://reservas-corferias.blueocean-86680030.eastus.azurecontainerapps.io/)

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
| Brevo (SMTP) | Notificaciones por correo | No — en desarrollo se escriben en el log; en producción usa el plan gratuito de Brevo (300 correos/día) |
| OpenStreetMap | Teselas del mapa | No (sin registro ni clave) |

## Diagrama de Arquitectura

```mermaid
flowchart TB

    subgraph Clientes["👥 Clientes"]
        Publico["🌐 Visitante<br/>Catálogo · Agenda · Reserva"]
        Cliente["👤 Cliente registrado<br/>Historial · Cancelación"]
        Admin["🛠️ Personal administrativo<br/>Panel de gestión"]
    end

    subgraph Azure["☁️ Azure Container Apps"]
        subgraph Laravel["Aplicación Laravel 12 — PHP 8.3"]
            subgraph Presentacion["Capa de Presentación"]
                Blade["Blade + Bootstrap 5<br/>Vistas públicas · admin · emails"]
                Leaflet["Leaflet + OpenStreetMap<br/>Mapa de escenarios"]
                JS["public/js/reserva.js<br/>Interactividad"]
            end

            subgraph Aplicacion["Capa de Aplicación"]
                Controllers["Http/Controllers/<br/>Público · Auth · Admin"]
                Requests["Http/Requests/<br/>Validación de formularios"]
                Middleware["Http/Middleware/<br/>Roles · cabeceras de seguridad"]
            end

            subgraph Dominio["Capa de Dominio"]
                Services["Services/<br/>Disponibilidad · Estados · QR<br/>Imágenes · Difusión"]
                Observers["Observers/<br/>Disparo de correos"]
                Listeners["Listeners/<br/>Eventos de dominio"]
                Mail["Mail/<br/>Plantillas de notificación"]
            end

            subgraph Datos["Capa de Acceso a Datos"]
                Models["Models/<br/>Escenario · Evento · Reserva<br/>Suscriptor · User"]
                Migrations["database/migrations/<br/>Esquema y seeders"]
            end
        end
    end

    subgraph AzureSQL["🗄️ Azure SQL Database"]
        DB[("Base de datos<br/>Escenarios · Eventos<br/>Reservas · Suscriptores · Usuarios")]
    end

    subgraph Externos["🔌 Servicios externos"]
        Brevo["📧 Brevo (SMTP)<br/>Notificaciones por correo"]
        OSM["🗺️ OpenStreetMap<br/>Teselas de mapa"]
        QR["🔲 bacon/bacon-qr-code<br/>Generación de QR"]
    end

    subgraph CI["🔧 CI/CD"]
        GHA["GitHub Actions<br/>Pruebas + despliegue"]
        Docker["Docker<br/>Imagen de la aplicación"]
    end

    %% ---- Flujo de datos ----
    Publico -->|HTTPS| Blade
    Cliente -->|HTTPS| Blade
    Admin -->|HTTPS| Blade
    Blade --> Controllers
    Blade --> Leaflet
    Blade --> JS
    Controllers --> Requests
    Controllers --> Middleware
    Middleware --> Services
    Services --> Models
    Services --> Mail
    Services --> QR
    Observers --> Mail
    Listeners --> Observers
    Models --> Migrations
    Models -->|Eloquent / SQL| DB
    Mail -->|SMTP| Brevo
    Leaflet -->|Teselas| OSM
    GHA -->|build & push| Docker
    Docker -.->|despliegue| Azure

    %% ---- Colores de marca (Brand Colors) ----
    classDef laravel fill:#FF2D20,stroke:#7F1610,stroke-width:2px,color:#FFFFFF,rx:12,ry:12;
    classDef php fill:#777BB4,stroke:#3A3C5C,stroke-width:2px,color:#FFFFFF,rx:12,ry:12;
    classDef sqlserver fill:#CC2927,stroke:#7F1A19,stroke-width:2px,color:#FFFFFF;
    classDef azure fill:#0078D4,stroke:#004578,stroke-width:2px,color:#FFFFFF,rx:12,ry:12;
    classDef bootstrap fill:#7952B3,stroke:#4A2F7A,stroke-width:2px,color:#FFFFFF,rx:10,ry:10;
    classDef leaflet fill:#199900,stroke:#0F5C00,stroke-width:2px,color:#FFFFFF,rx:10,ry:10;
    classDef brevo fill:#0B996E,stroke:#065C42,stroke-width:2px,color:#FFFFFF,rx:10,ry:10;
    classDef github fill:#2088FF,stroke:#0D4A99,stroke-width:2px,color:#FFFFFF,rx:10,ry:10;
    classDef docker fill:#2496ED,stroke:#0B6FC2,stroke-width:2px,color:#FFFFFF,rx:10,ry:10;
    classDef neutral fill:#F5F5F5,stroke:#CCCCCC,stroke-width:1px,color:#333333,rx:10,ry:10;

    class Publico,Cliente,Admin neutral;
    class Blade,Controllers,Requests,Middleware,Services,Observers,Listeners,Mail,Models,Migrations laravel;
    class Leaflet leaflet;
    class JS bootstrap;
    class DB sqlserver;
    class Brevo brevo;
    class OSM neutral;
    class QR neutral;
    class GHA github;
    class Docker docker;

    %% ---- Estilos de subgráficos ----
    style Clientes fill:#FAFAFA,stroke:#DDDDDD,stroke-width:1px,rx:14,ry:14;
    style Azure fill:#E1F5FE,stroke:#0078D4,stroke-width:2px,stroke-dasharray:6 4,rx:16,ry:16;
    style Laravel fill:#FFEBEE,stroke:#FF2D20,stroke-width:1px,rx:12,ry:12;
    style Presentacion fill:#FFF3E0,stroke:#FF2D20,stroke-width:1px,rx:10,ry:10;
    style Aplicacion fill:#FFE0B2,stroke:#FF2D20,stroke-width:1px,rx:10,ry:10;
    style Dominio fill:#FFCC80,stroke:#FF2D20,stroke-width:1px,rx:10,ry:10;
    style Datos fill:#FFB74D,stroke:#FF2D20,stroke-width:1px,rx:10,ry:10;
    style AzureSQL fill:#FFF0F0,stroke:#CC2927,stroke-width:2px,stroke-dasharray:6 4,rx:16,ry:16;
    style Externos fill:#E8F5E9,stroke:#199900,stroke-width:2px,stroke-dasharray:6 4,rx:16,ry:16;
    style CI fill:#E3F2FD,stroke:#2088FF,stroke-width:2px,stroke-dasharray:6 4,rx:16,ry:16;
```

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

Si el proveedor SMTP bloquea IPs no reconocidas (como Brevo), y la app se
despliega en una plataforma cloud con IP de salida variable (como Azure
Container Apps), hay que autorizar esa IP en el proveedor o desactivar el
bloqueo por IP para las claves SMTP — de lo contrario los envíos fallarán
con un error de conexión.

## Despliegue

La aplicación corre en Azure Container Apps (imagen Docker propia), con la base
de datos en Azure SQL Database (plan gratuito) y el envío de correo en producción
a través de Brevo. Las variables de `.env` se definen como secrets de la Container
App.

## Autor

**Luis Felipe Arias Carriazo**
[GitHub](https://github.com/lariasca1994) · [LinkedIn](https://linkedin.com/in/lfac1)

## Licencia

MIT