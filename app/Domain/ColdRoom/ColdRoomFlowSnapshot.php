<?php

namespace App\Domain\ColdRoom;

/**
 * Réponses structurées aux questions de traçabilité d'un flux chambre froide.
 */
final class ColdRoomFlowSnapshot
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $when,
        public readonly ?string $where,
        public readonly ?string $who,
        public readonly ?string $from,
        public readonly ?string $to,
        public readonly ?string $conditions,
        public readonly ?string $duration,
        public readonly string $event,
        public readonly array $payload = [],
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function asQuestions(): array
    {
        return [
            'QUAND ?' => $this->when,
            'OÙ ?' => $this->where,
            'QUI ?' => $this->who,
            "D'OÙ ?" => $this->from,
            'VERS OÙ ?' => $this->to,
            'DANS QUELLES CONDITIONS ?' => $this->conditions,
            'COMBIEN DE TEMPS ?' => $this->duration,
            'QUEL ÉVÉNEMENT ?' => $this->event,
        ];
    }
}
