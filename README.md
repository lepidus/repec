# ReDIF Generator for RePEc

Generic plugin for OJS 3.3 that publishes journal metadata in ReDIF format for RePEc indexing.

Translations:

- [English](README.md)
- [Español](docs/README.es.md)
- [Português do Brasil](docs/README.pt_BR.md)

## Usage

1. Enable the plugin for a journal.
2. Open the plugin settings and enter the RePEc archive code, the series code, and the optional maintainer email.
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

An OJS installation can also publish a single RePEc archive for multiple journals. Configure the plugin in the site context (`index`) and select the journals that belong to the archive. For example, a fictional archive `abc` can publish the fictional journals `Journal One` with series code `journ1` and `Journal Two` with series code `journ2`.

The global archive publishes:

- `/index/repec/{aaa}/`
- `/index/repec/{aaa}/{aaa}arch.redif`
- `/index/repec/{aaa}/{aaa}seri.redif`
- `/index/repec/{aaa}/{seriesCode}/`
- `/index/repec/{aaa}/{seriesCode}/{issue}.redif`

All selected journals are included in the same `{aaa}seri.redif` file, with one `ReDIF-Series 1.0` template per journal. A journal can use either the global archive or an individual journal archive, but not both.

## Credits

This plugin was developed by [Lepidus Tecnologia](https://lepidus.com.br/)

## License

![License](https://img.shields.io/badge/license-GPLv3-blue)

**License: GNU General Public License v3.0**

**Copyright: 2026 Lepidus Tecnologia**
