<?php

declare(strict_types=1);

namespace Larascan\Support;

enum ReportFormat: string
{
    case Table = 'table';
    case Json = 'json';
    case Markdown = 'markdown';
    case Html = 'html';

    public function isExportable(): bool
    {
        return $this !== self::Table;
    }

    public static function tryFromExtension(string $extension): ?self
    {
        return match (strtolower(trim($extension, '.'))) {
            'json' => self::Json,
            'md', 'markdown' => self::Markdown,
            'html', 'htm' => self::Html,
            default => null,
        };
    }

    public static function tryFromAlias(string $value): ?self
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'md' => self::Markdown,
            default => self::tryFrom($normalized),
        };
    }
}
