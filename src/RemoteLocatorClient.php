<?php

declare(strict_types=1);

final class RemoteLocatorClient
{
    public function fetch(): array
    {
        $endpoint = getenv('LOCATOR_ENDPOINT_URL') ?: '';
        $token = getenv('LOCATOR_API_TOKEN') ?: '';

        if ($endpoint === '') {
            throw new RuntimeException('LOCATOR_ENDPOINT_URL non défini.');
        }

        $headers = [
            'Accept: application/json',
        ];

        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 15,
            ],
        ]);

        $response = @file_get_contents($endpoint, false, $context);
        if ($response === false) {
            throw new RuntimeException('Impossible de récupérer la position distante.');
        }

        $json = json_decode($response, true);
        if (!is_array($json)) {
            throw new RuntimeException('Réponse JSON invalide.');
        }

        return $json;
    }
}
