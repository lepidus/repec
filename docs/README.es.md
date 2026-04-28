# Generador de ReDIF para RePEc

Plugin genérico para OJS que publica metadatos de la revista en formato ReDIF para su indexación en RePEc.

Traducciones:

- [English](../README.md)
- [Español](README.es.md)
- [Português do Brasil](README.pt_BR.md)

## Compatibilidad

Use la rama y el paquete del plugin que correspondan a la versión de OJS:

| Versión de OJS | Rama del plugin | Versión del plugin |
| --- | --- | --- |
| OJS 3.3.x | `stable-3_3_0` | `v1.2.2.0` |
| OJS 3.4.x | `stable-3_4_0` | `v2.0.0.0` |
| OJS 3.5.x | `stable-3_5_0` | `v3.0.0.0` |

La rama `main` actualmente sigue la línea compatible con OJS 3.5. Para instalaciones de producción, prefiera la rama estable y la etiqueta de release que correspondan a la versión de OJS de destino.

## Uso

El plugin puede usarse de dos formas:

- Individualmente, en el contexto de cada revista. Cada revista publica su propio archivo RePEc y su propio archivo de serie.
- Globalmente, en el contexto del sitio (`index`). El administrador de OJS configura un archivo RePEc para la instalación y selecciona solo las revistas que deben formar parte de ese archivo.

Estos modos pueden coexistir en la misma instalación OJS. Una revista seleccionada para el archivo global queda gestionada por ese archivo global y no puede mantener también una configuración RePEc individual. Las revistas no seleccionadas para el archivo global aún pueden usar su propia configuración RePEc individual.

Para un archivo individual de revista:

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

Una instalación OJS también puede publicar un único archivo RePEc para varias revistas. Configure el plugin en el contexto del sitio (`index`) y seleccione solo las revistas que forman parte del archivo. Por ejemplo, un archivo ficticio `abc` puede publicar las revistas ficticias `Revista Uno`, con código de serie `journ1`, y `Revista Dos`, con código de serie `journ2`.

El archivo global publica:

- `/index/repec/{aaa}/`
- `/index/repec/{aaa}/{aaa}arch.redif`
- `/index/repec/{aaa}/{aaa}seri.redif`
- `/index/repec/{aaa}/{seriesCode}/`
- `/index/repec/{aaa}/{seriesCode}/{issue}.redif`

Todas las revistas seleccionadas se incluyen en el mismo archivo `{aaa}seri.redif`, con un template `ReDIF-Series 1.0` por revista. Una revista puede usar el archivo global o un archivo individual de la propia revista, pero no ambos. Otras revistas de la misma instalación OJS pueden quedar fuera del archivo global y usar archivos RePEc individuales.

## Handles RePEc heredados

Si una revista ya tiene handles de artículos publicados por otro flujo, importe un archivo JSON en la configuración de la revista para preservar esos handles. El JSON debe ser un objeto en el que cada clave es el `submission_id` de OJS y cada valor es el handle RePEc heredado completo:

```json
{
  "123": "RePEc:abc:journ1:a:old123",
  "456": "RePEc:abc:journ1:a:old456"
}
```

Los handles heredados se configuran por revista. También se aplican cuando la revista se publica mediante un archivo RePEc global.

## Ramas de desarrollo

El trabajo de compatibilidad se mantiene en ramas estables versionadas:

- `stable-3_3_0`: línea de código compatible con OJS 3.3.
- `stable-3_4_0`: línea de código compatible con OJS 3.4, publicada como `v2.0.0.0`.
- `stable-3_5_0`: línea de código compatible con OJS 3.5, publicada como `v3.0.0.0`.

Al modificar documentación mantenida en más de un idioma, actualice `README.md`, `docs/README.es.md` y `docs/README.pt_BR.md` en conjunto.

## Créditos

Este plugin fue desarrollado por [Lepidus Tecnologia](https://lepidus.com.br/)

## Licencia

![License](https://img.shields.io/badge/license-GPLv3-blue)

**Licencia: Licencia Pública General GNU v3.0**

**Copyright: 2026 Lepidus Tecnologia**
