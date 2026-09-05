<?php
/**
 * ============================================================================
 *  Voluntario — la inscripción y su ciclo de vida.
 * ============================================================================
 *
 *  Único punto por el que entran y salen los datos personales. Todo lo que
 *  tenga que ver con cifrar, enmascarar o buscar por DNI vive aquí; ni el
 *  controlador ni la vista tocan `Cripto` directamente.
 *
 *  Lo que se guarda de cada dato sensible:
 *      dni       → dni_cifrado (AES-GCM) + dni_hash (HMAC, para buscar) + máscara
 *      teléfonos → cifrado + máscara
 *      dirección → cifrada, y el distrito/provincia aparte para poder filtrar
 *
 *  La máscara se calcula UNA vez al guardar y se guarda como columna. Podría
 *  derivarse al vuelo descifrando, pero entonces pintar un listado de cien
 *  filas exigiría cien descifrados sólo para tapar los dígitos.
 */

declare(strict_types=1);

namespace Intranet\Models;

use Intranet\Core\Cripto;
use Intranet\Core\ErrorDeNegocio;
use Intranet\Core\Model;
use PDO;

final class Voluntario extends Model
{
    protected string $tabla = 'voluntarios';

    protected array $ordenables = ['creado_en', 'nombres', 'estado', 'codigo', 'id'];

    // ── Alta desde el formulario público ────────────────────────────────

    /**
     * Registra una inscripción. Los datos ya vienen validados por Inscripcion.
     *
     * @param array<string, mixed> $datos
     * @return array{id: int, codigo: string}
     */
    public function registrar(array $datos, ?string $ip, string $agente): array
    {
        $cripto = $this->c->cripto();
        $dni    = (string) $datos['dni'];

        return $this->bd()->transaccion(function () use ($datos, $dni, $cripto, $ip, $agente): array {
            $hash = $cripto->huella($dni);

            // El índice único de dni_hash ya lo impediría, pero un error 1062 en
            // pantalla no le dice nada a quien se está inscribiendo.
            $existe = $this->bd()->valor(
                'SELECT codigo FROM voluntarios WHERE dni_hash = :h LIMIT 1',
                ['h' => $hash]
            );

            if ($existe !== null) {
                throw new ErrorDeNegocio('Ese DNI ya está inscrito como voluntario.', 'dni');
            }

            $codigo = $this->siguienteCodigo();

            $id = $this->bd()->insertar('voluntarios', [
                'codigo'      => $codigo,
                'nombres'     => $datos['nombres'],
                'dni_cifrado' => $cripto->cifrar($dni),
                'dni_hash'    => $hash,
                'dni_mascara' => Cripto::enmascarar($dni),
                'nacimiento'  => $datos['nacimiento'],

                'correo'            => $datos['correo'],
                'telefono_cifrado'  => $cripto->cifrar((string) $datos['telefono']),
                'telefono_mascara'  => Cripto::enmascarar((string) $datos['telefono']),
                'direccion_cifrada' => $cripto->cifrar((string) $datos['direccion']),

                'ubigeo_departamento_id' => $datos['ubigeo_departamento_id'] ?? null,
                'ubigeo_provincia_id'    => $datos['ubigeo_provincia_id'] ?? null,
                'ubigeo_distrito_id'     => $datos['ubigeo_distrito_id'] ?? null,
                'distrito'               => $datos['distrito'] ?? null,
                'provincia'              => $datos['provincia'] ?? null,

                // El contacto de emergencia es opcional. Si no lo dieron, se
                // guarda NULL y no una cadena cifrada vacía: así el panel
                // distingue «no lo dio» de «lo dio en blanco», y la máscara no
                // muestra un «***» que no oculta nada.
                'emergencia_nombre'           => $datos['emergencia_nombre'] ?? null,
                'emergencia_telefono_cifrado' => isset($datos['emergencia_telefono'])
                    ? $cripto->cifrar((string) $datos['emergencia_telefono'])
                    : null,
                'emergencia_telefono_mascara' => isset($datos['emergencia_telefono'])
                    ? Cripto::enmascarar((string) $datos['emergencia_telefono'])
                    : null,

                'jurisdiccion_id' => (int) $datos['jurisdiccion_id'],
                'servicio_id'     => (int) $datos['servicio_id'],
                'talla'           => $datos['talla'],

                'fase'   => 1,
                'estado' => 'nuevo',

                'consentimiento'         => 1,
                'consentimiento_en'      => date('Y-m-d H:i:s'),
                'consentimiento_version' => (string) $datos['consentimiento_version'],

                'origen_ip' => $ip,
                'origen_ua' => $agente,
            ]);

            $this->bd()->insertar('voluntarios_historial', [
                'voluntario_id' => $id,
                'usuario_id'    => null,
                'estado_nuevo'  => 'nuevo',
                'nota'          => 'Inscripción recibida desde el formulario público.',
            ]);

            return ['id' => $id, 'codigo' => $codigo];
        });
    }

    /**
     * Código correlativo por año: VOL-2026-000123.
     *
     * Se calcula dentro de la transacción del alta. En un pico de envíos
     * simultáneos dos peticiones podrían leer el mismo máximo; el índice único
     * de `codigo` rechazaría la segunda. Con el volumen esperado no compensa
     * una tabla de contadores con bloqueo.
     */
    private function siguienteCodigo(): string
    {
        $anio = date('Y');

        $ultimo = (int) $this->bd()->valor(
            'SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, 10) AS UNSIGNED)), 0)
               FROM voluntarios
              WHERE codigo LIKE :patron',
            ['patron' => "VOL-{$anio}-%"]
        );

        return sprintf('VOL-%s-%06d', $anio, $ultimo + 1);
    }

    public function dniYaInscrito(string $dni): bool
    {
        return $this->bd()->valor(
            'SELECT 1 FROM voluntarios WHERE dni_hash = :h LIMIT 1',
            ['h' => $this->c->cripto()->huella($dni)]
        ) !== null;
    }

    /**
     * El código de un envío que ya entró y está llegando por segunda vez.
     *
     * No sirve para buscar inscripciones: sirve para reconocer un reintento.
     * Si el servidor guarda bien pero la respuesta se pierde por el camino, el
     * formulario lo intenta otra vez y ese segundo intento choca con el índice
     * único del DNI. Sin esto, quien hizo todo bien vería «ese DNI ya está
     * inscrito» dos segundos después de inscribirse.
     *
     * ── Por qué hace falta el correo además del DNI ─────────────────────
     * Con el DNI solo, esto era peligroso: quien se equivoca de dígito y
     * acierta por casualidad el DNI de otra persona inscrita hace un rato
     * recibiría SU código de confirmación, se quedaría tranquilo, y sus datos
     * no se habrían guardado. Improbable en una inscripción; a los miles, deja
     * de serlo.
     *
     * Con los dos a la vez, la coincidencia deja de ser casualidad: es el
     * mismo formulario reenviado.
     *
     * La ventana es corta a propósito. Dentro de ella, dos envíos idénticos
     * son el mismo envío; fuera, es otra cosa y debe rechazarse.
     */
    public function codigoDeReintento(string $dni, string $correo, int $minutos = 30): ?string
    {
        if ($correo === '') {
            return null;
        }

        $codigo = $this->bd()->valor(
            'SELECT codigo FROM voluntarios
              WHERE dni_hash = :h AND correo = :c
                AND creado_en > (NOW() - INTERVAL :min MINUTE)
              ORDER BY id DESC LIMIT 1',
            [
                'h'   => $this->c->cripto()->huella($dni),
                'c'   => $correo,
                'min' => $minutos,
            ]
        );

        return $codigo === null ? null : (string) $codigo;
    }

    /** Envíos recientes desde una IP. Freno de spam del formulario público. */
    public function enviosRecientes(?string $ip, int $minutos = 60): int
    {
        if ($ip === null) {
            return 0;
        }

        return (int) $this->bd()->valor(
            'SELECT COUNT(*) FROM voluntarios
              WHERE origen_ip = :ip AND creado_en > (NOW() - INTERVAL :min MINUTE)',
            ['ip' => $ip, 'min' => $minutos]
        );
    }

    // ── Listado del panel ───────────────────────────────────────────────

    /**
     * Listado con filtros. Ni un valor se concatena: todos van como parámetro,
     * y las columnas de ordenación pasan por la lista blanca de $ordenables.
     *
     * El filtro por DNI busca contra `dni_hash`, así que sólo encuentra
     * coincidencias exactas de los ocho dígitos. Es la contrapartida de tener
     * el DNI cifrado: no hay forma de hacer un LIKE parcial sin descifrar la
     * tabla entera en cada búsqueda.
     *
     * @param array<string, mixed> $filtros
     * @return array{filas: array, total: int, pagina: int, paginas: int, porPagina: int}
     */
    /**
     * El WHERE del listado, aparte.
     *
     * Lo comparten la pantalla y la exportación, y tienen que ser el MISMO:
     * si divergen, el CSV trae un conjunto distinto del que se está viendo
     * y nadie se entera hasta que alguien cuenta las filas a mano.
     *
     * @param array<string, string> $filtros
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function condiciones(array $filtros): array
    {
        $condiciones = ['v.borrado_en IS NULL'];
        $params      = [];

        if (($filtros['texto'] ?? '') !== '') {
            // Tres marcadores distintos para el mismo valor, y no `:texto`
            // repetido: con ATTR_EMULATE_PREPARES desactivado —que es como
            // está configurada la conexión— MySQL no admite reutilizar un
            // marcador con nombre dentro de la misma sentencia.
            $condiciones[] = '(v.nombres LIKE :txt1 OR v.correo LIKE :txt2 OR v.codigo LIKE :txt3)';

            $patron = '%' . $filtros['texto'] . '%';
            $params['txt1'] = $patron;
            $params['txt2'] = $patron;
            $params['txt3'] = $patron;
        }

        if (($filtros['dni'] ?? '') !== '') {
            $condiciones[]  = 'v.dni_hash = :dni';
            $params['dni']  = $this->c->cripto()->huella((string) $filtros['dni']);
        }

        if (!empty($filtros['jurisdiccion_id'])) {
            $condiciones[]              = 'v.jurisdiccion_id = :jur';
            $params['jur']              = (int) $filtros['jurisdiccion_id'];
        }

        if (!empty($filtros['servicio_id'])) {
            $condiciones[]   = 'v.servicio_id = :srv';
            $params['srv']   = (int) $filtros['servicio_id'];
        }

        // El filtro por zona va contra las columnas de `voluntarios`, no contra
        // las tablas de ubigeo unidas. Es a propósito: la consulta de conteo de
        // la paginación no lleva esos JOIN, y una condición que mencionara
        // `d.id` la haría fallar con «unknown column».
        if (($filtros['departamento_id'] ?? '') !== '') {
            $condiciones[]      = 'v.ubigeo_departamento_id = :dep';
            $params['dep']      = $filtros['departamento_id'];
        }

        if (($filtros['provincia_id'] ?? '') !== '') {
            $condiciones[]      = 'v.ubigeo_provincia_id = :prov';
            $params['prov']     = $filtros['provincia_id'];
        }

        if (($filtros['estado'] ?? '') !== '') {
            $condiciones[]      = 'v.estado = :estado';
            $params['estado']   = $filtros['estado'];
        }

        if (($filtros['desde'] ?? '') !== '') {
            $condiciones[]     = 'v.creado_en >= :desde';
            $params['desde']   = $filtros['desde'] . ' 00:00:00';
        }

        if (($filtros['hasta'] ?? '') !== '') {
            $condiciones[]     = 'v.creado_en <= :hasta';
            $params['hasta']   = $filtros['hasta'] . ' 23:59:59';
        }

        return [$this->donde($condiciones), $params];
    }

    public function listar(array $filtros, int $pagina, int $porPagina = 25, int $tope = self::TOPE_PANTALLA): array
    {
        [$where, $params] = $this->condiciones($filtros);
        $orden  = $this->columnaSegura((string) ($filtros['orden'] ?? ''), 'creado_en');
        $dir    = $this->direccionSegura((string) ($filtros['dir'] ?? 'DESC'));

        // LEFT JOIN y no JOIN a secas con las tablas de ubigeo: las
        // inscripciones anteriores a que existieran los desplegables no tienen
        // distrito, y con un JOIN normal desaparecerían del listado.
        $base = "SELECT v.id, v.codigo, v.nombres, v.dni_mascara, v.telefono_mascara, v.correo,
                        v.estado, v.fase, v.talla, v.creado_en, v.distrito, v.provincia,
                        j.nombre AS jurisdiccion, s.nombre AS servicio,
                        ud.name             AS ubigeo_departamento,
                        up.nombre_provincia AS ubigeo_provincia,
                        ux.nombre_distrito  AS ubigeo_distrito
                   FROM voluntarios v
                   JOIN jurisdicciones j ON j.id = v.jurisdiccion_id
                   JOIN servicios s      ON s.id = v.servicio_id
                   LEFT JOIN ubigeo_departamento ud ON ud.id = v.ubigeo_departamento_id
                   LEFT JOIN ubigeo_provincia    up ON up.id = v.ubigeo_provincia_id
                   LEFT JOIN ubigeo_distrito     ux ON ux.id = v.ubigeo_distrito_id
                  WHERE {$where}
                  ORDER BY v.`{$orden}` {$dir}";

        $conteo = "SELECT COUNT(*) FROM voluntarios v WHERE {$where}";

        return $this->paginar($base, $conteo, $params, $pagina, $porPagina, $tope);
    }

    /**
     * TODAS las inscripciones que cumplen el filtro, para exportar.
     *
     * Existe como método propio y no como `listar(..., 1, 5000)` porque eso
     * era justo el fallo: el listado recorta a doscientas filas —que es lo
     * correcto para una pantalla— y la exportación pedía cinco mil sin saber
     * que se las estaban recortando. El CSV salía con doscientas líneas y sin
     * ninguna señal de que faltaba nada.
     *
     * Con dos métodos distintos, cada uno con su tope, el error no se puede
     * repetir: exportar ya no pasa por las reglas de una pantalla.
     *
     * Devuelve además el total real, para poder avisar si se llegó al tope.
     *
     * @param array<string, string> $filtros
     * @return array{filas: array<int, array<string,mixed>>, total: int, recortado: bool}
     */
    public function exportar(array $filtros, bool $conDatosSensibles = false): iterable
    {
        /* ── Sin LIMIT y sin cargar nada en memoria ──────────────────────
           Aquí hubo dos topes puestos por mí, y los dos estaban mal: primero
           200 —heredado de la paginación de pantalla—, luego 20 000. Un tope
           en una exportación es una mentira silenciosa: el archivo se abre,
           tiene filas, parece completo, y le faltan las que no caben.

           Lo que había detrás del tope era un miedo legítimo —que descargar
           la tabla entera agote la memoria de PHP— pero recortar no es
           resolverlo, es esconderlo. Lo que lo resuelve es no cargar la tabla
           en memoria: se pide sin bufferizar, y cada fila se escribe al
           navegador y se suelta antes de leer la siguiente. La memoria que se
           usa es la de UNA fila, den igual diez inscripciones que doscientas
           mil.

           Por eso esto devuelve un generador y no un array: un array sería
           volver a tenerlo todo en memoria por otro camino. */
        [$sql, $params] = $this->consultaDeExportacion($filtros, $conDatosSensibles);

        $pdo = $this->bd()->pdo();
        $bufferizaba = $pdo->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        try {
            $sentencia = $this->bd()->consultar($sql, $params);
            $cripto = $conDatosSensibles ? $this->c->cripto() : null;

            while (($fila = $sentencia->fetch()) !== false) {
                yield $cripto === null ? $fila : self::abrirFila($fila, $cripto);
            }
        } finally {
            // Se restaura pase lo que pase. Dejar la conexión sin bufferizar
            // rompería la siguiente consulta de esta misma petición con un
            // «Cannot execute queries while other unbuffered queries are
            // active», que es de los errores más difíciles de rastrear.
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $bufferizaba);
        }
    }

    /**
     * La consulta de exportación: el mismo WHERE del listado, sin LIMIT, y con
     * las columnas cifradas dentro cuando hacen falta.
     *
     * Van en la MISMA consulta y no en una segunda pasada porque mientras un
     * cursor sin bufferizar está abierto no se puede lanzar otra consulta por
     * la misma conexión. Y también porque es más simple: una consulta, una
     * pasada, sin listas de identificadores que mantener sincronizadas.
     *
     * @param array<string, string> $filtros
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function consultaDeExportacion(array $filtros, bool $conSensibles): array
    {
        [$where, $params] = $this->condiciones($filtros);

        $sensibles = $conSensibles
            ? ', v.dni_cifrado, v.telefono_cifrado, v.direccion_cifrada,
                 v.emergencia_telefono_cifrado, v.emergencia_nombre, v.nacimiento'
            : '';

        $sql = "SELECT v.id, v.codigo, v.nombres, v.dni_mascara, v.telefono_mascara, v.correo,
                       v.estado, v.fase, v.talla, v.creado_en, v.distrito, v.provincia,
                       j.nombre AS jurisdiccion, s.nombre AS servicio,
                       ud.name             AS ubigeo_departamento,
                       up.nombre_provincia AS ubigeo_provincia,
                       ux.nombre_distrito  AS ubigeo_distrito
                       {$sensibles}
                  FROM voluntarios v
                  JOIN jurisdicciones j ON j.id = v.jurisdiccion_id
                  JOIN servicios s      ON s.id = v.servicio_id
                  LEFT JOIN ubigeo_departamento ud ON ud.id = v.ubigeo_departamento_id
                  LEFT JOIN ubigeo_provincia    up ON up.id = v.ubigeo_provincia_id
                  LEFT JOIN ubigeo_distrito     ux ON ux.id = v.ubigeo_distrito_id
                 WHERE {$where}
                 ORDER BY v.creado_en DESC";

        return [$sql, $params];
    }

    /**
     * Descifra los campos sensibles de UNA fila.
     *
     * Cada uno con su propio try: si viene corrupto —una fila vieja, un cambio
     * de clave a medias— se pierde ese dato y no la descarga entera. Un CSV
     * que revienta por una fila mala es un CSV que nadie consigue bajar.
     *
     * @param array<string, mixed> $fila
     * @return array<string, mixed>
     */
    private static function abrirFila(array $fila, Cripto $cripto): array
    {
        $abrir = static function (?string $valor) use ($cripto): string {
            if ($valor === null || $valor === '') {
                return '';
            }
            try {
                return $cripto->descifrar($valor);
            } catch (\Throwable $e) {
                return '(no se pudo leer)';
            }
        };

        $fila['dni']                 = $abrir($fila['dni_cifrado'] ?? null);
        $fila['telefono']            = $abrir($fila['telefono_cifrado'] ?? null);
        $fila['direccion']           = $abrir($fila['direccion_cifrada'] ?? null);
        $fila['emergencia_telefono'] = $abrir($fila['emergencia_telefono_cifrado'] ?? null);

        // Las columnas cifradas no viajan al CSV: sólo su contenido abierto.
        unset(
            $fila['dni_cifrado'],
            $fila['telefono_cifrado'],
            $fila['direccion_cifrada'],
            $fila['emergencia_telefono_cifrado']
        );

        return $fila;
    }

    /** Cuántas inscripciones cumplen el filtro. Para avisar antes de descargar. */
    public function contar(array $filtros): int
    {
        [$where, $params] = $this->condiciones($filtros);

        return (int) $this->bd()->valor(
            "SELECT COUNT(*) FROM voluntarios v WHERE {$where}",
            $params
        );
    }


    /** @return array<string, mixed>|null */
    public function ficha(int $id): ?array
    {
        return $this->bd()->fila(
            'SELECT v.*, j.nombre AS jurisdiccion, s.nombre AS servicio
               FROM voluntarios v
               JOIN jurisdicciones j ON j.id = v.jurisdiccion_id
               JOIN servicios s      ON s.id = v.servicio_id
              WHERE v.id = :id
              LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * Datos sensibles en claro. Sólo se llama tras comprobar el permiso
     * `voluntarios.datos_sensibles`, y quien la llama debe dejar registro en
     * auditoría.
     *
     * @param array<string, mixed> $fila
     * @return array<string, string>
     */
    public function revelar(array $fila): array
    {
        $cripto = $this->c->cripto();

        return [
            'dni'                 => $cripto->descifrar($fila['dni_cifrado'] ?? ''),
            'telefono'            => $cripto->descifrar($fila['telefono_cifrado'] ?? ''),
            'direccion'           => $cripto->descifrar($fila['direccion_cifrada'] ?? ''),
            'emergencia_telefono' => $cripto->descifrar($fila['emergencia_telefono_cifrado'] ?? ''),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function historial(int $id): array
    {
        return $this->bd()->filas(
            'SELECT h.*, u.nombre AS usuario
               FROM voluntarios_historial h
               LEFT JOIN usuarios u ON u.id = h.usuario_id
              WHERE h.voluntario_id = :id
              ORDER BY h.id DESC',
            ['id' => $id]
        );
    }

    public function cambiarEstado(int $id, string $estado, ?string $nota, ?int $usuarioId): void
    {
        $this->bd()->transaccion(function () use ($id, $estado, $nota, $usuarioId): void {
            $anterior = (string) $this->bd()->valor('SELECT estado FROM voluntarios WHERE id = :id', ['id' => $id]);

            // La fase se deduce del estado en lugar de pedirla aparte: si son
            // dos campos independientes, acaban contradiciéndose.
            $fase = match ($estado) {
                'nuevo', 'en_validacion' => $estado === 'nuevo' ? 1 : 2,
                'validado'               => 2,
                'acreditado'             => 3,
                default                  => 1,
            };

            $this->bd()->actualizar('voluntarios', [
                'estado' => $estado,
                'fase'   => $fase,
            ], 'id = :id', ['id' => $id]);

            $this->bd()->insertar('voluntarios_historial', [
                'voluntario_id'   => $id,
                'usuario_id'      => $usuarioId,
                'estado_anterior' => $anterior,
                'estado_nuevo'    => $estado,
                'nota'            => $nota,
            ]);
        });
    }

    /** Baja lógica: una solicitud de supresión no puede romper el histórico. */
    public function darDeBaja(int $id, ?int $usuarioId, ?string $motivo): void
    {
        $this->bd()->transaccion(function () use ($id, $usuarioId, $motivo): void {
            $this->bd()->actualizar('voluntarios', [
                'estado'     => 'baja',
                'borrado_en' => date('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => $id]);

            $this->bd()->insertar('voluntarios_historial', [
                'voluntario_id' => $id,
                'usuario_id'    => $usuarioId,
                'estado_nuevo'  => 'baja',
                'nota'          => $motivo,
            ]);
        });
    }

    /**
     * Departamentos que TIENEN inscripciones, con cuántas.
     *
     * El desplegable del filtro no lista los 25 departamentos del Perú: lista
     * aquellos de los que ha llegado alguien. Un filtro con veinte opciones que
     * no devuelven nada obliga a probarlas una a una para descubrir dónde hay
     * gente; con el recuento al lado, se ve de un vistazo.
     *
     * @return array<int, array{id: string, nombre: string, total: int}>
     */
    public function departamentosConInscripciones(): array
    {
        return $this->bd()->filas(
            'SELECT d.id, d.name AS nombre, COUNT(*) AS total
               FROM voluntarios v
               JOIN ubigeo_departamento d ON d.id = v.ubigeo_departamento_id
              WHERE v.borrado_en IS NULL
              GROUP BY d.id, d.name
              ORDER BY total DESC, d.name'
        );
    }

    /**
     * Provincias con inscripciones dentro de un departamento.
     *
     * @return array<int, array{id: string, nombre: string, total: int}>
     */
    public function provinciasConInscripciones(string $departamentoId): array
    {
        if ($departamentoId === '') {
            return [];
        }

        return $this->bd()->filas(
            'SELECT p.id, p.nombre_provincia AS nombre, COUNT(*) AS total
               FROM voluntarios v
               JOIN ubigeo_provincia p ON p.id = v.ubigeo_provincia_id
              WHERE v.borrado_en IS NULL
                AND v.ubigeo_departamento_id = :d
              GROUP BY p.id, p.nombre_provincia
              ORDER BY total DESC, p.nombre_provincia',
            ['d' => $departamentoId]
        );
    }

    /** @return array<string, string> */
    public static function estados(): array
    {
        return [
            'nuevo'         => 'Sin revisar',
            'en_validacion' => 'En validación',
            'validado'      => 'Validado',
            'acreditado'    => 'Acreditado',
            'rechazado'     => 'Rechazado',
            'baja'          => 'Baja',
        ];
    }
}
