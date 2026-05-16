<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourcesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sources_can_be_listed_with_filters(): void
    {
        $activeSource = Source::create([
            'name' => 'Fonte Ativa',
            'url' => 'https://example.com/rss',
            'editorial_leaning' => 'centro',
            'is_active' => true,
        ]);

        Source::create([
            'name' => 'Fonte Inativa',
            'url' => 'https://example.com/inativa/rss',
            'editorial_leaning' => 'direita',
            'is_active' => false,
        ]);

        Article::create([
            'source_id' => $activeSource->id,
            'external_id' => 'a-1',
            'title' => 'Noticia politica',
            'original_url' => 'https://example.com/a-1',
            'content' => 'Conteudo',
            'published_at' => now(),
            'fetched_at' => now(),
        ]);

        $this->getJson('/api/v1/sources?active=true&editorial_leaning=centro')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Fonte Ativa')
            ->assertJsonPath('data.0.articles_count', 1);
    }

    public function test_source_articles_are_paginated(): void
    {
        $source = Source::create([
            'name' => 'Fonte',
            'url' => 'https://example.com/rss',
            'is_active' => true,
        ]);

        Article::create([
            'source_id' => $source->id,
            'external_id' => 'a-1',
            'title' => 'Congresso aprova pauta',
            'original_url' => 'https://example.com/a-1',
            'content' => 'Conteudo',
            'published_at' => now(),
            'fetched_at' => now(),
        ]);

        $this->getJson("/api/v1/sources/{$source->id}/articles?q=Congresso&per_page=5")
            ->assertOk()
            ->assertJsonPath('data.0.source.name', 'Fonte')
            ->assertJsonPath('meta.per_page', 5);
    }
}
