<?php
/**
 * ============================================================================
 *  Inscripcion — validación y alta desde el formulario público.
 * ============================================================================
 *
 *  Repite en el servidor TODAS las comprobaciones que assets/js/form.js hace
 *  en el navegador, y no porque el JavaScript esté mal: la validación de
 *  cliente es comodidad para quien rellena, nunca una defensa. Cualquiera
 *  puede enviar el formulario sin pasar por esa página.
 *
 *  Los mensajes de error van dirigidos a la persona que se inscribe, no al
 *  programador: dicen qué corregir.
 */

declare(strict_types=1);

namespace Intranet\Publico;

use Intranet\Core\ErrorDeNegocio;
use Intranet\Publico\Contingencia;
use Intranet\Models\Catalogo;
use Intranet\Models\Ubigeo;
use Intranet\Models\Voluntario;
use Throwable;

final class Inscripcion
{
    /**
     * Los topes antispam viven en config.php, sección «voluntariado», no como
     * constantes aquí dentro. Estaban quemadas en el código y el límite por IP
     * era de 5/hora: en una parroquia o un colegio, donde decenas de personas
     * se inscriben desde la misma conexión, el sexto envío legítimo se
     * rechazaba sin que nadie pudiera saber por qué.
     */

    /** @var array<string, string> campo => mensaje */
    private array $errores = [];

    /** @var array<string, mixed> */
    private array $limpio = [];

    /**
     * Verdadero cuando la inscripción no pudo entrar en la base y se guardó en
     * el almacén de contingencia. Cambia el mensaje que ve la persona: sigue
     * siendo una confirmación, pero no dice lo mismo.
     */
    private bool $porContingencia = false;

    public function __construct(
        private Sitio $sitio,
        private Voluntario $voluntarios,
        private Catalogo $catalogo
    ) {
    }

    /** @return array<string, string> */
    public function errores(): array
    {
        return $this->errores;
    }

    public function primerError(): ?string
    {
        return $this->errores === [] ? null : reset($this->errores);
    }

    /**
     * Valida y guarda. Devuelve el código de inscripción, o null si algo falló
     * (los motivos quedan en errores()).
     *
     * @param array<string, mixed> $entrada
     */
    public function procesar(array $entrada): ?string
    {
        $peticion = $this->sitio->peticion();

        // ── 0. ¿Está abierta la convocatoria? ───────────────────────────
        //
        // El ajuste vive en la base. Si no se puede leer, se da por abierta:
        // el error más barato es aceptar una inscripción de más estando
        // cerrada; el caro es rechazar a todo el mundo porque una consulta no
        // respondió.
        $abierta = true;

        try {
            $abierta = $this->catalogo->ajusteBool('voluntariado.abierto', true);
        } catch (Throwable $e) {
            error_log('[inscripcion] no se pudo leer si la convocatoria está abierta: ' . $e->getMessage());
        }

        if (!$abierta) {
            $this->errores['_'] = (string) $this->catalogo->ajuste(
                'voluntariado.cerrado_texto',
                'La convocatoria de voluntarios no está abierta en este momento.'
            );

            return null;
        }

        // ── 1. Testigo del formulario ───────────────────────────────────
        //
        // ⚠ DEUDA TÉCNICA · el ajuste `voluntariado.exigir_testigo` permite
        //   aceptar envíos con el testigo caducado.
        //
        //   Está así porque el hosting sirve el formulario desde una caché de
        //   página: el visitante recibe un HTML de hace horas, con un testigo
        //   ya vencido, y su inscripción se rechazaba. Perder inscripciones
        //   reales es peor que el riesgo que cubre el testigo.
        //
        //   El testigo se sigue COMPROBANDO aunque no se exija: lo que cambia
        //   es que un fallo se anota en lugar de cortar el envío. Así se puede
        //   ver en el registro si el problema de caché continúa, antes de
        //   volver a exigirlo.
        //
        //   Para reactivarlo:
        //     UPDATE ajustes SET valor = '1' WHERE clave = 'voluntariado.exigir_testigo';
        // Si no se puede leer el ajuste, NO se exige: una base que no responde
        // no puede convertirse en un motivo más para rechazar a alguien.
        $exigirTestigo = false;

        try {
            $exigirTestigo = $this->catalogo->ajusteBool('voluntariado.exigir_testigo', true);
        } catch (Throwable $e) {
            error_log('[inscripcion] no se pudo leer exigir_testigo: ' . $e->getMessage());
        }

        try {
            $token   = new Token((string) $this->sitio->config('app.clave', ''));
            $valido  = $token->valido((string) ($entrada['_testigo'] ?? ''));

            if (!$valido) {
                error_log(sprintf(
                    '[inscripcion] testigo caducado o inválido · %s · ip %s',
                    $exigirTestigo ? 'ENVÍO RECHAZADO' : 'se deja pasar (exigir_testigo = 0)',
                    $peticion->ip()
                ));
            }

            if (!$valido && $exigirTestigo) {
                // El mensaje dice qué hacer y tranquiliza sobre lo escrito: el
                // borrador se guarda en el navegador, así que recargar no
                // pierde nada. Sin esa frase, quien lleva diez campos escritos
                // no se atreve a recargar y abandona.
                $this->errores['_'] = 'Esta página llevaba demasiado tiempo abierta y el formulario '
                                    . 'expiró. Recárgala y vuelve a enviarlo: lo que escribiste se '
                                    . 'ha guardado y aparecerá de nuevo.';

                return null;
            }
        } catch (Throwable $e) {
            // Aquí no se llega por un testigo malo, sino porque la clave de la
            // aplicación no está configurada. Eso sí es un fallo del sitio.
            error_log('[inscripcion] ' . $e->getMessage());

            if ($exigirTestigo) {
                $this->errores['_'] = 'El formulario no está disponible ahora mismo. Inténtalo más tarde.';

                return null;
            }
        }

        // ── 2. Robots ───────────────────────────────────────────────────
        // La trampa es un campo que ningún humano ve y todo robot rellena.
        if (trim((string) ($entrada['sitio-web'] ?? '')) !== '') {
            $this->errores['_'] = 'No hemos podido registrar el envío.';

            return null;
        }

        $minimo = (int) $this->sitio->config('voluntariado.tiempo_minimo', 3);
        $nacido = (int) ($entrada['_nacido'] ?? 0);

        if ($nacido > 0 && (time() - $nacido) < $minimo) {
            $this->errores['_'] = 'El envío llegó demasiado rápido. Inténtalo de nuevo en unos segundos.';

            return null;
        }

        // ── 3. Límite por IP ────────────────────────────────────────────
        $ip      = $peticion->ipBinaria();
        $tope    = (int) $this->sitio->config('voluntariado.envios_por_ip', 40);
        $ventana = (int) $this->sitio->config('voluntariado.ventana_minutos', 60);

        // Contar envíos recientes es una consulta más. Si falla se da por
        // cero: quedarse sin freno de spam un rato es molesto; rechazar
        // inscripciones legítimas es lo que estamos intentando dejar de hacer.
        // El disparo de robots por esta rendija lo frenan la trampa y el
        // tiempo mínimo, que no necesitan base, y el tope del propio almacén.
        $recientes = 0;

        try {
            $recientes = $this->voluntarios->enviosRecientes($ip, $ventana);
        } catch (Throwable $e) {
            error_log('[inscripcion] no se pudo contar envíos por IP: ' . $e->getMessage());
        }

        if ($recientes >= $tope) {
            // El mensaje dice qué hacer. La versión anterior sólo decía
            // «demasiadas inscripciones», y quien se inscribía desde la
            // parroquia no podía saber que el tope lo había gastado otro.
            $this->errores['_'] = 'Se han recibido muchas inscripciones desde esta misma conexión en la '
                                . 'última hora. Si estáis inscribiendo a un grupo, esperad un rato y '
                                . 'continuad; si no, escríbenos y lo resolvemos.';

            error_log('[inscripcion] tope por IP alcanzado (' . $tope . '/' . $ventana . ' min)');

            return null;
        }

        // ── 4. Codificación ─────────────────────────────────────────────
        // La base es utf8mb4 y trabaja en modo estricto: unos bytes que no
        // sean UTF-8 válido la hacen abortar el INSERT. Mejor detenerlo aquí y
        // decir algo comprensible que dejar salir un error de SQL.
        foreach ($entrada as $clave => $valor) {
            if (is_string($valor) && $valor !== '' && !mb_check_encoding($valor, 'UTF-8')) {
                $this->errores['_'] = 'Algún dato llegó con caracteres que no reconocemos. '
                                    . 'Vuelve a escribirlo y envíalo otra vez.';
                error_log('[inscripcion] campo no UTF-8: ' . $clave);

                return null;
            }
        }

        // ── 5. Campos ───────────────────────────────────────────────────
        //
        // Validar necesita la base: comprueba la jurisdicción, el servicio y
        // el ubigeo contra sus tablas. Si la base no responde, esto revienta
        // ANTES de llegar al alta, y sin este try la persona se encontraría
        // una página rota —ni confirmación ni error, la pantalla en blanco que
        // no puede pasar de ninguna manera—.
        //
        // Cuando ocurre no se puede validar nada, así que se guarda lo
        // recibido y se revisa al recuperarlo. Un dato sin comprobar es
        // recuperable; un dato perdido, no.
        try {
            $this->validar($entrada);
        } catch (Throwable $e) {
            error_log('[inscripcion] no se pudo validar (¿base caída?): ' . $e->getMessage());

            $codigo = $this->contingencia()->guardar($this->camposConocidos($entrada), [
                'motivo'      => 'la base no respondió al validar: ' . $e->getMessage(),
                'sin_validar' => true,
                'ip'          => $peticion->ip(),
                'navegador'   => $peticion->agente(),
            ]);

            if ($codigo !== null) {
                $this->porContingencia = true;

                return $codigo;
            }

            $this->errores['_'] = 'No hemos podido registrar tu inscripción. Inténtalo de nuevo en unos minutos.';

            return null;
        }

        if ($this->errores !== []) {
            return null;
        }

        // ── 6. Alta ─────────────────────────────────────────────────────
        try {
            $resultado = $this->voluntarios->registrar($this->limpio, $ip, $peticion->agente());

            return $resultado['codigo'];
        } catch (ErrorDeNegocio $e) {
            // Motivo previsto y redactado para quien se inscribe, como el DNI
            // repetido. Se muestra tal cual… salvo un caso.
            //
            // ── El reintento que parece un duplicado ────────────────────
            // Si el alta funciona pero la respuesta se pierde por el camino
            // —el servidor tarda diez segundos y el navegador se cansa—, el
            // formulario reintenta. El segundo intento encuentra el DNI ya
            // inscrito y diría «ese DNI ya está inscrito» a alguien que acaba
            // de inscribirse correctamente hace dos segundos. Un error, en la
            // cara, por haber hecho todo bien.
            //
            // Si ese DNI se inscribió hace un momento, no es un duplicado: es
            // el mismo envío llegando dos veces. Se devuelve su código y la
            // persona ve su confirmación, que es la verdad.
            if ($e->campo() === 'dni') {
                $reciente = $this->inscripcionReciente(
                    (string) ($this->limpio['dni'] ?? ''),
                    (string) ($this->limpio['correo'] ?? '')
                );

                if ($reciente !== null) {
                    error_log('[inscripcion] reintento del mismo envío, se devuelve ' . $reciente);

                    return $reciente;
                }
            }

            $this->errores[$e->campo()] = $e->getMessage();

            return null;
        } catch (Throwable $e) {
            // ── La base no pudo guardar ────────────────────────────────
            //
            // Antes esto era un callejón sin salida: se enseñaba «inténtalo de
            // nuevo en unos minutos» a alguien que había rellenado cuatro
            // pasos, y casi nadie vuelve.
            //
            // Ahora la inscripción se escribe cifrada en un archivo y se da
            // por recibida. No es lo mismo que estar en la base —hay que
            // recuperarla a mano— pero el dato está a salvo y la persona sabe
            // que llegó.
            error_log('[inscripcion] alta fallida: ' . $e->getMessage());

            $codigo = $this->contingencia()->guardar($this->limpio, [
                'motivo'    => $e->getMessage(),
                'ip'        => $peticion->ip(),
                'navegador' => $peticion->agente(),
            ]);

            if ($codigo !== null) {
                $this->porContingencia = true;

                return $codigo;
            }

            // Ni la base ni el archivo. Ahora sí no hay nada que hacer, y
            // decirlo es mejor que fingir que se guardó.
            $this->errores['_'] = 'No hemos podido registrar tu inscripción. Inténtalo de nuevo en unos minutos.';

            return null;
        }
    }

    /**
     * ¿Se inscribió este DNI en los últimos minutos?
     *
     * Sirve para distinguir un reintento del mismo envío de una inscripción
     * duplicada de verdad. Media hora es de sobra para lo primero y demasiado
     * poco para confundirse con lo segundo: quien se inscribe dos veces lo
     * hace con días de diferencia, no con segundos.
     */
    private function inscripcionReciente(string $dni, string $correo): ?string
    {
        if ($dni === '' || $correo === '') {
            return null;
        }

        try {
            return $this->voluntarios->codigoDeReintento($dni, $correo, 30);
        } catch (Throwable $e) {
            error_log('[inscripcion] no se pudo comprobar el reintento: ' . $e->getMessage());

            return null;
        }
    }

    private function contingencia(): Contingencia
    {
        return new Contingencia($this->sitio->contenedor()->cripto());
    }

    /**
     * Los campos del formulario y nada más.
     *
     * Se usa cuando la base no responde y hay que guardar sin poder validar.
     * Guardar `$_POST` entero sería aceptar cualquier cosa que alguien decida
     * mandar —campos inventados, cargas enormes— dentro de un archivo nuestro.
     * Esta lista es la frontera: lo que no está aquí, no se guarda.
     *
     * @param array<string, mixed> $entrada
     * @return array<string, string>
     */
    private function camposConocidos(array $entrada): array
    {
        $campos = [
            'dni', 'nombres', 'nacimiento', 'nacimiento_dia', 'nacimiento_mes', 'nacimiento_anio',
            'ubigeo_departamento_id', 'ubigeo_provincia_id', 'ubigeo_distrito_id',
            'provincia_nombre', 'distrito_nombre', 'direccion',
            'correo', 'telefono', 'emergencia_nombre', 'emergencia_telefono',
            'jurisdiccion_id', 'servicio_id', 'talla', 'consentimiento',
        ];

        $limpio = [];

        foreach ($campos as $campo) {
            $valor = $entrada[$campo] ?? null;

            if (is_string($valor) && $valor !== '') {
                // 500 caracteres es mucho más de lo que cabe en cualquiera de
                // estos campos y corta en seco un envío hinchado a propósito.
                $limpio[$campo] = mb_substr(trim($valor), 0, 500);
            }
        }

        return $limpio;
    }

    /** ¿La última inscripción se guardó por la vía de contingencia? */
    public function porContingencia(): bool
    {
        return $this->porContingencia;
    }

    /** @param array<string, mixed> $e */
    private function validar(array $e): void
    {
        $texto = static fn (string $c): string => trim((string) ($e[$c] ?? ''));

        // ── Nombres ─────────────────────────────────────────────────────
        $nombres = $texto('nombres');
        if ($nombres === '') {
            $this->errores['nombres'] = 'Escribe tus nombres y apellidos.';
        } elseif (mb_strlen($nombres) < 5) {
            $this->errores['nombres'] = 'Escribe el nombre completo, con apellidos.';
        } elseif (mb_strlen($nombres) > 160) {
            $this->errores['nombres'] = 'Ese nombre es demasiado largo para el formulario.';
        } else {
            $this->limpio['nombres'] = $nombres;
        }

        // ── DNI ─────────────────────────────────────────────────────────
        $dni = preg_replace('/\D/', '', $texto('dni')) ?? '';
        if ($dni === '') {
            $this->errores['dni'] = 'Escribe tu DNI.';
        } elseif (!preg_match('/^\d{8}$/', $dni)) {
            // Mismo texto que usa el navegador (reglas.dni en form.js). Ver el
            // mismo aviso escrito de dos maneras según por dónde llegue el
            // error es desconcertante, y además delata que hay dos sitios
            // donde tocarlo.
            $this->errores['dni'] = 'Deben ser 8 dígitos.';
        } else {
            $this->limpio['dni'] = $dni;
        }

        // ── Nacimiento ──────────────────────────────────────────────────
        //
        // Llega en tres desplegables (día, mes, año) y se compone aquí. Se
        // acepta también el campo `nacimiento` en formato ISO por si algún
        // envío antiguo o automatizado lo manda así.
        $nacimiento = $texto('nacimiento');

        if ($nacimiento === '') {
            $dia  = (int) ($e['nacimiento_dia'] ?? 0);
            $mes  = (int) ($e['nacimiento_mes'] ?? 0);
            $anio = (int) ($e['nacimiento_anio'] ?? 0);

            // checkdate rechaza el 31 de febrero y el 30 de febrero bisiesto:
            // con tres desplegables independientes, esas combinaciones se
            // pueden elegir, y sin esta comprobación PHP las «arreglaría»
            // solo, convirtiendo el 31 de febrero en el 3 de marzo.
            if ($dia > 0 && $mes > 0 && $anio > 0 && checkdate($mes, $dia, $anio)) {
                $nacimiento = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            } elseif ($dia > 0 || $mes > 0 || $anio > 0) {
                $this->errores['nacimiento'] = 'Esa fecha no existe. Revisa el día, el mes y el año.';
            }
        }

        $fecha = $nacimiento !== '' ? \DateTimeImmutable::createFromFormat('Y-m-d', $nacimiento) : false;

        if (isset($this->errores['nacimiento'])) {
            // Ya hay un error más concreto; no se pisa con el genérico.
        } elseif ($nacimiento === '') {
            $this->errores['nacimiento'] = 'Elige tu fecha de nacimiento.';
        } elseif ($fecha === false || $fecha->format('Y-m-d') !== $nacimiento) {
            $this->errores['nacimiento'] = 'Revisa la fecha de nacimiento.';
        } elseif ($fecha > new \DateTimeImmutable('today') || (int) $fecha->format('Y') < 1900) {
            $this->errores['nacimiento'] = 'Revisa la fecha de nacimiento.';
        } else {
            // La mayoría de edad es un ajuste, no una constante: el cliente
            // todavía no ha decidido si se admiten menores, y si los admite
            // harán falta consentimiento de tutor y protocolo de salvaguarda.
            if ($this->catalogo->ajusteBool('voluntariado.exige_mayoria_edad', false)) {
                $edad = $fecha->diff(new \DateTimeImmutable('today'))->y;

                if ($edad < 18) {
                    $this->errores['nacimiento'] = 'La inscripción está abierta a mayores de 18 años.';
                }
            }

            if (!isset($this->errores['nacimiento'])) {
                $this->limpio['nacimiento'] = $nacimiento;
            }
        }

        // ── Ubigeo ──────────────────────────────────────────────────────
        //
        // El departamento se elige de una lista cerrada y se exige que exista:
        // son 25, caben en un desplegable y esa lista la pinta el servidor con
        // la página, así que nunca falta.
        //
        // La provincia y el distrito se ESCRIBEN, y aquí se aceptan tal cual
        // vengan. Antes se rechazaba todo lo que no fuera un código válido, y
        // eso convertía cualquier tropiezo de la red en una inscripción
        // perdida: si las sugerencias no cargaban, no había código que mandar
        // y no había forma de pasar. Se comprobó en producción y varias
        // personas lo dijeron el primer día.
        //
        // El criterio ahora es otro: un nombre mal escrito se corrige después
        // desde el panel en diez segundos; una persona que se rinde no vuelve.
        // Así que sólo se exige que haya escrito algo.
        //
        // Cuando el código SÍ llega, se comprueba de verdad —tiene que encajar
        // con su departamento— porque un código inventado sería peor que
        // ninguno: parecería un dato verificado sin serlo.
        $departamento    = $texto('ubigeo_departamento_id');
        $provinciaId     = $texto('ubigeo_provincia_id');
        $distritoId      = $texto('ubigeo_distrito_id');
        $provinciaTexto  = $texto('provincia_nombre');
        $distritoTexto   = $texto('distrito_nombre');

        // ── Compatibilidad con la página anterior ───────────────────────
        // Hasta hace nada provincia y distrito eran desplegables y sólo se
        // enviaba el código, sin nombre. Esas páginas siguen vivas: en la
        // caché del hosting, en el navegador de quien ya la visitó, en una
        // pestaña abierta desde ayer. Si exigiéramos el nombre, toda esa gente
        // se encontraría de golpe con que no puede enviar —justo el problema
        // que estamos arreglando, otra vez y por nuestra culpa—.
        //
        // Si viene un código sin nombre, el nombre se saca del código.
        if ($provinciaTexto === '' && $provinciaId !== '') {
            $provinciaTexto = $this->nombreDeCodigo('provincia', $departamento, $provinciaId);
        }
        if ($distritoTexto === '' && $distritoId !== '') {
            $distritoTexto = $this->nombreDeCodigo('distrito', $provinciaId, $distritoId);
        }

        if ($departamento === '') {
            $this->errores['ubigeo_departamento_id'] = 'Elige tu departamento.';
        }
        if ($provinciaTexto === '') {
            $this->errores['ubigeo_provincia_id'] = 'Escribe tu provincia.';
        }
        if ($distritoTexto === '') {
            $this->errores['ubigeo_distrito_id'] = 'Escribe tu distrito.';
        }

        if ($departamento !== '' && $provinciaTexto !== '' && $distritoTexto !== '') {
            $ubigeo = new Ubigeo($this->sitio->contenedor());

            // El departamento sí se valida contra la lista: viene de un
            // desplegable, y si no está es que alguien manipuló el envío.
            $existeDep = false;
            foreach ($ubigeo->departamentos() as $d) {
                if ((string) $d['id'] === $departamento) { $existeDep = true; break; }
            }

            if (!$existeDep) {
                $this->errores['ubigeo_departamento_id'] = 'Elige tu departamento.';
            } else {
                $this->limpio['ubigeo_departamento_id'] = $departamento;

                // ── Provincia ──────────────────────────────────────────
                // Se acepta el código sólo si pertenece a este departamento.
                // Si no llegó código, se intenta reconocer el nombre escrito.
                $provinciaOk = null;

                if ($provinciaId !== '') {
                    foreach ($ubigeo->provincias($departamento) as $p) {
                        if ((string) $p['id'] === $provinciaId) {
                            $provinciaOk = ['id' => (string) $p['id'], 'nombre' => (string) $p['nombre']];
                            break;
                        }
                    }
                }

                if ($provinciaOk === null) {
                    $provinciaOk = $ubigeo->provinciaPorNombre($departamento, $provinciaTexto);
                }

                if ($provinciaOk !== null) {
                    $this->limpio['ubigeo_provincia_id'] = $provinciaOk['id'];
                    $this->limpio['provincia'] = mb_substr($provinciaOk['nombre'], 0, 96);
                } else {
                    // Sin código: se guarda lo tecleado y se deja pasar.
                    $this->limpio['ubigeo_provincia_id'] = null;
                    $this->limpio['provincia'] = mb_substr($provinciaTexto, 0, 96);
                }

                // ── Distrito ───────────────────────────────────────────
                // Sólo puede tener código si la provincia lo tiene: un
                // distrito cuelga de una provincia concreta.
                $distritoOk = null;
                $provinciaResuelta = $this->limpio['ubigeo_provincia_id'] ?? null;

                if ($provinciaResuelta !== null) {
                    if ($distritoId !== '') {
                        foreach ($ubigeo->distritos($provinciaResuelta) as $x) {
                            if ((string) $x['id'] === $distritoId) {
                                $distritoOk = ['id' => (string) $x['id'], 'nombre' => (string) $x['nombre']];
                                break;
                            }
                        }
                    }

                    if ($distritoOk === null) {
                        $distritoOk = $ubigeo->distritoPorNombre($provinciaResuelta, $distritoTexto);
                    }
                }

                if ($distritoOk !== null) {
                    $this->limpio['ubigeo_distrito_id'] = $distritoOk['id'];
                    $this->limpio['distrito'] = mb_substr($distritoOk['nombre'], 0, 96);
                } else {
                    $this->limpio['ubigeo_distrito_id'] = null;
                    $this->limpio['distrito'] = mb_substr($distritoTexto, 0, 96);
                }
            }
        }

        // ── Dirección ───────────────────────────────────────────────────
        // Ya sólo la calle y el número: el distrito, la provincia y el
        // departamento vienen de los desplegables.
        $direccion = $texto('direccion');
        if ($direccion === '') {
            $this->errores['direccion'] = 'Escribe tu dirección.';
        } elseif (mb_strlen($direccion) < 5) {
            $this->errores['direccion'] = 'Incluye la calle y el número.';
        } else {
            $this->limpio['direccion'] = $direccion;
        }

        // ── Correo ──────────────────────────────────────────────────────
        $correo = mb_strtolower($texto('correo'));
        if ($correo === '') {
            $this->errores['correo'] = 'Escribe tu correo electrónico.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 190) {
            $this->errores['correo'] = 'Ese correo no parece válido. Revísalo e inténtalo otra vez.';
        } else {
            $this->limpio['correo'] = $correo;
        }

        // ── Teléfono propio ─────────────────────────────────────────────
        $telefono = preg_replace('/[\s()+-]/', '', $texto('telefono')) ?? '';

        if ($telefono === '') {
            $this->errores['telefono'] = 'Escribe tu número telefónico.';
        } elseif (!preg_match('/^\d{6,15}$/', $telefono)) {
            $this->errores['telefono'] = 'Escribe el número sin espacios ni guiones.';
        } else {
            $this->limpio['telefono'] = $telefono;
        }

        // ── Contacto de emergencia · OPCIONAL ───────────────────────────
        //
        // Se pide, pero no se exige: quien no lo tenga a mano no puede quedarse
        // sin inscribirse por eso. La organización lo reclamará en la Fase 02.
        //
        // Ahora bien, si lo escriben, tiene que estar bien: un teléfono de
        // emergencia mal copiado es peor que no tenerlo, porque nadie
        // descubrirá que no sirve hasta el día que haga falta.
        $emergencia = $texto('emergencia_nombre');

        if ($emergencia !== '' && mb_strlen($emergencia) > 160) {
            $this->errores['emergencia_nombre'] = 'Ese nombre es demasiado largo.';
        } else {
            $this->limpio['emergencia_nombre'] = $emergencia !== '' ? $emergencia : null;
        }

        $emergenciaTel = preg_replace('/[\s()+-]/', '', $texto('emergencia_telefono')) ?? '';

        if ($emergenciaTel !== '' && !preg_match('/^\d{6,15}$/', $emergenciaTel)) {
            $this->errores['emergencia_telefono'] = 'Escribe el número sin espacios ni guiones, o déjalo vacío.';
        } else {
            $this->limpio['emergencia_telefono'] = $emergenciaTel !== '' ? $emergenciaTel : null;
        }

        // ── Talla ───────────────────────────────────────────────────────
        $talla = strtoupper($texto('talla'));
        if (!in_array($talla, ['S', 'M', 'L', 'XL', 'XXL'], true)) {
            $this->errores['talla'] = 'Elige una talla de polo.';
        } else {
            $this->limpio['talla'] = $talla;
        }

        // ── Jurisdicción y servicio ─────────────────────────────────────
        // Se comprueba contra la base, no contra una lista escrita aquí: son
        // catálogos que el panel puede cambiar.
        $jurisdiccion = (int) ($e['jurisdiccion_id'] ?? 0);
        if ($jurisdiccion <= 0 || !$this->catalogo->jurisdiccionValida($jurisdiccion)) {
            $this->errores['jurisdiccion_id'] = 'Elige la jurisdicción en la que quieres servir.';
        } else {
            $this->limpio['jurisdiccion_id'] = $jurisdiccion;
        }

        $servicio = (int) ($e['servicio_id'] ?? 0);
        if ($servicio <= 0 || !$this->catalogo->servicioValido($servicio)) {
            $this->errores['servicio_id'] = 'Elige el servicio que prefieres.';
        } else {
            $this->limpio['servicio_id'] = $servicio;
        }

        // ── Consentimiento ──────────────────────────────────────────────
        $acepta = in_array($e['consentimiento'] ?? null, ['1', 'on', 'true', 'si', 1, true], true);
        if (!$acepta) {
            $this->errores['consentimiento'] = 'Necesitamos tu consentimiento para continuar.';
        } else {
            // Se guarda QUÉ versión del texto aceptó, no sólo que aceptó: si
            // el texto legal cambia, hay que poder demostrar cuál firmó cada
            // persona.
            $this->limpio['consentimiento_version'] = (string) $this->catalogo->ajuste(
                'consentimiento.version',
                'sin-version'
            );
        }
    }

    /**
     * El nombre de una provincia o un distrito a partir de su código.
     *
     * Sólo lo usa la compatibilidad con la página anterior, que mandaba el
     * código sin el nombre. Devuelve cadena vacía si el código no existe o no
     * pertenece a su padre; entonces el envío se trata como si no hubiera
     * escrito nada, que es lo correcto: un código suelto e inválido no es
     * información sobre dónde vive nadie.
     */
    private function nombreDeCodigo(string $nivel, string $padre, string $codigo): string
    {
        if ($padre === '' || $codigo === '') {
            return '';
        }

        $ubigeo = new Ubigeo($this->sitio->contenedor());
        $lista  = $nivel === 'provincia' ? $ubigeo->provincias($padre) : $ubigeo->distritos($padre);

        foreach ($lista as $fila) {
            if ((string) $fila['id'] === $codigo) {
                return (string) $fila['nombre'];
            }
        }

        return '';
    }
}
