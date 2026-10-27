# Suites Son Sampoli Adults Only — web

Web de **Suites Son Sampoli Adults Only** (https://suites.sonsampoli.com), en cinco idiomas (es, en, de, fr, it).

La carpeta `web/` es la web tal como se publica en el hosting: HTML estático, sin base de datos.
Se modifica editando directamente esos archivos.

| Qué | Dónde |
| --- | --- |
| Páginas | `web/<idioma>/…/index.html` |
| Fotos | `web/images/` |
| Formulario de contacto (PHP) | `web/api/contacto.php` (configuración: `web/api/config.sample.php`) |
| Redirecciones y cabeceras (Apache) | `web/.htaccess` |

## Publicación

`.github/workflows/deploy.yml` sube `web/` a IONOS por SFTP en cada cambio de `main`.
Necesita los secretos `SFTP_HOST`, `SFTP_USER`, `SFTP_PASSWORD` y `SFTP_PATH`
(Settings → Secrets and variables → Actions). Sin ellos no publica nada.

## Ofertas

La sección «Ofertas» está en la portada de cada idioma (`web/<idioma>/index.html`, bloque `id="ofertas"`),
con estilos en `web/ofertas.css` y comportamiento en `web/ofertas.js`:

- `data-hasta="AAAA-MM-DD"`: último día en que se muestra la oferta; después desaparece sola.
- `data-llegada="AAAA-MM-DD"`: fecha de llegada con la que se rellena el buscador al pulsar el botón.

Las imágenes (`web/images/oferta-*.webp`) se generan con `python3 tools/imagenes-ofertas.py web`.
El descuento se aplica en Octorate: la tarifa o promoción tiene que estar creada allí.

## Reservas (página «Búsqueda»)

`/<idioma>/busqueda/` muestra los resultados del motor de Octorate con su widget oficial
(`https://resx.octorate.com/octobook/resources/widget/js/form.js`). Los buscadores de la web
(`form[data-booking]`) envían ahí con `web/reservas.js`, en el mismo formato que el widget
(`?octobooksearch=…`). Debajo de los resultados hay un enlace para abrir el motor a pantalla completa.
