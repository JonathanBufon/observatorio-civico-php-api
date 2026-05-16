<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use App\Models\Event;
use App\Models\Source;
use App\Models\SourceComparison;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_can_be_listed_and_shown_with_articles(): void
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

        $event = Event::create([
            'title' => 'Evento politico',
            'summary' => 'Resumo',
            'category' => 'congresso',
            'detected_at' => now(),
        ]);
        $event->articles()->attach($article->id, ['similarity_score' => 0.950]);

        $this->getJson('/api/v1/events?category=congresso')
            ->assertOk()
            ->assertJsonPath('data.0.articles_count', 1);

        $this->getJson("/api/v1/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('data.articles.0.source.name', 'Fonte Teste');
    }

    public function test_event_comparison_returns_resource_or_404(): void
    {
        $event = Event::create([
            'title' => 'Evento politico',
            'summary' => 'Resumo',
            'category' => 'congresso',
            'detected_at' => now(),
        ]);

        $this->getJson("/api/v1/events/{$event->id}/comparison")
            ->assertNotFound()
            ->assertJsonPath('message', 'Comparacao ainda nao disponivel para este evento.');

        SourceComparison::create([
            'event_id' => $event->id,
            'common_facts' => [['text' => 'fato comum']],
            'divergent_frames' => [],
            'missing_aspects' => [],
            'synthesis' => 'Sintese',
            'model_used' => 'gpt-test',
            'prompt_version' => 'v1.0',
        ]);

        $this->getJson("/api/v1/events/{$event->id}/comparison")
            ->assertOk()
            ->assertJsonPath('data.synthesis', 'Sintese');
    }
}
