<?php

declare(strict_types=1);

namespace Larascan\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Larascan\Commands\StatsCommand;
use Larascan\Engine\InventoryResult;
use Larascan\Support\HtmlFormatter;
use Larascan\Support\MarkdownFormatter;
use Larascan\Tests\TestCase;

class StatsCommandTest extends TestCase
{
    public function test_it_executes_artisan_command_with_json_output(): void
    {
        $exitCode = Artisan::call(StatsCommand::COMMAND_NAME, [
            'path' => $this->workbenchPath(),
            '--json' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();

        $this->assertJson($output);
        $data = json_decode($output, true);

        $this->assertArrayHasKey('laravel_version', $data);
        $this->assertArrayHasKey('adoption_rate', $data);
        $this->assertArrayHasKey('used_count', $data);
        $this->assertArrayHasKey('unused_count', $data);
        $this->assertArrayHasKey('used', $data);
        $this->assertArrayHasKey('unused', $data);
    }

    public function test_format_json_matches_the_legacy_json_option(): void
    {
        $exitCode = Artisan::call(StatsCommand::COMMAND_NAME, [
            'path' => $this->workbenchPath(),
            '--format' => 'json',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertJson(Artisan::output());
    }

    public function test_it_executes_artisan_command_with_formatted_output(): void
    {
        $exitCode = Artisan::call(StatsCommand::COMMAND_NAME, [
            'path' => $this->workbenchPath(),
        ]);

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();

        $this->assertStringContainsString('LARASCAN', $output);
        $this->assertStringContainsString('Laravel Core Version', $output);
        $this->assertStringContainsString('USED LARAVEL CAPABILITIES', $output);
    }

    public function test_it_executes_artisan_command_with_markdown_output_and_alias(): void
    {
        foreach (['markdown', 'md'] as $format) {
            $exitCode = Artisan::call(StatsCommand::COMMAND_NAME, [
                'path' => $this->workbenchPath(),
                '--format' => $format,
            ]);

            $this->assertSame(0, $exitCode);
            $output = Artisan::output();
            $this->assertStringContainsString('# Larascan adoption report', $output);
            $this->assertStringContainsString('| Capability | Type | Calls | Files |', $output);
            $this->assertStringContainsString('Adoption rate:', $output);
        }
    }

    public function test_it_executes_artisan_command_with_html_output(): void
    {
        $exitCode = Artisan::call(StatsCommand::COMMAND_NAME, [
            'path' => $this->workbenchPath(),
            '--format' => 'html',
        ]);

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('<!doctype html>', $output);
        $this->assertStringContainsString('<html lang="en">', $output);
        $this->assertStringContainsString('role="progressbar"', $output);
        $this->assertStringContainsString('<table>', $output);
    }

    public function test_html_formatter_escapes_untrusted_report_values(): void
    {
        $result = new InventoryResult(
            ['example' => [
                'name' => '<img src=x onerror=alert(1)>',
                'type' => '<script>alert(1)</script>',
                'count' => 1,
                'files' => 1,
            ]],
            1,
            '<script>scan</script>',
            '<b>13</b>',
            [['file' => '<script>.php', 'error' => '<img src=x>']]
        );

        $output = HtmlFormatter::format($result);

        $this->assertStringContainsString('&lt;script&gt;', $output);
        $this->assertStringContainsString('&lt;img', $output);
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('<img', $output);
    }

    public function test_markdown_formatter_escapes_table_delimiters(): void
    {
        $result = new InventoryResult(
            ['example' => [
                'name' => 'Cache|remember',
                'type' => 'Facade',
                'count' => 1,
                'files' => 1,
            ]],
            1,
            'app',
            '13.x'
        );

        $output = MarkdownFormatter::format($result);

        $this->assertStringContainsString('Cache\|remember', $output);
    }

    public function test_it_rejects_conflicting_json_options(): void
    {
        $exitCode = Artisan::call(StatsCommand::COMMAND_NAME, [
            'path' => $this->workbenchPath(),
            '--json' => true,
            '--format' => 'html',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Do not combine --json with --format', Artisan::output());
    }

    public function test_it_rejects_conflicting_used_and_unused_options(): void
    {
        $exitCode = Artisan::call(StatsCommand::COMMAND_NAME, [
            'path' => $this->workbenchPath(),
            '--used' => true,
            '--unused' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Do not combine --used with --unused', Artisan::output());
    }

    public function test_it_rejects_an_unsupported_report_format(): void
    {
        $exitCode = Artisan::call(StatsCommand::COMMAND_NAME, [
            'path' => $this->workbenchPath(),
            '--format' => 'pdf',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Unsupported report format', Artisan::output());
    }

    public function test_it_executes_artisan_command_with_explicit_table_format(): void
    {
        $exitCode = Artisan::call(StatsCommand::COMMAND_NAME, [
            'path' => $this->workbenchPath(),
            '--format' => 'table',
        ]);

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('LARASCAN', $output);
        $this->assertStringContainsString('USED LARAVEL CAPABILITIES', $output);
    }

    public function test_it_filters_markdown_output_with_used_option(): void
    {
        $exitCode = Artisan::call(StatsCommand::COMMAND_NAME, [
            'path' => $this->workbenchPath(),
            '--format' => 'markdown',
            '--used' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('## Used capabilities', $output);
        $this->assertStringNotContainsString('## Unused capabilities', $output);
    }

    public function test_it_filters_html_output_with_unused_option(): void
    {
        $exitCode = Artisan::call(StatsCommand::COMMAND_NAME, [
            'path' => $this->workbenchPath(),
            '--format' => 'html',
            '--unused' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Unused capabilities', $output);
        $this->assertStringNotContainsString('Used capabilities', $output);
    }

    public function test_markdown_formatter_formats_symbols_as_code_spans_and_preserves_quotes_in_errors(): void
    {
        $result = new InventoryResult(
            ['example' => [
                'name' => '__call',
                'type' => 'Helper',
                'count' => 2,
                'files' => 1,
            ]],
            1,
            'app',
            '13.x',
            [['file' => 'app/Broken.php', 'error' => "Can't parse token ';', expecting ')'"]]
        );

        $output = MarkdownFormatter::format($result);

        $this->assertStringContainsString('| `__call` | Helper | 2 | 1 |', $output);
        $this->assertStringContainsString('| `app/Broken.php` | Can\'t parse token \';\', expecting \')\' |', $output);
        $this->assertStringNotContainsString('&\#039;', $output);
        $this->assertStringNotContainsString('&#039;', $output);
        $this->assertStringNotContainsString('\_\_call', $output);
    }

    public function test_markdown_formatter_handles_empty_items_and_total_capabilities(): void
    {
        $result = new InventoryResult([], 0, 'empty');
        $output = MarkdownFormatter::format($result);

        $this->assertStringContainsString('- Total capabilities: 0', $output);
        $this->assertStringContainsString('| None | | | |', $output);
    }

    public function test_html_formatter_handles_empty_items(): void
    {
        $result = new InventoryResult([], 0, 'empty');
        $output = HtmlFormatter::format($result);

        $this->assertStringContainsString('<p>None</p>', $output);
        $this->assertStringContainsString('Total capabilities</dt><dd>0</dd>', $output);
    }
}
