<?php

declare(strict_types=1);

namespace LLM\Agents\Chat;

use LLM\Agents\Chat\Exception\ChatNotFoundException;
use Ramsey\Uuid\UuidInterface;

interface ChatServiceInterface
{
    /**
     * Get session by UUID.
     *
     * @throws ChatNotFoundException
     */
    public function getSession(UuidInterface $sessionUuid): SessionInterface;

    public function updateSession(SessionInterface $session): void;

    /**
     * Start session on context.
     *
     * @return UuidInterface Session UUID
     */
    public function startSession(UuidInterface $accountUuid, string $agentName): UuidInterface;

    /**
     * Ask question to chat.
     *
     * @return UuidInterface Message UUID.
     */
    public function ask(UuidInterface $sessionUuid, string|\Stringable $message): UuidInterface;

    /**
     * Close session.
     */
    public function closeSession(UuidInterface $sessionUuid): void;
}
