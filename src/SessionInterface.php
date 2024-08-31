<?php

declare(strict_types=1);

namespace LLM\Agents\Chat;

use Ramsey\Uuid\UuidInterface;

interface SessionInterface
{
    public function getUuid(): UuidInterface;

    public function getAgentName(): string;

    public function updateHistory(array $messages): void;

    public function isFinished(): bool;
}
