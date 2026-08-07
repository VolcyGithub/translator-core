<?php

namespace Volcy\Translator\Drivers;

class GroqTranslationDriver
{
    public function __construct(protected array $config = [])
    {
    }

    public function translate(string $text, string $targetLocale, ?string $sourceLocale = null): string
    {
        $key = $this->config['key'] ?? null;

        if (! is_string($key) || trim($key) === '') {
            return $text;
        }

        $model = $this->config['model'] ?? 'llama-3.1-8b-instant';

        $response = $this->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You translate interface text. Return only the translated text and preserve markup-free plain text.',
                ],
                [
                    'role' => 'user',
                    'content' => sprintf(
                        'Translate this text to %s%s: %s',
                        $targetLocale,
                        $sourceLocale ? ' from ' . $sourceLocale : '',
                        $text
                    ),
                ],
            ],
            'temperature' => 0.1,
        ], ['Authorization: Bearer ' . $key]);

        if ($response === null) {
            return $text;
        }

        $translated = $response['choices'][0]['message']['content'] ?? null;

        if (! is_string($translated) || trim($translated) === '') {
            return $text;
        }

        return trim($translated);
    }

    protected function post(string $url, array $payload, array $headers = []): ?array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '' || $body === false || $status < 200 || $status >= 300) {
            return null;
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }
}
