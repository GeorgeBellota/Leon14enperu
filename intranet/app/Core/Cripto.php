<?php
/**
 * ============================================================================
 *  Cripto — cifrado de los datos personales sensibles.
 * ============================================================================
 *
 *  Qué protege: DNI, dirección y teléfonos de los voluntarios. Si alguien
 *  consigue un volcado de la base —una copia mal guardada, un phpMyAdmin
 *  abierto, un backup en una carpeta pública— esos campos no le sirven de
 *  nada sin la clave, que vive en config.local.php y no en la base.
 *
 *  Qué NO protege: nada frente a quien tenga a la vez la base y el archivo de
 *  configuración. No es cifrado de conocimiento cero; es reducir el daño de la
 *  fuga más probable.
 *
 *  Algoritmo: AES-256-GCM. Cifrado autenticado, así que además de ocultar el
 *  dato detecta si alguien lo ha manipulado en la base. Cada valor lleva su
 *  propio nonce aleatorio: dos voluntarios con el mismo DNI producirían textos
 *  cifrados distintos.
 *
 *  Búsqueda: un campo cifrado no se puede buscar con LIKE. Por eso el DNI se
 *  guarda además como HMAC-SHA256 (`huella()`), que permite la búsqueda exacta
 *  y el control de duplicados sin poder recuperar el número. Un DNI son ocho
 *  dígitos: un hash sin clave se rompería por fuerza bruta en segundos, así
 *  que el HMAC con clave es la única forma sensata de hacerlo.
 *
 *  ⚠ SIN LA CLAVE NO HAY RECUPERACIÓN POSIBLE. Va en la copia de seguridad y
 *    en el gestor de contraseñas de la organización.
 * ============================================================================
 */

declare(strict_types=1);

namespace Intranet\Core;

use RuntimeException;

final class Cripto
{
    private const CIFRADO = 'aes-256-gcm';
    private const ETIQUETA = 16;   // bytes de la etiqueta de autenticación GCM
    private const PREFIJO = 'v1:'; // versión del formato, por si algún día se rota

    private string $claveCifrado;
    private string $claveHuella;

    public function __construct(string $claveBase64, private bool $activo = true)
    {
        if (!$this->activo) {
            $this->claveCifrado = '';
            $this->claveHuella  = '';

            return;
        }

        $clave = base64_decode($claveBase64, true);

        if ($clave === false || strlen($clave) < 32) {
            throw new RuntimeException(
                'app.clave no está configurada o no llega a 32 bytes. '
                . 'Genérala con: php -r "echo base64_encode(random_bytes(32));" '
                . 'y ponla en config/config.local.php.'
            );
        }

        // Dos claves derivadas de una sola raíz: nunca se usa la misma clave
        // para cifrar y para firmar. Es la regla básica de separación de usos.
        $this->claveCifrado = hash_hkdf('sha256', $clave, 32, 'cifrado-datos-personales');
        $this->claveHuella  = hash_hkdf('sha256', $clave, 32, 'huella-busqueda');
    }

    public function activo(): bool
    {
        return $this->activo;
    }

    /** Devuelve «v1:base64(nonce|etiqueta|cifrado)». */
    public function cifrar(?string $texto): string
    {
        if ($texto === null || $texto === '') {
            return '';
        }

        if (!$this->activo) {
            return $texto;
        }

        $nonce   = random_bytes(12); // 96 bits, lo recomendado para GCM
        $etiqueta = '';

        $cifrado = openssl_encrypt(
            $texto,
            self::CIFRADO,
            $this->claveCifrado,
            OPENSSL_RAW_DATA,
            $nonce,
            $etiqueta,
            '',
            self::ETIQUETA
        );

        if ($cifrado === false) {
            throw new RuntimeException('No se pudo cifrar el dato.');
        }

        return self::PREFIJO . base64_encode($nonce . $etiqueta . $cifrado);
    }

    /**
     * Descifra. Devuelve '' si el valor está vacío y lanza si está manipulado:
     * un dato personal alterado no puede pasar por bueno en silencio.
     */
    public function descifrar(?string $guardado): string
    {
        if ($guardado === null || $guardado === '') {
            return '';
        }

        if (!$this->activo) {
            return $guardado;
        }

        // Valores anteriores a la activación del cifrado: se devuelven tal cual
        // en lugar de reventar la pantalla entera del listado.
        if (!str_starts_with($guardado, self::PREFIJO)) {
            return $guardado;
        }

        $crudo = base64_decode(substr($guardado, strlen(self::PREFIJO)), true);

        if ($crudo === false || strlen($crudo) < 12 + self::ETIQUETA) {
            throw new RuntimeException('Dato cifrado con formato inválido.');
        }

        $nonce    = substr($crudo, 0, 12);
        $etiqueta = substr($crudo, 12, self::ETIQUETA);
        $cifrado  = substr($crudo, 12 + self::ETIQUETA);

        $texto = openssl_decrypt(
            $cifrado,
            self::CIFRADO,
            $this->claveCifrado,
            OPENSSL_RAW_DATA,
            $nonce,
            $etiqueta
        );

        if ($texto === false) {
            throw new RuntimeException(
                'No se pudo descifrar: la clave no es la correcta o el dato fue alterado.'
            );
        }

        return $texto;
    }

    /**
     * Huella determinista para buscar y deduplicar. El mismo DNI da siempre el
     * mismo resultado; del resultado no se vuelve al DNI.
     */
    public function huella(string $valor): string
    {
        $normalizado = strtolower(trim($valor));

        if (!$this->activo) {
            return hash('sha256', $normalizado);
        }

        return hash_hmac('sha256', $normalizado, $this->claveHuella);
    }

    /**
     * Lo que se ve en el listado sin permiso para el dato completo.
     * «71234567» → «*****567»; «987654321» → «******321».
     */
    public static function enmascarar(string $valor, int $visibles = 3): string
    {
        $valor = trim($valor);
        $largo = mb_strlen($valor);

        if ($largo <= $visibles) {
            return str_repeat('*', max($largo, 3));
        }

        return str_repeat('*', $largo - $visibles) . mb_substr($valor, -$visibles);
    }
}
