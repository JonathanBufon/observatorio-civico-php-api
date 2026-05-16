# Sources

## Listar fontes

```http
GET /api/v1/sources
```

Query params:

| Parametro | Tipo | Descricao |
|---|---|---|
| `active` | boolean | Filtra fontes ativas ou inativas |
| `editorial_leaning` | string | Filtra linha editorial |
| `per_page` | int | Itens por pagina |

## Detalhar fonte

```http
GET /api/v1/sources/{source}
```

## Artigos de uma fonte

```http
GET /api/v1/sources/{source}/articles
```

Aceita os mesmos filtros de artigos, exceto `source_id`, pois a fonte ja vem na rota.
