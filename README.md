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

## Contenido

1. [Presentación](#1-presentación)
2. [Estructura del proyecto](#2-estructura-del-proyecto)
3. [Arquitectura](#3-arquitectura)
4. [Plataformas y su función](#4-plataformas-y-su-función)
5. [Cómo usar la plataforma](#5-cómo-usar-la-plataforma)
6. [Instalación para pruebas](#6-instalación-para-pruebas)
7. [Autor y licencia](#7-autor-y-licencia)

---

## 1. Presentación

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
  [Instalación para pruebas](#6-instalación-para-pruebas).

### Demo en vivo

**Aplicación:** [abrir la demo en vivo](https://reservas-corferias.blueocean-86680030.eastus.azurecontainerapps.io/)

### Funcionalidades

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

### Stack

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.3, Laravel 12 |
| Base de datos | SQL Server (Azure SQL) |
| Frontend | Blade, Bootstrap 5, CSS propio |
| Mapa | Leaflet + OpenStreetMap |
| Códigos QR | bacon/bacon-qr-code |
| Pruebas | PHPUnit 11 sobre SQLite en memoria |
| CI | GitHub Actions |

---

## 2. Estructura del proyecto

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

---

## 3. Arquitectura

<p align="center">
  <img src="docs/arquitectura.svg" alt="Diagrama de arquitectura: Laravel 12 en Azure Container Apps con sitio público, cuentas, panel de gestión, servicios de dominio y observers; SQL Server en Azure SQL, correos con Brevo y mapa con OpenStreetMap" width="100%">
</p>

- La aplicación Laravel 12 corre en una imagen Docker propia: el sitio público,
  las cuentas de cliente y el panel de gestión usan los mismos servicios de
  dominio (disponibilidad, estados y QR).
- Cada cambio de estado de una reserva dispara un **observer** que encola el
  correo.

### Correos

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

---

## 4. Plataformas y su función

| Plataforma | Función en el proyecto |
|---|---|
| ![Azure Container Apps](https://img.shields.io/badge/Azure_Container_Apps-0078D4?style=for-the-badge&logo=microsoftazure&logoColor=white) | Corre la aplicación Laravel 12 en una imagen Docker propia: sitio público, cuentas de cliente y panel de gestión. |
| ![Azure SQL](https://img.shields.io/badge/Azure_SQL-0078D4?style=for-the-badge&logo=microsoftazure&logoColor=white) | Guarda escenarios, eventos, reservas, suscriptores y usuarios. Obligatoria. |
| ![Brevo](https://img.shields.io/badge/Brevo-0B996E?style=for-the-badge&logo=brevo&logoColor=white) | Entrega por su API HTTP los avisos por correo de cada cambio de estado. Opcional: en desarrollo los correos se escriben en el log. |
| ![OpenStreetMap](https://img.shields.io/badge/OpenStreetMap-7EBC6F?style=for-the-badge&logo=openstreetmap&logoColor=white) | Teselas del mapa del recinto con Leaflet, sin registro ni clave de API. |
| ![GitHub Actions](https://img.shields.io/badge/GitHub_Actions-181717?style=for-the-badge&logo=githubactions&logoColor=white) | Ejecuta pruebas y estilo, publica la imagen en GitHub Container Registry y crea la nueva revisión de la Container App. |
| ![qa-evidencia](https://img.shields.io/badge/qa--evidencia-2EAD33?style=for-the-badge&logo=playwright&logoColor=white) | Prueba la demo automáticamente y publica la evidencia. |

---

## 5. Cómo usar la plataforma

### 5.1 Explorar sin cuenta

1. En el menú, abre **Escenarios**: cada tarjeta muestra capacidad y tarifa. Al
   entrar a un escenario ves su galería, **Qué incluye**, el mapa y las fechas
   ocupadas.
2. En **Eventos** consultas los **Eventos programados**.
3. Para recibir la agenda por correo, escribe tu **Correo electrónico** al pie de
   la página y pulsa **Avisarme**.

### 5.2 Crear una cuenta de cliente

1. Pulsa **Crear cuenta**.
2. Completa **Nombre completo**, **Correo electrónico**, **Teléfono**,
   **Contraseña** y **Confirmar**.
3. Pulsa **Crear cuenta** y confirma tu correo con el enlace de verificación.

### 5.3 Solicitar una reserva

1. Desde un escenario, abre el formulario de reserva y completa:

   | Campo | Dato |
   |---|---|
   | Fecha de inicio / Fecha final | Fechas del evento; se valida la disponibilidad |
   | Nombre de quien reserva | Responsable de la reserva |
   | Correo electrónico | Donde llegan los avisos |
   | Teléfono | Contacto |
   | Observaciones | Detalles adicionales (opcional) |

2. Pulsa **Enviar solicitud**. Recibes un comprobante con código único y QR, que
   puedes **Imprimir**.
3. Si iniciaste sesión, la reserva aparece en **Mis reservas**, desde donde
   puedes cancelarla.

### 5.4 Panel de gestión (personal)

1. Entra en **Ingresar** (`/ingresar`) con una cuenta del personal.
2. **Resumen** muestra métricas y las **Últimas solicitudes**.
3. **Reservas:** filtra, confirma o cancela; cada cambio de estado avisa por correo.
4. **Escenarios** y **Eventos:** crea, edita o elimina; desde un evento puedes
   enviar el aviso a la lista de suscriptores.
5. **Usuarios** (solo administrador): gestiona las cuentas del personal.

### 5.5 Cuentas y roles

Los clientes crean su propia cuenta desde **Crear cuenta**. El panel requiere una
cuenta del personal, cuyo papel es **gestionar los recursos** del recinto: reservas,
escenarios y eventos.

| Rol | Qué hace |
|---|---|
| Cliente | Crea su cuenta, solicita reservas y gestiona las suyas |
| Operador | Gestiona reservas, escenarios y eventos en el panel |
| Administrador | Lo mismo que el operador, y además gestiona las cuentas del personal |

**Cómo se crean las cuentas del personal:** las iniciales (administrador y operador)
las crea el seeder con `php artisan migrate --seed`, usando `ADMIN_EMAIL`,
`ADMIN_PASSWORD` y `OPERADOR_PASSWORD` del `.env`. Las siguientes las crea el
administrador desde **Usuarios**.

---

## 6. Instalación para pruebas

### Requisitos

- PHP 8.3 con las extensiones `sqlsrv`, `pdo_sqlsrv`, `pdo_sqlite`, `sqlite3`,
  `mbstring`, `openssl`, `curl`, `fileinfo`, `zip`, `intl` y `gd`
- Composer 2
- ODBC Driver 17 o 18 para SQL Server
- Una instancia de SQL Server accesible

`pdo_sqlite` y `sqlite3` son necesarias aunque no se use SQLite en producción: la
suite de pruebas corre sobre SQLite en memoria.

### Instalación

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

### Puesta en marcha

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

### Docker

Alternativa que evita instalar PHP y el driver ODBC:

```bash
docker compose build
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose up -d
docker compose exec app php artisan migrate --seed
```

### Pruebas

No requieren base de datos ni conexión a internet.

```bash
php artisan test
vendor/bin/pint --test
```

### Despliegue

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

---

## 7. Autor y licencia

**Luis Felipe Arias Carriazo**
[GitHub](https://github.com/lariasca1994) · [LinkedIn](https://linkedin.com/in/lfac1)

Licencia: MIT.
