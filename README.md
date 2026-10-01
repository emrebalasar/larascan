<p align="center">
  <img src="art/social-preview.png" alt="Larascan - Laravel Core Native Adoption & Inventory Engine" width="100%">
</p>

# Larascan

**Laravel Core Native Adoption & Inventory Engine**

Measure how much of native Laravel your team is actually utilizing. Uncover used and unused capabilities efficiently.

[![Tests](https://github.com/emrebalasar/larascan/actions/workflows/run-tests.yml/badge.svg)](https://github.com/emrebalasar/larascan/actions/workflows/run-tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/larascan/larascan.svg?style=flat-square)](https://packagist.org/packages/larascan/larascan)
[![Total Downloads](https://img.shields.io/packagist/dt/larascan/larascan.svg?style=flat-square)](https://packagist.org/packages/larascan/larascan)
[![Software License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-777bb4.svg?style=flat-square&logo=php)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg?style=flat-square&logo=laravel)](https://laravel.com)

---

```text
  LARASCAN • Laravel 13 Core Native Adoption & Inventory Engine

 Laravel Core Version: 13.x  •  Scan Path: app/  •  Files: 686 
 Native Laravel Adoption Rate: 25.4% [■■■■■░░░░░░░░░░░░░░░] (35 Used / 103 Unused) 

  USED LARAVEL CAPABILITIES (35)  
+-----------------------+-------------+------------------+--------------+
| Class / Function      | Type        | Call Count       | File Count   |
+-----------------------+-------------+------------------+--------------+
| __                    | Helper      | 490 times        | 101 files    |
| config                | Helper      | 280 times        | 156 files    |
| now                   | Helper      | 142 times        | 88 files     |
| route                 | Helper      | 129 times        | 44 files     |
| redirect              | Helper      | 82 times         | 43 files     |
| Str                   | Utility     | 81 times         | 40 files     |
| view                  | Helper      | 75 times         | 44 files     |
| DB                    | Facade      | 71 times         | 49 files     |
| response              | Helper      | 52 times         | 26 files     |
| Storage               | Facade      | 23 times         | 12 files     |
| Log                   | Facade      | 15 times         | 8 files      |
| Arr                   | Utility     | 8 times          | 6 files      |
| ...                   | ...         | ...              | ...          |
+-----------------------+-------------+------------------+--------------+
```

---

## What is Larascan?

Modern Laravel provides many core Facades, utilities, and global helpers (`Sleep`, `Benchmark`, `Number`, `Context`, `Concurrency`, `File::lines`, `once()`, `defer()`, `retry()`, etc.).

Many projects only utilize a portion of these native features. Larascan helps analyze this usage:
1. It connects directly to your installed `vendor/laravel/framework` core.
2. It dynamically extracts official Facades, utility classes (`Sleep`, `Benchmark`, `Number`, `Str`, `Arr`), and global helpers.
3. In an AST pass, it measures:
   - **Adoption Rate (%):** The percentage of native Laravel features your codebase leverages.
   - **Active Inventory:** The native symbols used, invocation counts, and file occurrences.
   - **Unused Features:** The native features that have not been used in the analyzed paths.

## Features

- **Fast Execution:** Analyzes production PHP files quickly via AST traversal.
- **Dynamic Analysis:** Introspects the active Laravel Core version without hardcoded class lists.
- **Read-Only:** Analyzes code via AST parsing without altering your source code.
- **Multiple Report Formats:** Export adoption reports as terminal tables, JSON, Markdown, or standalone HTML.
- **Artisan Integration:** Registers `php artisan native:stats` via Laravel Package Discovery.

## Installation

> [!WARNING]
> Larascan is currently in **alpha**. The API may change between releases. Please report any issues on GitHub.

Install Larascan as a development dependency via Composer:

```bash
composer require --dev larascan/larascan:^1.0@alpha
```

## Usage

### 1. Standalone CLI

Run Larascan from your vendor binaries:

```bash
# Analyze your application and show the full adoption report:
vendor/bin/larascan

# Show only the Laravel features actively used in your project:
vendor/bin/larascan --used

# Show only the unused Laravel features:
vendor/bin/larascan --unused

# Scan a specific directory or service path:
vendor/bin/larascan app/Services

# Output JSON for CI/CD pipelines or automated metrics:
vendor/bin/larascan --json

# Export a Markdown table or a standalone HTML report:
vendor/bin/larascan --format=markdown
vendor/bin/larascan --format=html

# Use the short Markdown alias or select JSON through --format:
vendor/bin/larascan --format=md
vendor/bin/larascan --format=json
```

### 2. Artisan Command

Larascan auto-registers in your Laravel console:

```bash
# Run adoption analysis:
php artisan native:stats

# Filter by used only:
php artisan native:stats --used

# Filter by unused only:
php artisan native:stats --unused

# Export a standalone HTML report:
php artisan native:stats --format=html
```

## Configuration (Optional)

To customize scanned directories or ignored paths, publish the configuration file:

```bash
php artisan vendor:publish --tag=larascan-config
```

This creates `config/larascan.php`:

```php
return [
    // Directories to scan (defaults to standard app directory)
    'paths' => [
        'app',
    ],

    // Skip test directories during analysis
    'skip_tests' => true,

    // Ignored paths
    'ignore_paths' => [
        'vendor',
        'storage',
        'bootstrap/cache',
        'node_modules',
    ],
];
```

## Real-World Benchmark

| Project Size | Scanned Files | Execution Time | Memory Usage |
|---|---|---|---|
| Medium App | 120 files | 0.08s | ~12 MB |
| Enterprise ERP | 686 files | 0.39s | ~24 MB |

## Contributing

Contributions, feature requests, and ideas are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) for development instructions.

## License

Larascan is open-sourced software licensed under the [MIT license](LICENSE).
