<?php

namespace App\Services\AiClient;

interface AiClientInterface
{
    public function chat(string $systemPrompt, string $userMessage, ?string $model = null): AiResponse;
}
