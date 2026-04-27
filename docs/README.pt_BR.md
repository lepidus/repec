# Gerador de ReDIF para RePEc

Plugin genérico para OJS 3.4 que publica metadados da revista em formato ReDIF para indexação no RePEc.

Use o ramo `stable-3_4_0` para OJS 3.4. Use o ramo `stable-3_3_0` para OJS 3.3.

Traduções:

- [English](../README.md)
- [Español](README.es.md)
- [Português do Brasil](README.pt_BR.md)

## Uso

O plugin pode ser usado de duas formas:

- Individualmente, no contexto de cada revista. Cada revista publica seu próprio arquivo RePEc e seu próprio arquivo de série.
- Globalmente, no contexto do site (`index`). O administrador do OJS configura um arquivo RePEc para a instalação e seleciona apenas as revistas que devem fazer parte desse arquivo.

Esses modos podem coexistir na mesma instalação OJS. Uma revista selecionada para o arquivo global passa a ser gerenciada por esse arquivo global e não pode manter também uma configuração RePEc individual. Revistas não selecionadas para o arquivo global ainda podem usar sua própria configuração RePEc individual.

Para um arquivo individual de revista:

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

Uma instalação OJS também pode publicar um único arquivo RePEc para várias revistas. Configure o plugin no contexto do site (`index`) e selecione apenas as revistas que fazem parte do arquivo. Por exemplo, um arquivo fictício `abc` pode publicar as revistas fictícias `Revista Um`, com código de série `journ1`, e `Revista Dois`, com código de série `journ2`.

O arquivo global publica:

- `/index/repec/{aaa}/`
- `/index/repec/{aaa}/{aaa}arch.redif`
- `/index/repec/{aaa}/{aaa}seri.redif`
- `/index/repec/{aaa}/{seriesCode}/`
- `/index/repec/{aaa}/{seriesCode}/{issue}.redif`

Todas as revistas selecionadas são incluídas no mesmo arquivo `{aaa}seri.redif`, com um template `ReDIF-Series 1.0` por revista. Uma revista pode usar o arquivo global ou um arquivo individual da própria revista, mas não ambos. Outras revistas da mesma instalação OJS podem ficar fora do arquivo global e usar arquivos RePEc individuais.

## Handles RePEc legados

Se uma revista já tem handles de artigos publicados por outro fluxo, importe um arquivo JSON nas configurações da revista para preservar esses handles. O JSON deve ser um objeto em que cada chave é o `submission_id` do OJS e cada valor é o handle RePEc legado completo:

```json
{
  "123": "RePEc:abc:journ1:a:old123",
  "456": "RePEc:abc:journ1:a:old456"
}
```

Os handles legados são configurados por revista. Eles também são aplicados quando a revista é publicada por um arquivo RePEc global.

## Créditos

Este plugin foi desenvolvido por [Lepidus Tecnologia](https://lepidus.com.br/)

## Licença

![License](https://img.shields.io/badge/license-GPLv3-blue)

**Licença: Licença Pública Geral GNU v3.0**

**Copyright: 2026 Lepidus Tecnologia**
