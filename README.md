# Observatorio Civico — API

API REST em Laravel para a plataforma Observatorio Civico: leitura critica de noticias politicas brasileiras, assistida por IA.

Este repositorio contem exclusivamente o backend (API) do projeto. O frontend e desenvolvido em repositorio separado.

## O Problema

Muitas noticias politicas no Brasil chegam ao cidadao com linguagem dificil, recortes enviesados ou favorecendo determinados lados. Isso dificulta a compreensao dos fatos e afasta a populacao do debate publico.

## A Proposta

O Observatorio Civico coleta noticias de multiplas fontes RSS, processa o conteudo com IA (OpenAI) e apresenta uma analise critica que:

- **Separa fatos de opinioes** em cada noticia
- **Detecta estratagemas argumentativos** baseados no framework de Schopenhauer
- **Compara enquadramentos** entre fontes de diferentes linhas editoriais
- **Explica termos tecnicos** (juridicos, politicos, financeiros) em linguagem acessivel
- **Identifica vies por omissao** -- o que uma fonte cobre e outra ignora
- **Mantem transparencia total** -- mostra criterios aplicados, fontes originais e limitacoes

O sistema nao se posiciona como arbitro da verdade. E um mediador de leitura critica com criterios explicitos e auditaveis.

## Principios de Design

1. **Transparencia e rastreabilidade** -- toda analise mostra fonte original, criterios e justificativa
2. **Mediador, nao arbitro** -- apoia a leitura critica, nao dita a verdade
3. **Comparacao multi-fonte** -- nunca depende de uma unica fonte
4. **Neutralidade operacional** -- criterios explicitos, nao neutralidade absoluta
5. **Schopenhauer como framework** -- estratagemas argumentativos como base de deteccao retorica
6. **Politicos como funcionarios publicos** -- reposiciona a relacao cidadao-representante

## Stack

- **Backend:** Laravel 13 / PHP 8.3
- **Banco:** PostgreSQL 16
- **IA:** OpenAI API
- **Infra:** Docker + Docker Compose
- **Coleta:** Laminas Feed (RSS/Atom parser)

## Estado Atual

### Implementado
- Pipeline de coleta RSS com 10 fontes brasileiras de diferentes linhas editoriais
- Deduplicacao atomica via PostgreSQL (`ON CONFLICT DO NOTHING`)
- Isolamento de erros por fonte (uma falha nao interrompe as demais)
- Schema completo do banco (sources, articles, events, analyses, argumentative_strategies, source_comparisons)
- Infraestrutura Docker (app + banco) com Makefile
- Prova de conceito do pipeline IA (teste manual com noticias reais -- ver `tests/experiment/`)

### Proximo Passo
- Servico de analise por IA (plano em `backlog/003-servico-analise-ia.md`)
- Agrupamento de eventos (matching semantico de artigos sobre o mesmo fato)
- Comparacao multi-fonte automatizada
- API REST para consumo pelo frontend

## Setup

### Pre-requisitos
- Docker e Docker Compose

### Subir o ambiente
```bash
cp .env.example .env          # ajustar portas se necessario
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan migrate --seed
```

### Coletar noticias
```bash
docker compose exec app php artisan rss:fetch
```

### Comandos uteis (Makefile)
```bash
make help          # lista todos os comandos
make up            # sobe containers
make fetch         # coleta RSS
make fetch-dry     # simula coleta sem persistir
make db-count      # contagem de fontes e artigos
make migrate-fresh # reseta banco e roda migrations + seed
make logs-app      # logs do app
```

## Fontes RSS

| Fonte | Linha Editorial | Status |
|-------|----------------|--------|
| Folha de S.Paulo - Poder | centro-esquerda | ativo |
| Estadao - Politica | centro-direita | ativo |
| G1 - Politica | centro | ativo |
| UOL Noticias | centro | ativo |
| Carta Capital | esquerda | ativo |
| Gazeta do Povo | direita | ativo |
| Congresso em Foco | independente | ativo |
| Poder360 | centro | ativo |
| Nexo Jornal | centro-esquerda | ativo |
| BBC Brasil | centro-independente | ativo |

## Documentacao

- `AGENTS.md` -- Analise conceitual do projeto (stress-test com The Fool)
- `backlog/` -- Planos de implementacao e licoes aprendidas
- `tests/experiment/` -- Prova de conceito do pipeline de analise IA

## Licenca

MIT
