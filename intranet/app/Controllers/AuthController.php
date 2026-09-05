<?php
/**
 * AuthController — entrada, salida y cambio de contraseña.
 */

declare(strict_types=1);

namespace Intranet\Controllers;

use Intranet\Core\Auditoria;
use Intranet\Core\Controller;
use Intranet\Core\Request;

final class AuthController extends Controller
{
    public function formulario(Request $peticion): void
    {
        if ($this->c->auth()->autenticado()) {
            $this->redirigir('/');
        }

        $this->ver('auth/login', [
            'titulo' => 'Entrar',
            'aviso'  => $this->c->sesion()->sacar('aviso'),
        ], 'limpio');
    }

    public function entrar(Request $peticion): void
    {
        $this->exigirCsrf($peticion);

        $correo = $peticion->texto('correo');
        $clave  = (string) $peticion->post('clave', '');

        if ($correo === '' || $clave === '') {
            $this->conError('Rellena el correo y la contraseña.', '/login');
        }

        $resultado = $this->c->auth()->entrar($correo, $clave, $peticion);

        if (!$resultado['ok']) {
            // La pausa iguala el tiempo de respuesta entre un correo que existe
            // y uno que no, y encarece el barrido automático.
            usleep(random_int(200_000, 400_000));
            $this->conError($resultado['error'] ?? 'No se pudo iniciar sesión.', '/login');
        }

        // Vuelve a donde iba antes de que le mandáramos al login.
        $destino = (string) $this->c->sesion()->sacar('destino', '/');
        $this->redirigir(str_starts_with($destino, '/') ? $destino : '/');
    }

    public function salir(Request $peticion): void
    {
        // El cierre de sesión también es una acción con efecto: por GET, un
        // <img src="…/salir"> en cualquier página echaría al usuario. Se acepta
        // el GET sólo porque el enlace del menú lo usa, pero el POST lleva CSRF.
        if ($peticion->esPost()) {
            $this->exigirCsrf($peticion);
        }

        $this->c->auth()->salir();
        $this->redirigir('/login');
    }

    public function formularioClave(Request $peticion): void
    {
        $this->ver('auth/clave', [
            'titulo'    => 'Cambiar la contraseña',
            'obligado'  => $this->c->auth()->debeCambiarClave(),
            'minimo'    => (int) $this->c->config('seguridad.clave_min', 10),
        ], 'limpio');
    }

    public function cambiarClave(Request $peticion): void
    {
        $this->exigirCsrf($peticion);

        $auth   = $this->c->auth();
        $actual = (string) $peticion->post('actual', '');
        $nueva  = (string) $peticion->post('nueva', '');
        $repite = (string) $peticion->post('repite', '');
        $minimo = (int) $this->c->config('seguridad.clave_min', 10);

        $usuario = $auth->usuario();
        if ($usuario === null) {
            $this->redirigir('/login');
        }

        if (!password_verify($actual, (string) $usuario['clave_hash'])) {
            $this->conError('La contraseña actual no es correcta.', '/clave');
        }

        if (mb_strlen($nueva) < $minimo) {
            $this->conError("La contraseña nueva debe tener al menos {$minimo} caracteres.", '/clave');
        }

        if (!hash_equals($nueva, $repite)) {
            $this->conError('Las dos contraseñas nuevas no coinciden.', '/clave');
        }

        if (hash_equals($actual, $nueva)) {
            $this->conError('La contraseña nueva debe ser distinta de la actual.', '/clave');
        }

        $auth->cambiarClave((int) $usuario['id'], $nueva);

        $this->conExito('Contraseña actualizada.', '/');
    }
}
