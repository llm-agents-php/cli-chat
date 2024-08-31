<?php

declare(strict_types=1);

namespace LLM\Agents\Chat\Console;

use LLM\Agents\Chat\ChatHistoryRepositoryInterface;
use LLM\Agents\Chat\ChatServiceInterface;
use LLM\Agents\Chat\Event\Message;
use LLM\Agents\Chat\Event\MessageChunk;
use LLM\Agents\Chat\Event\Question;
use LLM\Agents\Chat\Event\ToolCall;
use LLM\Agents\Chat\Event\ToolCallResult;
use LLM\Agents\Chat\Exception\ChatNotFoundException;
use LLM\Agents\Chat\Exception\SessionNotFoundException;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ChatHistory
{
    private UuidInterface $sessionUuid;
    /** @var array<non-empty-string> */
    private array $shownMessages = [];
    private bool $shouldStop = false;
    private string $lastMessage = '';

    private readonly ChatStyle $io;

    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        private readonly ChatHistoryRepositoryInterface $chatHistory,
        private readonly ChatServiceInterface $chat,
    ) {
        $this->io = new ChatStyle($input, $output);
    }

    public function run(UuidInterface $sessionUuid): void
    {
        $this->sessionUuid = $sessionUuid;

        $this->io->write("\033\143");

        $session = $this->chat->getSession($this->sessionUuid);

        $this->io->block([
            \sprintf('Connecting to chat session [%s]...', $this->sessionUuid),
            \sprintf('Chat session started with agent [%s]. Press Ctrl+C to exit.', $session->getAgentName()),
        ], style: 'info', padding: true);

        do {
            try {
                $this->chat->getSession($this->sessionUuid);
            } catch (ChatNotFoundException) {
                throw new SessionNotFoundException('Session is closed.');
            }

            foreach ($this->iterateMessages() as $message) {
                if ($message instanceof MessageChunk) {
                    if ($this->lastMessage === '' && !$message->isLast) {
                        $this->io->newLine();
                    }

                    $this->lastMessage .= $message->message;
                    $this->io->write($line = sprintf('<%s>%s</>', 'response', $message->message));
                    \usleep(20_000);
                    if ($message->isLast) {
                        if ($this->lastMessage !== '') {
                            $this->io->newLine();
                        }

                        $this->lastMessage = '';
                    }
                } elseif ($message instanceof Question) {
                    $this->io->block(
                        \sprintf('> User: %s', $message->message),
                        style: 'question',
                        padding: true,
                    );
                } elseif ($message instanceof ToolCall) {
                    $this->io->block(
                        \sprintf(
                            "<-- Let me call [%s] tool",
                            $message->tool,
                        ),
                        style: 'tool_call',
                        padding: true,
                    );

                    if ($this->io->isVerbose()) {
                        $this->io->block(
                            messages: \json_encode(\json_decode($message->arguments, true), \JSON_PRETTY_PRINT),
                            style: 'muted',
                        );
                    }
                } elseif ($message instanceof ToolCallResult) {
                    $this->io->block(
                        \sprintf(
                            "--> [%s]",
                            $message->tool,
                        ),
                        style: 'tool_result',
                        padding: true,
                    );

                    if ($this->io->isVerbose()) {
                        // unescape the JSON string
                        $result = \str_replace('\\"', '"', $message->result);

                        $this->io->block(
                            messages: \json_validate($result)
                                ? \json_encode(\json_decode($result, true), \JSON_PRETTY_PRINT)
                                : $result,
                            style: 'muted',
                        );
                    }
                }
            }

            \sleep(2);
        } while (!$this->shouldStop);
    }

    /**
     * @return iterable<Message|ToolCall|ToolCallResult|MessageChunk>
     */
    private function iterateMessages(): iterable
    {
        $messages = $this->chatHistory->getMessages($this->sessionUuid);

        foreach ($messages as $message) {
            if ($message instanceof Message || $message instanceof Question || $message instanceof MessageChunk) {
                if (\in_array((string) $message->uuid, $this->shownMessages, true)) {
                    continue;
                }

                $this->shownMessages[] = (string) $message->uuid;
                yield $message;
            } elseif ($message instanceof ToolCall) {
                if (\in_array($message->id . 'ToolCall', $this->shownMessages, true)) {
                    continue;
                }

                $this->shownMessages[] = $message->id . 'ToolCall';
                yield $message;
            } elseif ($message instanceof ToolCallResult) {
                if (\in_array($message->id . 'ToolCallResult', $this->shownMessages, true)) {
                    continue;
                }

                $this->shownMessages[] = $message->id . 'ToolCallResult';
                yield $message;
            }
        }
    }
}
