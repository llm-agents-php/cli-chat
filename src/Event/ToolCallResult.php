<?php

declare(strict_types=1);

namespace LLM\Agents\Chat\Event;

use Ramsey\Uuid\UuidInterface;

final readonly class ToolCallResult
{
    public function __construct(
        public UuidInterface $sessionUuid,
        public string $id,
        public string|\Stringable $tool,
        public string|\Stringable $result,
        public \DateTimeImmutable $createdAt,
    ) {}
}
