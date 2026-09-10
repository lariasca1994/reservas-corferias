<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Carga y borrado de imagenes del catalogo.
 *
 * Las imagenes se guardan en el disco 'public' bajo storage/app/public,
 * nunca dentro de la carpeta public/ del proyecto. Eso mantiene el
 * repositorio limpio de archivos subidos por usuarios y permite cambiar a
 * S3 o Azure Blob Storage mas adelante sin tocar los controladores.
 *
 * Requiere haber ejecutado: php artisan storage:link
 *
 * En 2019 todas las imagenes eran archivos fijos versionados en el repo y
 * referenciados a mano desde arreglos PHP; no habia forma de cargar una
 * nueva sin editar codigo y desplegar.
 */
class AlmacenamientoImagenService
{
    private const DISCO = 'public';

    /** Extensiones aceptadas, coherentes con la validacion de los Requests. */
    public const EXTENSIONES = ['jpg', 'jpeg', 'png', 'webp'];

    /** Tamano maximo por archivo, en kilobytes. */
    public const MAX_KB = 3072;

    /**
     * Guarda la imagen y devuelve la ruta relativa para persistir en base
     * de datos. El nombre se genera en el servidor: usar el nombre
     * original permitiria sobrescribir archivos ajenos o inyectar rutas.
     */
    public function guardar(UploadedFile $archivo, string $carpeta): string
    {
        $nombre = Str::uuid()->toString().'.'.strtolower($archivo->getClientOriginalExtension());

        return $archivo->storeAs(trim($carpeta, '/'), $nombre, self::DISCO);
    }

    /**
     * Reemplaza una imagen existente, borrando la anterior solo si el
     * guardado de la nueva salio bien.
     */
    public function reemplazar(UploadedFile $archivo, string $carpeta, ?string $rutaAnterior): string
    {
        $nueva = $this->guardar($archivo, $carpeta);

        $this->eliminar($rutaAnterior);

        return $nueva;
    }

    /**
     * Borra una imagen del disco.
     *
     * Ignora las rutas que apuntan a los archivos historicos versionados en
     * public/images: esos pertenecen al repositorio y no deben eliminarse.
     */
    public function eliminar(?string $ruta): void
    {
        if (blank($ruta) || str_starts_with($ruta, 'images/')) {
            return;
        }

        Storage::disk(self::DISCO)->delete($ruta);
    }

    /** URL publica de una imagen, venga del disco o del repositorio. */
    public function url(?string $ruta): ?string
    {
        if (blank($ruta)) {
            return null;
        }

        return str_starts_with($ruta, 'images/')
            ? asset($ruta)
            : Storage::disk(self::DISCO)->url($ruta);
    }
}
