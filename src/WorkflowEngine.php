<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class WorkflowEngine
{
    public const STATUSES = ['recu', 'pris_en_compte', 'en_cours', 'terminee'];

    /** @var array<string, string[]> */
    private array $transitions = [
        'recu' => ['pris_en_compte', 'en_cours'],
        'pris_en_compte' => ['en_cours', 'terminee'],
        'en_cours' => ['terminee', 'pris_en_compte'],
        'terminee' => [],
    ];

    /**
     * Autorise les transitions standard + ad-hoc pour la flexibilité demandée.
     */
    public function assertTransitionAllowed(string $from, string $to, bool $forceAdHoc): void
    {
        if (!in_array($to, self::STATUSES, true)) {
            throw new RuntimeException('Statut cible inconnu.');
        }

        if ($forceAdHoc) {
            return;
        }

        if (!isset($this->transitions[$from]) || !in_array($to, $this->transitions[$from], true)) {
            throw new RuntimeException('Transition non autorisée sans mode ad-hoc.');
        }
    }

    /** @return string[] */
    public function nextStatuses(string $current): array
    {
        return array_values(array_unique(array_merge($this->transitions[$current] ?? [], self::STATUSES)));
    }
}
