<?php

namespace App\Services;

use App\Models\Analysis;
use App\Models\Article;
use App\Models\ArgumentativeStrategy;
use App\Services\AiClient\AiClientInterface;
use Illuminate\Support\Facades\Log;

class ArticleAnalyzerService
{
    private const REQUIRED_FIELDS = [
        'rewritten_text',
        'facts',
        'opinions',
        'simplified_terms',
        'argumentative_strategies',
        'transparency_log',
    ];

    public function __construct(
        private readonly AiClientInterface $aiClient,
        private readonly PromptBuilder $promptBuilder,
    ) {}

    /**
     * @return array{processed: int, errors: int, strategies_count: int, total_tokens: int, results: list<array{article_id: int, title: string, status: string, tokens: int, error?: string}>}
     */
    public function analyze(
        int $limit = 10,
        ?int $articleId = null,
        ?int $sourceId = null,
        bool $dryRun = false,
        ?callable $onProgress = null,
    ): array {
        $articles = $this->fetchArticles($limit, $articleId, $sourceId);

        $metrics = [
            'processed' => 0,
            'errors' => 0,
            'strategies_count' => 0,
            'total_tokens' => 0,
            'results' => [],
        ];

        $total = $articles->count();
        $rpmInterval = $this->getRpmInterval();

        foreach ($articles as $index => $article) {
            $result = [
                'article_id' => $article->id,
                'title' => $article->title,
                'status' => 'pending',
                'tokens' => 0,
            ];

            try {
                $systemPrompt = $this->promptBuilder->buildSystemPrompt();
                $userMessage = $this->promptBuilder->buildUserMessage($article);

                if ($dryRun) {
                    $result['status'] = 'dry-run';
                    $result['prompt'] = $userMessage;
                    $metrics['results'][] = $result;
                    if ($onProgress) {
                        $onProgress($index + 1, $total, $result);
                    }
                    continue;
                }

                $aiResponse = $this->aiClient->chat($systemPrompt, $userMessage);
                $result['tokens'] = $aiResponse->totalTokens();
                $metrics['total_tokens'] += $result['tokens'];

                $parsed = $this->parseResponse($aiResponse->content);
                $this->validateSchema($parsed);

                $analysis = $this->persistAnalysis($article, $parsed, $aiResponse->model);
                $strategiesCount = $this->persistStrategies($analysis, $parsed['argumentative_strategies'] ?? []);

                $metrics['strategies_count'] += $strategiesCount;
                $metrics['processed']++;
                $result['status'] = 'ok';

                if ($onProgress) {
                    $onProgress($index + 1, $total, $result);
                }

                if ($index < $total - 1 && $rpmInterval > 0) {
                    usleep((int) ($rpmInterval * 1_000_000));
                }
            } catch (\Throwable $e) {
                $metrics['errors']++;
                $result['status'] = 'erro';
                $result['error'] = $e->getMessage();
                Log::error("Erro ao analisar artigo ID {$article->id}: {$e->getMessage()}", [
                    'article_id' => $article->id,
                    'exception' => $e,
                ]);

                if ($onProgress) {
                    $onProgress($index + 1, $total, $result);
                }
            }

            $metrics['results'][] = $result;
        }

        return $metrics;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Article>
     */
    private function fetchArticles(int $limit, ?int $articleId, ?int $sourceId): \Illuminate\Database\Eloquent\Collection
    {
        $query = Article::query()
            ->with('source')
            ->whereDoesntHave('analysis');

        if ($articleId !== null) {
            $query->where('id', $articleId);
        }

        if ($sourceId !== null) {
            $query->where('source_id', $sourceId);
        }

        return $query->orderBy('id')->limit($limit)->get();
    }

    private function parseResponse(string $content): array
    {
        $data = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }

        // Fallback: tentar extrair JSON de bloco markdown ```json ... ```
        if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/', $content, $matches)) {
            $data = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }

        Log::error('Resposta da IA nao e JSON valido', ['raw_response' => mb_substr($content, 0, 2000)]);
        throw new \RuntimeException('Resposta da IA nao e JSON valido');
    }

    private function validateSchema(array $data): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                throw new \RuntimeException("Campo obrigatorio ausente na resposta da IA: {$field}");
            }
        }

        if (!is_string($data['rewritten_text']) || $data['rewritten_text'] === '') {
            throw new \RuntimeException('Campo rewritten_text deve ser string nao vazia');
        }

        if (!is_string($data['transparency_log'])) {
            throw new \RuntimeException('Campo transparency_log deve ser string');
        }

        foreach (['facts', 'opinions', 'simplified_terms', 'argumentative_strategies'] as $field) {
            if (!is_array($data[$field])) {
                throw new \RuntimeException("Campo {$field} deve ser array");
            }
        }
    }

    private function persistAnalysis(Article $article, array $data, string $model): Analysis
    {
        return Analysis::create([
            'article_id' => $article->id,
            'rewritten_text' => $data['rewritten_text'],
            'fact_fragments' => $data['facts'] ?? [],
            'opinion_fragments' => $data['opinions'] ?? [],
            'simplified_terms' => $data['simplified_terms'] ?? [],
            'bias_indicators' => $data['bias_indicators'] ?? [],
            'transparency_log' => $data['transparency_log'] ?? '',
            'model_used' => $model,
            'prompt_version' => PromptBuilder::PROMPT_VERSION,
            'processed_at' => now(),
        ]);
    }

    private function persistStrategies(Analysis $analysis, array $strategies): int
    {
        $count = 0;

        foreach ($strategies as $strategy) {
            ArgumentativeStrategy::create([
                'analysis_id' => $analysis->id,
                'strategy_name' => $strategy['strategy_name'] ?? 'desconhecido',
                'who_uses' => $strategy['who_uses'] ?? null,
                'excerpt' => $strategy['excerpt'] ?? '',
                'explanation' => $strategy['explanation'] ?? '',
                'severity' => $strategy['severity'] ?? 'medium',
            ]);
            $count++;
        }

        return $count;
    }

    private function getRpmInterval(): float
    {
        $rpm = (int) config('services.ai.rate_limit_rpm', 50);
        return $rpm > 0 ? 60.0 / $rpm : 0;
    }
}
