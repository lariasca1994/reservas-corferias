<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Carga y borrado de imagenes del catalogo.
 *
 * En produccion las imagenes van a Cloudinary (CLOUDINARY_URL): el contenedor escala a cero y
 * su disco no sobrevive a un reinicio ni a un despliegue. En la base se guarda
 * "cloudinary:<public_id>". Sin CLOUDINARY_URL (desarrollo y pruebas) se usa el disco 'public'
 * bajo storage/app/public, que requiere php artisan storage:link.
 *
 * El nombre del archivo siempre se genera en el servidor, nunca se usa el original.
 */
class AlmacenamientoImagenService
{
    private const DISCO = 'public';

    private const PREFIJO_NUBE = 'cloudinary:';

    /** Carpeta raiz de las imagenes de este proyecto dentro de Cloudinary. */
    private const CARPETA_NUBE = 'reservas-corferias';

    /** Extensiones aceptadas, coherentes con la validacion de los Requests. */
    public const EXTENSIONES = ['jpg', 'jpeg', 'png', 'webp'];

    /** Tamano maximo por archivo, en kilobytes. */
    public const MAX_KB = 3072;

    /**
     * Guarda la imagen y devuelve la ruta para persistir en base de datos.
     */
    public function guardar(UploadedFile $archivo, string $carpeta): string
    {
        $nombre = Str::uuid()->toString();
        $nube   = $this->nube();

        if ($nube === null) {
            return $archivo->storeAs(trim($carpeta, '/'), $nombre.'.'.strtolower($archivo->getClientOriginalExtension()), self::DISCO);
        }

        $publicId   = self::CARPETA_NUBE.'/'.trim($carpeta, '/').'/'.$nombre;
        $parametros = ['public_id' => $publicId, 'timestamp' => (string) time()];
        $respuesta  = Http::timeout(60)
            ->attach('file', fopen($archivo->getRealPath(), 'r'), $nombre)
            ->post("https://api.cloudinary.com/v1_1/{$nube['nube']}/image/upload", $parametros + [
                'api_key'   => $nube['clave'],
                'signature' => $this->firma($parametros, $nube['secreto']),
            ]);

        if (! $respuesta->successful() || $respuesta->json('public_id') !== $publicId) {
            throw new RuntimeException('No se pudo guardar la imagen. Intenta de nuevo.');
        }

        return self::PREFIJO_NUBE.$publicId;
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
     * Borra una imagen.
     *
     * Ignora las rutas que apuntan a los archivos historicos versionados en
     * public/images: esos pertenecen al repositorio y no deben eliminarse.
     */
    public function eliminar(?string $ruta): void
    {
        if (blank($ruta) || str_starts_with($ruta, 'images/')) {
            return;
        }

        if (! str_starts_with($ruta, self::PREFIJO_NUBE)) {
            Storage::disk(self::DISCO)->delete($ruta);

            return;
        }

        $nube = $this->nube();
        if ($nube === null) {
            return;
        }

        $parametros = ['public_id' => substr($ruta, strlen(self::PREFIJO_NUBE)), 'timestamp' => (string) time()];
        // Si falla, la imagen queda huerfana en Cloudinary pero el catalogo sigue funcionando.
        Http::timeout(30)->asForm()->post("https://api.cloudinary.com/v1_1/{$nube['nube']}/image/destroy", $parametros + [
            'api_key'   => $nube['clave'],
            'signature' => $this->firma($parametros, $nube['secreto']),
        ]);
    }

    /** URL publica de una imagen, venga de Cloudinary, del disco o del repositorio. */
    public function url(?string $ruta): ?string
    {
        if (blank($ruta)) {
            return null;
        }

        if (str_starts_with($ruta, self::PREFIJO_NUBE)) {
            $nube     = $this->nube();
            $publicId = substr($ruta, strlen(self::PREFIJO_NUBE));

            return $nube === null ? null : "https://res.cloudinary.com/{$nube['nube']}/image/upload/f_auto,q_auto/{$publicId}";
        }

        return str_starts_with($ruta, 'images/')
            ? asset($ruta)
            : Storage::disk(self::DISCO)->url($ruta);
    }

    /** Datos de CLOUDINARY_URL (cloudinary://clave:secreto@nube), o null si no esta configurada. */
    private function nube(): ?array
    {
        $url = (string) config('services.cloudinary.url');
        if (! preg_match('#^cloudinary://([^:]+):([^@]+)@(.+)$#', trim($url), $m)) {
            return null;
        }

        return ['clave' => $m[1], 'secreto' => $m[2], 'nube' => $m[3]];
    }

    /** Firma de Cloudinary: parametros ordenados, unidos con & y el secreto al final (SHA-1). */
    private function firma(array $parametros, string $secreto): string
    {
        ksort($parametros);

        return sha1(urldecode(http_build_query($parametros)).$secreto);
    }
}
