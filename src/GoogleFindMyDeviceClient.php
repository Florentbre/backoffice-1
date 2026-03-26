<?php

declare(strict_types=1);

/**
 * Connecteur non officiel Google Find My Device.
 *
 * L'utilisateur doit fournir les paramètres réseau (URL, méthode, headers, body)
 * observés depuis son propre compte Google, car il n'existe pas d'API publique stable.
 */
final class GoogleFindMyDeviceClient
{
    public function fetch(): array
    {
        $url = getenv('GOOGLE_FMD_URL') ?: 'https://android.googleapis.com/nova/nbe_list_devices';
        $method = strtoupper(getenv('GOOGLE_FMD_METHOD') ?: 'POST');
        $auth = getenv('GOOGLE_FMD_AUTH_BEARER') ?: '';
        $cookie = getenv('GOOGLE_FMD_COOKIE') ?: '';
        $body = getenv('GOOGLE_FMD_BODY_JSON') ?: '{}';

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0',
        ];

        if ($auth !== '') {
            $headers[] = 'Authorization: Bearer ' . $auth;
        }

        if ($cookie !== '') {
            $headers[] = 'Cookie: ' . $cookie;
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => in_array($method, ['POST', 'PUT', 'PATCH'], true) ? $body : '',
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new RuntimeException('Échec d’appel Google Find My Device.');
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Réponse Google non JSON ou invalide.');
        }

        return $decoded;
    }
}
