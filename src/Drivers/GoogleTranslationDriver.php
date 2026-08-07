<?php

namespace Volcy\Translator\Drivers;

class GoogleTranslationDriver
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

        $query = http_build_query([
            'q' => $text,
            'target' => $targetLocale,
            'source' => $sourceLocale,
            'format' => 'text',
            'key' => $key,
        ]);

        $response = $this->post('https://translation.googleapis.com/language/translate/v2?' . $query);

        if ($response === null) {
            return $text;
        }

        $translated = $response['data']['translations'][0]['translatedText'] ?? null;

        if (! is_string($translated) || trim($translated) === '') {
            return $text;
        }

        return trim($translated);
    }

    protected function post(string $url): ?array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '',
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
