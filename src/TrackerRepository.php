<?php

declare(strict_types=1);

final class TrackerRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function insert(array $position): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO positions (latitude, longitude, accuracy, source, captured_at, raw_payload)
             VALUES (:lat, :lng, :accuracy, :source, :capturedAt, :rawPayload)'
        );

        $stmt->execute([
            ':lat' => $position['latitude'],
            ':lng' => $position['longitude'],
            ':accuracy' => $position['accuracy'],
            ':source' => $position['source'],
            ':capturedAt' => $position['captured_at'],
            ':rawPayload' => json_encode($position['raw_payload'], JSON_UNESCAPED_UNICODE),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function between(?string $from, ?string $to): array
    {
        $clauses = [];
        $params = [];

        if ($from) {
            $clauses[] = 'captured_at >= :from';
            $params[':from'] = $from;
        }

        if ($to) {
            $clauses[] = 'captured_at <= :to';
            $params[':to'] = $to;
        }

        $sql = 'SELECT id, latitude, longitude, accuracy, source, captured_at FROM positions';

        if ($clauses !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        $sql .= ' ORDER BY captured_at ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
