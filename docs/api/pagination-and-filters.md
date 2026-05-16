# Paginacao e Filtros

Listagens usam a paginacao padrao do Laravel e retornam:

```json
{
  "data": [],
  "links": {},
  "meta": {}
}
```

## Parametros comuns

| Parametro | Tipo | Descricao |
|---|---|---|
| `page` | int | Pagina solicitada |
| `per_page` | int | Itens por pagina, maximo 100 |

## Filtros por recurso

### Sources

| Parametro | Tipo |
|---|---|
| `active` | boolean |
| `editorial_leaning` | string |

### Articles

| Parametro | Tipo |
|---|---|
| `source_id` | int |
| `has_analysis` | boolean |
| `published_from` | date |
| `published_to` | date |
| `q` | string |

### Analyses

| Parametro | Tipo |
|---|---|
| `source_id` | int |
| `model_used` | string |
| `prompt_version` | string |

### Events

| Parametro | Tipo |
|---|---|
| `category` | string |
