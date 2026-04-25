# Gerador de ReDIF para RePEc

Plugin genérico para OJS 3.3 que publica metadados da revista em formato ReDIF para indexação no RePEc.

Traduções:

- [English](../README.md)
- [Español](README.es.md)
- [Português do Brasil](README.pt_BR.md)

## Uso

1. Habilite o plugin em uma revista.
2. Abra as configurações do plugin e informe o código RePEc do arquivo, o código da série e o e-mail opcional do mantenedor.
3. Acesse a URL pública indicada na configuração do plugin.

O e-mail do mantenedor é opcional no formulário. Quando não for preenchido, o plugin usa o e-mail de suporte técnico configurado na revista no OJS e, se ele também estiver vazio, usa o e-mail de contato principal da revista.

O plugin publica dinamicamente:

- `/{journal}/repec/{aaa}/`
- `/{journal}/repec/{aaa}/{aaa}arch.redif`
- `/{journal}/repec/{aaa}/{aaa}seri.redif`
- `/{journal}/repec/{aaa}/{seriesCode}/`
- `/{journal}/repec/{aaa}/{seriesCode}/{issue}.redif`

O plugin publica um arquivo ReDIF por edição publicada. O nome do arquivo é gerado a partir da identificação da edição configurada no OJS, por exemplo `v42i2y2022.redif` para volume 42, edição 2, ano 2022.

O conteúdo exportado no v1 é limitado a artigos publicados da revista atual, como `ReDIF-Article 1.0`. O campo `File-URL` aponta para a página pública do artigo no OJS.
