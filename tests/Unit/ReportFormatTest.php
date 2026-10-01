<?php

declare(strict_types=1);

namespace Larascan\Tests\Unit;

use Larascan\Support\ReportFormat;
use Larascan\Tests\TestCase;

class ReportFormatTest extends TestCase
{
    public function test_it_resolves_supported_formats(): void
    {
        $this->assertSame(ReportFormat::Table, ReportFormat::tryFromAlias('table'));
        $this->assertSame(ReportFormat::Json, ReportFormat::tryFromAlias('json'));
        $this->assertSame(ReportFormat::Markdown, ReportFormat::tryFromAlias('markdown'));
        $this->assertSame(ReportFormat::Html, ReportFormat::tryFromAlias('html'));
    }

    public function test_it_resolves_aliases_and_handles_case_and_whitespace(): void
    {
        $this->assertSame(ReportFormat::Markdown, ReportFormat::tryFromAlias('md'));
        $this->assertSame(ReportFormat::Markdown, ReportFormat::tryFromAlias('  MD  '));
        $this->assertSame(ReportFormat::Html, ReportFormat::tryFromAlias('HTML'));
        $this->assertSame(ReportFormat::Json, ReportFormat::tryFromAlias('JSON'));
    }

    public function test_it_returns_null_for_unsupported_formats(): void
    {
        $this->assertNull(ReportFormat::tryFromAlias('pdf'));
        $this->assertNull(ReportFormat::tryFromAlias('csv'));
        $this->assertNull(ReportFormat::tryFromAlias(''));
    }
}
