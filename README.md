# ReDIF Generator for RePEc

Generic plugin for OJS 3.3 that publishes journal metadata in ReDIF format for RePEc indexing.

Translations:

- [English](README.md)
- [Español](docs/README.es.md)
- [Português do Brasil](docs/README.pt_BR.md)

## Usage

The plugin can be used in two ways:

- Individually, in each journal context. Each journal publishes its own RePEc archive and its own series file.
- Globally, in the site context (`index`). The OJS administrator configures one RePEc archive for the installation and selects only the journals that should be included in that archive.

These modes can coexist in the same OJS installation. A journal selected for the global archive is managed by that global archive and cannot also keep an individual RePEc configuration. Journals not selected for the global archive may still use their own individual RePEc configuration.

For an individual journal archive:

1. Enable the plugin for a journal.
2. Open the plugin settings and enter the RePEc archive code, the series code, and the optional maintainer email.
3. Access the public URL shown in the plugin settings.

The settings form keeps the required fields separate from advanced options. For most journals, fill in only the RePEc archive code, the series code, and the maintainer email if needed.

If the series code field is empty, use **Generate automatically** to fill it from the journal data, then review it before saving. Once the journal is published in RePEc, avoid changing the archive code, series code, or article handle pattern.

The maintainer email is optional. When it is not filled in, the plugin uses the technical support email configured for the journal in OJS. If that is also empty, it uses the journal's main contact email.

The plugin publishes these URLs dynamically:

- `/{journal}/repec/{aaa}/`
- `/{journal}/repec/{aaa}/{aaa}arch.redif`
- `/{journal}/repec/{aaa}/{aaa}seri.redif`
- `/{journal}/repec/{aaa}/{seriesCode}/`
- `/{journal}/repec/{aaa}/{seriesCode}/{issue}.redif`

The plugin publishes one ReDIF file for each published issue. The file name is generated from the issue identification configured in OJS, for example `v42i2y2022.redif` for volume 42, issue 2, year 2022.

The v1 export is limited to published articles in the current journal, as `ReDIF-Article 1.0`. The `File-URL` field points to the public article landing page in OJS.

## Multiple Journals In One Archive

An OJS installation can also publish a single RePEc archive for multiple journals. Configure the plugin in the site context (`index`) and select only the journals that belong to the archive. For example, a fictional archive `abc` can publish the fictional journals `Journal One` with series code `journ1` and `Journal Two` with series code `journ2`.

The global archive publishes:

- `/index/repec/{aaa}/`
- `/index/repec/{aaa}/{aaa}arch.redif`
- `/index/repec/{aaa}/{aaa}seri.redif`
- `/index/repec/{aaa}/{seriesCode}/`
- `/index/repec/{aaa}/{seriesCode}/{issue}.redif`

All selected journals are included in the same `{aaa}seri.redif` file, with one `ReDIF-Series 1.0` template per journal. A journal can use either the global archive or an individual journal archive, but not both. Other journals in the same OJS installation can remain outside the global archive and use individual RePEc archives.

If a journal was previously configured with an individual archive code and series code, remove those individual settings from the journal's **Advanced** section before selecting it in the global archive.

## Legacy RePEc Handles

If a journal already has article handles published by another workflow, import a JSON file in the journal settings to preserve those handles. The JSON must be an object where each key is the OJS `submission_id` and each value is the full legacy RePEc handle:

```json
{
  "123": "RePEc:abc:journ1:a:old123",
  "456": "RePEc:abc:journ1:a:old456"
}
```

Legacy handles are configured per journal. They are also applied when the journal is published through a global RePEc archive.

Legacy handles always take precedence over handles generated from the article handle pattern. Use them when specific articles already have public RePEc handles that must be preserved exactly.

## Advanced Options

The **Advanced** section is intended for journals that already have published RePEc records or need to match a specific handle convention. Changes in this section can affect public identifiers, so only use it when you are sure about the expected RePEc handles.

### Article Handle Pattern

The plugin generates article handles as `RePEc:{archiveCode}:{seriesCode}:{suffix}`. By default, the suffix keeps the previous behavior:

```text
v:%v:y:%Y:i:%i:id:%a
```

Before publishing the journal's RePEc files, you may configure another suffix in the article handle pattern field. The accepted tokens are:

- `%v`: issue volume
- `%Y`: publication year
- `%i`: issue number
- `%a`: OJS submission ID

For example, this pattern:

```text
v:%v:y:%Y:i:%i:a:%a
```

can generate:

```text
RePEc:fgv:eaerae:v:35:y:1995:i:3:a:59960
```

After the article handle pattern is saved once, the form shows it as read-only. This avoids accidental changes to public identifiers after they have been harvested by RePEc.

### Moving a Journal to the Global Archive

A journal cannot use an individual RePEc configuration and the global archive at the same time. If the journal already has individual `archiveCode` and `seriesCode` settings, it will not be available for selection in the global archive.

To make the journal available in the global archive:

1. Open the plugin settings in the journal context.
2. Open the **Advanced** section.
3. Select the option to remove the individual `archiveCode` and `seriesCode` settings.
4. Save the form.
5. Open the plugin settings in the site context and select the journal in the global archive.

This only removes the individual archive and series codes. It does not remove legacy handles or the article handle pattern configured for the journal.

## Credits

This plugin was developed by [Lepidus Tecnologia](https://lepidus.com.br/)

## License

![License](https://img.shields.io/badge/license-GPLv3-blue)

**License: GNU General Public License v3.0**

**Copyright: 2026 Lepidus Tecnologia**
