<?php
/**
 * Controller — base de todos los controladores.
 *
 * Da acceso al contenedor y resuelve tres cosas que si no se repiten en cada
 * método: pintar una vista, redirigir con mensaje y validar el testigo CSRF.
 *
 * `exigirCsrf()` no se llama solo: cada método que recibe un POST tiene que
 * invocarlo en su primera línea. Se valoró hacerlo automático en el enrutador
 * para toda petición POST, pero entonces el endpoint público del formulario de
 * voluntariado —que viene de otro origen y no tiene sesión de panel— quedaría
 * bloqueado sin remedio.
 */

declare(strict_types=1);

namespace Intranet\Core;

abstract class Controller
{
    protected View $vista;
    protected Csrf $csrf;

    public function __construct(protected Contenedor $c)
    {
        $this->vista = new View($this->c);
        $this->csrf  = new Csrf($this->c->sesion());

        $this->prepararVista();
    }

    /** Datos que necesita el layout en todas las pantallas. */
    private function prepararVista(): void
    {
        $this->vista->compartir('usuarioActual', $this->c->auth()->usuario());
        $this->vista->compartir('destellos', $this->c->sesion()->destellos());
        $this->vista->compartir('rutaActual', $this->c->peticion()->ruta());
    }

    /** @param array<string, mixed> $datos */
    protected function ver(string $plantillaVista, array $datos = [], string $layout = 'panel'): void
    {
        Response::html($this->vista->renderizar($plantillaVista, $datos, $layout));
    }

    protected function redirigir(string $ruta): void
    {
        Response::redirigir($this->c->url($ruta));
    }

    protected function conExito(string $mensaje, string $ruta): void
    {
        $this->c->sesion()->destello('exito', $mensaje);
        $this->redirigir($ruta);
    }

    protected function conError(string $mensaje, string $ruta): void
    {
        $this->c->sesion()->destello('error', $mensaje);
        $this->redirigir($ruta);
    }

    /**
     * Corta la petición si el testigo no cuadra. Se llama al principio de todo
     * método que modifique datos.
     */
    protected function exigirCsrf(Request $peticion): void
    {
        if ($this->csrf->valido((string) $peticion->post('_csrf', ''))) {
            return;
        }

        http_response_code(419);

        // El caso habitual no es un ataque, es una pestaña abierta desde ayer
        // con la sesión ya caducada. El mensaje debe decir qué hacer.
        Response::html($this->vista->renderizar('errores/generico', [
            'codigo'  => 419,
            'mensaje' => 'La sesión de este formulario caducó. Vuelve a entrar y repite la operación.',
        ], 'limpio'), 419);

        exit;
    }

    /** Comprobación puntual dentro de un método, cuando la ruta no basta. */
    protected function exigirPermiso(string $permiso): void
    {
        if ($this->c->auth()->puede($permiso)) {
            return;
        }

        http_response_code(403);
        Response::html($this->vista->renderizar('errores/generico', [
            'codigo'  => 403,
            'mensaje' => 'No tienes permiso para hacer esto.',
        ], 'limpio'), 403);

        exit;
    }
}
