<?php
/**
 * ============================================================================
 *  Plantillas — qué campos tiene cada tipo de sección.
 * ============================================================================
 *
 *  El panel no puede enseñar un formulario genérico con las quince columnas de
 *  `secciones` y las nueve de `bloques`: la mitad no aplican a cada sección, y
 *  un editor de contenidos frente a doce campos vacíos sin saber cuáles
 *  importan acaba rellenando los que no debe.
 *
 *  Aquí se declara, por plantilla:
 *   · qué campos propios de la sección se muestran y cómo se editan;
 *   · si admite bloques repetibles, cómo se llaman y qué campos tiene cada uno;
 *   · qué claves guarda en la columna JSON `datos`.
 *
 *  ESTE ES EL PUNTO DE EXTENSIÓN DE LA FASE 2: hacer administrable una sección
 *  de la portada es añadir una entrada a este mapa y leerla desde index.php.
 *  Ni una tabla nueva, ni un controlador nuevo.
 */

declare(strict_types=1);

namespace Intranet\Cms;

final class Plantillas
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function todas(): array
    {
        return [
            // ── Voluntariado ────────────────────────────────────────────
            'cabecera_pagina' => [
                'nombre'  => 'Cabecera de página',
                'ayuda'   => 'La franja con la fotografía grande, el título y la bajada. Es la '
                           . 'portada de la página: si no eliges foto, queda la banda roja.',
                'campos'  => ['rotulo', 'titulo', 'texto', 'imagen', 'imagen_movil'],
                'bloques' => null,
                'datos'   => [],
            ],

            'pasos_numerados' => [
                'nombre'  => 'Pasos numerados',
                'ayuda'   => 'Una lista de pasos con número correlativo. El número se pone solo según el orden.',
                'campos'  => ['rotulo', 'titulo'],
                'bloques' => [
                    'nombre'   => 'Paso',
                    'plural'   => 'Pasos',
                    'campos'   => ['titulo', 'texto', 'enlace_texto', 'enlace_url'],
                    'maximo'   => 6,
                ],
                'datos'   => [],
            ],

            'tarjetas_icono' => [
                'nombre'  => 'Tarjetas con icono',
                'ayuda'   => 'Las tarjetas salen del catálogo de Servicios, no de aquí: son la misma '
                           . 'lista que llena el desplegable del formulario. Aquí se edita el marco: '
                           . 'el titular y el texto de cierre.',
                'campos'  => ['rotulo', 'titulo', 'texto'],
                'bloques' => null,
                'datos'   => [
                    'cierre'      => ['etiqueta' => 'Líneas de cierre', 'tipo' => 'lista'],
                    'grito'       => ['etiqueta' => 'Última línea destacada', 'tipo' => 'texto'],
                    'boton_texto' => ['etiqueta' => 'Texto del botón', 'tipo' => 'texto'],
                    'boton_url'   => ['etiqueta' => 'Destino del botón', 'tipo' => 'texto'],
                ],
            ],

            'fases' => [
                'nombre'  => 'Fases del proceso',
                'ayuda'   => 'Cada fase con su número, su explicación y su lista de requisitos.',
                'campos'  => ['rotulo', 'titulo', 'texto'],
                'bloques' => [
                    'nombre' => 'Fase',
                    'plural' => 'Fases',
                    'campos' => ['rotulo', 'titulo', 'texto'],
                    'datos'  => [
                        'vinetas' => ['etiqueta' => 'Puntos de la lista', 'tipo' => 'lista'],
                    ],
                    'maximo' => 6,
                ],
                'datos'   => [],
            ],

            'formulario' => [
                'nombre'  => 'Formulario de inscripción',
                'ayuda'   => 'Los CAMPOS del formulario no se editan aquí: los fija la tabla de '
                           . 'voluntarios. Lo que se edita son los textos que lo rodean.',
                'campos'  => ['rotulo', 'titulo'],
                'bloques' => null,
                'datos'   => [
                    'ten_a_mano_titulo' => ['etiqueta' => 'Título del recuadro previo', 'tipo' => 'texto'],
                    'ten_a_mano'        => ['etiqueta' => 'Lista «Ten a mano»', 'tipo' => 'lista'],
                    'nota'              => ['etiqueta' => 'Nota al pie del recuadro', 'tipo' => 'texto'],
                    'consentimiento'    => [
                        'etiqueta' => 'Texto legal del consentimiento',
                        'tipo'     => 'area',
                        'ayuda'    => 'Este texto es el que acepta cada voluntario y queda guardado con '
                                    . 'su inscripción. Antes de abrir la convocatoria debe revisarlo el '
                                    . 'asesor legal: hoy sigue con los marcadores entre corchetes.',
                    ],
                    'ancla_rotulo'      => ['etiqueta' => 'Panel flotante · rótulo', 'tipo' => 'texto'],
                    'ancla_titulo'      => ['etiqueta' => 'Panel flotante · título', 'tipo' => 'texto'],
                    'ancla_dato'        => ['etiqueta' => 'Panel flotante · dato', 'tipo' => 'texto'],
                ],
            ],

            // ── Fase 3 · lo que pide el informe del cliente ──────────────
            'carrusel_hero' => [
                'nombre'  => 'Carrusel de portada',
                'ayuda'   => 'Las láminas a pantalla completa de la portada. Cada una con su '
                           . 'fotografía, su frase y sus botones. La primera es la que ve todo '
                           . 'el mundo: si sólo se quiere una imagen fija, se deja una sola.',
                'campos'  => ['rotulo', 'titulo', 'texto'],
                'bloques' => [
                    'nombre' => 'Lámina',
                    'plural' => 'Láminas',
                    'campos' => ['rotulo', 'titulo', 'texto', 'imagen', 'imagen_movil', 'enlace_texto', 'enlace_url'],
                    // El diseño viaja en `datos`, que es una columna JSON que la
                    // tabla `bloques` ya tiene. Así no hace falta ni una columna
                    // nueva ni un ALTER: una lámina sin este valor se pinta
                    // exactamente como se pintaba antes.
                    //
                    // Es un campo de texto porque el editor del panel sabe pintar
                    // tres cosas —imagen, área y texto— y añadir un desplegable
                    // obligaría a tocar la vista del panel. La vista pública
                    // normaliza lo que llegue: cualquier cosa que no sea «fondo»
                    // cae en el diseño partido, que es el de siempre. Una errata
                    // no rompe la portada, sólo no cambia el diseño.
                    'datos'  => [
                        'diseno' => [
                            'etiqueta' => 'Diseño de la lámina — escribe «fondo» para la '
                                        . 'fotografía a sangre con el texto encima; déjalo '
                                        . 'vacío para el diseño partido, con el texto en el '
                                        . 'panel de color al lado de la imagen',
                            'tipo'     => 'texto',
                        ],
                    ],
                    'maximo' => 8,
                ],
                'datos'   => [
                    'lema'    => ['etiqueta' => 'Lema principal', 'tipo' => 'texto',
                                  'ayuda' => 'La frase grande. Hoy: «Abramos el corazón».'],
                    'sublema' => ['etiqueta' => 'Texto secundario', 'tipo' => 'texto'],
                ],
            ],

            'contador' => [
                'nombre'  => 'Cuenta atrás',
                'ayuda'   => 'Los días que faltan. La fecha se toma de Configuración general '
                           . '—inicio del viaje—, así que cambiarla allí la cambia aquí.',
                'campos'  => ['rotulo', 'titulo', 'texto', 'imagen'],
                'bloques' => null,
                'datos'   => [
                    'antes'   => ['etiqueta' => 'Texto antes del número', 'tipo' => 'texto'],
                    'despues' => ['etiqueta' => 'Texto después del número', 'tipo' => 'texto'],
                ],
            ],

            'tarjetas_foto' => [
                'nombre'  => 'Tarjetas con fotografía',
                'ayuda'   => 'Una rejilla de tarjetas, cada una con su foto, su titular y su '
                           . 'enlace. Sirve para las sedes, los santos y los accesos destacados.',
                'campos'  => ['rotulo', 'titulo', 'texto_html'],
                'bloques' => [
                    'nombre' => 'Tarjeta',
                    'plural' => 'Tarjetas',
                    'campos' => ['rotulo', 'titulo', 'texto', 'imagen', 'imagen_movil', 'enlace_texto', 'enlace_url'],
                    'datos'  => [
                        'resumen' => ['etiqueta' => 'Resumen', 'tipo' => 'area',
                                      'ayuda' => 'Una o dos líneas, para el listado y para cuando se '
                                               . 'comparte el enlace. Si lo dejas vacío se usan las '
                                               . 'primeras líneas del texto.'],
                    ],
                    'maximo' => 12,
                ],
                'datos'   => [],
            ],

            'jornadas' => [
                'nombre'  => 'Itinerario por jornadas',
                'ayuda'   => 'Un día por bloque: la fecha en el rótulo, la sede en el titular y '
                           . 'las actividades en la lista. El programa oficial todavía no está '
                           . 'publicado, así que lo que hay ahora es referencial.',
                'campos'  => ['rotulo', 'titulo', 'texto_html'],
                'bloques' => [
                    'nombre' => 'Jornada',
                    'plural' => 'Jornadas',
                    'campos' => ['rotulo', 'titulo', 'texto', 'imagen', 'imagen_movil', 'enlace_texto', 'enlace_url'],
                    'datos'  => [
                        'actividades' => ['etiqueta' => 'Actividades del día', 'tipo' => 'lista'],
                    ],
                    'maximo' => 12,
                ],
                'datos'   => [],
            ],

            'noticias' => [
                'nombre'  => 'Noticias',
                'ayuda'   => 'Cada noticia con su fecha, su titular, su sumario y su enlace. '
                           . 'Las más recientes arriba; usa las flechas para ordenarlas.',
                'campos'  => ['rotulo', 'titulo', 'texto_html'],
                'bloques' => [
                    'nombre' => 'Noticia',
                    'plural' => 'Noticias',
                    'campos' => ['titulo', 'texto', 'imagen', 'imagen_movil', 'enlace_texto', 'enlace_url'],
                    'datos'  => [
                        'fecha'  => ['etiqueta' => 'Fecha de publicación', 'tipo' => 'texto',
                                     'ayuda' => 'Como se quiere leer: «5 de agosto de 2026».'],
                        'fuente' => ['etiqueta' => 'Fuente', 'tipo' => 'texto'],
                    ],
                    'maximo' => 30,
                ],
                'datos'   => [],
            ],

            'hitos' => [
                'nombre'  => 'Hitos del camino',
                'ayuda'   => 'El carrusel de la portada con lo que ya ha pasado y lo que está '
                           . 'por venir. Cada hito lleva su fecha, su explicación y su estado: '
                           . 'cumplido, previsto o pendiente.',
                'campos'  => ['rotulo', 'titulo', 'texto_html'],
                'bloques' => [
                    'nombre' => 'Hito',
                    'plural' => 'Hitos',
                    'campos' => ['rotulo', 'titulo', 'texto', 'imagen'],
                    'datos'  => [
                        'estado' => ['etiqueta' => 'Estado', 'tipo' => 'texto',
                                     'ayuda' => 'Escribe «cumplido», «previsto» o «pendiente». '
                                              . 'Cualquier otra cosa se muestra tal cual, sin color.'],
                    ],
                    'maximo' => 12,
                ],
                'datos'   => [],
            ],

            'accesos' => [
                'nombre'  => 'Accesos con icono',
                'ayuda'   => 'Una fila de accesos breves, cada uno con su icono, su titular, '
                           . 'una línea de explicación y su destino. Los iconos están dibujados '
                           . 'para el sitio y se asignan por orden: no se eligen desde aquí.',
                'campos'  => ['rotulo', 'titulo', 'texto_html'],
                'bloques' => [
                    'nombre' => 'Acceso',
                    'plural' => 'Accesos',
                    'campos' => ['titulo', 'texto', 'enlace_texto', 'enlace_url'],
                    'datos'  => [
                        'nota' => ['etiqueta' => 'Nota al pie', 'tipo' => 'texto',
                                   'ayuda' => 'La línea pequeña del final. Hoy dice «Próximamente».'],
                    ],
                    'maximo' => 8,
                ],
                'datos'   => [],
            ],

            'personas' => [
                'nombre'  => 'Personas',
                'ayuda'   => 'Fichas de personas: nombre, cargo, jurisdicción y fotografía. '
                           . 'Sirve para la presidencia de la CEP, los obispos y los '
                           . 'responsables de las comisiones.',
                'campos'  => ['rotulo', 'titulo', 'texto_html'],
                'bloques' => [
                    'nombre' => 'Persona',
                    'plural' => 'Personas',
                    'campos' => ['titulo', 'rotulo', 'texto', 'imagen', 'imagen_movil', 'enlace_texto', 'enlace_url'],
                    'datos'  => [
                        // Los lee la página propia de cada ficha. Estaban en la
                        // base desde la primera migración y no los declaraba
                        // nadie: no se podían editar, y el primer «Guardar» de
                        // la sección se los llevaba por delante.
                        'anios'   => ['etiqueta' => 'Años', 'tipo' => 'texto',
                                      'ayuda' => 'Como se quiere leer: «1586 – 1617».'],
                        'resumen' => ['etiqueta' => 'Resumen', 'tipo' => 'area',
                                      'ayuda' => 'Una o dos líneas. Es lo que sale en el listado y '
                                               . 'al compartir el enlace.'],
                    ],
                    'maximo' => 60,
                ],
                'datos'   => [],
            ],

            // ── La colecta nacional ─────────────────────────────────────
            //
            // Plantilla NUEVA. No sustituye ni modifica ninguna de las que ya
            // había: se añade al mapa y con eso el panel ya sabe dibujarla,
            // porque el formulario se genera recorriendo estas declaraciones.
            //
            // Cada cuenta es un bloque. El número y el CCI van en `datos` y no
            // en columnas propias: son dos cadenas de dígitos y la tabla
            // `bloques` ya tiene su columna JSON. Cero cambios de esquema.
            'colecta' => [
                'nombre'  => 'Colecta nacional',
                'ayuda'   => 'La llamada a colaborar con la visita, con las cuentas para el '
                           . 'depósito. Los números se copian tal cual del comunicado oficial '
                           . 'de la Conferencia Episcopal: un dígito cambiado manda el dinero '
                           . 'de alguien a otra cuenta. Revísalos dos veces antes de guardar.',
                'campos'  => ['rotulo', 'titulo', 'subtitulo', 'texto_html', 'cta_texto', 'cta_url'],
                'bloques' => [
                    'nombre' => 'Cuenta',
                    'plural' => 'Cuentas',
                    'campos' => ['rotulo', 'titulo'],
                    'datos'  => [
                        'numero' => ['etiqueta' => 'Número de cuenta', 'tipo' => 'texto'],
                        'cci'    => ['etiqueta' => 'Código de cuenta interbancario (CCI)', 'tipo' => 'texto'],
                    ],
                    'maximo' => 6,
                ],
                'datos'   => [
                    // El encabezado que /donativo/ pone encima de la lista de
                    // cuentas. La portada no lo usa: allí la sección ya tiene su
                    // propio titular.
                    'titulo_cuentas' => ['etiqueta' => 'Encabezado de las cuentas en la página de donativos', 'tipo' => 'texto'],
                    'nota'           => ['etiqueta' => 'Nota al pie de las cuentas', 'tipo' => 'area'],
                ],
            ],

            // ── A qué se destina el aporte ──────────────────────────────
            //
            // Plantilla NUEVA, para la sección «destinan» de /donativo/. Sus
            // tres tarjetas estaban escritas en la vista y no había forma de
            // tocarlas sin desplegar.
            //
            // El icono se escribe con el nombre del símbolo del sprite
            // (assets/parciales/sprite.php). Si se deja vacío o se escribe uno
            // que no existe, la vista pinta el del corazón y la tarjeta sigue
            // funcionando: una errata aquí no rompe la página.
            'destinos_aporte' => [
                'nombre'  => 'Destinos del aporte',
                'ayuda'   => 'Las tarjetas de «A qué se destinan». Cada una con su icono, su '
                           . 'título y su explicación.',
                'campos'  => ['rotulo', 'titulo', 'texto_html'],
                'bloques' => [
                    'nombre' => 'Destino',
                    'plural' => 'Destinos',
                    'campos' => ['icono', 'titulo', 'texto'],
                    'maximo' => 6,
                ],
                'datos'   => [],
            ],

            'destacado' => [
                'nombre'  => 'Bloque destacado',
                'ayuda'   => 'Un bloque conceptual a todo el ancho: rótulo, titular, un texto '
                           . 'breve y un botón. Es la forma del bloque «Abramos el corazón».',
                'campos'  => ['rotulo', 'titulo', 'texto_html', 'imagen', 'cta_texto', 'cta_url'],
                'bloques' => null,
                'datos'   => [],
            ],

            'texto_apartados' => [
                'nombre'  => 'Texto con apartados',
                'ayuda'   => 'Un texto de entrada y debajo una lista de apartados, cada uno '
                           . 'con su titular y su explicación. Es la forma de las preguntas '
                           . 'frecuentes, del «qué llevar» de la guía y de los compromisos '
                           . 'de transparencia.',
                'campos'  => ['rotulo', 'titulo', 'texto_html', 'imagen'],
                'bloques' => [
                    'nombre' => 'Apartado',
                    'plural' => 'Apartados',
                    'campos' => ['titulo', 'texto'],
                    'maximo' => 20,
                ],
                'datos'   => [],
            ],
            'texto_lectura' => [
                'nombre'  => 'Texto de lectura',
                'ayuda'   => 'Un bloque de párrafos. Admite <p>, <strong> y enlaces.',
                'campos'  => ['rotulo', 'titulo', 'texto_html', 'imagen'],
                'bloques' => null,
                'datos'   => [],
            ],

            'generica' => [
                'nombre'  => 'Sección genérica',
                'ayuda'   => 'Campos básicos. Para secciones que todavía no tienen plantilla propia.',
                'campos'  => ['rotulo', 'titulo', 'subtitulo', 'texto', 'imagen', 'cta_texto', 'cta_url'],
                'bloques' => [
                    'nombre' => 'Elemento',
                    'plural' => 'Elementos',
                    'campos' => ['rotulo', 'titulo', 'texto', 'imagen', 'imagen_movil', 'enlace_texto', 'enlace_url'],
                    'maximo' => 12,
                ],
                'datos'   => [],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function de(string $clave): array
    {
        return self::todas()[$clave] ?? self::todas()['generica'];
    }

    /** Etiqueta y tipo de cada campo propio de la sección. */
    public static function campo(string $campo): array
    {
        return match ($campo) {
            'rotulo'     => ['etiqueta' => 'Rótulo', 'tipo' => 'texto',
                             'ayuda' => 'La línea corta en versalitas que va encima del título.'],
            'titulo'     => ['etiqueta' => 'Título', 'tipo' => 'texto'],
            'subtitulo'  => ['etiqueta' => 'Subtítulo', 'tipo' => 'texto'],
            'texto'      => ['etiqueta' => 'Texto de entrada', 'tipo' => 'area'],
            'texto_html' => ['etiqueta' => 'Texto', 'tipo' => 'area', 'columna' => 'texto',
                             'ayuda' => 'Admite &lt;p&gt;, &lt;strong&gt; y enlaces.'],
            // La sección no guarda una ruta sino el id de la biblioteca: así la
            // misma foto se cambia una vez y cambia en todas partes, y el sitio
            // público puede pedirle sus variantes para armar el <picture>.
            'imagen'     => ['etiqueta' => 'Imagen', 'tipo' => 'imagen', 'columna' => 'imagen_id',
                             'ayuda' => 'Se elige de la biblioteca. Para subir una nueva, ve a Imágenes.'],
            // La segunda fotografía, para teléfono. Es opcional: sin ella se
            // sirve la de arriba en todas las pantallas, como hasta ahora.
            'imagen_movil' => ['etiqueta' => 'Imagen para móvil', 'tipo' => 'imagen',
                             'columna' => 'imagen_movil_id',
                             'ayuda' => 'Opcional. Una foto vertical para teléfonos: la apaisada, '
                                      . 'recortada a 390 píxeles de ancho, deja fuera justo lo que '
                                      . 'importa. Si la dejas vacía se usa la de arriba.'],
            'cta_texto'  => ['etiqueta' => 'Texto del botón', 'tipo' => 'texto'],
            'cta_url'    => ['etiqueta' => 'Destino del botón', 'tipo' => 'texto'],
            default      => ['etiqueta' => ucfirst(str_replace('_', ' ', $campo)), 'tipo' => 'texto'],
        };
    }

    /** Nombre de la columna real en la tabla para un campo del formulario. */
    public static function columna(string $campo): string
    {
        return self::campo($campo)['columna'] ?? $campo;
    }

    /** Etiqueta de un campo de bloque. */
    public static function campoBloque(string $campo): string
    {
        return match ($campo) {
            'rotulo'       => 'Número o rótulo',
            'slug'         => 'Dirección de su página',
            'titulo'       => 'Título',
            'texto'        => 'Texto',
            'icono'        => 'Icono',
            'imagen'       => 'Imagen',
            'imagen_movil' => 'Imagen para móvil',
            'enlace_texto' => 'Texto del enlace',
            'enlace_url'   => 'Destino del enlace',
            default        => ucfirst(str_replace('_', ' ', $campo)),
        };
    }
}
