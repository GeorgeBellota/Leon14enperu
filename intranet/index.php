<?php
/**
 * Atajo. Lo único servible de esta carpeta.
 *
 * /intranet/ es lo que uno teclea; el panel vive en /intranet/public/, porque
 * todo lo demás de aquí —la configuración con la clave de cifrado, las
 * clases, las migraciones— tiene que quedar fuera del alcance del navegador.
 *
 * Este archivo no lee configuración, no toca la base y no imprime nada:
 * redirige y se acaba. Si algún día se sirviera como texto plano por un fallo
 * de PHP, lo que se vería es este comentario.
 */

declare(strict_types=1);

$destino = rtrim((string) $_SERVER['REQUEST_URI'], '/') . '/public/';

header('Location: ' . $destino, true, 302);
exit;
