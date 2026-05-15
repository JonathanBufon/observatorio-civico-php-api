# Licoes Aprendidas - Prototipo v0 (Slim Framework)

Data: 2026-05-15

---

## O que deu certo

### Coleta RSS
- **Laminas Feed** funciona bem para parse de RSS/Atom -- manter na v1 Laravel
- **Deduplicacao via `ON CONFLICT DO NOTHING`** e atomica e performatica -- usar no Eloquent com DB::statement raw
- **Isolamento de erros por fonte** (try/catch individual) foi critico: 5 fontes falharam mas as outras 5 coletaram 250 artigos normalmente
- **Normalizacao de encoding** (detectar charset no Content-Type e converter com mb_convert_encoding) evitou caracteres corrompidos
- **Fallback encadeado para external_id**: getId() -> getLink() -> hash SHA-256 cobriu todos os feeds

### Fontes RSS validadas (URLs corretas em 2026-05-15)
| Fonte | URL | Artigos | Linha editorial |
|-------|-----|---------|-----------------|
| Folha de S.Paulo - Poder | `https://feeds.folha.uol.com.br/poder/rss091.xml` | 101 | centro-esquerda |
| G1 - Politica | `https://g1.globo.com/rss/g1/politica/` | 100 | centro |
| Gazeta do Povo | `https://www.gazetadopovo.com.br/feed/rss/republica.xml` | 62 | direita |
| BBC Brasil | `https://feeds.bbci.co.uk/portuguese/rss.xml` | 38 | centro-independente |
| Nexo Jornal | `https://www.nexojornal.com.br/rss.xml` | 20 | centro-esquerda |
| Estadao - Politica | `https://www.estadao.com.br/arc/outboundfeeds/feeds/rss/sections/politica/` | 20 | centro-direita |
| Carta Capital | `https://www.cartacapital.com.br/feed/` | 20 | esquerda |
| Congresso em Foco | `https://congressoemfoco.uol.com.br/feed/` | 20 | independente |
| UOL Noticias | `https://rss.uol.com.br/feed/noticias.xml` | 15 | centro |
| Poder360 | `https://www.poder360.com.br/feed/` | 10 | centro |

### Docker
- **Init-db.sh** via `/docker-entrypoint-initdb.d/` funciona bem para setup automatico
- **Healthcheck com pg_isready** + depends_on condition garante que app so sobe com banco pronto
- **Volume separado para vendor** evita conflito com bind-mount do host
- **Makefile** com alvos como `fetch`, `db-fresh`, `db-count` agiliza muito o dev

### Experimento de Analise IA
- Pipeline completo testado manualmente com 2 fontes (CNN + BBC) sobre caso Vorcaro/Bolsonaro
- A IA conseguiu: separar fatos de opinioes, detectar 7 estratagemas retoricos, identificar vies por omissao em ambos os veiculos, explicar termos tecnicos, gerar log de transparencia
- **JSON estruturado como formato de saida** (nao markdown) e essencial para persistencia automatica
- **Prompt versionado** permite rastrear qual prompt gerou cada analise

---

## O que deu errado

### URLs RSS
- **5 de 10 URLs iniciais estavam quebradas** (404 ou 403): Estadao, UOL, Gazeta do Povo, Nexo, BBC
- URLs de feeds RSS sao **altamente instaveis** -- mudam sem aviso
- O UOL nao tem feed RSS especifico de politica (so feed geral de noticias)
- A BBC mudou o dominio de feeds de `www.bbc.com` para `feeds.bbci.co.uk`
- Estadao mudou de `/pf/api/rss/` para `/arc/outboundfeeds/feeds/rss/sections/`

### Framework
- Construir CLI routing manual (`match` em `$argv`) e fragil e nao escala
- Container DI manual (PHP-DI + factories) e muito boilerplate para o que Laravel entrega de graca
- Migrations em SQL raw funcionam, mas perdem versionamento e rollback automatico

### Scraping
- WebFetch do Claude Code nao consegue acessar UOL (403 por bloqueio de bot)
- Defuddle CLI tambem falha em sites com protecao anti-bot (UOL, Folha)
- Conteudo do RSS geralmente e apenas lead/resumo, nao texto completo

---

## O que NAO fazer

1. **Nao assumir que URLs RSS sao estaveis** -- sempre validar com curl antes de commitar o seed
2. **Nao criar CLI routing manual** -- usar Artisan commands do Laravel
3. **Nao criar container DI manual** -- usar Service Providers do Laravel
4. **Nao escrever migrations em SQL raw** -- usar Laravel migrations (PHP) com Blueprint
5. **Nao esperar texto completo do RSS** -- a maioria dos feeds brasileiros so tem lead
6. **Nao processar todos os artigos de uma vez na analise IA** -- usar --limit para controlar custo
7. **Nao confiar em resposta markdown da IA** -- exigir JSON estruturado e validar schema
8. **Nao usar User-Agent generico** -- configurar um User-Agent especifico que nao seja bloqueado

---

## O que FAZER

1. **Usar `ON CONFLICT DO NOTHING`** para deduplicacao -- funciona perfeitamente com PostgreSQL via `DB::statement`
2. **Isolar erros por fonte** -- try/catch individual em cada source no loop de coleta
3. **Versionar prompts** -- campo `prompt_version` em toda analise, permite re-processar com prompt novo
4. **Manter log de transparencia** -- toda analise deve ter justificativa dos criterios aplicados
5. **Validar JSON da IA antes de persistir** -- campos obrigatorios, tipos corretos
6. **Rate limiting entre requests** -- respeitar limites da API, implementar retry com backoff
7. **Makefile para dev** -- atalhos para comandos Docker frequentes
8. **Seed de fontes com data de validacao** -- incluir quando as URLs foram verificadas
9. **Normalizar encoding** -- verificar charset no Content-Type e converter para UTF-8
10. **Comparacao multi-fonte** -- nunca analisar artigo isolado, sempre cruzar enquadramentos

---

## Arquivos reutilizaveis na v1 Laravel

| Arquivo | Status | Notas |
|---------|--------|-------|
| `AGENTS.md` | Manter integralmente | Analise conceitual e principios de design |
| `backlog/003-servico-analise-ia.md` | Adaptar para Laravel | Logica e prompt sao reutilizaveis, mudar infra |
| `database/seeds/sources.sql` | Converter para Laravel Seeder | URLs validadas sao o valor |
| `tests/experiment/` | Manter como referencia | Prova de conceito do pipeline IA |
| `docker-compose.yml` | Adaptar | Trocar imagem app para Laravel Sail ou custom |
| `Makefile` | Adaptar | Trocar comandos para Artisan |
| `Dockerfile` | Recriar | Laravel tem requisitos diferentes |
