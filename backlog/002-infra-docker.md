# 002 - Infraestrutura Docker (API + PostgreSQL) [CONCLUIDA]

## Objetivo

Criar a infraestrutura Docker para rodar o backend PHP (Slim Framework) e o banco PostgreSQL em containers, permitindo que qualquer desenvolvedor suba o ambiente com um unico comando (`docker compose up`). A infra deve suportar tanto o servidor web (API REST) quanto a execucao de comandos CLI (como `rss:fetch`).

---

## Arquivos a Criar

### 1. `docker-compose.yml` -- Orquestracao dos servicos

Dois servicos principais:

| Servico | Imagem base | Porta | Descricao |
|---------|-------------|-------|-----------|
| `app` | Build local (Dockerfile) | 8080:80 | PHP 8.3 + Apache/Nginx servindo `public/index.php` |
| `db` | `postgres:16-alpine` | 5432:5432 | PostgreSQL com volume persistente |

Configuracoes:
- Network compartilhada entre os servicos
- Volume nomeado para dados do PostgreSQL (`pgdata`)
- Volume bind-mount do projeto para o container app (development hot-reload)
- Variaves de ambiente do banco via `.env` (reutilizar o `.env` existente)
- Healthcheck no servico `db` para garantir que o app so inicia quando o banco estiver pronto
- `depends_on` com condition `service_healthy`

### 2. `Dockerfile` -- Imagem da aplicacao PHP

Baseado em `php:8.3-apache` (ou `php:8.3-fpm` + nginx, a decidir). Deve:
- Instalar extensoes PHP necessarias: `pdo_pgsql`, `pgsql`, `intl`, `mbstring`
- Instalar Composer dentro da imagem
- Copiar o codigo-fonte e rodar `composer install --no-dev`
- Configurar o DocumentRoot para `public/`
- Habilitar `mod_rewrite` (se Apache)
- Definir permissoes corretas em `storage/logs/`

**Decisao a tomar:** Apache vs Nginx+FPM
- Apache: mais simples, uma unica imagem, bom para projeto pequeno
- Nginx+FPM: mais performatico, mas exige 2 containers ou config mais complexa
- **Recomendacao:** Apache para v1 (simplicidade). Migrar para Nginx+FPM se necessario.

### 3. `.dockerignore` -- Exclusoes do build

Excluir: `vendor/`, `.git/`, `.idea/`, `.claude/`, `.cursor/`, `.windsurf/`, `.agents/`, `storage/logs/*`, `tests/experiment/`, `node_modules/` (futuro frontend React).

### 4. `docker/apache.conf` (ou `docker/nginx.conf`) -- Config do servidor web

Configuracao minima do virtualhost:
- DocumentRoot em `/var/www/html/public`
- AllowOverride All (para .htaccess se necessario)
- FallbackResource para `/index.php` (Slim precisa disso para rotas limpas)

### 5. `docker/init-db.sh` -- Script de inicializacao do banco

Script que roda automaticamente na primeira vez que o container `db` sobe (via volume em `/docker-entrypoint-initdb.d/`):
- Cria o banco `observatorio_civico` (se nao existir)
- Aplica a migration `database/migrations/001_create_schema.sql`
- Aplica o seed `database/seeds/sources.sql`

Assim o `docker compose up` ja entrega o ambiente completo, com schema e fontes.

### 6. Atualizacao do `.env.example` -- Variaveis Docker

Adicionar/ajustar variaveis para refletir o ambiente Docker:
- `DB_HOST=db` (nome do servico no compose, nao localhost)
- `DB_PORT=5432`
- `DB_NAME=observatorio_civico`
- `DB_USER=observatorio`
- `DB_PASSWORD=observatorio_dev`

---

## Fluxo de Uso

### Subir o ambiente pela primeira vez
```bash
cp .env.example .env        # ajustar se necessario
docker compose up -d        # sobe app + db
# banco ja inicializa com schema + seed automaticamente
```

### Rodar comandos CLI dentro do container
```bash
docker compose exec app php bin/console rss:fetch
docker compose exec app php bin/console rss:fetch --source=3
docker compose exec app php bin/console rss:fetch --dry-run
```

### Ver logs
```bash
docker compose logs -f app   # logs do Apache/PHP
docker compose logs -f db    # logs do PostgreSQL
```

### Acessar o banco diretamente
```bash
docker compose exec db psql -U observatorio -d observatorio_civico
```

### Rebuild apos mudancas no Dockerfile
```bash
docker compose up -d --build
```

---

## Decisoes Tecnicas

### 1. Imagem PHP: `php:8.3-apache`

Escolha por simplicidade. Um unico container serve tanto a API quanto o CLI. Para producao futura, considerar separar em `php:8.3-fpm` + `nginx:alpine`.

### 2. Composer no build

Rodar `composer install --no-dev --optimize-autoloader` no Dockerfile. Em desenvolvimento, o bind-mount sobrescreve o vendor/ do container, entao o dev roda `composer install` localmente tambem.

Alternativa para dev: usar multi-stage build onde o stage de producao faz o install, mas o compose em dev monta o volume local por cima.

### 3. Inicializacao automatica do banco

Usar o mecanismo nativo do container postgres: scripts em `/docker-entrypoint-initdb.d/` rodam automaticamente apenas na primeira vez (quando o volume esta vazio). Isso garante que o `docker compose up` ja entrega tudo pronto sem passos manuais.

### 4. Volume persistente para PostgreSQL

Volume nomeado `pgdata` para que os dados sobrevivam a `docker compose down`. Para resetar o banco: `docker compose down -v` (remove volumes).

### 5. Hot-reload em desenvolvimento

Bind-mount do diretorio do projeto (`./:/var/www/html`) para que mudancas no codigo reflitam imediatamente no container sem rebuild. Excecao: mudancas no Dockerfile ou em extensoes PHP exigem rebuild.

---

## Criterios de Aceitacao

- [ ] `docker compose up -d` sobe os dois servicos sem erro
- [ ] `curl http://localhost:8080/api/sources` retorna resposta HTTP (mesmo que 500 por controller nao implementado)
- [ ] `docker compose exec app php bin/console help` mostra o help do CLI
- [ ] `docker compose exec app php bin/console rss:fetch` executa a coleta (com banco ja populado pelo seed)
- [ ] `docker compose exec db psql -U observatorio -d observatorio_civico -c "SELECT count(*) FROM sources"` retorna 10
- [ ] `docker compose down && docker compose up -d` mantem os dados do banco (volume persistente)
- [ ] `docker compose down -v && docker compose up -d` reseta o banco do zero (reinicializa schema + seed)
- [ ] Nao ha credenciais hardcoded — tudo vem do `.env`

---

## Dependencias e Riscos

| # | Risco | Impacto | Mitigacao |
|---|-------|---------|-----------|
| 1 | Docker nao instalado na maquina do dev | Bloqueio total | Documentar pre-requisitos no README |
| 2 | Porta 8080 ou 5432 ja em uso | Container nao sobe | Tornar portas configuraveis via `.env` (ex: `APP_PORT=8080`) |
| 3 | Bind-mount em Linux vs macOS tem performance diferente | Lentidao no macOS | Aceitavel para v1. Se necessario, usar `consistency: cached` |
| 4 | Init script falha silenciosamente | Banco vazio, app quebra | Testar o script isoladamente. Verificar logs do container db |

---

## Estrutura final esperada

```
observatorio-php/
├── docker-compose.yml
├── Dockerfile
├── .dockerignore
├── docker/
│   ├── apache.conf
│   └── init-db.sh
├── .env.example            # atualizado com variaveis Docker
└── (demais arquivos do projeto)
```
