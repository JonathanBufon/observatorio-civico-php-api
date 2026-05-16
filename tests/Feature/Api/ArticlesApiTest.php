<?php

namespace Tests\Feature\Api;

use App\Models\Analysis;
use App\Models\ArgumentativeStrategy;
use App\Models\Article;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_articles_can_be_filtered_by_analysis_presence(): void
    {
        $source = $this->createSource();
        $articleWithAnalysis = $this->createArticle($source, 'a-1', 'Camara vota projeto');
        $this->createAnalysis($articleWithAnalysis);
        $this->createArticle($source, 'a-2', 'Senado debate proposta');

        $this->getJson('/api/v1/articles?has_analysis=true')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Camara vota projeto')
            ->assertJsonPath('data.0.has_analysis', true);
    }

    public function test_article_detail_includes_source_and_analysis(): void
    {
        $source = $this->createSource();
        $article = $this->createArticle($source, 'a-1', 'Camara vota projeto');
        $analysis = $this->createAnalysis($article);

        ArgumentativeStrategy::create([
            'analysis_id' => $analysis->id,
            'strategy_name' => 'apelo a emocao',
            'who_uses' => 'veiculo',
            'excerpt' => 'trecho',
            'explanation' => 'explicacao',
            'severity' => 'medium',
        ]);

        $this->getJson("/api/v1/articles/{$article->id}")
            ->assertOk()
            ->assertJsonPath('data.source.name', 'Fonte Teste')
            ->assertJsonPath('data.analysis.strategies.0.strategy_name', 'apelo a emocao');
    }

    public function test_article_analysis_returns_404_when_missing(): void
    {
        $source = $this->createSource();
        $article = $this->createArticle($source, 'a-1', 'Sem analise');

        $this->getJson("/api/v1/articles/{$article->id}/analysis")
            ->assertNotFound()
            ->assertJsonPath('message', 'Analise ainda nao disponivel para este artigo.');
    }

    public function test_invalid_article_filter_returns_422(): void
    {
        $this->getJson('/api/v1/articles?per_page=101')
            ->assertUnprocessable();
    }

    private function createSource(): Source
    {
        return Source::create([
            'name' => 'Fonte Teste',
            'url' => 'https://example.com/rss',
            'editorial_leaning' => 'centro',
            'is_active' => true,
        ]);
    }

    private function createArticle(Source $source, string $externalId, string $title): Article
    {
        return Article::create([
            'source_id' => $source->id,
            'external_id' => $externalId,
            'title' => $title,
            'original_url' => "https://example.com/{$externalId}",
            'content' => 'Conteudo',
            'published_at' => now(),
            'fetched_at' => now(),
        ]);
    }

    private function createAnalysis(Article $article): Analysis
    {
        return Analysis::create([
            'article_id' => $article->id,
            'rewritten_text' => 'Texto reescrito',
            'fact_fragments' => [['excerpt' => 'fato', 'explanation' => 'verificavel']],
            'opinion_fragments' => [],
            'simplified_terms' => [],
            'bias_indicators' => [],
            'transparency_log' => 'criterios',
            'model_used' => 'gpt-test',
            'prompt_version' => 'v1.0',
            'processed_at' => now(),
        ]);
    }
}
