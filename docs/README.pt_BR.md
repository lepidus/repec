# Gerador de ReDIF para RePEc

Plugin genérico para OJS 3.3 que publica metadados da revista em formato ReDIF para indexação no RePEc.

Traduções:

- [English](../README.md)
- [Español](README.es.md)
- [Português do Brasil](README.pt_BR.md)

## Antes de começar

O RePEc é voltado à literatura de Economia e ciências relacionadas. Antes de configurar este plugin, confirme que a revista ou instituição é adequada para indexação no RePEc.

Antes de publicar qualquer arquivo RePEc, siga as instruções oficiais passo a passo do RePEc:

https://ideas.repec.org/stepbystep.html

O guia passo a passo explica como solicitar um código de arquivo e como preparar um arquivo RePEc. Para a etapa específica de solicitação do código de arquivo, veja também:

https://ideas.repec.org/t/archivehandle.html

Não invente um código de arquivo RePEc nem use um código pertencente a outra instituição. Todo código de arquivo RePEc deve ser solicitado ao RePEc e atribuído ao seu departamento ou instituição para evitar conflitos com códigos já usados por terceiros.

Se a sua instituição já tem um arquivo RePEc, normalmente não é necessário solicitar um novo código de arquivo. Um único arquivo RePEc pode incluir várias revistas ou séries.

## Uso

O plugin pode ser usado de duas formas:

- Individualmente, no contexto de cada revista. Cada revista publica seu próprio arquivo RePEc e seu próprio arquivo de série.
- Globalmente, no contexto do site (`index`). O administrador do OJS configura um arquivo RePEc para a instalação e seleciona apenas as revistas que devem fazer parte desse arquivo.

Esses modos podem coexistir na mesma instalação OJS. Uma revista selecionada para o arquivo global passa a ser gerenciada por esse arquivo global e não pode manter também uma configuração RePEc individual. Revistas não selecionadas para o arquivo global ainda podem usar sua própria configuração RePEc individual.

Para um arquivo individual de revista:

1. Habilite o plugin em uma revista.
2. Abra as configurações do plugin e informe o código de arquivo atribuído pelo RePEc, o código da série e o e-mail opcional do mantenedor.
3. Acesse a URL pública indicada na configuração do plugin.

O formulário separa os campos obrigatórios das opções avançadas. Para a maioria das revistas, preencha apenas o código do arquivo RePEc, o código da série e, se necessário, o e-mail do mantenedor.

Se o campo de código da série estiver vazio, use **Gerar automaticamente** para preenchê-lo a partir dos dados da revista e revise antes de salvar. Depois que a revista for publicada no RePEc, evite mudar o código do arquivo, o código da série ou o padrão de handle dos artigos.

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

Se uma revista foi configurada anteriormente com código de arquivo e código de série individuais, remova essas configurações individuais na seção **Avançado** da revista antes de selecioná-la no arquivo global.

## Handles RePEc legados

Se uma revista já tem handles de artigos publicados por outro fluxo, importe um arquivo JSON nas configurações da revista para preservar esses handles. O JSON deve ser um objeto em que cada chave é o `submission_id` do OJS e cada valor é o handle RePEc legado completo:

```json
{
  "123": "RePEc:abc:journ1:a:old123",
  "456": "RePEc:abc:journ1:a:old456"
}
```

Os handles legados são configurados por revista. Eles também são aplicados quando a revista é publicada por um arquivo RePEc global.

Os handles legados sempre têm prioridade sobre handles gerados a partir do padrão de handle dos artigos. Use-os quando artigos específicos já têm handles RePEc públicos que precisam ser preservados exatamente.

## Opções avançadas

A seção **Avançado** é destinada a revistas que já têm registros RePEc publicados ou que precisam seguir uma convenção específica de handles. Mudanças nessa seção podem afetar identificadores públicos, então use apenas quando houver certeza sobre os handles RePEc esperados.

### Padrão de handle dos artigos

O plugin gera handles de artigos no formato `RePEc:{archiveCode}:{seriesCode}:{suffix}`. Por padrão, o sufixo mantém o comportamento anterior:

```text
v:%v:y:%Y:i:%i:id:%a
```

Antes de publicar os arquivos RePEc da revista, você pode configurar outro sufixo no campo de padrão de handle dos artigos. Os tokens aceitos são:

- `%v`: volume da edição
- `%Y`: ano de publicação
- `%i`: número da edição
- `%a`: ID da submissão no OJS

Por exemplo, este padrão:

```text
v:%v:y:%Y:i:%i:a:%a
```

pode gerar:

```text
RePEc:fgv:eaerae:v:35:y:1995:i:3:a:59960
```

Depois que o padrão de handle dos artigos é salvo uma vez, o formulário passa a exibi-lo como somente leitura. Isso evita mudanças acidentais em identificadores públicos depois que eles forem coletados pelo RePEc.

### Migrar uma revista para o arquivo global

Uma revista não pode usar uma configuração RePEc individual e o arquivo global ao mesmo tempo. Se a revista já tem `archiveCode` e `seriesCode` individuais, ela não fica disponível para seleção no arquivo global.

Para disponibilizar a revista no arquivo global:

1. Abra as configurações do plugin no contexto da revista.
2. Abra a seção **Avançado**.
3. Selecione a opção para remover as configurações individuais `archiveCode` e `seriesCode`.
4. Salve o formulário.
5. Abra as configurações do plugin no contexto do site e selecione a revista no arquivo global.

Isso remove apenas o código de arquivo e o código de série individuais. Não remove handles legados nem o padrão de handle dos artigos configurado para a revista.

## Créditos

Este plugin foi desenvolvido por [Lepidus Tecnologia](https://lepidus.com.br/)

## Licença

![License](https://img.shields.io/badge/license-GPLv3-blue)

**Licença: Licença Pública Geral GNU v3.0**

**Copyright: 2026 Lepidus Tecnologia**
