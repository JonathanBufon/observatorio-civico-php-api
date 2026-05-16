# Articles

## Listar artigos

```http
GET /api/v1/articles
```

Query params:

| Parametro | Tipo | Descricao |
|---|---|---|
| `source_id` | int | Filtra por fonte |
| `has_analysis` | boolean | Filtra artigos com ou sem analise |
| `published_from` | date | Data inicial |
| `published_to` | date | Data final |
| `q` | string | Busca simples por titulo |
| `per_page` | int | Itens por pagina |

## Detalhar artigo

```http
GET /api/v1/articles/{article}
```

Retorna artigo com fonte, analise carregada quando existir e eventos relacionados.

## Analise do artigo

```http
GET /api/v1/articles/{article}/analysis
```

Retorna 404 quando o artigo ainda nao foi analisado.
