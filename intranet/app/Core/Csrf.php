<?php
/**
 * Csrf — testigo contra la falsificación de peticiones entre sitios.
 *
 * Sin esto, una página cualquiera podría llevar un formulario oculto que
 * apunte al panel; si la persona tiene la sesión abierta, el navegador manda
 * su cookie y la acción se ejecuta. Con datos de voluntarios de por medio, eso
 * incluye borrar registros o exportarlos.
 *
 * Un solo testigo por sesión (no uno por formulario): más simple, y no rompe
 * cuando alguien trabaja con dos pestañas abiertas, que es exactamente lo que
 * hace quien está revisando inscripciones.
 */

declare(strict_types=1);

namespace Intranet\Core;

final class Csrf
{
    private const CLAVE = '_csrf';

    public function __construct(private Session $sesion)
    {
    }

    public function token(): string
    {
        $token = $this->sesion->leer(self::CLAVE);

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->sesion->guardar(self::CLAVE, $token);
        }

        return $token;
    }

    /** Campo oculto listo para pegar en cualquier <form method="post">. */
    public function campo(): string
    {
        return sprintf(
            '<input type="hidden" name="_csrf" value="%s">',
            htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8')
        );
    }

    /** hash_equals, no ===: la comparación debe ser de tiempo constante. */
    public function valido(?string $enviado): bool
    {
        $guardado = $this->sesion->leer(self::CLAVE);

        if (!is_string($guardado) || $guardado === '' || !is_string($enviado) || $enviado === '') {
            return false;
        }

        return hash_equals($guardado, $enviado);
    }
}
