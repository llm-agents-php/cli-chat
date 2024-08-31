<?php

declare(strict_types=1);

namespace LLM\Agents\Chat;

enum Role: string
{
    case User = 'user';
    case Bot = 'bot';
    case Agent = 'agent';
    case System = 'system';
    case Tool = 'tool';
}
