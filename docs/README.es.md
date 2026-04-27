# Generador de ReDIF para RePEc

Plugin genérico para OJS 3.3 que publica metadatos de la revista en formato ReDIF para su indexación en RePEc.

Traducciones:

- [English](../README.md)
- [Español](README.es.md)
- [Português do Brasil](README.pt_BR.md)

## Uso

1. Active el plugin en una revista.
2. Abra la configuración del plugin e informe el código RePEc del archivo, el código de la serie y el correo electrónico opcional del mantenedor.
3. Acceda a la URL pública indicada en la configuración del plugin.

El correo electrónico del mantenedor es opcional. Cuando no se completa, el plugin usa el correo electrónico de soporte técnico configurado para la revista en OJS. Si ese correo también está vacío, usa el correo electrónico principal de contacto de la revista.

El plugin publica dinámicamente:

- `/{journal}/repec/{aaa}/`
- `/{journal}/repec/{aaa}/{aaa}arch.redif`
- `/{journal}/repec/{aaa}/{aaa}seri.redif`
- `/{journal}/repec/{aaa}/{seriesCode}/`
- `/{journal}/repec/{aaa}/{seriesCode}/{issue}.redif`

El plugin publica un archivo ReDIF por cada número publicado. El nombre del archivo se genera a partir de la identificación del número configurada en OJS, por ejemplo `v42i2y2022.redif` para volumen 42, número 2, año 2022.

La exportación v1 se limita a los artículos publicados de la revista actual, como `ReDIF-Article 1.0`. El campo `File-URL` apunta a la página pública del artículo en OJS.

## Varias revistas en un archivo

Una instalación OJS también puede publicar un único archivo RePEc para varias revistas. Configure el plugin en el contexto del sitio (`index`) y seleccione las revistas que forman parte del archivo. Por ejemplo, un archivo ficticio `abc` puede publicar las revistas ficticias `Revista Uno`, con código de serie `journ1`, y `Revista Dos`, con código de serie `journ2`.

El archivo global publica:

- `/index/repec/{aaa}/`
- `/index/repec/{aaa}/{aaa}arch.redif`
- `/index/repec/{aaa}/{aaa}seri.redif`
- `/index/repec/{aaa}/{seriesCode}/`
- `/index/repec/{aaa}/{seriesCode}/{issue}.redif`

Todas las revistas seleccionadas se incluyen en el mismo archivo `{aaa}seri.redif`, con un template `ReDIF-Series 1.0` por revista. Una revista puede usar el archivo global o un archivo individual de la propia revista, pero no ambos.

## Créditos

Este plugin fue desarrollado por [Lepidus Tecnologia](https://lepidus.com.br/)

## Licencia

![License](https://img.shields.io/badge/license-GPLv3-blue)

**Licencia: Licencia Pública General GNU v3.0**

**Copyright: 2026 Lepidus Tecnologia**
