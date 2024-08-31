<?php

declare(strict_types=1);

namespace LLM\Agents\Chat;

use LLM\Agents\Chat\Event\MessageChunk;
use LLM\Agents\OpenAI\Client\StreamChunkCallbackInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ramsey\Uuid\UuidInterface;

final readonly class StreamChunkCallback implements StreamChunkCallbackInterface
{
    public function __construct(
        private UuidInterface $sessionUuid,
        private ?EventDispatcherInterface $eventDispatcher = null,
    ) {}

    public function __invoke(?string $chunk, bool $stop, ?string $finishReason = null): void
    {
        $this->eventDispatcher?->dispatch(
            new MessageChunk(
                sessionUuid: $this->sessionUuid,
                createdAt: new \DateTimeImmutable(),
                message: (string) $chunk,
                isLast: $stop,
            ),
        );
    }
}
