COMPOSE ?= docker compose
APP ?= app
DB ?= db
SQL ?= SELECT 1;
LIMIT ?= 10

.DEFAULT_GOAL := help

.PHONY: help up build rebuild down stop restart ps logs logs-app logs-db \
	shell bash db-cli db-query db-count \
	migrate migrate-fresh seed db-reset \
	fetch fetch-dry fetch-source \
	composer-install composer-update test phpstan artisan url

help: ## Mostra esta lista de comandos
	@awk 'BEGIN {FS = ":.*##"; printf "Uso: make <alvo>\n\nAlvos:\n"} /^[a-zA-Z0-9_.-]+:.*##/ {printf "  %-18s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

up: ## Sobe app e banco em background
	$(COMPOSE) up -d

build: ## Faz build das imagens
	$(COMPOSE) build

rebuild: ## Rebuilda e sobe os containers
	$(COMPOSE) up -d --build

down: ## Para e remove containers (mantendo volumes)
	$(COMPOSE) down

stop: ## Para containers sem remover
	$(COMPOSE) stop

restart: ## Reinicia containers
	$(COMPOSE) restart

ps: ## Mostra status dos containers
	$(COMPOSE) ps

logs: ## Logs de todos os servicos
	$(COMPOSE) logs -f

logs-app: ## Logs do app
	$(COMPOSE) logs -f $(APP)

logs-db: ## Logs do banco
	$(COMPOSE) logs -f $(DB)

shell: ## Shell no container app
	$(COMPOSE) exec $(APP) bash

bash: shell ## Alias para shell

db-cli: ## Abre psql no banco
	$(COMPOSE) exec $(DB) psql -U "$$DB_USERNAME" -d "$$DB_DATABASE"

db-query: ## Executa SQL: make db-query SQL="SELECT count(*) FROM sources"
	$(COMPOSE) exec -T $(DB) psql -U "$$DB_USERNAME" -d "$$DB_DATABASE" -c '$(SQL)'

db-count: ## Contagem de fontes e artigos
	$(COMPOSE) exec -T $(DB) psql -U "$$DB_USERNAME" -d "$$DB_DATABASE" -c 'SELECT (SELECT count(*) FROM sources) AS fontes, (SELECT count(*) FROM articles) AS artigos;'

migrate: ## Roda migrations
	$(COMPOSE) exec -T $(APP) php artisan migrate

migrate-fresh: ## Dropa tudo e roda migrations + seed
	$(COMPOSE) exec -T $(APP) php artisan migrate:fresh --seed

seed: ## Roda seeders
	$(COMPOSE) exec -T $(APP) php artisan db:seed

db-reset: ## Remove volumes e sobe tudo do zero
	$(COMPOSE) down -v
	$(COMPOSE) up -d
	@sleep 5
	$(COMPOSE) exec -T $(APP) php artisan migrate --seed

fetch: ## Coleta RSS de todas as fontes
	$(COMPOSE) exec -T $(APP) php artisan rss:fetch

fetch-dry: ## Simula coleta sem persistir
	$(COMPOSE) exec -T $(APP) php artisan rss:fetch --dry-run

fetch-source: ## Coleta fonte especifica: make fetch-source SOURCE=3
	$(COMPOSE) exec -T $(APP) php artisan rss:fetch --source=$(SOURCE)

composer-install: ## Roda composer install
	$(COMPOSE) exec -T $(APP) composer install

composer-update: ## Roda composer update
	$(COMPOSE) exec -T $(APP) composer update

test: ## Roda testes
	$(COMPOSE) exec -T $(APP) php artisan test

phpstan: ## Roda PHPStan
	$(COMPOSE) exec -T $(APP) ./vendor/bin/phpstan analyse app

artisan: ## Roda artisan: make artisan CMD="route:list"
	$(COMPOSE) exec -T $(APP) php artisan $(CMD)

url: ## Mostra URLs de acesso
	@echo "API: http://localhost:$${APP_PORT:-8080}"
	@echo "PostgreSQL: localhost:$${DB_FORWARD_PORT:-5432}"
