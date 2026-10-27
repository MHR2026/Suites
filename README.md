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
