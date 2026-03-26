#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/TrackerRepository.php';
require __DIR__ . '/../src/RemoteLocatorClient.php';
require __DIR__ . '/../src/GoogleFindMyDeviceClient.php';
require __DIR__ . '/../src/PositionPoller.php';

$provider = getenv('LOCATOR_PROVIDER') ?: 'google_unofficial';
$client = match ($provider) {
    'generic' => new RemoteLocatorClient(),
    'google_unofficial' => new GoogleFindMyDeviceClient(),
    default => throw new RuntimeException('LOCATOR_PROVIDER invalide: ' . $provider),
};

$poller = new PositionPoller(
    $client,
    new TrackerRepository(db()),
);

try {
    $position = $poller->pollOnce();
    echo sprintf(
        "[%s] Position enregistrée: %.6f, %.6f\n",
        $position['captured_at'],
        $position['latitude'],
        $position['longitude'],
    );
} catch (Throwable $e) {
    fwrite(STDERR, 'Erreur: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
