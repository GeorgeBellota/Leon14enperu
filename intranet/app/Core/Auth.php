<?php
/**
 * ============================================================================
 *  Auth — inicio de sesión y control de acceso por permisos (RBAC).
 * ============================================================================
 *
 *  Modelo de permisos, de menos a más específico:
 *
 *      rol → permisos del rol → excepciones de la persona
 *
 *  Un usuario hereda los permisos de su rol y, encima, puede tener
 *  excepciones individuales en `usuario_permiso`: conceder = 1 añade,
 *  conceder = 0 retira. Esa última tabla existe para el caso real de «esta
 *  persona coordina voluntariado pero no debe ver los DNI», que de otro modo
 *  obligaría a inventar un rol nuevo por cada matiz.
 *
 *  El rol `superadmin` pasa todas las comprobaciones sin consultar tablas: si
 *  alguien retira por error el permiso de gestionar usuarios, tiene que quedar
 *  alguien capaz de devolverlo.
 *
 *  Las comprobaciones se hacen en el enrutador (Router::ejecutar), no dentro
 *  de cada método de controlador.
 */

declare(strict_types=1);

namespace Intranet\Core;

final class Auth
{
    private const CLAVE_SESION = 'usuario_id';

    /** @var array<string, mixed>|null Cache del usuario dentro de la petición. */
    private ?array $usuario = null;

    /** @var array<string, bool>|null Cache de permisos ya resueltos. */
    private ?array $permisos = null;

    private bool $cargado = false;

    public function __construct(private Contenedor $c)
    {
    }

    // ── Estado ──────────────────────────────────────────────────────────

    public function autenticado(): bool
    {
        return $this->usuario() !== null;
    }

    /** @return array<string, mixed>|null */
    public function usuario(): ?array
    {
        if ($this->cargado) {
            return $this->usuario;
        }

        $this->cargado = true;
        $id = $this->c->sesion()->leer(self::CLAVE_SESION);

        if (!is_int($id) && !ctype_digit((string) $id)) {
            return null;
        }

        $fila = $this->c->bd()->fila(
            'SELECT u.*, r.clave AS rol_clave, r.nombre AS rol_nombre
               FROM usuarios u
               JOIN roles r ON r.id = u.rol_id
              WHERE u.id = :id AND u.activo = 1
              LIMIT 1',
            ['id' => (int) $id]
        );

        // La cuenta se desactivó o se borró con la sesión abierta: fuera.
        if ($fila === null) {
            $this->c->sesion()->olvidar(self::CLAVE_SESION);

            return null;
        }

        return $this->usuario = $fila;
    }

    public function id(): ?int
    {
        $u = $this->usuario();

        return $u === null ? null : (int) $u['id'];
    }

    public function nombre(): string
    {
        return (string) ($this->usuario()['nombre'] ?? '');
    }

    public function rol(): string
    {
        return (string) ($this->usuario()['rol_clave'] ?? '');
    }

    public function esSuperadmin(): bool
    {
        return $this->rol() === 'superadmin';
    }

    public function debeCambiarClave(): bool
    {
        return (bool) ($this->usuario()['debe_cambiar'] ?? false);
    }

    // ── Permisos ────────────────────────────────────────────────────────

    public function puede(string $permiso): bool
    {
        if (!$this->autenticado()) {
            return false;
        }

        if ($this->esSuperadmin()) {
            return true;
        }

        return $this->permisos()[$permiso] ?? false;
    }

    /** Cualquiera de la lista. Para menús que agrupan varias pantallas. */
    public function puedeAlguno(string ...$permisos): bool
    {
        foreach ($permisos as $p) {
            if ($this->puede($p)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, bool> */
    public function permisos(): array
    {
        if ($this->permisos !== null) {
            return $this->permisos;
        }

        $id = $this->id();
        if ($id === null) {
            return $this->permisos = [];
        }

        $mapa = [];

        // 1. Los que trae el rol.
        $delRol = $this->c->bd()->columna(
            'SELECT p.clave
               FROM usuarios u
               JOIN rol_permiso rp ON rp.rol_id = u.rol_id
               JOIN permisos p     ON p.id = rp.permiso_id
              WHERE u.id = :id',
            ['id' => $id]
        );

        foreach ($delRol as $clave) {
            $mapa[$clave] = true;
        }

        // 2. Las excepciones de la persona, que mandan sobre lo anterior.
        $excepciones = $this->c->bd()->filas(
            'SELECT p.clave, up.conceder
               FROM usuario_permiso up
               JOIN permisos p ON p.id = up.permiso_id
              WHERE up.usuario_id = :id',
            ['id' => $id]
        );

        foreach ($excepciones as $e) {
            $mapa[$e['clave']] = (bool) $e['conceder'];
        }

        return $this->permisos = $mapa;
    }

    // ── Entrada y salida ────────────────────────────────────────────────

    /**
     * Intenta iniciar sesión.
     *
     * @return array{ok: bool, error?: string}
     */
    public function entrar(string $correo, string $clave, Request $peticion): array
    {
        $correo = mb_strtolower(trim($correo));
        $bd     = $this->c->bd();
        $ip     = $peticion->ipBinaria();

        if ($this->bloqueado($correo, $peticion)) {
            $minutos = (int) $this->c->config('seguridad.bloqueo_minutos', 15);

            return ['ok' => false, 'error' => "Demasiados intentos fallidos. Espera {$minutos} minutos."];
        }

        $usuario = $bd->fila(
            'SELECT id, nombre, correo, clave_hash, activo FROM usuarios WHERE correo = :correo LIMIT 1',
            ['correo' => $correo]
        );

        // Se comprueba el hash aunque el usuario no exista, contra un hash
        // falso. Si no, el tiempo de respuesta delata qué correos están dados
        // de alta: responder rápido = «esa cuenta no existe».
        $hash = $usuario['clave_hash']
            ?? '$2y$12$usuarioinexistenteusuarioinexistenteusuarioinexistenteuuuuu';

        $correcta = password_verify($clave, $hash);

        if ($usuario === null || !$correcta || !$usuario['activo']) {
            $this->registrarIntento($correo, $ip, false);

            // Mensaje único a propósito: no se distingue «no existe» de
            // «contraseña incorrecta» ni de «cuenta desactivada».
            return ['ok' => false, 'error' => 'Correo o contraseña incorrectos.'];
        }

        // Rehash si el coste por defecto de PHP ha subido desde que se guardó.
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $bd->actualizar('usuarios', ['clave_hash' => password_hash($clave, PASSWORD_DEFAULT)],
                'id = :id', ['id' => (int) $usuario['id']]);
        }

        $this->registrarIntento($correo, $ip, true);

        // Identificador nuevo justo al entrar: contra la fijación de sesión.
        $this->c->sesion()->rotar();
        $this->c->sesion()->guardar(self::CLAVE_SESION, (int) $usuario['id']);

        $bd->actualizar('usuarios', [
            'ultimo_acceso_en' => date('Y-m-d H:i:s'),
            'ultimo_acceso_ip' => $ip,
        ], 'id = :id', ['id' => (int) $usuario['id']]);

        $this->cargado  = false;
        $this->permisos = null;

        Auditoria::registrar($this->c, 'login', 'usuarios', (int) $usuario['id']);

        return ['ok' => true];
    }

    public function salir(): void
    {
        if ($this->autenticado()) {
            Auditoria::registrar($this->c, 'logout', 'usuarios', $this->id());
        }

        $this->c->sesion()->destruir();
        $this->usuario  = null;
        $this->permisos = null;
        $this->cargado  = true;
    }

    /** Cambia la contraseña propia y levanta la marca de «debe cambiar». */
    public function cambiarClave(int $usuarioId, string $nueva): void
    {
        $this->c->bd()->actualizar('usuarios', [
            'clave_hash'   => password_hash($nueva, PASSWORD_DEFAULT),
            'debe_cambiar' => 0,
        ], 'id = :id', ['id' => $usuarioId]);

        // La contraseña cambió: cualquier sesión robada anterior deja de valer.
        $this->c->sesion()->rotar();
        $this->cargado = false;

        Auditoria::registrar($this->c, 'cambio_clave', 'usuarios', $usuarioId);
    }

    // ── Freno de fuerza bruta ───────────────────────────────────────────

    private function bloqueado(string $correo, Request $peticion): bool
    {
        $max     = (int) $this->c->config('seguridad.intentos_max', 5);
        $minutos = (int) $this->c->config('seguridad.bloqueo_minutos', 15);
        $ip      = $peticion->ipBinaria();

        // Se cuenta por correo Y por IP: sin lo segundo, probar una contraseña
        // contra mil correos distintos no dispararía ningún límite.
        $fallos = (int) $this->c->bd()->valor(
            'SELECT COUNT(*) FROM intentos_login
              WHERE exito = 0
                AND creado_en > (NOW() - INTERVAL :minutos MINUTE)
                AND (correo = :correo OR ip = :ip)',
            ['minutos' => $minutos, 'correo' => $correo, 'ip' => $ip]
        );

        return $fallos >= $max;
    }

    private function registrarIntento(string $correo, ?string $ip, bool $exito): void
    {
        $this->c->bd()->insertar('intentos_login', [
            'correo' => mb_substr($correo, 0, 190),
            'ip'     => $ip ?? inet_pton('0.0.0.0'),
            'exito'  => $exito ? 1 : 0,
        ]);

        // Al acertar se limpia el contador de esa cuenta: si no, un usuario
        // legítimo que falla cuatro veces y acierta a la quinta seguiría a un
        // error de quedarse fuera durante el cuarto de hora siguiente.
        if ($exito) {
            $this->c->bd()->eliminar('intentos_login', 'correo = :correo AND exito = 0', ['correo' => $correo]);
        }
    }
}
