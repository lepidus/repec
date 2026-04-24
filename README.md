# RePEc/ReDIF

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
- `/{journal}/repec/{aaa}/{aaa}arch.rdf`
- `/{journal}/repec/{aaa}/{aaa}seri.rdf`
- `/{journal}/repec/{aaa}/{seriesCode}/`
- `/{journal}/repec/{aaa}/{seriesCode}/{issue}.rdf`

The plugin publishes one RDF file for each published issue. The file name is generated from the issue identification configured in OJS, for example `v42i2y2022.rdf` for volume 42, issue 2, year 2022.

The v1 export is limited to published articles in the current journal, as `ReDIF-Article 1.0`. The `File-URL` field points to the public article landing page in OJS.
