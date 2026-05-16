# Events

## Listar eventos

```http
GET /api/v1/events
```

Query params:

| Parametro | Tipo | Descricao |
|---|---|---|
| `category` | string | Filtra categoria |
| `per_page` | int | Itens por pagina |

## Detalhar evento

```http
GET /api/v1/events/{event}
```

Retorna evento com artigos relacionados e comparacao quando existir.

## Comparacao multi-fonte

```http
GET /api/v1/events/{event}/comparison
```

Retorna 404 quando a comparacao ainda nao foi gerada.
