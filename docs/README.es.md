# Generador de ReDIF para RePEc

Plugin genérico para OJS 3.5 que publica metadatos de la revista en formato ReDIF para su indexación en RePEc.

Use la rama `stable-3_5_0` para OJS 3.5. Use la rama `stable-3_4_0` para OJS 3.4 y la rama `stable-3_3_0` para OJS 3.3.

Traducciones:

- [English](../README.md)
- [Español](README.es.md)
- [Português do Brasil](README.pt_BR.md)

## Antes de empezar

RePEc está destinado a la literatura de Economía y ciencias relacionadas. Antes de configurar este plugin, confirme que la revista o institución es adecuada para la indexación en RePEc.

Antes de publicar cualquier archivo RePEc, siga las instrucciones oficiales paso a paso de RePEc:

https://ideas.repec.org/stepbystep.html

La guía paso a paso explica cómo solicitar un código de archivo y cómo preparar un archivo RePEc. Para la etapa específica de solicitud del código de archivo, vea también:

https://ideas.repec.org/t/archivehandle.html

No invente un código de archivo RePEc ni use un código perteneciente a otra institución. Todo código de archivo RePEc debe solicitarse a RePEc y ser asignado a su departamento o institución para evitar conflictos con códigos ya usados por terceros.

Si su institución ya tiene un archivo RePEc, normalmente no es necesario solicitar un nuevo código de archivo. Un único archivo RePEc puede incluir varias revistas o series.

## Antes de empezar

RePEc está destinado a la literatura de Economía y ciencias relacionadas. Antes de configurar este plugin, confirme que la revista o institución es adecuada para la indexación en RePEc.

Antes de publicar cualquier archivo RePEc, siga las instrucciones oficiales paso a paso de RePEc:

https://ideas.repec.org/stepbystep.html

La guía paso a paso explica cómo solicitar un código de archivo y cómo preparar un archivo RePEc. Para la etapa específica de solicitud del código de archivo, vea también:

https://ideas.repec.org/t/archivehandle.html

No invente un código de archivo RePEc ni use un código perteneciente a otra institución. Todo código de archivo RePEc debe solicitarse a RePEc y ser asignado a su departamento o institución para evitar conflictos con códigos ya usados por terceros.

Si su institución ya tiene un archivo RePEc, normalmente no es necesario solicitar un nuevo código de archivo. Un único archivo RePEc puede incluir varias revistas o series.

## Uso

El plugin puede usarse de dos formas:

- Individualmente, en el contexto de cada revista. Cada revista publica su propio archivo RePEc y su propio archivo de serie.
- Globalmente, en el contexto del sitio (`index`). El administrador de OJS configura un archivo RePEc para la instalación y selecciona solo las revistas que deben formar parte de ese archivo.

Estos modos pueden coexistir en la misma instalación OJS. Una revista seleccionada para el archivo global queda gestionada por ese archivo global y no puede mantener también una configuración RePEc individual. Las revistas no seleccionadas para el archivo global aún pueden usar su propia configuración RePEc individual.

Para un archivo individual de revista:

1. Active el plugin en una revista.
2. Abra la configuración del plugin e informe el código de archivo asignado por RePEc, el código de la serie y el correo electrónico opcional del mantenedor.
3. Acceda a la URL pública indicada en la configuración del plugin.

El formulario separa los campos obligatorios de las opciones avanzadas. Para la mayoría de las revistas, complete solo el código del archivo RePEc, el código de la serie y, si es necesario, el correo electrónico del mantenedor.

Si el campo de código de serie está vacío, use **Generar automáticamente** para completarlo a partir de los datos de la revista y revíselo antes de guardar. Después de publicar la revista en RePEc, evite cambiar el código del archivo, el código de la serie o el patrón de handle de los artículos.

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

Si una revista fue configurada anteriormente con código de archivo y código de serie individuales, elimine esas configuraciones individuales en la sección **Avanzado** de la revista antes de seleccionarla en el archivo global.

## Handles RePEc heredados

Si una revista ya tiene handles de artículos publicados por otro flujo, importe un archivo JSON en la configuración de la revista para preservar esos handles. El JSON debe ser un objeto en el que cada clave es el `submission_id` de OJS y cada valor es el handle RePEc heredado completo:

```json
{
  "123": "RePEc:abc:journ1:a:old123",
  "456": "RePEc:abc:journ1:a:old456"
}
```

Los handles heredados se configuran por revista. También se aplican cuando la revista se publica mediante un archivo RePEc global.

Los handles heredados siempre tienen prioridad sobre los handles generados a partir del patrón de handle de los artículos. Úselos cuando artículos específicos ya tienen handles RePEc públicos que deben preservarse exactamente.

## Opciones avanzadas

La sección **Avanzado** está destinada a revistas que ya tienen registros RePEc publicados o que necesitan seguir una convención específica de handles. Los cambios en esta sección pueden afectar identificadores públicos, así que úsela solo cuando tenga certeza sobre los handles RePEc esperados.

### Patrón de handle de los artículos

El plugin genera handles de artículos en el formato `RePEc:{archiveCode}:{seriesCode}:{suffix}`. De forma predeterminada, el sufijo mantiene el comportamiento anterior:

```text
v:%v:y:%Y:i:%i:id:%a
```

Antes de publicar los archivos RePEc de la revista, puede configurar otro sufijo en el campo de patrón de handle de los artículos. Los tokens aceptados son:

- `%v`: volumen del número
- `%Y`: año de publicación
- `%i`: número de la edición
- `%a`: ID del envío en OJS

Por ejemplo, este patrón:

```text
v:%v:y:%Y:i:%i:a:%a
```

puede generar:

```text
RePEc:fgv:eaerae:v:35:y:1995:i:3:a:59960
```

Después de guardar una vez el patrón de handle de los artículos, el formulario lo muestra como solo lectura. Esto evita cambios accidentales en identificadores públicos después de que hayan sido recolectados por RePEc.

### Migrar una revista al archivo global

Una revista no puede usar una configuración RePEc individual y el archivo global al mismo tiempo. Si la revista ya tiene `archiveCode` y `seriesCode` individuales, no estará disponible para selección en el archivo global.

Para dejar la revista disponible en el archivo global:

1. Abra la configuración del plugin en el contexto de la revista.
2. Abra la sección **Avanzado**.
3. Seleccione la opción para eliminar las configuraciones individuales `archiveCode` y `seriesCode`.
4. Guarde el formulario.
5. Abra la configuración del plugin en el contexto del sitio y seleccione la revista en el archivo global.

Esto elimina solo el código de archivo y el código de serie individuales. No elimina handles heredados ni el patrón de handle de los artículos configurado para la revista.

## Créditos

Este plugin fue desarrollado por [Lepidus Tecnologia](https://lepidus.com.br/)

## Licencia

![License](https://img.shields.io/badge/license-GPLv3-blue)

**Licencia: Licencia Pública General GNU v3.0**

**Copyright: 2026 Lepidus Tecnologia**
