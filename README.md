# ReDIF Generator for RePEc

Generic plugin for OJS that publishes journal metadata in ReDIF format for RePEc indexing.

Translations:

- [English](README.md)
- [Español](docs/README.es.md)
- [Português do Brasil](docs/README.pt_BR.md)

## Compatibility

Use the plugin branch and package that match your OJS version:

| OJS version | Plugin branch | Plugin release |
| --- | --- | --- |
| OJS 3.3.x | `stable-3_3_0` | `v1.2.2.0` |
| OJS 3.4.x | `stable-3_4_0` | `v2.0.0.0` |
| OJS 3.5.x | `stable-3_5_0` | `v3.0.0.0` |

The `main` branch currently follows the OJS 3.5 compatible line. For production installations, prefer the stable branch and release tag that match the target OJS version.

## Before You Start

RePEc is intended for Economics literature and related sciences. Before configuring this plugin, make sure that the journal or institution is eligible for RePEc indexing.

Before publishing any RePEc files, follow the official RePEc step-by-step instructions:

https://ideas.repec.org/stepbystep.html

The step-by-step guide explains how to request an archive code and how to prepare a RePEc archive. For the archive code request step, see also:

https://ideas.repec.org/t/archivehandle.html

Do not invent a RePEc archive code, and do not use a code that belongs to another institution. Every RePEc archive code must be requested from RePEc and assigned to your department or institution to avoid conflicts with archive codes already used by others.

If your institution already has a RePEc archive, you usually do not need a new archive code. A single RePEc archive can include multiple journals or series.

## Usage

The plugin can be used in two ways:

- Individually, in each journal context. Each journal publishes its own RePEc archive and its own series file.
- Globally, in the site context (`index`). The OJS administrator configures one RePEc archive for the installation and selects only the journals that should be included in that archive.

These modes can coexist in the same OJS installation. A journal selected for the global archive is managed by that global archive and cannot also keep an individual RePEc configuration. Journals not selected for the global archive may still use their own individual RePEc configuration.

For an individual journal archive:

1. Enable the plugin for a journal.
2. Open the plugin settings and enter the RePEc archive code assigned by RePEc, the series code, and the optional maintainer email.
3. Access the public URL shown in the plugin settings.

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

## Legacy RePEc Handles

If a journal already has article handles published by another workflow, import a JSON file in the journal settings to preserve those handles. The JSON must be an object where each key is the OJS `submission_id` and each value is the full legacy RePEc handle:

```json
{
  "123": "RePEc:abc:journ1:a:old123",
  "456": "RePEc:abc:journ1:a:old456"
}
```

Legacy handles are configured per journal. They are also applied when the journal is published through a global RePEc archive.

## Development Branches

Compatibility work is maintained in versioned stable branches:

- `stable-3_3_0`: OJS 3.3 compatible code line.
- `stable-3_4_0`: OJS 3.4 compatible code line, released as `v2.0.0.0`.
- `stable-3_5_0`: OJS 3.5 compatible code line, released as `v3.0.0.0`.

When changing documentation that exists in more than one language, update `README.md`, `docs/README.es.md`, and `docs/README.pt_BR.md` together.

## Credits

This plugin was developed by [Lepidus Tecnologia](https://lepidus.com.br/)

## License

![License](https://img.shields.io/badge/license-GPLv3-blue)

**License: GNU General Public License v3.0**

**Copyright: 2026 Lepidus Tecnologia**
