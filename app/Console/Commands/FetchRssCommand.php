<?php

namespace App\Console\Commands;

use App\Services\RssFetcherService;
use Illuminate\Console\Command;

class FetchRssCommand extends Command
{
    protected $signature = 'rss:fetch
                            {--source= : ID da fonte especifica para coletar}
                            {--dry-run : Simula a coleta sem persistir dados}';

    protected $description = 'Coleta noticias de todas as fontes RSS ativas';

    public function handle(RssFetcherService $service): int
    {
        $sourceId = $this->option('source') ? (int) $this->option('source') : null;
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('[rss:fetch] Modo dry-run ativado (nenhum dado sera persistido)');
            $this->newLine();
        }

        $metrics = $service->fetch($sourceId, $dryRun);

        $this->newLine();
        $this->info('[rss:fetch] Coleta finalizada');
        $this->line("  Fontes processadas: {$metrics['sources_processed']}/{$metrics['sources_total']}");
        $this->line("  Artigos novos:      {$metrics['articles_new']}");
        $this->line("  Artigos duplicados: {$metrics['articles_skipped']}");

        $errorLine = "  Erros:              {$metrics['errors']}";
        if ($metrics['errors'] > 0) {
            $errorLine .= ' (ver storage/logs/laravel.log para detalhes)';
        }
        $this->line($errorLine);

        return $metrics['sources_processed'] > 0 || $metrics['sources_total'] === 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
