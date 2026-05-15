<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Source;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laminas\Feed\Reader\Reader;

class RssFetcherService
{
    /**
     * @return array{sources_processed: int, sources_total: int, articles_new: int, articles_skipped: int, errors: int}
     */
    public function fetch(?int $sourceId = null, bool $dryRun = false): array
    {
        $sources = $sourceId !== null
            ? Source::where('id', $sourceId)->where('is_active', true)->get()
            : Source::where('is_active', true)->orderBy('id')->get();

        $metrics = [
            'sources_processed' => 0,
            'sources_total' => $sources->count(),
            'articles_new' => 0,
            'articles_skipped' => 0,
            'errors' => 0,
        ];

        if ($metrics['sources_total'] === 0) {
            Log::warning('Nenhuma fonte ativa encontrada para coleta');
            return $metrics;
        }

        Log::info("Iniciando coleta RSS de {$metrics['sources_total']} fonte(s)");

        foreach ($sources as $source) {
            try {
                $result = $this->fetchSource($source, $dryRun);
                $metrics['articles_new'] += $result['new'];
                $metrics['articles_skipped'] += $result['skipped'];
                $metrics['sources_processed']++;

                Log::info("Fonte '{$source->name}' processada: {$result['new']} novos, {$result['skipped']} duplicados");
            } catch (\Throwable $e) {
                $metrics['errors']++;
                Log::error("Erro ao processar fonte '{$source->name}' (ID {$source->id}): {$e->getMessage()}");
            }
        }

        Log::info("Coleta finalizada: {$metrics['sources_processed']}/{$metrics['sources_total']} fontes, {$metrics['articles_new']} novos, {$metrics['errors']} erros");

        return $metrics;
    }

    /**
     * @return array{new: int, skipped: int}
     */
    private function fetchSource(Source $source, bool $dryRun): array
    {
        $response = Http::timeout(15)
            ->connectTimeout(10)
            ->withHeaders([
                'User-Agent' => 'ObservatorioCivico/1.0',
                'Accept' => 'application/rss+xml, application/xml, text/xml, */*',
            ])
            ->get($source->url);

        $response->throw();

        $body = $this->normalizeEncoding($response->body(), $response->header('Content-Type') ?? '');
        $feed = Reader::importString($body);

        $new = 0;
        $skipped = 0;

        foreach ($feed as $entry) {
            $externalId = $this->resolveExternalId($entry, $source);

            if ($externalId === null) {
                Log::warning("Entry sem identificador na fonte '{$source->name}', pulando");
                continue;
            }

            $title = $entry->getTitle();
            $link = $entry->getLink();

            if ($title === null || $link === null) {
                Log::warning("Entry incompleto na fonte '{$source->name}', pulando");
                continue;
            }

            $content = $entry->getContent() ?? $entry->getDescription() ?? '';
            $publishedAt = $this->resolvePublishedAt($entry);

            if ($dryRun) {
                Log::debug("[DRY-RUN] Artigo: {$title}");
                $new++;
                continue;
            }

            $inserted = $this->insertIfNotExists(
                $source->id, $externalId, $title, $link, $content, $publishedAt
            );

            $inserted ? $new++ : $skipped++;
        }

        if (!$dryRun) {
            $source->update(['last_fetched_at' => now()]);
        }

        return ['new' => $new, 'skipped' => $skipped];
    }

    private function insertIfNotExists(
        int $sourceId,
        string $externalId,
        string $title,
        string $originalUrl,
        string $content,
        ?\DateTimeImmutable $publishedAt,
    ): bool {
        $result = DB::selectOne(
            <<<'SQL'
                INSERT INTO articles (source_id, external_id, title, original_url, content, published_at, fetched_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON CONFLICT (source_id, external_id) DO NOTHING
                RETURNING id
            SQL,
            [$sourceId, $externalId, $title, $originalUrl, $content, $publishedAt?->format('Y-m-d H:i:sP')]
        );

        return $result !== null;
    }

    private function resolveExternalId(mixed $entry, Source $source): ?string
    {
        $id = $entry->getId();
        if ($id !== null && $id !== '') {
            return $id;
        }

        $link = $entry->getLink();
        if ($link !== null && $link !== '') {
            return $link;
        }

        $title = $entry->getTitle();
        if ($title !== null && $title !== '') {
            return hash('sha256', $title . '|' . $source->url);
        }

        return null;
    }

    private function resolvePublishedAt(mixed $entry): ?\DateTimeImmutable
    {
        $date = $entry->getDateModified() ?? $entry->getDateCreated();

        if ($date instanceof \DateTime) {
            return \DateTimeImmutable::createFromMutable($date);
        }

        if ($date instanceof \DateTimeImmutable) {
            return $date;
        }

        return null;
    }

    private function normalizeEncoding(string $body, string $contentType): string
    {
        if (preg_match('/charset=([^\s;]+)/i', $contentType, $matches)) {
            $charset = strtoupper(trim($matches[1], '"\''));
            if ($charset !== 'UTF-8' && $charset !== '') {
                $converted = mb_convert_encoding($body, 'UTF-8', $charset);
                if ($converted !== false) {
                    return $converted;
                }
            }
        }

        return $body;
    }
}
