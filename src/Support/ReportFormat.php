<?php

declare(strict_types=1);

namespace Larascan\Support;

enum ReportFormat: string
{
    case Table = 'table';
    case Json = 'json';
    case Markdown = 'markdown';
    case Html = 'html';

    public static function tryFromAlias(string $value): ?self
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'md' => self::Markdown,
            default => self::tryFrom($normalized),
        };
    }
}
