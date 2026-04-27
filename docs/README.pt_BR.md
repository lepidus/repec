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

## Várias revistas em um arquivo

Uma instalação OJS também pode publicar um único arquivo RePEc para várias revistas. Configure o plugin no contexto do site (`index`) e selecione as revistas que fazem parte do arquivo. Por exemplo, um arquivo fictício `abc` pode publicar as revistas fictícias `Revista Um`, com código de série `journ1`, e `Revista Dois`, com código de série `journ2`.

O arquivo global publica:

- `/index/repec/{aaa}/`
- `/index/repec/{aaa}/{aaa}arch.redif`
- `/index/repec/{aaa}/{aaa}seri.redif`
- `/index/repec/{aaa}/{seriesCode}/`
- `/index/repec/{aaa}/{seriesCode}/{issue}.redif`

Todas as revistas selecionadas são incluídas no mesmo arquivo `{aaa}seri.redif`, com um template `ReDIF-Series 1.0` por revista. Uma revista pode usar o arquivo global ou um arquivo individual da própria revista, mas não ambos.

## Créditos

Este plugin foi desenvolvido por [Lepidus Tecnologia](https://lepidus.com.br/)

## Licença

![License](https://img.shields.io/badge/license-GPLv3-blue)

**Licença: Licença Pública Geral GNU v3.0**

**Copyright: 2026 Lepidus Tecnologia**
