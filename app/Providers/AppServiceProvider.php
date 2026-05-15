<?php

namespace App\Providers;

use App\Services\AiClient\AiClientInterface;
use App\Services\AiClient\OpenAiClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiClientInterface::class, function ($app) {
            return new OpenAiClient(
                apiKey: config('services.ai.api_key'),
                defaultModel: config('services.ai.model', 'gpt-4o'),
                maxTokens: (int) config('services.ai.max_tokens', 4096),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
