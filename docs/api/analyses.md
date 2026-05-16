# Analyses

## Listar analises

```http
GET /api/v1/analyses
```

Query params:

| Parametro | Tipo | Descricao |
|---|---|---|
| `source_id` | int | Filtra pela fonte do artigo analisado |
| `model_used` | string | Filtra pelo modelo de IA |
| `prompt_version` | string | Filtra pela versao do prompt |
| `per_page` | int | Itens por pagina |

## Detalhar analise

```http
GET /api/v1/analyses/{analysis}
```

Inclui artigo, fonte e estratagemas argumentativos detectados.
