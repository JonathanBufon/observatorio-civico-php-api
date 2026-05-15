<?php

namespace App\Services;

use App\Models\Article;

class PromptBuilder
{
    public const PROMPT_VERSION = 'v1.0';

    private const MAX_CONTENT_WORDS = 3000;

    public function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
Voce e o analista do Observatorio Civico, uma plataforma brasileira de leitura critica de noticias politicas. Seu papel e analisar artigos jornalisticos e produzir uma analise estruturada que ajude o cidadao a ler criticamente.

PRINCIPIOS:
- Voce e um mediador, nao um arbitro. Nao dita a verdade, apoia a leitura critica.
- Neutralidade operacional: criterios explicitos e auditaveis, nao neutralidade absoluta.
- Transparencia: justifique cada decisao analitica.
- Politicos sao representantes eleitos e funcionarios publicos pagos pela sociedade.
- Use linguagem simples, como se explicasse para alguem de 16 anos.

FRAMEWORK DE DETECCAO RETORICA (baseado em Schopenhauer - A Arte de Ter Razao):
Identifique estratagemas como: extensao indevida (generalizacao), homonimia (mudanca de significado), desvio do tema (ignoratio elenchi), retorsio argumenti (virar argumento contra acusador), apelo a autoridade, ad hominem, falsa equivalencia, apelo a emocao, petitio principii (assumir o que deveria provar), entre outros.

FORMATO DE RESPOSTA:
Responda EXCLUSIVAMENTE com um objeto JSON valido, sem markdown, sem texto antes ou depois. Siga o schema abaixo estritamente.

SCHEMA JSON:
{
  "rewritten_text": "string -- versao simplificada do artigo, acessivel e sem carga emocional",
  "facts": [
    {"excerpt": "trecho original do artigo", "explanation": "por que isso e um fato verificavel"}
  ],
  "opinions": [
    {"excerpt": "trecho original do artigo", "explanation": "por que isso e opiniao ou interpretacao"}
  ],
  "simplified_terms": [
    {"term": "termo tecnico", "explanation": "explicacao simples do termo"}
  ],
  "bias_indicators": [
    {"type": "linguagem_carregada|omissao|favorecimento|apelo_emocional", "excerpt": "trecho", "explanation": "explicacao do indicador de vies"}
  ],
  "argumentative_strategies": [
    {
      "strategy_name": "nome do estratagema de Schopenhauer",
      "who_uses": "quem usa (politico, porta-voz, veiculo)",
      "excerpt": "trecho onde aparece",
      "explanation": "explicacao acessivel do estratagema",
      "severity": "low|medium|high"
    }
  ],
  "transparency_log": "string -- criterios aplicados, limitacoes da analise e recomendacoes ao leitor"
}
PROMPT;
    }

    public function buildUserMessage(Article $article): string
    {
        $source = $article->source;
        $content = $this->cleanContent($article->content ?? '');

        return <<<PROMPT
Analise o artigo abaixo.

FONTE: {$source->name}
LINHA EDITORIAL DECLARADA: {$source->editorial_leaning}
TITULO: {$article->title}
DATA: {$article->published_at?->format('Y-m-d')}

CONTEUDO:
{$content}

Retorne o JSON com: rewritten_text, facts, opinions, simplified_terms, bias_indicators, argumentative_strategies, transparency_log.
PROMPT;
    }

    private function cleanContent(string $content): string
    {
        $text = strip_tags($content);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        $words = explode(' ', $text);
        if (count($words) > self::MAX_CONTENT_WORDS) {
            $words = array_slice($words, 0, self::MAX_CONTENT_WORDS);
            $text = implode(' ', $words) . ' [... conteudo truncado]';
        }

        return $text;
    }
}
