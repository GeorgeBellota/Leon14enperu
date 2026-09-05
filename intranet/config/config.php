<?php
/**
 * ============================================================================
 *  CONFIGURACIÓN
 * ============================================================================
 *
 *  Este archivo SÍ se versiona y no contiene ni una credencial real. Todo lo
 *  que sea secreto o dependa de la máquina va en config.local.php, que se
 *  fusiona encima de estos valores y está excluido del control de versiones.
 *
 *  Para arrancar:  copiar config.local.example.php → config.local.php
 */

declare(strict_types=1);

$config = [

    // ── Aplicación ───────────────────────────────────────────────────────
    'app' => [
        'nombre'   => 'Intranet · León XIV en el Perú',
        // 'desarrollo' muestra los errores en pantalla. En producción,
        // 'produccion': el error se registra y el usuario ve una página neutra.
        'entorno'  => 'desarrollo',
        'zona'     => 'America/Lima',
        // Clave maestra de cifrado de datos sensibles. Se define en
        // config.local.php. Si está vacía, Cripto lanza una excepción antes de
        // guardar nada: es preferible un error visible a un DNI en claro.
        'clave'    => '',
    ],

    // ── URLs ─────────────────────────────────────────────────────────────
    'url' => [
        // Raíz del sitio público. Sin barra final.
        'sitio'  => 'http://localhost/leon14peru',
        // Raíz del panel. Debe apuntar a intranet/public/.
        'panel'  => 'http://localhost/leon14peru/intranet/public',
    ],

    // ── Base de datos ────────────────────────────────────────────────────
    'bd' => [
        'host'    => '127.0.0.1',
        'puerto'  => 3306,
        'base'    => 'leon14peru',
        'usuario' => 'root',
        'clave'   => '',
        'charset' => 'utf8mb4',
        'cotejo'  => 'utf8mb4_unicode_ci',
    ],

    // ── Sesión ───────────────────────────────────────────────────────────
    'sesion' => [
        'nombre'          => 'LX14PANEL',
        // Minutos de inactividad antes de cerrar la sesión. Hay datos
        // personales en pantalla: un panel abierto y desatendido es una fuga.
        'inactividad'     => 45,
        // Minutos entre renovaciones del id de sesión.
        'rotacion'        => 30,
        'solo_https'      => false, // ponerlo a true en producción
    ],

    // ── Seguridad ────────────────────────────────────────────────────────
    'seguridad' => [
        // Intentos fallidos permitidos por correo/IP antes de bloquear.
        'intentos_max'     => 5,
        'bloqueo_minutos'  => 15,
        'clave_min'        => 10,
        // Cifrar DNI, dirección y teléfonos. Desactivarlo simplifica el
        // desarrollo pero deja datos personales en claro en la base: no se
        // debe apagar en producción.
        'cifrar_sensibles' => true,
    ],

    // ── Formulario público de voluntariado ───────────────────────────────
    'voluntariado' => [
        // Inscripciones admitidas por hora desde una misma conexión.
        //
        // Empezó en 5 y se subió a 40: en una parroquia, un colegio o una
        // cabina, decenas de personas se inscriben desde la MISMA IP en la
        // misma tarde. Con el tope bajo, a partir del quinto el formulario
        // rechazaba envíos legítimos, y quien lo veía no tenía forma de saber
        // que el problema era el vecino de al lado.
        //
        // 40/hora sigue frenando a un robot, que enviaría cientos por minuto.
        'envios_por_ip'    => 40,
        'ventana_minutos'  => 60,

        // Segundos por debajo de los cuales el envío no puede ser humano.
        // Rellenar diez campos lleva minutos; tres segundos es un guion.
        'tiempo_minimo'    => 3,
    ],

    // ── Subida de archivos ───────────────────────────────────────────────
    'medios' => [
        // Carpeta física donde caen las imágenes, relativa a la raíz del sitio.
        'carpeta'  => 'assets/img/subidas',
        'peso_max' => 5 * 1024 * 1024,
        'tipos'    => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'application/pdf'],
    ],
];

// ── Fusión con la configuración local ────────────────────────────────────
$rutaLocal = __DIR__ . '/config.local.php';
if (is_file($rutaLocal)) {
    $local = require $rutaLocal;
    if (is_array($local)) {
        foreach ($local as $seccion => $valores) {
            $config[$seccion] = is_array($valores) && isset($config[$seccion]) && is_array($config[$seccion])
                ? array_replace($config[$seccion], $valores)
                : $valores;
        }
    }
}

return $config;
