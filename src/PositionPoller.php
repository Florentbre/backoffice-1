<?php

declare(strict_types=1);

final class PositionPoller
{
    public function __construct(
        private object $client,
        private TrackerRepository $repository,
    ) {
    }

    public function pollOnce(): array
    {
        $payload = $this->client->fetch();
        [$lat, $lng] = $this->extractLatLng($payload);

        $position = [
            'latitude' => $lat,
            'longitude' => $lng,
            'accuracy' => $this->extractAccuracy($payload),
            'source' => 'google-find-my-device-compatible',
            'captured_at' => gmdate('c'),
            'raw_payload' => $payload,
        ];

        $this->repository->insert($position);

        return $position;
    }

    /** @return array{0: float, 1: float} */
    private function extractLatLng(array $payload): array
    {
        $lat = $this->findNumericByKeys($payload, ['latitude', 'lat']);
        $lng = $this->findNumericByKeys($payload, ['longitude', 'lng', 'lon']);

        if ($lat === null || $lng === null) {
            throw new RuntimeException('Latitude/longitude introuvables dans la réponse distante.');
        }

        return [$lat, $lng];
    }

    private function extractAccuracy(array $payload): ?float
    {
        return $this->findNumericByKeys($payload, ['accuracy', 'horizontalAccuracy', 'radius']);
    }

    private function findNumericByKeys(array $node, array $keys): ?float
    {
        foreach ($node as $key => $value) {
            if (in_array((string) $key, $keys, true) && is_numeric($value)) {
                return (float) $value;
            }

            if (is_array($value)) {
                $found = $this->findNumericByKeys($value, $keys);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }
}
