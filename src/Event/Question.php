<?php

declare(strict_types=1);

namespace LLM\Agents\Chat\Event;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class Question
{
    public UuidInterface $uuid;

    public function __construct(
        public UuidInterface $sessionUuid,
        public UuidInterface $messageUuid,
        public \DateTimeImmutable $createdAt,
        public string|\Stringable $message,
    ) {
        $this->uuid = Uuid::uuid4();
    }
}
