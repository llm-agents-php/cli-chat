<?php

declare(strict_types=1);

namespace LLM\Agents\Chat\Console;

use LLM\Agents\Agent\AgentInterface;
use LLM\Agents\Agent\AgentRegistryInterface;
use LLM\Agents\Chat\ChatHistoryRepositoryInterface;
use LLM\Agents\Chat\ChatServiceInterface;
use LLM\Agents\Tool\ToolRegistryInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ChatSession
{
    private readonly ChatStyle $io;
    private readonly Cursor $cursor;
    private bool $firstMessage = true;
    private bool $lastMessageCustom = false;
    private UuidInterface $sessionUuid;

    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        private readonly AgentRegistryInterface $agents,
        private readonly ChatServiceInterface $chat,
        private readonly ChatHistoryRepositoryInterface $chatHistory,
        private readonly ToolRegistryInterface $tools,
    ) {
        $this->io = new ChatStyle($input, $output);
        $this->cursor = new Cursor($output);
    }

    public function run(UuidInterface $accountUuid): void
    {
        $agent = $this->selectAgent();

        $getCommand = $this->getCommand($agent);

        $this->sessionUuid = $this->chat->startSession(
            accountUuid: $accountUuid,
            agentName: $agent->getKey(),
        );

        $sessionInfo = [];
        if ($this->io->isVerbose()) {
            $sessionInfo = [
                \sprintf('Session started with UUID: %s', $this->sessionUuid),
            ];
        }

        $message = \sprintf('php app.php chat:session %s -v', $this->sessionUuid);

        $this->io->block(\array_merge($sessionInfo, [
            'Run the following command to see the AI response',
            \str_repeat('-', \strlen($message)),
            \sprintf('php app.php chat:session %s -v', $this->sessionUuid),
        ]), style: 'info', padding: true);

        while (true) {
            $message = $getCommand();

            if ($message === 'exit') {
                $this->io->info('Goodbye! Closing chat session...');
                $this->chat->closeSession($this->sessionUuid);

                $this->chatHistory->clear($this->sessionUuid);
                break;
            } elseif ($message === 'refresh') {
                continue;
            }

            if (!empty($message)) {
                $this->chat->ask($this->sessionUuid, $message);
            } else {
                $this->io->warning('Message cannot be empty');
            }
        }
    }

    private function selectAgent(): AgentInterface
    {
        $availableAgents = [];

        foreach ($this->agents->all() as $agent) {
            $availableAgents[$agent->getKey()] = $agent->getName();
        }

        while (true) {
            $agentName = $this->io->choice(
                'Hello! Let\'s start a chat session. Please select an agent:',
                $availableAgents,
            );

            if ($agentName && $this->agents->has($agentName)) {
                $this->cursor->moveUp(\count($availableAgents) + 4);
                // clears all the output from the current line
                $this->cursor->clearOutput();

                $agent = $this->agents->get($agentName);
                $this->io->title($agent->getName());

                // split the description into multiple lines by 200 characters
                $this->io->block(\wordwrap($agent->getDescription(), 200, "\n", true));

                $rows = [];
                foreach ($agent->getTools() as $tool) {
                    $tool = $this->tools->get($tool->name);
                    $rows[] = [$tool->name, \wordwrap($tool->description, 70, "\n", true)];
                }
                $this->io->table(['Tool', 'Description'], $rows);

                break;
            }

            $this->io->error('Invalid agent');
        }

        return $agent;
    }

    private function getCommand(AgentInterface $agent): callable
    {
        return function () use ($agent): string|null {
            $initialPrompts = ['custom'];
            $cursorOffset = $this->lastMessageCustom ? 5 : 4;

            $this->lastMessageCustom = false;

            foreach ($agent->getPrompts() as $prompt) {
                $initialPrompts[] = $prompt->content;
            }

            $initialPrompts[] = 'reset';
            $initialPrompts[] = 'exit';

            if (!$this->firstMessage) {
                $this->cursor->moveUp(\count($initialPrompts) + $cursorOffset);
                // clears all the output from the current line
                $this->cursor->clearOutput();
                $this->cursor->moveUp();
            }


            if ($this->firstMessage) {
                $this->firstMessage = false;
            }

            $initialPrompt = $this->io->choice('Choose a prompt:', $initialPrompts, 'custom');
            if ($initialPrompt === 'custom') {
                // Re-enable input echoing in case it was disabled
                \shell_exec('stty sane');
                $initialPrompt = $this->io->ask('You');
                $this->lastMessageCustom = true;
            }

            return $initialPrompt;
        };
    }
}
