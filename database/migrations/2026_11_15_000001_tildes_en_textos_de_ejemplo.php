<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pone las tildes que faltaban en los textos de ejemplo de escenarios y eventos.
 * Solo cambia las filas cuyo texto coincide exactamente con el original: si alguien
 * ya lo editó desde el panel, se respeta su versión.
 */
return new class extends Migration
{
    private const TEXTOS = [
        ['Economico y versatil, con grandes salones de exposicion y presentacion.', 'Económico y versátil, con grandes salones de exposición y presentación.'],
        ['El Alfa es la opcion mas solicitada para ferias comerciales y muestras empresariales. Combina amplitud con un costo contenido, y su distribucion permite dividir el area en zonas independientes sin obra adicional.', 'El Alfa es la opción más solicitada para ferias comerciales y muestras empresariales. Combina amplitud con un costo contenido, y su distribución permite dividir el área en zonas independientes sin obra adicional.'],
        ['Salones de exposicion', 'Salones de exposición'],
        ['Economico, pero cuenta con grandes salones de exposicion y presentacion.', 'Económico, pero cuenta con grandes salones de exposición y presentación.'],
        ['Zona de exposicion del escenario Alfa', 'Zona de exposición del escenario Alfa'],
        ['Formato intermedio pensado para congresos y encuentros academicos.', 'Formato intermedio pensado para congresos y encuentros académicos.'],
        ['El Beta esta configurado para eventos con agenda de conferencias: auditorio principal, salas paralelas y area de registro independiente para evitar congestion en el ingreso.', 'El Beta está configurado para eventos con agenda de conferencias: auditorio principal, salas paralelas y área de registro independiente para evitar congestión en el ingreso.'],
        ['Auditorio con graderia fija y cabina de traduccion simultanea.', 'Auditorio con gradería fija y cabina de traducción simultánea.'],
        ['Area de registro', 'Área de registro'],
        ['Zona de acreditacion separada del acceso principal.', 'Zona de acreditación separada del acceso principal.'],
        ['Capacidad para 2.200 personas en configuracion de auditorio.', 'Capacidad para 2.200 personas en configuración de auditorio.'],
        ['Area de registro del escenario Beta', 'Área de registro del escenario Beta'],
        ['El Omega es el unico con capacidad de carga en cubierta para montajes escenicos pesados. Cuenta con acceso vehicular directo a la zona de tarima y patio de maniobras.', 'El Omega es el único con capacidad de carga en cubierta para montajes escénicos pesados. Cuenta con acceso vehicular directo a la zona de tarima y patio de maniobras.'],
        ['Estructura preparada para montajes escenicos e iluminacion colgada.', 'Estructura preparada para montajes escénicos e iluminación colgada.'],
        ['Area exterior para logistica de montaje y desmontaje.', 'Área exterior para logística de montaje y desmontaje.'],
        ['El Metro es el mas cercano a la entrada y al transporte publico. Su tamano lo hace apropiado para ruedas de prensa, lanzamientos de producto y activaciones de marca de corta duracion.', 'El Metro es el más cercano a la entrada y al transporte público. Su tamaño lo hace apropiado para ruedas de prensa, lanzamientos de producto y activaciones de marca de corta duración.'],
        ['Ubicacion privilegiada', 'Ubicación privilegiada'],
        ['A menos de cinco minutos del acceso principal y del transporte publico.', 'A menos de cinco minutos del acceso principal y del transporte público.'],
        ['Montaje agil', 'Montaje ágil'],
        ['Configuracion basica lista en menos de cuatro horas.', 'Configuración básica lista en menos de cuatro horas.'],
        ['Espacio anexo equipado para atencion a medios.', 'Espacio anexo equipado para atención a medios.'],
        ['Tres dias de comic, disfraces, gastronomia y charlas con autores invitados.', 'Tres días de cómic, disfraces, gastronomía y charlas con autores invitados.'],
    ];

    private const COLUMNAS = [
        'escenarios'                => ['nombre', 'resumen', 'descripcion'],
        'escenario_caracteristicas' => ['titulo', 'descripcion'],
        'eventos'                   => ['nombre', 'resumen', 'descripcion', 'horario'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNAS as $tabla => $columnas) {
            foreach ($columnas as $columna) {
                foreach (self::TEXTOS as [$antes, $despues]) {
                    DB::table($tabla)->where($columna, $antes)->update([$columna => $despues]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNAS as $tabla => $columnas) {
            foreach ($columnas as $columna) {
                foreach (self::TEXTOS as [$antes, $despues]) {
                    DB::table($tabla)->where($columna, $despues)->update([$columna => $antes]);
                }
            }
        }
    }
};
