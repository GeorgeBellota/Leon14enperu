<?php
/**
 * ============================================================================
 *  RUTAS
 *  El mapa completo del panel en un solo archivo. Si una URL no está aquí, no
 *  existe: no hay enrutado automático que publique un controlador nuevo sin
 *  que nadie lo haya decidido.
 *
 *  El permiso se declara en la ruta, no dentro del controlador. Así se lee de
 *  un vistazo quién puede llegar a dónde, y no depende de que cada método se
 *  acuerde de comprobarlo.
 * ============================================================================
 */

declare(strict_types=1);

use Intranet\Controllers\AuthController;
use Intranet\Controllers\ComunicadoController;
use Intranet\Controllers\ConfiguracionController;
use Intranet\Controllers\MantenimientoController;
use Intranet\Controllers\MedioController;
use Intranet\Controllers\PaginaController;
use Intranet\Controllers\PanelController;
use Intranet\Controllers\VoluntarioController;
use Intranet\Core\Router;

return static function (Router $r): void {

    // ── Público del panel: la puerta ─────────────────────────────────────
    $r->get('/login',  [AuthController::class, 'formulario'])->nombre('login');
    $r->post('/login', [AuthController::class, 'entrar']);
    $r->post('/salir', [AuthController::class, 'salir'])->auth();
    $r->get('/salir',  [AuthController::class, 'salir'])->auth();

    // Cambio de contraseña obligatorio en el primer acceso. Exige sesión pero
    // NO permiso: quien acaba de entrar todavía no ha pasado por aquí y no
    // puede quedarse encerrado fuera de su propia cuenta.
    $r->get('/clave',  [AuthController::class, 'formularioClave'])->auth();
    $r->post('/clave', [AuthController::class, 'cambiarClave'])->auth();

    // ── Escritorio ───────────────────────────────────────────────────────
    $r->get('/',       [PanelController::class, 'inicio'])->permiso('panel.acceder');
    $r->get('/inicio', [PanelController::class, 'inicio'])->permiso('panel.acceder');

    // ── Voluntariado ─────────────────────────────────────────────────────
    // El orden importa: /voluntarios/exportar se declara ANTES que
    // /voluntarios/{id}. Con la restricción :\d+ no habría colisión de todos
    // modos, pero dejar la ruta literal delante evita el problema por completo.
    $r->grupo('/voluntarios', ['auth' => true, 'permiso' => 'voluntarios.ver'], function (Router $r): void {
        $r->get('',                  [VoluntarioController::class, 'listar'])->nombre('voluntarios');
        $r->get('/exportar',         [VoluntarioController::class, 'exportar'])->permiso('voluntarios.exportar');
        $r->get('/{id:\d+}',         [VoluntarioController::class, 'detalle']);
        $r->post('/{id:\d+}/estado', [VoluntarioController::class, 'cambiarEstado'])->permiso('voluntarios.editar');
        $r->post('/{id:\d+}/baja',   [VoluntarioController::class, 'darDeBaja'])->permiso('voluntarios.borrar');
    });

    // ── CMS de páginas ───────────────────────────────────────────────────
    $r->grupo('/paginas', ['auth' => true, 'permiso' => 'paginas.ver'], function (Router $r): void {
        $r->get('',                  [PaginaController::class, 'listar'])->nombre('paginas');
        $r->get('/{clave}',          [PaginaController::class, 'secciones']);
        $r->post('/{clave}/publicar', [PaginaController::class, 'publicar'])->permiso('paginas.publicar');
        $r->get('/{clave}/{seccion}', [PaginaController::class, 'editar'])->permiso('paginas.editar');
        $r->post('/{clave}/{seccion}', [PaginaController::class, 'guardar'])->permiso('paginas.editar');
    });

    // ── Biblioteca de imágenes ───────────────────────────────────────────
    // Alimenta a las páginas: lo que se sube aquí es lo que después se elige
    // en cada sección. Igual que arriba, las rutas literales antes que {id}.
    $r->grupo('/medios', ['auth' => true, 'permiso' => 'medios.ver'], function (Router $r): void {
        $r->get('',                  [MedioController::class, 'listar'])->nombre('medios');
        $r->post('',                 [MedioController::class, 'subir'])->permiso('medios.subir');
        $r->post('/{id:\d+}',        [MedioController::class, 'actualizar'])->permiso('medios.subir');
        $r->post('/{id:\d+}/borrar', [MedioController::class, 'borrar'])->permiso('medios.subir');
    });

    // ── Comunicados ──────────────────────────────────────────────────────
    // El aviso que aparece sobre la web. Igual que en voluntarios, las rutas
    // literales van ANTES que la de {id}: si «/nuevo» quedara detrás, el
    // enrutador intentaría leerlo como un identificador.
    $r->grupo('/comunicados', ['auth' => true, 'permiso' => 'comunicados.ver'], function (Router $r): void {
        $r->get('',                    [ComunicadoController::class, 'listar'])->nombre('comunicados');
        $r->get('/nuevo',              [ComunicadoController::class, 'nuevo'])->permiso('comunicados.editar');
        $r->post('/nuevo',             [ComunicadoController::class, 'guardar'])->permiso('comunicados.editar');
        $r->get('/{id:\d+}',           [ComunicadoController::class, 'editar'])->permiso('comunicados.editar');
        $r->post('/{id:\d+}',          [ComunicadoController::class, 'guardar'])->permiso('comunicados.editar');
        $r->post('/{id:\d+}/alternar', [ComunicadoController::class, 'alternar'])->permiso('comunicados.editar');
    });

    // ── Configuración general ────────────────────────────────────────────
    // Página de inicio, entradas del menú y pie. Afecta a todo el sitio, así
    // que es cosa del administrador y no del rol de contenidos.
    $r->get('/configuracion',  [ConfiguracionController::class, 'panel'])->permiso('ajustes.general');
    $r->post('/configuracion', [ConfiguracionController::class, 'guardar'])->permiso('ajustes.general');

    // ── Mantenimiento del sitio ──────────────────────────────────────────
    // Sólo el administrador: cerrar la web al público no es editar contenidos.
    $r->grupo('/mantenimiento', ['auth' => true, 'permiso' => 'mantenimiento.gestionar'], function (Router $r): void {
        $r->get('',                       [MantenimientoController::class, 'panel'])->nombre('mantenimiento');
        $r->post('',                      [MantenimientoController::class, 'guardar']);
        $r->post('/ip',                   [MantenimientoController::class, 'anadirIp']);
        $r->post('/ip/{id:\d+}/alternar', [MantenimientoController::class, 'alternarIp']);
        $r->post('/ip/{id:\d+}/borrar',   [MantenimientoController::class, 'borrarIp']);
    });

    /* ══════════════════════════════════════════════════════════════════════
       PENDIENTE. El núcleo ya soporta estas rutas; faltan sus controladores.
       Se dejan escritas para que el orden del trabajo quede fijado.

    // Catálogos: los seis servicios y las jurisdicciones, que alimentan a la
    // vez el formulario público y las tarjetas de la sección «servicios».
    $r->get('/catalogos', [CatalogoController::class, 'listar'])->permiso('catalogos.editar');

    // Usuarios, roles y auditoría
    $r->get('/usuarios',                 [UsuarioController::class, 'listar'])->permiso('usuarios.ver');
    $r->formulario('/usuarios/nuevo',    [UsuarioController::class, 'formulario'],
                                         [UsuarioController::class, 'crear'])->permiso('usuarios.editar');
    $r->get('/auditoria',                [AuditoriaController::class, 'listar'])->permiso('auditoria.ver');
    $r->get('/ajustes',                  [AjusteController::class, 'editar'])->permiso('ajustes.editar');
       ══════════════════════════════════════════════════════════════════════ */
};
