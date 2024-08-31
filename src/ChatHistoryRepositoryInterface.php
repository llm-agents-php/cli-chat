<?php

declare(strict_types=1);

namespace LLM\Agents\Chat;

use Ramsey\Uuid\UuidInterface;

interface ChatHistoryRepositoryInterface
{
    public function clear(UuidInterface $sessionUuid): void;

    public function getMessages(UuidInterface $sessionUuid): iterable;

    public function addMessage(UuidInterface $sessionUuid, object $message): void;
}
