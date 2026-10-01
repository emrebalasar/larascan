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

        $rawFormat = $this->option('json') ? 'json' : (string) ($formatOption ?? 'table');
        $format = ReportFormat::tryFromAlias($rawFormat);

        if ($format === null) {
            $this->error(sprintf(
                'Unsupported report format "%s". Choose table, json, markdown, or html.',
                $rawFormat
            ));

            return Command::FAILURE;
        }

        $activeScanner = $scanner ?? $this->scanner ?? $this->resolveScanner();
        $result = $activeScanner->scan($path, $skipTests);

        match ($format) {
            ReportFormat::Json => $this->output->writeln(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
            ReportFormat::Markdown => $this->output->writeln(MarkdownFormatter::format($result, usedOnly: $usedOnly, unusedOnly: $unusedOnly)),
            ReportFormat::Html => $this->output->writeln(HtmlFormatter::format($result, usedOnly: $usedOnly, unusedOnly: $unusedOnly)),
            ReportFormat::Table => $this->renderTableOutput($result, usedOnly: $usedOnly, unusedOnly: $unusedOnly),
        };

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
