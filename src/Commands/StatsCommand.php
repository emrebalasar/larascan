<?php

declare(strict_types=1);

namespace Larascan\Commands;

use Illuminate\Console\Command;
use Larascan\Engine\InventoryResult;
use Larascan\Engine\InventoryScanner;
use Larascan\Support\HtmlFormatter;
use Larascan\Support\MarkdownFormatter;
use Larascan\Support\PathResolver;
use Larascan\Support\ReportFormat;
use Larascan\Support\TermwindFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class StatsCommand extends Command
{
    public const COMMAND_NAME = 'native:stats';

    public const DEFAULT_ALIASES = [
        'larascan:stats',
        'larascan:inventory',
        'larascan:scan',
    ];

    /**
     * The console command name.
     */
    protected $name = self::COMMAND_NAME;

    /**
     * The console command description.
     */
    protected $description = 'Analyze codebase adoption and inventory of native Laravel 13 Core Facades, Utilities, and Helpers';

    /**
     * The console command aliases.
     *
     * @var array<string>
     */
    protected $aliases = self::DEFAULT_ALIASES;

    /**
     * Get aliases excluding a specific command name.
     *
     * @return array<string>
     */
    public static function aliasesExcept(string $commandName): array
    {
        return array_values(array_diff(
            [self::COMMAND_NAME, ...self::DEFAULT_ALIASES],
            [$commandName]
        ));
    }

    public function __construct(private ?InventoryScanner $scanner = null)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('path', InputArgument::OPTIONAL, 'Path to file or directory to scan');
        $this->addOption('skip-tests', null, InputOption::VALUE_NONE, 'Skip tests directory from scan');
        $this->addOption('no-skip-tests', null, InputOption::VALUE_NONE, 'Do not skip tests directory');
        $this->addOption('used', null, InputOption::VALUE_NONE, 'Show only used Laravel 13 features');
        $this->addOption('unused', null, InputOption::VALUE_NONE, 'Show only unused Laravel 13 features');
        $this->addOption(
            'json',
            null,
            InputOption::VALUE_NONE,
            'Output result in JSON format (legacy alias for --format=json)'
        );
        $this->addOption(
            'format',
            null,
            InputOption::VALUE_REQUIRED,
            'Report format: table, json, markdown (md), or html'
        );
        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'Write json, markdown, or html report directly to a file'
        );
    }

    public function handle(?InventoryScanner $scanner = null): int
    {
        $path = $this->resolvePath();
        $skipTests = $this->resolveSkipTests();

        $usedOnly = (bool) $this->option('used');
        $unusedOnly = (bool) $this->option('unused');

        if ($usedOnly && $unusedOnly) {
            $this->error('Do not combine --used with --unused.');

            return Command::FAILURE;
        }

        $formatOption = $this->option('format');

        if ($this->option('json') && $formatOption !== null) {
            $this->error('Do not combine --json with --format.');

            return Command::FAILURE;
        }

        $rawOutput = $this->option('output');
        $outputPath = null;

        if ($rawOutput !== null) {
            if (! is_string($rawOutput) || trim($rawOutput) === '') {
                $this->error('The --output option requires a non-empty path.');

                return Command::FAILURE;
            }

            $outputPath = trim($rawOutput);
        }

        $rawFormat = $this->option('json')
            ? 'json'
            : ($formatOption !== null ? (string) $formatOption : '');

        $format = $rawFormat !== ''
            ? ReportFormat::tryFromAlias($rawFormat)
            : ($outputPath !== null
                ? ReportFormat::tryFromExtension(pathinfo($outputPath, PATHINFO_EXTENSION)) ?? ReportFormat::Table
                : ReportFormat::Table);

        if ($format === null) {
            $this->error(sprintf(
                'Unsupported report format "%s". Choose table, json, markdown, or html.',
                $rawFormat
            ));

            return Command::FAILURE;
        }

        if ($outputPath !== null) {
            if (! $format->isExportable()) {
                $this->error('The --output option requires json, markdown, or html report format.');

                return Command::FAILURE;
            }

            if (! $this->prepareOutputPath($outputPath)) {
                return Command::FAILURE;
            }
        }

        $activeScanner = $scanner ?? $this->scanner ?? $this->resolveScanner();
        $result = $activeScanner->scan($path, $skipTests);

        if ($format === ReportFormat::Table) {
            $this->renderTableOutput($result, usedOnly: $usedOnly, unusedOnly: $unusedOnly);

            return Command::SUCCESS;
        }

        $content = $this->formatExportableReport($format, $result, $usedOnly, $unusedOnly);

        if ($outputPath !== null) {
            return $this->writeReport($outputPath, $content);
        }

        $this->output->writeln($content);

        return Command::SUCCESS;
    }

    private function formatExportableReport(
        ReportFormat $format,
        InventoryResult $result,
        bool $usedOnly,
        bool $unusedOnly
    ): string {
        return match ($format) {
            ReportFormat::Json => json_encode(
                $result->toArray(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ),
            ReportFormat::Markdown => MarkdownFormatter::format(
                $result,
                usedOnly: $usedOnly,
                unusedOnly: $unusedOnly
            ),
            ReportFormat::Html => HtmlFormatter::format(
                $result,
                usedOnly: $usedOnly,
                unusedOnly: $unusedOnly
            ),
            ReportFormat::Table => throw new \LogicException('Table format is not exportable.'),
        };
    }

    private function prepareOutputPath(string $path): bool
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            error_clear_last();

            $created = @mkdir($directory, 0755, true);

            if (! $created && ! is_dir($directory)) {
                $error = error_get_last();
                $details = is_array($error) && isset($error['message'])
                    ? sprintf(' (%s)', $error['message'])
                    : '';

                $this->error(sprintf(
                    'Output directory could not be created: %s%s',
                    $directory,
                    $details
                ));

                return false;
            }
        }

        if (! is_writable($directory)) {
            $this->error(sprintf('Output directory is not writable: %s', $directory));

            return false;
        }

        if (is_dir($path)) {
            $this->error(sprintf('Destination path is a directory: %s', $path));

            return false;
        }

        if (file_exists($path) && ! is_writable($path)) {
            $this->error(sprintf('Destination file is not writable: %s', $path));

            return false;
        }

        return true;
    }

    private function writeReport(string $path, string $contents): int
    {
        error_clear_last();

        $bytesWritten = @file_put_contents($path, $contents, LOCK_EX);

        if ($bytesWritten === false) {
            $error = error_get_last();
            $details = is_array($error) && isset($error['message'])
                ? sprintf(' (%s)', $error['message'])
                : '';

            $this->error(sprintf(
                'Unable to write report to: %s%s',
                $path,
                $details
            ));

            return Command::FAILURE;
        }

        $this->info(sprintf('✓ Report successfully exported to %s', $path));

        return Command::SUCCESS;
    }

    private function renderTableOutput(InventoryResult $result, bool $usedOnly, bool $unusedOnly): void
    {
        $formatter = new TermwindFormatter($this->output);
        $formatter->renderHeader();
        $formatter->renderResult($result, usedOnly: $usedOnly, unusedOnly: $unusedOnly);
    }

    private function resolvePath(): string
    {
        $configuredPaths = (array) PathResolver::packageConfig('paths', []);

        return PathResolver::resolveScanPath($this->argument('path'), $configuredPaths);
    }

    private function resolveSkipTests(): bool
    {
        if ($this->option('no-skip-tests')) {
            return false;
        }

        if ($this->option('skip-tests')) {
            return true;
        }

        return (bool) PathResolver::packageConfig('skip_tests', true);
    }

    private function resolveScanner(): InventoryScanner
    {
        if ($this->laravel !== null && $this->laravel->bound(InventoryScanner::class)) {
            return $this->laravel->make(InventoryScanner::class);
        }

        return new InventoryScanner();
    }
}
