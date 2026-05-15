<?php

namespace App\Console\Commands;

use App\Services\ArticleAnalyzerService;
use Illuminate\Console\Command;

class AnalyzeArticlesCommand extends Command
{
    protected $signature = 'ai:analyze
                            {--limit=10 : Numero maximo de artigos a processar}
                            {--article= : ID de um artigo especifico}
                            {--source= : ID da fonte para filtrar artigos}
                            {--dry-run : Mostra o prompt sem chamar a API}';

    protected $description = 'Analisa artigos coletados usando IA (OpenAI)';

    public function handle(ArticleAnalyzerService $service): int
    {
        $limit = (int) $this->option('limit');
        $articleId = $this->option('article') ? (int) $this->option('article') : null;
        $sourceId = $this->option('source') ? (int) $this->option('source') : null;
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('[ai:analyze] Modo dry-run (prompt sera exibido, API nao sera chamada)');
            $this->newLine();
        }

        $label = $articleId ? "artigo #{$articleId}" : "{$limit} artigos";
        $this->info("[ai:analyze] Analise iniciada ({$label})");
        $this->newLine();

        $onProgress = function (int $current, int $total, array $result) use ($dryRun) {
            $title = mb_substr($result['title'], 0, 50);

            if ($dryRun) {
                $this->line("  [{$current}/{$total}] \"{$title}\" [DRY-RUN]");
                if (!empty($result['prompt'])) {
                    $this->newLine();
                    $this->line($result['prompt']);
                    $this->newLine();
                }
                return;
            }

            $status = match ($result['status']) {
                'ok' => "<fg=green>OK</> ({$result['tokens']} tokens)",
                'erro' => "<fg=red>ERRO</>: {$result['error']}",
                default => $result['status'],
            };

            $this->line("  [{$current}/{$total}] \"{$title}\" {$status}");
        };

        $metrics = $service->analyze(
            limit: $limit,
            articleId: $articleId,
            sourceId: $sourceId,
            dryRun: $dryRun,
            onProgress: $onProgress,
        );

        $this->newLine();
        $this->info('[ai:analyze] Analise finalizada');
        $this->line("  Artigos processados: {$metrics['processed']}");
        $this->line("  Estratagemas detectados: {$metrics['strategies_count']}");
        $this->line("  Tokens consumidos: " . number_format($metrics['total_tokens'], 0, ',', '.'));

        $errorLine = "  Erros:              {$metrics['errors']}";
        if ($metrics['errors'] > 0) {
            $errorLine .= ' (ver storage/logs/laravel.log para detalhes)';
        }
        $this->line($errorLine);

        if ($dryRun) {
            return self::SUCCESS;
        }

        return $metrics['processed'] > 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
