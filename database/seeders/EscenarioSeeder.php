<?php

namespace Database\Seeders;

use App\Models\Escenario;
use Illuminate\Database\Seeder;

/**
 * Datos rescatados de ScenariosController (version Lumen 2019).
 *
 * Los cuatro escenarios y el detalle del "Alfa" estaban escritos como
 * arreglos PHP dentro del controlador. Los precios y capacidades de los
 * otros tres no existian en el original: se derivaron de forma coherente
 * a partir del unico ejemplo disponible.
 */
class EscenarioSeeder extends Seeder
{
    public function run(): void
    {
        $escenarios = [
            [
                'slug'             => 'alfa',
                'nombre'           => 'Escenario Alfa',
                'resumen'          => 'Economico y versatil, con grandes salones de exposicion y presentacion.',
                'descripcion'      => 'El Alfa es la opcion mas solicitada para ferias comerciales y muestras empresariales. Combina amplitud con un costo contenido, y su distribucion permite dividir el area en zonas independientes sin obra adicional.',
                'precio_dia'       => 15000000,
                'capacidad'        => 1500,
                'imagen_principal' => 'images/slide1.jpg',
                'caracteristicas'  => [
                    ['Salones de exposicion', 'Economico, pero cuenta con grandes salones de exposicion y presentacion.'],
                    ['Espacio de trabajo', 'Amplia gama de salones, cada uno con espacio de trabajo generoso.'],
                    ['Tarimas y camerinos', 'Diferentes tarimas, camerinos exclusivos y banos privados en cada uno.'],
                    ['Aforo y mobiliario', 'Capacidad para 1.500 personas, con mesas y sillas disponibles.'],
                ],
                'imagenes' => [
                    ['images/slide1.jpg', 'Vista general del escenario Alfa'],
                    ['images/slide2.jpg', 'Salon principal del escenario Alfa'],
                    ['images/slide3.jpg', 'Zona de exposicion del escenario Alfa'],
                ],
            ],
            [
                'slug'             => 'beta',
                'nombre'           => 'Escenario Beta',
                'resumen'          => 'Formato intermedio pensado para congresos y encuentros academicos.',
                'descripcion'      => 'El Beta esta configurado para eventos con agenda de conferencias: auditorio principal, salas paralelas y area de registro independiente para evitar congestion en el ingreso.',
                'precio_dia'       => 22000000,
                'capacidad'        => 2200,
                'imagen_principal' => 'images/slide2.jpg',
                'caracteristicas'  => [
                    ['Auditorio principal', 'Auditorio con graderia fija y cabina de traduccion simultanea.'],
                    ['Salas paralelas', 'Seis salas independientes para sesiones simultaneas.'],
                    ['Area de registro', 'Zona de acreditacion separada del acceso principal.'],
                    ['Aforo y mobiliario', 'Capacidad para 2.200 personas en configuracion de auditorio.'],
                ],
                'imagenes' => [
                    ['images/slide2.jpg', 'Auditorio del escenario Beta'],
                    ['images/slide3.jpg', 'Salas paralelas del escenario Beta'],
                    ['images/slide1.jpg', 'Area de registro del escenario Beta'],
                ],
            ],
            [
                'slug'             => 'omega',
                'nombre'           => 'Escenario Omega',
                'resumen'          => 'El de mayor aforo, para conciertos y ferias de gran formato.',
                'descripcion'      => 'El Omega es el unico con capacidad de carga en cubierta para montajes escenicos pesados. Cuenta con acceso vehicular directo a la zona de tarima y patio de maniobras.',
                'precio_dia'       => 38000000,
                'capacidad'        => 5000,
                'imagen_principal' => 'images/slide3.jpg',
                'caracteristicas'  => [
                    ['Carga en cubierta', 'Estructura preparada para montajes escenicos e iluminacion colgada.'],
                    ['Acceso vehicular', 'Ingreso directo de camiones hasta la zona de tarima.'],
                    ['Patio de maniobras', 'Area exterior para logistica de montaje y desmontaje.'],
                    ['Aforo y mobiliario', 'Capacidad para 5.000 personas de pie.'],
                ],
                'imagenes' => [
                    ['images/slide3.jpg', 'Vista general del escenario Omega'],
                    ['images/slide1.jpg', 'Zona de tarima del escenario Omega'],
                    ['images/event1.jpg', 'Montaje en el escenario Omega'],
                ],
            ],
            [
                'slug'             => 'metro',
                'nombre'           => 'Escenario Metro',
                'resumen'          => 'Espacio compacto junto al acceso principal, ideal para lanzamientos.',
                'descripcion'      => 'El Metro es el mas cercano a la entrada y al transporte publico. Su tamano lo hace apropiado para ruedas de prensa, lanzamientos de producto y activaciones de marca de corta duracion.',
                'precio_dia'       => 8500000,
                'capacidad'        => 600,
                'imagen_principal' => 'images/slide1.jpg',
                'caracteristicas'  => [
                    ['Ubicacion privilegiada', 'A menos de cinco minutos del acceso principal y del transporte publico.'],
                    ['Montaje agil', 'Configuracion basica lista en menos de cuatro horas.'],
                    ['Sala de prensa', 'Espacio anexo equipado para atencion a medios.'],
                    ['Aforo y mobiliario', 'Capacidad para 600 personas en formato coctel.'],
                ],
                'imagenes' => [
                    ['images/slide1.jpg', 'Vista general del escenario Metro'],
                    ['images/event2.jpg', 'Activacion de marca en el escenario Metro'],
                    ['images/slide2.jpg', 'Sala de prensa del escenario Metro'],
                ],
            ],
        ];

        foreach ($escenarios as $datos) {
            $caracteristicas = $datos['caracteristicas'];
            $imagenes        = $datos['imagenes'];
            unset($datos['caracteristicas'], $datos['imagenes']);

            $escenario = Escenario::updateOrCreate(['slug' => $datos['slug']], $datos);

            $escenario->caracteristicas()->delete();
            foreach ($caracteristicas as $orden => [$titulo, $descripcion]) {
                $escenario->caracteristicas()->create([
                    'titulo'      => $titulo,
                    'descripcion' => $descripcion,
                    'orden'       => $orden,
                ]);
            }

            $escenario->imagenes()->delete();
            foreach ($imagenes as $orden => [$ruta, $alt]) {
                $escenario->imagenes()->create([
                    'ruta'              => $ruta,
                    'texto_alternativo' => $alt,
                    'orden'             => $orden,
                ]);
            }
        }
    }
}
