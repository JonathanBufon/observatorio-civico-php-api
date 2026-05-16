# 004 - API REST Laravel, Documentacao Estruturada e Postman

## Objetivo

Ajustar o repositorio para entregar uma API REST Laravel consumivel pelo frontend do Observatorio Civico, com documentacao estruturada e uma colecao `.json` importavel no Postman.

O README ja define este repositorio como backend API, mas a API REST ainda aparece como proximo passo. A implementacao deve consolidar o projeto como uma API Laravel nativa, usando `routes/api.php`, controllers, Form Requests quando necessario, API Resources, testes de feature e documentacao versionada no proprio repo.

---

## Escopo

### Dentro do escopo

- Criar a superficie publica inicial da API para leitura de fontes, artigos, analises e eventos.
- Padronizar formato de resposta, erros, paginacao e filtros.
- Expor endpoints administrativos apenas se houver uma estrategia minima de protecao.
- Criar documentacao em Markdown com estrutura clara para humanos.
- Criar especificacao OpenAPI como contrato tecnico da API.
- Criar colecao Postman `.json` para importacao direta.
- Atualizar README apontando para a documentacao da API.
- Cobrir endpoints principais com testes automatizados.

### Fora do escopo nesta task

- Autenticacao completa de usuarios finais.
- Painel administrativo.
- Frontend.
- Matching semantico de eventos.
- Comparacao multi-fonte automatizada se o pipeline ainda nao estiver pronto.
- Reprocessamento massivo por IA via endpoint publico.

---

## Diagnostico Atual

### O que ja existe

- Laravel 13 com estrutura `app/`, `routes/`, `database/`, `tests/`.
- Models Eloquent para:
  - `Source`
  - `Article`
  - `Analysis`
  - `ArgumentativeStrategy`
  - `Event`
  - `SourceComparison`
- Services ja implementados para:
  - Coleta RSS (`RssFetcherService`)
  - Analise por IA (`ArticleAnalyzerService`)
  - Cliente OpenAI (`OpenAiClient`)
  - Montagem de prompt (`PromptBuilder`)
- Comandos Artisan:
  - `rss:fetch`
  - `ai:analyze`
- README descrevendo a proposta, stack, setup e estado atual.

### Lacunas

- Nao existe `routes/api.php`.
- Nao existem controllers HTTP da API.
- Nao existem API Resources para estabilizar o contrato JSON.
- Nao existe documentacao de endpoints.
- Nao existe OpenAPI/Swagger.
- Nao existe colecao Postman.
- Testes atuais sao os exemplos padrao do Laravel.
- Backlogs antigos ainda mencionam decisoes de arquitetura nao alinhadas ao estado atual, como Slim, `src/` e `bin/console`.

---

## Decisoes Tecnicas

### 1. Usar Laravel nativo

Implementar a API com componentes padrao do Laravel:

- `routes/api.php`
- `app/Http/Controllers/Api/V1/*`
- `app/Http/Resources/*`
- `app/Http/Requests/*`, quando houver entrada complexa
- `tests/Feature/Api/*`

Nao criar uma camada paralela de `src/`, repositories manuais ou entrypoints fora do Artisan.

### 2. Versionar a API desde o inicio

Prefixo recomendado:

```txt
/api/v1
```

Isso evita quebrar o frontend quando a representacao de analises, eventos ou comparacoes evoluir.

### 3. Resposta JSON padronizada

Listagens devem usar o formato nativo de pagination/resources do Laravel, preservando `data`, `links` e `meta`.

Respostas unitarias:

```json
{
  "data": {
    "id": 1
  }
}
```

Erros de validacao:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "per_page": ["O campo per_page deve ser no maximo 100."]
  }
}
```

Erros de dominio ou operacao:

```json
{
  "message": "Analise ainda nao disponivel para este artigo."
}
```

### 4. Documentacao em duas camadas

Manter documentacao humana e contrato tecnico:

- Markdown em `docs/api/`
- OpenAPI em `docs/openapi.yaml`
- Postman em `docs/postman/observatorio-civico-api.postman_collection.json`

O OpenAPI deve ser a fonte tecnica para schemas, parametros, status codes e exemplos. A colecao Postman deve ser simples de importar e usar manualmente.

### 5. Endpoints administrativos protegidos

Endpoints que disparam coleta RSS ou analise IA podem gerar custo, alterar banco e consumir recursos. Eles nao devem ser publicos.

Para v1, escolher uma destas opcoes:

- Nao expor operacoes administrativas via HTTP; manter apenas Artisan.
- Expor sob `/api/v1/admin/*` com token simples por header (`X-Admin-Token`) enquanto nao houver auth real.

Recomendacao para primeira entrega: manter administracao via Artisan e criar apenas endpoints de leitura.

---

## Contrato Inicial da API

### Health

| Metodo | Rota | Descricao |
|---|---|---|
| GET | `/api/v1/health` | Verifica se a API esta no ar |

Resposta:

```json
{
  "data": {
    "status": "ok",
    "app": "Observatorio Civico API"
  }
}
```

### Sources

| Metodo | Rota | Descricao |
|---|---|---|
| GET | `/api/v1/sources` | Lista fontes RSS |
| GET | `/api/v1/sources/{source}` | Detalha uma fonte |
| GET | `/api/v1/sources/{source}/articles` | Lista artigos de uma fonte |

Filtros de `GET /sources`:

| Parametro | Tipo | Descricao |
|---|---|---|
| `active` | boolean | Filtra fontes ativas/inativas |
| `editorial_leaning` | string | Filtra por linha editorial |
| `per_page` | int | Tamanho da pagina, maximo 100 |

### Articles

| Metodo | Rota | Descricao |
|---|---|---|
| GET | `/api/v1/articles` | Lista artigos coletados |
| GET | `/api/v1/articles/{article}` | Detalha um artigo |
| GET | `/api/v1/articles/{article}/analysis` | Retorna a analise do artigo |

Filtros de `GET /articles`:

| Parametro | Tipo | Descricao |
|---|---|---|
| `source_id` | int | Filtra por fonte |
| `has_analysis` | boolean | Filtra artigos com/sem analise |
| `published_from` | date | Data minima de publicacao |
| `published_to` | date | Data maxima de publicacao |
| `q` | string | Busca simples por titulo |
| `per_page` | int | Tamanho da pagina, maximo 100 |

### Analyses

| Metodo | Rota | Descricao |
|---|---|---|
| GET | `/api/v1/analyses` | Lista analises geradas |
| GET | `/api/v1/analyses/{analysis}` | Detalha uma analise |

Filtros de `GET /analyses`:

| Parametro | Tipo | Descricao |
|---|---|---|
| `source_id` | int | Filtra analises pela fonte do artigo |
| `model_used` | string | Filtra pelo modelo de IA usado |
| `prompt_version` | string | Filtra pela versao do prompt |
| `per_page` | int | Tamanho da pagina, maximo 100 |

### Events

| Metodo | Rota | Descricao |
|---|---|---|
| GET | `/api/v1/events` | Lista eventos agrupados |
| GET | `/api/v1/events/{event}` | Detalha evento com artigos relacionados |
| GET | `/api/v1/events/{event}/comparison` | Retorna comparacao multi-fonte do evento |

Observacao: estes endpoints podem ser entregues em modo leitura mesmo que a populacao automatica de eventos ainda seja uma task futura.

---

## Estrutura de Arquivos

```txt
routes/
└── api.php

app/Http/Controllers/Api/V1/
├── HealthController.php
├── SourceController.php
├── ArticleController.php
├── AnalysisController.php
└── EventController.php

app/Http/Resources/
├── SourceResource.php
├── ArticleResource.php
├── AnalysisResource.php
├── ArgumentativeStrategyResource.php
├── EventResource.php
└── SourceComparisonResource.php

app/Http/Requests/Api/V1/
├── ListSourcesRequest.php
├── ListArticlesRequest.php
└── ListAnalysesRequest.php

docs/
├── api/
│   ├── README.md
│   ├── authentication.md
│   ├── errors.md
│   ├── pagination-and-filters.md
│   ├── sources.md
│   ├── articles.md
│   ├── analyses.md
│   └── events.md
├── openapi.yaml
└── postman/
    ├── observatorio-civico-api.postman_collection.json
    └── observatorio-civico-local.postman_environment.json

tests/Feature/Api/
├── HealthTest.php
├── SourcesApiTest.php
├── ArticlesApiTest.php
├── AnalysesApiTest.php
└── EventsApiTest.php
```

---

## Ordem de Implementacao

### Fase 1 - Base da API

1. Criar `routes/api.php` com prefixo `v1`.
2. Garantir que o bootstrap do Laravel carrega rotas de API, se necessario.
3. Criar `HealthController`.
4. Criar convencao de resposta JSON e revisar comportamento de erros 404/422.
5. Configurar CORS para consumo pelo frontend em desenvolvimento.

### Fase 2 - Resources e endpoints publicos

1. Criar `SourceResource`.
2. Criar `ArticleResource`, incluindo dados resumidos da fonte.
3. Criar `AnalysisResource`, incluindo `argumentative_strategies`.
4. Criar `EventResource` e `SourceComparisonResource`.
5. Criar controllers de listagem/detalhe.
6. Implementar eager loading para evitar N+1.
7. Definir limites de `per_page` e ordenacao padrao.

### Fase 3 - Filtros e validacao

1. Criar Form Requests para filtros de listagem.
2. Validar datas, booleanos, ids e limites de paginacao.
3. Implementar busca simples por titulo em artigos.
4. Garantir respostas 422 consistentes.

### Fase 4 - Documentacao estruturada

1. Criar `docs/api/README.md` com visao geral e indice.
2. Documentar autenticacao atual: API publica de leitura, administracao via Artisan.
3. Documentar paginacao, filtros, ordenacao e erros.
4. Criar pagina por grupo de recurso: sources, articles, analyses, events.
5. Incluir exemplos reais de request/response.
6. Atualizar README principal apontando para `docs/api/README.md`.

### Fase 5 - OpenAPI

1. Criar `docs/openapi.yaml`.
2. Definir `servers` para local Docker e producao futura.
3. Definir schemas dos resources.
4. Definir parametros reutilizaveis (`page`, `per_page`, filtros comuns).
5. Definir respostas reutilizaveis (`NotFound`, `ValidationError`, `ServerError`).
6. Validar o arquivo com ferramenta local ou importacao no Postman.

### Fase 6 - Postman

1. Criar colecao Postman v2.1 em JSON.
2. Usar variavel `{{base_url}}`.
3. Criar environment local com `base_url=http://localhost:8080/api/v1`.
4. Organizar pastas:
   - Health
   - Sources
   - Articles
   - Analyses
   - Events
5. Incluir exemplos de requests com query params.
6. Incluir testes basicos no Postman para status code 200 e presenca de `data`.

### Fase 7 - Testes automatizados

1. Criar testes de feature para todos os endpoints principais.
2. Testar paginacao.
3. Testar filtros.
4. Testar detalhes com relacionamentos.
5. Testar 404 para registros inexistentes.
6. Testar 422 para filtros invalidos.

---

## Criterios de Aceitacao

- [ ] `GET /api/v1/health` responde 200 com JSON.
- [ ] `GET /api/v1/sources` lista fontes paginadas.
- [ ] `GET /api/v1/articles` lista artigos paginados com filtros.
- [ ] `GET /api/v1/articles/{article}` retorna artigo com fonte.
- [ ] `GET /api/v1/articles/{article}/analysis` retorna analise com estratagemas ou 404 claro quando nao existir.
- [ ] `GET /api/v1/analyses` lista analises com artigo e fonte.
- [ ] `GET /api/v1/events` lista eventos sem quebrar quando nao houver comparacao.
- [ ] Respostas seguem formato JSON consistente.
- [ ] Filtros invalidos retornam 422.
- [ ] Registros inexistentes retornam 404.
- [ ] Nao ha N+1 obvio nos endpoints com relacionamentos.
- [ ] `docs/api/README.md` existe e aponta para todos os grupos de endpoints.
- [ ] `docs/openapi.yaml` descreve todos os endpoints da v1.
- [ ] `docs/postman/observatorio-civico-api.postman_collection.json` importa no Postman sem erro.
- [ ] README principal aponta para a documentacao da API e para o arquivo Postman.
- [ ] Testes de feature passam com `php artisan test`.

---

## Riscos e Mitigacoes

| # | Risco | Impacto | Mitigacao |
|---|---|---|---|
| 1 | Expor endpoint que dispara IA sem protecao | Custo financeiro e abuso | Manter IA apenas via Artisan na v1 |
| 2 | Contrato JSON mudar com facilidade | Frontend quebra | Usar API Resources e OpenAPI como contrato |
| 3 | Listagens grandes ficarem lentas | API lenta | Paginacao obrigatoria, `per_page` maximo 100 e eager loading |
| 4 | Documentacao ficar desatualizada | Postman/OpenAPI divergem do codigo | Atualizar docs na mesma PR das rotas e cobrir endpoints com testes |
| 5 | Eventos/comparacoes ainda vazios confundirem frontend | UX inconsistente | Documentar status experimental e retornar arrays vazios de forma previsivel |
| 6 | Backlogs antigos induzirem implementacao fora do Laravel | Retrabalho arquitetural | Tratar `004` como fonte atual para API REST Laravel |

---

## Resultado Esperado

Ao final desta task, o repositorio deve estar pronto para ser consumido como API pelo frontend:

- Endpoints REST versionados em `/api/v1`.
- Contrato JSON estavel via Resources.
- Documentacao humana em `docs/api/`.
- Contrato tecnico em `docs/openapi.yaml`.
- Colecao Postman importavel em `docs/postman/`.
- Testes cobrindo comportamento principal da API.
