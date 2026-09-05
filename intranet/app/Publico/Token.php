<?php
/**
 * ============================================================================
 *  Token — testigo antifalsificación para formularios PÚBLICOS.
 * ============================================================================
 *
 *  El CSRF del panel (Core\Csrf) guarda el testigo en la sesión. Aquí no sirve:
 *  abrir una sesión de PHP en cada visita a la web pública significa una cookie
 *  para todo el mundo —con lo que eso arrastra en materia de consentimiento de
 *  cookies— y un archivo de sesión por visitante, para un formulario que la
 *  mayoría no va a rellenar.
 *
 *  Solución: un testigo FIRMADO y sin estado. Lleva dentro su propia fecha de
 *  caducidad y una firma HMAC hecha con la clave de la aplicación. El servidor
 *  no guarda nada: comprueba la firma y la hora.
 *
 *      caducidad.firma      →  1771891200.9f86d081884c7d65…
 *
 *  Lo que protege: que una página ajena publique un formulario que envíe
 *  inscripciones a este sitio en nombre del visitante.
 *  Lo que NO protege: que alguien pida la página, coja un testigo válido y lo
 *  use desde un script. Contra eso están la trampa para robots, el tiempo
 *  mínimo de relleno y el límite de envíos por IP.
 */

declare(strict_types=1);

namespace Intranet\Publico;

use RuntimeException;

final class Token
{
    /**
     * Cuánto vale un testigo.
     *
     * Empezó en dos horas y se subió a doce. El motivo no es que alguien tarde
     * doce horas en rellenar el formulario, sino que entre el momento en que se
     * pinta la página y el momento en que se envía puede haber una caché por
     * medio —la del navegador, la de un proxy, la del propio hosting—, y
     * entonces el testigo que recibe la persona ya viene con horas encima.
     *
     * La página ya se sirve con «no-store» precisamente para eso, pero un
     * margen amplio es la segunda línea de defensa: si algún intermediario
     * ignora la cabecera, lo que pasa es que la inscripción entra igual en vez
     * de perderse. Lo que protege el testigo —que un sitio ajeno publique un
     * formulario contra este— no se debilita por durar más: sigue siendo
     * necesario tener uno firmado con nuestra clave.
     */
    private const VIGENCIA = 43200; // 12 horas

    private string $clave;

    public function __construct(string $claveApp)
    {
        $bruta = base64_decode($claveApp, true);

        if ($bruta === false || strlen($bruta) < 32) {
            throw new RuntimeException('app.clave no está configurada; no se puede firmar el formulario.');
        }

        // Clave derivada y de uso exclusivo: la que firma formularios no es la
        // misma que cifra los DNI, aunque nazcan de la misma raíz.
        $this->clave = hash_hkdf('sha256', $bruta, 32, 'testigo-formulario-publico');
    }

    public function generar(): string
    {
        $caduca = time() + self::VIGENCIA;

        return $caduca . '.' . $this->firmar((string) $caduca);
    }

    public function valido(?string $testigo): bool
    {
        if (!is_string($testigo) || !str_contains($testigo, '.')) {
            return false;
        }

        [$caduca, $firma] = explode('.', $testigo, 2);

        if (!ctype_digit($caduca)) {
            return false;
        }

        if (!hash_equals($this->firmar($caduca), $firma)) {
            return false;
        }

        return (int) $caduca >= time();
    }

    private function firmar(string $carga): string
    {
        return hash_hmac('sha256', $carga, $this->clave);
    }
}
