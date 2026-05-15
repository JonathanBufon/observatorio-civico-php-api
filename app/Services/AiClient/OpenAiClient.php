<?php

namespace App\Services\AiClient;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiClient implements AiClientInterface
{
    private const MAX_RETRIES = 3;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $defaultModel,
        private readonly int $maxTokens,
    ) {}

    public function chat(string $systemPrompt, string $userMessage, ?string $model = null): AiResponse
    {
        $model = $model ?? $this->defaultModel;

        $payload = [
            'model' => $model,
            'max_tokens' => $this->maxTokens,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
        ];

        $response = $this->requestWithRetry($payload);

        return new AiResponse(
            content: $response['choices'][0]['message']['content'],
            model: $response['model'],
            inputTokens: $response['usage']['prompt_tokens'] ?? 0,
            outputTokens: $response['usage']['completion_tokens'] ?? 0,
        );
    }

    private function requestWithRetry(array $payload): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $response = Http::timeout(120)
                    ->connectTimeout(10)
                    ->withHeaders([
                        'Authorization' => "Bearer {$this->apiKey}",
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.openai.com/v1/chat/completions', $payload);

                if ($response->status() === 429) {
                    $retryAfter = (int) ($response->header('retry-after') ?: 30);
                    Log::warning("OpenAI rate limit (429), tentativa {$attempt}/{self::MAX_RETRIES}, aguardando {$retryAfter}s");
                    sleep($retryAfter);
                    continue;
                }

                $response->throw();

                return $response->json();
            } catch (\Illuminate\Http\Client\RequestException $e) {
                $lastException = $e;
                $status = $e->response?->status();

                if ($status === 429) {
                    $retryAfter = (int) ($e->response->header('retry-after') ?: 30);
                    Log::warning("OpenAI rate limit (429), tentativa {$attempt}/{self::MAX_RETRIES}, aguardando {$retryAfter}s");
                    sleep($retryAfter);
                    continue;
                }

                Log::error("OpenAI API erro (HTTP {$status}), tentativa {$attempt}/{self::MAX_RETRIES}: {$e->getMessage()}");

                if ($attempt < self::MAX_RETRIES && $status !== null && $status >= 500) {
                    sleep(5 * $attempt);
                    continue;
                }

                throw $e;
            }
        }

        throw $lastException ?? new \RuntimeException('OpenAI API: max retries excedido');
    }
}
