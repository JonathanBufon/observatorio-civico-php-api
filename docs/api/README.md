# Observatorio Civico API

Documentacao da API REST Laravel do Observatorio Civico.

Base local padrao:

```txt
http://localhost:8080/api/v1
```

## Recursos

- [Autenticacao](authentication.md)
- [Paginacao e filtros](pagination-and-filters.md)
- [Erros](errors.md)
- [Sources](sources.md)
- [Articles](articles.md)
- [Analyses](analyses.md)
- [Events](events.md)

## Contrato tecnico

- OpenAPI: [`../openapi.yaml`](../openapi.yaml)
- Postman: [`../postman/observatorio-civico-api.postman_collection.json`](../postman/observatorio-civico-api.postman_collection.json)
- Environment local: [`../postman/observatorio-civico-local.postman_environment.json`](../postman/observatorio-civico-local.postman_environment.json)

## Endpoints

| Metodo | Endpoint | Descricao |
|---|---|---|
| GET | `/health` | Status da API |
| GET | `/sources` | Lista fontes RSS |
| GET | `/sources/{source}` | Detalha fonte |
| GET | `/sources/{source}/articles` | Lista artigos da fonte |
| GET | `/articles` | Lista artigos |
| GET | `/articles/{article}` | Detalha artigo |
| GET | `/articles/{article}/analysis` | Analise de um artigo |
| GET | `/analyses` | Lista analises |
| GET | `/analyses/{analysis}` | Detalha analise |
| GET | `/events` | Lista eventos |
| GET | `/events/{event}` | Detalha evento |
| GET | `/events/{event}/comparison` | Comparacao multi-fonte do evento |
