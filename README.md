# RePEc/ReDIF

Plugin genérico para OJS 3.3 que publica metadados da revista em formato ReDIF para indexação no RePEc.

## Uso

1. Habilite o plugin em uma revista.
2. Abra as configurações do plugin e informe o código RePEc do arquivo, o código da série, dados do provedor e mantenedor.
3. Acesse a URL pública indicada na configuração do plugin.

O plugin publica dinamicamente:

- `/{journal}/repec/{aaa}/`
- `/{journal}/repec/{aaa}/{aaa}arch.rdf`
- `/{journal}/repec/{aaa}/{aaa}seri.rdf`
- `/{journal}/repec/{aaa}/{seriesCode}/`
- `/{journal}/repec/{aaa}/{seriesCode}/articles.rdf`

O conteúdo exportado no v1 é limitado a artigos publicados da revista atual, como `ReDIF-Article 1.0`.
