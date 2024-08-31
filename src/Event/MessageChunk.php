<?php

declare(strict_types=1);

namespace LLM\Agents\Chat\Event;

use Ramsey\Uuid\UuidInterface;

final readonly class MessageChunk extends Message
{
    public function __construct(
        UuidInterface $sessionUuid,
        \DateTimeImmutable $createdAt,
        \Stringable|string $message,
        public bool $isLast,
    ) {
        parent::__construct($sessionUuid, $createdAt, $message);
    }
}
