<?php

declare(strict_types=1);

namespace Larascan\Support;

use Larascan\Engine\InventoryResult;

final class HtmlFormatter
{
    private function __construct()
    {
    }

    public static function format(InventoryResult $result, bool $usedOnly = false, bool $unusedOnly = false): string
    {
        $rate = number_format($result->getAdoptionRate(), 1, '.', '');
        $lines = [
            '<!doctype html>',
            '<html lang="en">',
            '<head>',
            '    <meta charset="utf-8">',
            '    <meta name="viewport" content="width=device-width, initial-scale=1">',
            '    <title>Larascan adoption report</title>',
            '    <style>',
            '        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #172033; background: #f4f6fa; }',
            '        body { margin: 0; padding: 2rem; }',
            '        main { max-width: 72rem; margin: 0 auto; }',
            '        .summary, .adoption, section {',
            '            margin: 1rem 0; padding: 1rem; border: 1px solid #d7deea;',
            '            border-radius: .5rem; background: #fff;',
            '        }',
            '        .summary {',
            '            display: grid; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr)); gap: .75rem;',
            '        }',
            '        dt { color: #526078; font-size: .875rem; }',
            '        dd { margin: .25rem 0 0; overflow-wrap: anywhere; }',
            '        .progress { height: .8rem; overflow: hidden; border-radius: 999px; background: #e5eaf2; }',
            '        .progress span { display: block; height: 100%; background: #2563eb; }',
            '        .badges { display: flex; flex-wrap: wrap; gap: .5rem; margin: .75rem 0 0; }',
            '        .badge { display: inline-block; padding: .2rem .55rem; border-radius: 999px; background: #e8edf5; font-size: .875rem; }',
            '        .badge.used { color: #166534; background: #dcfce7; }',
            '        .badge.unused { color: #991b1b; background: #fee2e2; }',
            '        table { width: 100%; border-collapse: collapse; }',
            '        th, td { padding: .55rem; border-bottom: 1px solid #e5eaf2; text-align: left; }',
            '        td.numeric { text-align: right; font-variant-numeric: tabular-nums; }',
            '        code { overflow-wrap: anywhere; }',
            '        .errors { color: #92400e; }',
            '        @media (max-width: 40rem) { body { padding: .75rem; } }',
            '    </style>',
            '</head>',
            '<body>',
            '<main>',
            '    <h1>Larascan adoption report</h1>',
            '    <dl class="summary">',
            '        <div><dt>Laravel version</dt><dd><code>' . self::escape($result->getLaravelVersion()) . '</code></dd></div>',
            '        <div><dt>Scan path</dt><dd><code>' . self::escape($result->getScannedPath()) . '</code></dd></div>',
            '        <div><dt>Files scanned</dt><dd>' . $result->getScannedFilesCount() . '</dd></div>',
            '        <div><dt>Total capabilities</dt><dd>' . $result->getTotalTrackedCount() . '</dd></div>',
            '    </dl>',
            '    <section class="adoption">',
            '        <h2>Adoption rate: ' . $rate . '%</h2>',
            '        <div class="progress" role="progressbar" aria-label="Adoption rate"',
            '             aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $rate . '">',
            '            <span style="width: ' . $rate . '%"></span>',
            '        </div>',
            '        <div class="badges">',
            '            <span class="badge used">' . $result->getUsedCount() . ' used</span>',
            '            <span class="badge unused">' . $result->getUnusedCount() . ' unused</span>',
            '        </div>',
            '    </section>',
        ];

        if (! $unusedOnly) {
            self::appendInventoryTable($lines, 'Used capabilities', $result->getUsed());
        }

        if (! $usedOnly) {
            self::appendInventoryTable($lines, 'Unused capabilities', $result->getUnused());
        }

        if ($result->hasParseErrors()) {
            $lines[] = '    <section>';
            $lines[] = '        <h2>Parse errors (' . count($result->getParseErrors()) . ')</h2>';
            $lines[] = '        <ul class="errors">';

            foreach ($result->getParseErrors() as $error) {
                $lines[] = '            <li><code>' . self::escape($error['file']) . '</code>: '
                    . self::escape($error['error']) . '</li>';
            }

            $lines[] = '        </ul>';
            $lines[] = '    </section>';
        }

        $lines[] = '</main>';
        $lines[] = '</body>';
        $lines[] = '</html>';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param  array<string, array{name: string, type: string, class?: string, count: int, files: int}>  $items
     * @param  array<string>  $lines
     */
    private static function appendInventoryTable(array &$lines, string $title, array $items): void
    {
        $lines[] = '    <section>';
        $lines[] = '        <h2>' . self::escape($title) . ' (' . count($items) . ')</h2>';

        if ($items === []) {
            $lines[] = '        <p>None</p>';
            $lines[] = '    </section>';

            return;
        }

        $lines[] = '        <table>';
        $lines[] = '            <thead><tr><th scope="col">Capability</th><th scope="col">Type</th>'
            . '<th scope="col">Calls</th><th scope="col">Files</th></tr></thead>';
        $lines[] = '            <tbody>';

        foreach ($items as $item) {
            $lines[] = sprintf(
                '                <tr><th scope="row"><code>%s</code></th>'
                    . '<td><span class="badge">%s</span></td>'
                    . '<td class="numeric">%d</td><td class="numeric">%d</td></tr>',
                self::escape($item['name']),
                self::escape($item['type']),
                $item['count'],
                $item['files']
            );
        }

        $lines[] = '            </tbody>';
        $lines[] = '        </table>';
        $lines[] = '    </section>';
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
