<?php

declare(strict_types=1);

namespace Larascan\Support;

use Larascan\Engine\InventoryResult;

final class MarkdownFormatter
{
    private function __construct()
    {
    }

    public static function format(InventoryResult $result, bool $usedOnly = false, bool $unusedOnly = false): string
    {
        $lines = [
            '# Larascan adoption report',
            '',
            '- Laravel version: ' . self::codeSpan($result->getLaravelVersion()),
            '- Scan path: ' . self::codeSpan($result->getScannedPath()),
            '- Files scanned: ' . $result->getScannedFilesCount(),
            '- Total capabilities: ' . $result->getTotalTrackedCount(),
            sprintf(
                '- Adoption rate: **%.1f%%** (%d used / %d unused)',
                $result->getAdoptionRate(),
                $result->getUsedCount(),
                $result->getUnusedCount()
            ),
            '',
        ];

        if (! $unusedOnly) {
            self::appendInventoryTable($lines, 'Used capabilities', $result->getUsed());
        }

        if (! $usedOnly) {
            self::appendInventoryTable($lines, 'Unused capabilities', $result->getUnused());
        }

        if ($result->hasParseErrors()) {
            $lines[] = '## Parse errors (' . count($result->getParseErrors()) . ')';
            $lines[] = '';
            $lines[] = '| File | Error |';
            $lines[] = '| --- | --- |';

            foreach ($result->getParseErrors() as $error) {
                $lines[] = '| ' . self::codeSpan($error['file']) . ' | ' . self::tableCell($error['error']) . ' |';
            }

            $lines[] = '';
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param  array<string, array{name: string, type: string, class?: string, count: int, files: int}>  $items
     * @param  array<string>  $lines
     */
    private static function appendInventoryTable(array &$lines, string $title, array $items): void
    {
        $lines[] = '## ' . $title . ' (' . count($items) . ')';
        $lines[] = '';
        $lines[] = '| Capability | Type | Calls | Files |';
        $lines[] = '| --- | --- | ---: | ---: |';

        if ($items === []) {
            $lines[] = '| None | | | |';
            $lines[] = '';

            return;
        }

        foreach ($items as $item) {
            $lines[] = sprintf(
                '| %s | %s | %d | %d |',
                self::codeSpan($item['name']),
                self::tableCell($item['type']),
                $item['count'],
                $item['files']
            );
        }

        $lines[] = '';
    }

    private static function tableCell(string $value): string
    {
        $value = preg_replace('/[\r\n]+/', ' ', $value) ?? $value;

        return str_replace(
            ['&', '<', '>', '|'],
            ['&amp;', '&lt;', '&gt;', '\|'],
            $value
        );
    }

    private static function codeSpan(string $value): string
    {
        $value = preg_replace('/[\r\n]+/', ' ', $value) ?? $value;
        $value = str_replace('|', '\\|', $value);
        preg_match_all('/`+/', $value, $matches);
        $longestRun = 0;

        foreach ($matches[0] as $run) {
            $longestRun = max($longestRun, strlen($run));
        }

        $delimiter = str_repeat('`', $longestRun + 1);
        $needsPadding = $value !== '' && (
            $value[0] === '`'
            || str_ends_with($value, '`')
            || trim($value) !== $value
        );
        $padding = $needsPadding ? ' ' : '';

        return $delimiter . $padding . $value . $padding . $delimiter;
    }
}
