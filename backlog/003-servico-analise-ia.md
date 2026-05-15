# 003 - Servico de Analise por IA

## Objetivo

Implementar o segundo estagio do pipeline: um servico que pega artigos coletados (tabela `articles`), envia para a API da Anthropic (Claude) com um prompt estruturado, e persiste o resultado nas tabelas `analyses` e `argumentative_strategies`. O servico deve ser executavel via CLI (`php bin/console ai:analyze`) e processar artigos que ainda nao possuem analise.

Este estagio trata da **analise individual por artigo**. A comparacao multi-fonte (tabela `source_comparisons`) sera uma task futura que depende do agrupamento de eventos.

---

## Contexto: O que a analise produz

Baseado no experimento validado em `tests/experiment/analise_resultado.md`, cada analise de artigo deve extrair:

1. **Texto reescrito** -- versao simplificada, acessivel, sem carga emocional
2. **Fragmentos factuais** -- trechos identificados como fatos verificaveis
3. **Fragmentos opinativos** -- trechos que contem julgamento de valor ou interpretacao
4. **Termos tecnicos explicados** -- jargao juridico/politico/financeiro com explicacao simples
5. **Indicadores de vies** -- marcas de favorecimento, omissao, linguagem carregada
6. **Estratagemas argumentativos** -- baseados no framework de Schopenhauer (A Arte de Ter Razao)
7. **Log de transparencia** -- justificativa dos criterios aplicados e limitacoes da analise

---

## Arquivos a Criar

### 1. `src/Service/ArticleAnalyzerService.php` -- Orquestrador da analise

Classe `App\Service\ArticleAnalyzerService`. Recebe via construtor: `ArticleRepository`, `AnalysisRepository`, `AiClientInterface`, `LoggerInterface`. Responsavel por:
- Buscar artigos sem analise (LEFT JOIN analyses WHERE analyses.id IS NULL)
- Para cada artigo: montar o prompt, chamar a API de IA, parsear a resposta JSON, persistir resultado
- Controlar rate limiting (respeitar limites da API)
- Tratar erros por artigo sem interromper o loop
- Retornar metricas (processados, erros, tokens consumidos)

Opcoes de execucao:
- `--limit=N` para processar apenas N artigos por vez (default: 10)
- `--article=ID` para processar um artigo especifico
- `--dry-run` para ver o prompt sem chamar a API
- `--source=ID` para processar apenas artigos de uma fonte especifica

### 2. `src/Service/AiClient/AiClientInterface.php` -- Contrato da API de IA

Interface com metodo unico:
```
public function chat(string $systemPrompt, string $userMessage, ?string $model = null): AiResponse;
```

Retorna um value object `AiResponse` com: `content` (string), `model` (string), `inputTokens` (int), `outputTokens` (int).

A interface permite trocar de provider (Anthropic, OpenAI, local) sem mudar o servico.

### 3. `src/Service/AiClient/OpenAiClient.php` -- Implementacao OpenAI

Usa Laravel HTTP client para chamar `https://api.openai.com/v1/chat/completions`. Configuracao via `.env`:
- `AI_API_KEY` -- chave da API OpenAI
- `AI_MODEL` -- modelo (default: `gpt-4o`)
- `AI_MAX_TOKENS` -- limite de tokens da resposta (default: 4096)

Headers necessarios:
- `Authorization: Bearer {API_KEY}`
- `Content-Type: application/json`

Usar `response_format: { "type": "json_object" }` para garantir resposta JSON valida (JSON mode nativo da OpenAI).

Tratar erros da API: 429 (rate limit), 500 (server error), 401 (auth), timeout.
Em caso de 429, esperar o tempo indicado no header `retry-after` antes de tentar novamente (max 3 retries).

### 4. `src/Service/AiClient/AiResponse.php` -- Value object da resposta

Readonly class com: `content`, `model`, `inputTokens`, `outputTokens`.

### 5. `src/Service/PromptBuilder.php` -- Construtor de prompts

Classe responsavel por montar o system prompt e o user message para a analise. Separada do servico para facilitar versionamento e teste de prompts.

O prompt deve:
- Instruir a IA a retornar **JSON estruturado** (nao markdown)
- Definir o schema exato do JSON esperado
- Incluir os principios de design do AGENTS.md (neutralidade operacional, mediador nao arbitro, transparencia)
- Referenciar o framework de Schopenhauer para deteccao de estratagemas
- Pedir linguagem simples (como se explicasse para alguem de 16 anos)
- Pedir que politicos sejam referenciados como representantes/funcionarios publicos
- Incluir a versao do prompt como campo (`prompt_version`)

Schema JSON esperado na resposta da IA:
```json
{
  "rewritten_text": "string -- versao simplificada do artigo",
  "facts": [
    {"excerpt": "trecho original", "explanation": "por que e fato"}
  ],
  "opinions": [
    {"excerpt": "trecho original", "explanation": "por que e opiniao"}
  ],
  "simplified_terms": [
    {"term": "CPMI", "explanation": "comissao de investigacao do Congresso"}
  ],
  "bias_indicators": [
    {"type": "linguagem_carregada|omissao|favorecimento|apelo_emocional", "excerpt": "trecho", "explanation": "string"}
  ],
  "argumentative_strategies": [
    {
      "strategy_name": "nome do estratagema",
      "who_uses": "quem usa (politico, porta-voz, veiculo)",
      "excerpt": "trecho onde aparece",
      "explanation": "explicacao acessivel",
      "severity": "low|medium|high"
    }
  ],
  "transparency_log": "string -- criterios aplicados e limitacoes"
}
```

### 6. `src/Repository/AnalysisRepository.php` -- Acesso a dados de analises

Classe `App\Repository\AnalysisRepository` com `Doctrine\DBAL\Connection`. Metodos:
- `insert(int $articleId, array $analysisData, string $model, string $promptVersion): int` -- insere na tabela `analyses` e retorna o ID
- `insertStrategies(int $analysisId, array $strategies): void` -- insere em batch na tabela `argumentative_strategies`
- `findByArticleId(int $articleId): ?array` -- verifica se ja existe analise

### 7. `src/Repository/ArticleRepository.php` -- Adicionar metodo

Adicionar ao repository existente:
- `findWithoutAnalysis(int $limit, ?int $sourceId = null): array` -- busca artigos que ainda nao tem analise (LEFT JOIN)

### 8. `src/Command/AnalyzeArticlesCommand.php` -- Comando CLI

Classe `App\Command\AnalyzeArticlesCommand` com flags:
- `--limit=N` (default: 10)
- `--article=ID`
- `--source=ID`
- `--dry-run`

Output no terminal:
```
[ai:analyze] Analise iniciada (limite: 10 artigos)
  [1/10] "Titulo do artigo..." OK (1247 tokens)
  [2/10] "Titulo do artigo..." OK (983 tokens)
  [3/10] "Titulo do artigo..." ERRO: rate limit, aguardando 30s...
  [3/10] "Titulo do artigo..." OK (1102 tokens)
  ...

[ai:analyze] Analise finalizada
  Artigos processados: 10
  Estratagemas detectados: 23
  Tokens consumidos: 11.432
  Erros:              1
```

### 9. `bin/console` -- Atualizar com novo comando

Adicionar `ai:analyze` ao match de comandos.

### 10. `config/container.php` -- Atualizar com novas definicoes

- `AiClientInterface` -> `AnthropicClient` (factory com `AI_API_KEY`, `AI_MODEL` do `.env`)
- `AnalysisRepository` -- autowired
- `ArticleAnalyzerService` -- autowired
- `PromptBuilder` -- autowired
- `AnalyzeArticlesCommand` -- autowired

### 11. `.env.example` -- Adicionar variaveis

```
AI_MAX_TOKENS=4096
AI_RATE_LIMIT_RPM=50
```

---

## Fluxo de Execucao

```
php bin/console ai:analyze --limit=10
         |
         v
[AnalyzeArticlesCommand]
  1. Parseia flags (--limit, --article, --source, --dry-run)
  2. Chama ArticleAnalyzerService::analyze(...)
         |
         v
[ArticleAnalyzerService::analyze()]
  1. Busca artigos sem analise via ArticleRepository::findWithoutAnalysis()
  2. Para cada artigo:
     |
     +---> try {
     |       a. PromptBuilder::buildSystemPrompt() -- prompt do sistema
     |       b. PromptBuilder::buildUserMessage(article) -- conteudo do artigo
     |       c. AiClientInterface::chat(system, user) -- chamada a API
     |       d. JSON decode da resposta
     |       e. Validar schema do JSON (campos obrigatorios presentes)
     |       f. AnalysisRepository::insert(...) -- persiste analise
     |       g. AnalysisRepository::insertStrategies(...) -- persiste estratagemas
     |       h. Imprime progresso no terminal
     |       i. Rate limiting: sleep se necessario
     |     }
     +---> catch (RateLimitException) {
     |       - Aguarda retry-after, tenta novamente (max 3x)
     |     }
     +---> catch (\Throwable $e) {
     |       - Loga erro, CONTINUA para proximo artigo
     |     }
     |
  3. Retorna metricas
```

---

## Decisoes Tecnicas

### 1. Resposta em JSON estruturado, nao markdown

O prompt deve instruir a IA a retornar JSON puro. Isso permite parsing automatico e persistencia direta nos campos JSONB do banco. Se a IA retornar JSON invalido, logar o erro com a resposta bruta e pular o artigo.

Estrategia de parsing:
1. Tentar `json_decode` direto
2. Se falhar, tentar extrair JSON de um bloco ```json ... ``` (LLMs as vezes envolvem em markdown)
3. Se falhar, logar erro com resposta bruta

### 2. Rate limiting

A API da Anthropic tem limites por minuto (RPM) e por tokens (TPM). Estrategia:
- Configurar `AI_RATE_LIMIT_RPM` no `.env` (default: 50)
- Calcular intervalo minimo entre requests: `60 / RPM` segundos
- Em caso de 429, respeitar header `retry-after` da resposta
- Max 3 retries por artigo antes de desistir

### 3. Versionamento de prompts

O campo `prompt_version` na tabela `analyses` permite rastrear qual versao do prompt gerou cada analise. Formato sugerido: `v1.0`, `v1.1`, etc. O `PromptBuilder` define a versao como constante.

Isso e critico para o principio de transparencia: se o prompt mudar, as analises antigas continuam rastreando qual prompt as gerou. Permite re-processar artigos com prompt novo se necessario.

### 4. Processamento em lote com limite

Default de 10 artigos por execucao para:
- Controlar custo (tokens consumidos)
- Evitar timeout do processo
- Permitir execucao incremental via cron

Para processar todos: rodar o comando multiplas vezes ou usar `--limit=100`.

### 5. Validacao do JSON da IA

A resposta da IA pode ser malformada. Validar que os campos obrigatorios existem:
- `rewritten_text` (string, nao vazio)
- `facts` (array)
- `opinions` (array)
- `simplified_terms` (array)
- `argumentative_strategies` (array)
- `transparency_log` (string)

Se faltar campo obrigatorio, logar warning e pular artigo (nao salvar analise parcial).

### 6. Custo e otimizacao

Estimativa de custo com Claude Sonnet:
- Input medio por artigo: ~1500 tokens (conteudo RSS) + ~800 tokens (system prompt) = ~2300 tokens
- Output medio: ~2000 tokens
- Com 406 artigos no banco: ~930k input + ~812k output tokens
- Custo estimado: depende do pricing atual, mas o --limit permite controlar

Otimizacoes possiveis (futuras):
- Cache de system prompt (mesmo para todos os artigos)
- Batch API da Anthropic (se disponivel) para desconto de 50%
- Filtrar artigos curtos/irrelevantes antes de enviar a IA

### 7. Conteudo do artigo como input

O campo `articles.content` pode conter HTML. O prompt deve instruir a IA a ignorar tags HTML ou, alternativamente, limpar com `strip_tags()` antes de enviar. Recomendacao: limpar com `strip_tags()` no `PromptBuilder` para reduzir tokens e evitar confusao.

Tambem enviar `articles.title` e `sources.name` + `sources.editorial_leaning` como contexto para a IA saber de qual fonte vem o artigo.

---

## Design do Prompt (Rascunho v1.0)

### System Prompt

```
Voce e o analista do Observatorio Civico, uma plataforma brasileira de leitura
critica de noticias politicas. Seu papel e analisar artigos jornalisticos e
produzir uma analise estruturada que ajude o cidadao a ler criticamente.

PRINCIPIOS:
- Voce e um mediador, nao um arbitro. Nao dita a verdade, apoia a leitura critica.
- Neutralidade operacional: criterios explicitos e auditaveis, nao neutralidade absoluta.
- Transparencia: justifique cada decisao analitica.
- Politicos sao representantes eleitos e funcionarios publicos pagos pela sociedade.
- Use linguagem simples, como se explicasse para alguem de 16 anos.

FRAMEWORK DE DETECCAO RETORICA (baseado em Schopenhauer - A Arte de Ter Razao):
Identifique estratagemas como: extensao indevida (generalizacao), homonimia
(mudanca de significado), desvio do tema (ignoratio elenchi), retorsio argumenti
(virar argumento contra acusador), apelo a autoridade, ad hominem, falsa
equivalencia, apelo a emocao, petitio principii (assumir o que deveria provar),
entre outros.

FORMATO DE RESPOSTA:
Responda EXCLUSIVAMENTE com um objeto JSON valido, sem markdown, sem texto antes
ou depois. Siga o schema abaixo estritamente.
```

### User Message Template

```
Analise o artigo abaixo.

FONTE: {source_name}
LINHA EDITORIAL DECLARADA: {editorial_leaning}
TITULO: {article_title}
DATA: {published_at}

CONTEUDO:
{article_content_stripped}

Retorne o JSON com: rewritten_text, facts, opinions, simplified_terms,
bias_indicators, argumentative_strategies, transparency_log.
```

---

## Criterios de Aceitacao

- [ ] `php bin/console ai:analyze --limit=5` processa 5 artigos e persiste analises no banco
- [ ] Tabela `analyses` populada com todos os campos JSONB preenchidos
- [ ] Tabela `argumentative_strategies` populada com estratagemas vinculados a analise
- [ ] `--dry-run` mostra o prompt sem chamar a API
- [ ] `--article=ID` processa apenas o artigo especificado
- [ ] Rodar duas vezes nao reprocessa artigos ja analisados
- [ ] Erros da API (429, 500, timeout) sao tratados com retry e logging
- [ ] JSON invalido da IA e logado com resposta bruta e artigo e pulado
- [ ] Campo `prompt_version` e `model_used` preenchidos em toda analise
- [ ] `transparency_log` preenchido em toda analise
- [ ] Rate limiting respeita o intervalo configurado no `.env`
- [ ] Resumo impresso no terminal com contagem e tokens consumidos

---

## Dependencias e Riscos

### Dependencias

1. Chave de API da Anthropic configurada no `.env` (`AI_API_KEY`)
2. Artigos ja coletados no banco (task 001 concluida)
3. `guzzlehttp/guzzle` ja instalado (usado para chamadas HTTP a API)

### Riscos

| # | Risco | Impacto | Mitigacao |
|---|-------|---------|-----------|
| 1 | IA retorna JSON invalido ou incompleto | Analise perdida | Parsing com fallback, logar resposta bruta, pular artigo |
| 2 | Rate limit da API (429) | Processamento interrompido | Retry com backoff exponencial, respeitar retry-after |
| 3 | Custo de tokens alto com 406+ artigos | Gasto financeiro | --limit para controlar, processar incrementalmente |
| 4 | Conteudo RSS curto (apenas lead) | Analise superficial | Aceitar por agora. Scraping full-text e task futura |
| 5 | Prompt instavel (resultados inconsistentes) | Qualidade variavel | Versionamento de prompt, poder re-processar |
| 6 | Artigos em ingles ou outro idioma | Analise incorreta | Prompt instrui a IA a analisar em portugues, ignorar artigos em outro idioma |
| 7 | Artigos muito longos excedem limite de tokens | Chamada falha | Truncar conteudo a ~3000 palavras antes de enviar |

---

## Ordem de Implementacao

1. `src/Service/AiClient/AiResponse.php` (value object, sem dependencias)
2. `src/Service/AiClient/AiClientInterface.php` (interface)
3. `src/Service/AiClient/AnthropicClient.php` (implementacao)
4. `src/Service/PromptBuilder.php` (construtor de prompts)
5. `src/Repository/AnalysisRepository.php` (persistencia)
6. Atualizar `src/Repository/ArticleRepository.php` (novo metodo)
7. `src/Service/ArticleAnalyzerService.php` (orquestrador)
8. `src/Command/AnalyzeArticlesCommand.php` (CLI)
9. Atualizar `config/container.php`
10. Atualizar `bin/console`
11. Atualizar `.env.example`
12. Testar: `php bin/console ai:analyze --article=1 --dry-run`
13. Testar: `php bin/console ai:analyze --limit=5`
