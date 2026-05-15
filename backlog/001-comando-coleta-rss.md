# 001 - Comando de Coleta RSS

## Objetivo

Implementar o primeiro estagio do pipeline do Observatorio Civico: um comando CLI (`php bin/console rss:fetch`) que percorre todas as fontes RSS ativas cadastradas na tabela `sources`, faz o download e parsing dos feeds RSS/Atom usando `laminas/laminas-feed`, extrai os artigos e persiste-os na tabela `articles` com deduplicacao por `external_id`. Ao final do processamento de cada fonte, atualiza o campo `last_fetched_at` da respectiva source. O comando deve ser robusto o suficiente para lidar com falhas de rede, feeds mal-formados e fontes indisponiveis, sem interromper o processamento das demais fontes.

---

## Arquivos a Criar

### 1. `bin/console` -- Entry point CLI

Arquivo PHP executavel (com `#!/usr/bin/env php` no topo e permissao `chmod +x`). Responsavel por:
- Carregar o autoloader do Composer (`vendor/autoload.php`)
- Carregar variaveis de ambiente via `vlucas/phpdotenv`
- Construir o container DI (`php-di/php-di`) reutilizando `config/container.php`
- Fazer o routing basico de comandos via `$argv[1]` (sem framework de console -- manter simples)
- Mapear `rss:fetch` para `App\Command\FetchRssCommand::execute()`
- Retornar exit code 0 em sucesso, 1 em falha

Nao usar Symfony Console nem outro framework pesado. Um switch/match simples sobre `$argv[1]` e suficiente neste momento. Se o projeto crescer para muitos comandos, migrar para `symfony/console` depois.

### 2. `src/Command/FetchRssCommand.php` -- Comando de coleta

Classe `App\Command\FetchRssCommand` com metodo `execute(): int`. Recebe `RssFetcherService` via construtor (injecao de dependencia). Responsavel por:
- Chamar o servico de coleta
- Capturar e formatar output para o terminal (contagem de fontes processadas, artigos novos, erros)
- Aceitar flags opcionais via `$argv`: `--source=ID` para coletar apenas uma fonte especifica, `--dry-run` para simular sem persistir
- Retornar exit code

### 3. `src/Service/RssFetcherService.php` -- Logica de negocio da coleta

Classe `App\Service\RssFetcherService` que encapsula toda a logica de coleta. Recebe via construtor: `SourceRepository`, `ArticleRepository`, `GuzzleHttp\ClientInterface`, `Psr\Log\LoggerInterface`. Responsavel por:
- Buscar todas as sources ativas (ou uma especifica, se `sourceId` for passado)
- Para cada source: fazer HTTP GET na URL do feed, parsear com `Laminas\Feed\Reader\Reader`, iterar os entries
- Para cada entry: extrair `guid` (ou link como fallback) para `external_id`, title, link para `original_url`, content/description para `content`, dateModified/dateCreated para `published_at`
- Delegar persistencia ao `ArticleRepository`
- Atualizar `last_fetched_at` da source via `SourceRepository`
- Coletar metricas (novos, duplicados, erros) e retornar como array associativo
- Tratar excecoes por fonte (try/catch individual) para nao interromper o loop

### 4. `src/Repository/SourceRepository.php` -- Acesso a dados de fontes

Classe `App\Repository\SourceRepository` que recebe `Doctrine\DBAL\Connection`. Metodos:
- `findAllActive(): array` -- retorna todas as sources com `is_active = true`
- `findById(int $id): ?array` -- retorna uma source por ID
- `updateLastFetchedAt(int $id, \DateTimeImmutable $timestamp): void` -- atualiza `last_fetched_at`

### 5. `src/Repository/ArticleRepository.php` -- Acesso a dados de artigos

Classe `App\Repository\ArticleRepository` que recebe `Doctrine\DBAL\Connection`. Metodos:
- `insertIfNotExists(array $data): ?int` -- usa `INSERT ... ON CONFLICT (source_id, external_id) DO NOTHING RETURNING id` para atomicidade (PostgreSQL nativo via DBAL `executeStatement` com SQL raw). Retorna o ID se inseriu, null se ja existia.

### 6. `src/Entity/Source.php` -- Value object de fonte

Classe `App\Entity\Source` imutavel (readonly class, PHP 8.3). Propriedades espelhando a tabela: `id`, `name`, `url`, `editorialLeaning`, `isActive`, `lastFetchedAt`, `createdAt`, `updatedAt`. Metodo estatico `fromRow(array $row): self` para hidratar a partir de resultado do banco.

### 7. `src/Entity/Article.php` -- Value object de artigo

Classe `App\Entity\Article` imutavel (readonly class). Propriedades: `id`, `sourceId`, `externalId`, `title`, `originalUrl`, `content`, `publishedAt`, `fetchedAt`, `createdAt`. Metodo estatico `fromRow(array $row): self`.

### 8. `config/container.php` -- Atualizacao

Adicionar ao array de definicoes existente:
- `Psr\Log\LoggerInterface` -- factory que retorna Monolog handler escrevendo em `storage/logs/app.log`
- `GuzzleHttp\ClientInterface` -- factory que retorna `new GuzzleHttp\Client` com timeout de 15s e User-Agent customizado
- Repositories, Service e Command sao autowired pelo PHP-DI

### 9. `database/seeds/sources.sql` -- Fontes RSS iniciais

Script SQL com INSERT INTO sources para as seguintes fontes:

| # | Nome | URL do Feed RSS | editorial_leaning |
|---|------|-----------------|-------------------|
| 1 | Folha de S.Paulo - Poder | `https://feeds.folha.uol.com.br/poder/rss091.xml` | centro-esquerda |
| 2 | Estadao - Politica | `https://www.estadao.com.br/pf/api/rss/ultimas/politica` | centro-direita |
| 3 | G1 - Politica | `https://g1.globo.com/rss/g1/politica/` | centro |
| 4 | UOL Noticias - Politica | `https://rss.uol.com.br/feed/noticias/politica.xml` | centro |
| 5 | Carta Capital | `https://www.cartacapital.com.br/feed/` | esquerda |
| 6 | Gazeta do Povo | `https://www.gazetadopovo.com.br/feed/rss/` | direita |
| 7 | Congresso em Foco | `https://congressoemfoco.uol.com.br/feed/` | independente |
| 8 | Poder360 | `https://www.poder360.com.br/feed/` | centro |
| 9 | Nexo Jornal | `https://www.nexojornal.com.br/feed/` | centro-esquerda |
| 10 | BBC Brasil | `https://www.bbc.com/portuguese/topics/crezq0g9w0vt/rss.xml` | centro-independente |

**Nota:** As URLs dos feeds devem ser validadas manualmente antes de executar o seed. Incluir data de validacao como comentario no arquivo.

---

## Fluxo de Execucao

```
php bin/console rss:fetch
         |
         v
[bin/console]
  1. Carrega autoloader + .env
  2. Constroi container DI
  3. Resolve FetchRssCommand do container
  4. Chama execute($argv)
         |
         v
[FetchRssCommand::execute()]
  1. Parseia flags (--source=ID, --dry-run)
  2. Chama RssFetcherService::fetch(?sourceId, dryRun)
  3. Imprime resumo no terminal
  4. Retorna exit code
         |
         v
[RssFetcherService::fetch()]
  1. Busca sources ativas via SourceRepository
  2. Para cada source:
     |
     +---> try {
     |       a. HTTP GET na source.url via Guzzle (timeout 15s)
     |       b. Parseia com Laminas\Feed\Reader\Reader::importString($body)
     |       c. Itera entries do feed:
     |          - external_id = entry->getId() ?? entry->getLink() ?? sha256(title+url)
     |          - title, original_url, content, published_at
     |          - ArticleRepository::insertIfNotExists(...)
     |          - Incrementa contadores
     |       d. SourceRepository::updateLastFetchedAt(source.id, now)
     |       e. Loga sucesso
     |     }
     +---> catch (\Throwable $e) {
     |       - Loga erro, incrementa contador, CONTINUA
     |     }
     |
  3. Retorna metricas: [sources_processed, articles_new, articles_skipped, errors]
```

Output esperado no terminal:
```
[rss:fetch] Coleta finalizada
  Fontes processadas: 8/10
  Artigos novos:      47
  Artigos duplicados: 123
  Erros:              2 (ver logs para detalhes)
```

---

## Decisoes Tecnicas

### 1. Tratamento de falhas

Cada source e processada dentro de um bloco `try/catch(\Throwable)` individual:
- **Timeout/403/5xx:** Guzzle lanca `RequestException`. Logar warning, pular fonte.
- **Feed mal-formado:** Laminas lanca `RuntimeException`. Logar warning, pular fonte.
- **Erro de banco:** Se erro de conexao, abortar. Se erro de constraint, logar e continuar.

Exit code 0 se pelo menos uma source foi processada. Exit code 1 se todas falharam.

### 2. Estrategia de deduplicacao

`INSERT INTO articles (...) ON CONFLICT (source_id, external_id) DO NOTHING` via SQL nativo PostgreSQL. Atomico e seguro para execucao concorrente.

Prioridade para `external_id`:
1. `entry->getId()` (campo `<guid>` RSS / `<id>` Atom)
2. `entry->getLink()` como fallback
3. Hash SHA-256 de titulo + URL da fonte como ultimo recurso

### 3. Extracao de conteudo

Usar apenas o conteudo do feed RSS (sem scraping):
- `entry->getContent()` (`<content:encoded>`) como primeira opcao
- `entry->getDescription()` como fallback

Conteudo salvo com HTML intacto. Limpeza sera responsabilidade do estagio de analise por IA.

### 4. Logging

Monolog escrevendo em `storage/logs/app.log`:
- `INFO`: Inicio/fim, source processada, totais
- `WARNING`: Fonte pulada por erro, entry sem external_id
- `ERROR`: Falha de conexao com banco

### 5. Entry point CLI

Match simples sobre `$argv[1]`:
```php
$command = $argv[1] ?? 'help';
$exitCode = match ($command) {
    'rss:fetch' => $container->get(FetchRssCommand::class)->execute($argv),
    default => /* help */ 0,
};
exit($exitCode);
```

Migrar para `symfony/console` se o numero de comandos passar de 5.

### 6. Configuracao Guzzle

- `timeout` => 15s
- `connect_timeout` => 10s
- `User-Agent` => `ObservatorioCivico/1.0`
- `http_errors` => true
- `verify` => true

---

## Criterios de Aceitacao

- [ ] `php bin/console rss:fetch` executa sem erros com banco acessivel e fontes cadastradas
- [ ] Artigos inseridos com todos os campos obrigatorios preenchidos
- [ ] Rodar duas vezes nao gera duplicatas (deduplicacao por source_id + external_id)
- [ ] `last_fetched_at` da source atualizado apos coleta
- [ ] Fonte que falha nao interrompe processamento das demais
- [ ] Erros registrados em `storage/logs/app.log`
- [ ] Resumo legivel impresso no terminal ao finalizar
- [ ] `php bin/console rss:fetch --source=3` coleta apenas a fonte ID 3
- [ ] Seed SQL insere pelo menos 8 fontes com diferentes linhas editoriais
- [ ] Classes seguem PSR-4, strict_types, tipos explicitos

---

## Dependencias e Riscos

### Dependencias

1. PostgreSQL rodando com schema da migration `001_create_schema.sql` aplicado
2. `.env` configurado com credenciais do banco
3. `composer install` executado
4. Adicionar `monolog/monolog` e `psr/log` ao `composer.json`

### Riscos

| # | Risco | Impacto | Mitigacao |
|---|-------|---------|-----------|
| 1 | URLs de feeds desatualizadas | Zero coleta | Validar manualmente antes do seed |
| 2 | Sites bloqueiam bots | 403 em fontes | User-Agent realista, headers Accept |
| 3 | Entries sem guid nem link | Impossivel deduplicar | Fallback para hash SHA-256, logar warning |
| 4 | RSS contem apenas lead, nao texto completo | Analise IA limitada | Aceitar por agora. Scraping full-text e task futura |
| 5 | Feeds grandes (centenas de entries) | Memoria | Aceitar para v1. Limitar a N recentes se necessario |
| 6 | Encoding (UTF-8 vs ISO-8859-1) | Caracteres corrompidos | Verificar Content-Type, converter com `mb_convert_encoding()` |
| 7 | Rate limiting se cron muito frequente | Bloqueio temporario | Respeitar `RSS_FETCH_INTERVAL_MINUTES` do .env no cron |

---

## Ordem de Implementacao

1. `composer require monolog/monolog psr/log`
2. `src/Entity/Source.php` e `src/Entity/Article.php`
3. `src/Repository/SourceRepository.php` e `src/Repository/ArticleRepository.php`
4. Atualizar `config/container.php`
5. `src/Service/RssFetcherService.php`
6. `src/Command/FetchRssCommand.php`
7. `bin/console`
8. `database/seeds/sources.sql`
9. Testar: `php bin/console rss:fetch`
