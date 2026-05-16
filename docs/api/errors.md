# Erros

## 404

Registro inexistente ou recurso ainda nao disponivel:

```json
{
  "message": "Analise ainda nao disponivel para este artigo."
}
```

## 422

Filtro ou parametro invalido:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "per_page": [
      "The per page field must not be greater than 100."
    ]
  }
}
```

## 500

Erro inesperado da aplicacao. Verificar `storage/logs/laravel.log`.
