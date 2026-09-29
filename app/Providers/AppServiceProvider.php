<?php

namespace App\Providers;

use App\Listeners\AsociarReservasPrevias;
use App\Mail\Transport\BrevoApiTransport;
use App\Models\Reserva;
use App\Observers\ReservaObserver;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Reserva::observe(ReservaObserver::class);

        // Mailer "brevo": envio por la API HTTP con una clave, sin usuario ni
        // contrasena SMTP. Ver BrevoApiTransport.
        Mail::extend('brevo', fn (array $config) => new BrevoApiTransport(
            $this->app->make(HttpFactory::class),
            (string) ($config['key'] ?? config('services.brevo.key')),
        ));

        // Las reservas previas se asocian al verificar el correo, no al
        // registrarse: ver AsociarReservasPrevias.
        Event::listen(Verified::class, AsociarReservasPrevias::class);

        // Fuera de desarrollo, forzar HTTPS en las URL generadas: el QR del
        // comprobante lleva una URL absoluta y debe apuntar a https.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Falla temprano ante accesos a relaciones no cargadas y ante
        // asignacion masiva de atributos no declarados.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }
}
