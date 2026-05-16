<?php

namespace Tests\Feature\Api;

use App\Models\Analysis;
use App\Models\ArgumentativeStrategy;
use App\Models\Article;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalysesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyses_can_be_listed_with_article_source_and_strategies(): void
    {
        $source = Source::create([
            'name' => 'Fonte Teste',
            'url' => 'https://example.com/rss',
            'is_active' => true,
        ]);

        $article = Article::create([
            'source_id' => $source->id,
            'external_id' => 'a-1',
            'title' => 'Titulo',
            'original_url' => 'https://example.com/a-1',
            'content' => 'Conteudo',
            'published_at' => now(),
            'fetched_at' => now(),
        ]);

        $analysis = Analysis::create([
            'article_id' => $article->id,
            'rewritten_text' => 'Texto reescrito',
            'fact_fragments' => [],
            'opinion_fragments' => [],
            'simplified_terms' => [],
            'bias_indicators' => [],
            'transparency_log' => 'criterios',
            'model_used' => 'gpt-test',
            'prompt_version' => 'v1.0',
            'processed_at' => now(),
        ]);

        ArgumentativeStrategy::create([
            'analysis_id' => $analysis->id,
            'strategy_name' => 'falsa equivalencia',
            'excerpt' => 'trecho',
            'explanation' => 'explicacao',
            'severity' => 'high',
        ]);

        $this->getJson("/api/v1/analyses?source_id={$source->id}&model_used=gpt-test")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.article.source.name', 'Fonte Teste')
            ->assertJsonPath('data.0.strategies.0.severity', 'high');
    }
}
