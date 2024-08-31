<?php

declare(strict_types=1);

namespace LLM\Agents\Chat\Console;

use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ChatStyle extends SymfonyStyle
{
    private readonly Cursor $cursor;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $formatter = $output->getFormatter();
        $formatter->setStyle('muted', new OutputFormatterStyle('gray'));
        $formatter->setStyle('tool_call', new OutputFormatterStyle('white', 'blue', ['bold']));
        $formatter->setStyle('tool_result', new OutputFormatterStyle('white', 'magenta', ['bold']));
        $formatter->setStyle('response', new OutputFormatterStyle('cyan'));

        parent::__construct(
            $input,
            $output,
        );

        $this->cursor = new Cursor($output);
    }

    /**
     * Formats a message as a block of text.
     */
    public function block(
        string|array $messages,
        ?string $type = null,
        ?string $style = null,
        string $prefix = ' ',
        bool $padding = false,
        bool $escape = true,
    ): void {
        parent::block($messages, $type, $style, $prefix, $padding, $escape);
        $this->cursor->moveUp();
    }
}
