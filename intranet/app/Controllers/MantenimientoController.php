<?php
/**
 * MantenimientoController — cerrar y abrir el sitio público, y gestionar las
 * IP que siguen pudiendo verlo mientras está cerrado.
 */

declare(strict_types=1);

namespace Intranet\Controllers;

use Intranet\Core\Auditoria;
use Intranet\Core\Controller;
use Intranet\Core\Request;
use Intranet\Models\Ajuste;
use Intranet\Publico\Mantenimiento;

final class MantenimientoController extends Controller
{
    /**
     * Se llama `panel` y no `ver`: `ver()` es el método de la clase base que
     * pinta una vista, y redeclararlo con otra firma es un error fatal de PHP.
     */
    public function panel(Request $peticion): void
    {
        $ajustes = new Ajuste($this->c);
        $miIp    = $peticion->ip();

        $this->ver('mantenimiento/panel', [
            'titulo'   => 'Mantenimiento',
            'activo'   => $ajustes->esBool('mantenimiento.activo'),
            'textos'   => [
                'titulo'  => $ajustes->leer('mantenimiento.titulo', 'Volvemos enseguida'),
                'mensaje' => $ajustes->leer('mantenimiento.mensaje', ''),
                'vuelve'  => $ajustes->leer('mantenimiento.vuelve', ''),
            ],
            'ips'      => $this->ips(),
            'miIp'     => $miIp,
            // Aviso clave: si se cierra el sitio y la IP de quien lo cierra no
            // está autorizada, dejará de ver la web pública al instante.
            'miIpVale' => (new Mantenimiento($this->c))->permitida($miIp),
        ]);
    }

    public function guardar(Request $peticion): void
    {
        $this->exigirCsrf($peticion);

        $ajustes = new Ajuste($this->c);
        $activo  = $peticion->casilla('activo');
        $antes   = $ajustes->esBool('mantenimiento.activo');

        $ajustes->escribir('mantenimiento.activo', $activo ? '1' : '0');
        $ajustes->escribir('mantenimiento.titulo', $peticion->texto('titulo', 'Volvemos enseguida'));
        $ajustes->escribir('mantenimiento.mensaje', $peticion->texto('mensaje'));
        $ajustes->escribir('mantenimiento.vuelve', $peticion->texto('vuelve'));

        if ($activo !== $antes) {
            Auditoria::registrar($this->c, $activo ? 'cerrar_sitio' : 'abrir_sitio', 'ajustes', null, [
                'ip_de_quien_lo_hizo' => $peticion->ip(),
            ]);
        }

        $this->conExito(
            $activo
                ? 'El sitio público está CERRADO. Sólo lo ven las IP autorizadas.'
                : 'El sitio público vuelve a estar abierto.',
            '/mantenimiento'
        );
    }

    public function anadirIp(Request $peticion): void
    {
        $this->exigirCsrf($peticion);

        $ip       = $peticion->texto('ip');
        $etiqueta = $peticion->texto('etiqueta');

        if ($ip === '' || $etiqueta === '') {
            $this->conError('Hacen falta la dirección y una etiqueta que diga de quién es.', '/mantenimiento');
        }

        if (!$this->ipValida($ip)) {
            $this->conError(
                'Eso no parece una dirección IP. Escribe una dirección (203.0.113.7) o un rango (203.0.113.0/24).',
                '/mantenimiento'
            );
        }

        $repetida = $this->c->bd()->valor('SELECT id FROM ips_permitidas WHERE ip = :ip', ['ip' => $ip]);

        if ($repetida !== null) {
            $this->conError('Esa dirección ya está en la lista.', '/mantenimiento');
        }

        $id = $this->c->bd()->insertar('ips_permitidas', [
            'ip'         => $ip,
            'etiqueta'   => $etiqueta,
            'activa'     => 1,
            'creado_por' => $this->c->auth()->id(),
        ]);

        Auditoria::registrar($this->c, 'crear', 'ips_permitidas', $id, ['ip' => $ip, 'etiqueta' => $etiqueta]);

        $this->conExito('Dirección autorizada.', '/mantenimiento');
    }

    /** @param array<string, string> $params */
    public function alternarIp(Request $peticion, array $params): void
    {
        $this->exigirCsrf($peticion);

        $id = (int) $params['id'];

        $this->c->bd()->consultar(
            'UPDATE ips_permitidas SET activa = 1 - activa WHERE id = :id',
            ['id' => $id]
        );

        Auditoria::registrar($this->c, 'editar', 'ips_permitidas', $id);

        $this->conExito('Dirección actualizada.', '/mantenimiento');
    }

    /** @param array<string, string> $params */
    public function borrarIp(Request $peticion, array $params): void
    {
        $this->exigirCsrf($peticion);

        $id = (int) $params['id'];
        $ip = (string) $this->c->bd()->valor('SELECT ip FROM ips_permitidas WHERE id = :id', ['id' => $id]);

        // Quitar de la lista la IP desde la que se está trabajando, con el
        // sitio cerrado, deja a quien lo hace sin poder ver la web pública.
        // Se avisa en lugar de dejarle descubrirlo solo.
        $esLaSuya = $ip !== '' && Mantenimiento::coincide($peticion->ip(), $ip);
        $cerrado  = (new Ajuste($this->c))->esBool('mantenimiento.activo');

        $this->c->bd()->eliminar('ips_permitidas', 'id = :id', ['id' => $id]);

        Auditoria::registrar($this->c, 'borrar', 'ips_permitidas', $id, ['ip' => $ip]);

        $this->conExito(
            $esLaSuya && $cerrado
                ? 'Dirección eliminada. Era la tuya y el sitio está cerrado: dejarás de ver la web pública.'
                : 'Dirección eliminada.',
            '/mantenimiento'
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function ips(): array
    {
        return $this->c->bd()->filas(
            'SELECT i.*, u.nombre AS autor
               FROM ips_permitidas i
               LEFT JOIN usuarios u ON u.id = i.creado_por
              ORDER BY i.activa DESC, i.creado_en DESC'
        );
    }

    /** Dirección suelta o rango CIDR. */
    private function ipValida(string $valor): bool
    {
        if (filter_var($valor, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        if (!str_contains($valor, '/')) {
            return false;
        }

        [$red, $bits] = explode('/', $valor, 2);

        if (filter_var($red, FILTER_VALIDATE_IP) === false || !ctype_digit($bits)) {
            return false;
        }

        $max = filter_var($red, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? 128 : 32;

        return (int) $bits >= 0 && (int) $bits <= $max;
    }
}
