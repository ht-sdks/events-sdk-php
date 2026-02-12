# Agent Instructions

This file provides instructions for AI agents working on this PHP repository.

## Project Overview

- **Language**: PHP
- **PHP Versions**: 7.4, 8.0, 8.1, 8.2, 8.3
- **Package Manager**: Composer
- **Testing**: PHPUnit 9.x
- **Linting**: PHP_CodeSniffer (phpcs) with PSR-12 + Slevomat Coding Standard
- **Distribution**: Packagist (`ht-sdks/events-sdk-php`)

### Project Structure

```
lib/
  Client.php              # Core client implementation
  Hightouch.php           # Static facade / entry point
  HightouchException.php  # Custom exception class
  Version.php             # SDK version constant
  Consumer/
    Consumer.php          # Consumer interface
    File.php              # File consumer
    ForkCurl.php          # Fork+cURL consumer
    InMemory.php          # In-memory consumer (testing)
    LibCurl.php           # libcurl consumer
    QueueConsumer.php     # Base queued consumer
    Socket.php            # Socket consumer
test/
  ClientTest.php
  HightouchTest.php
  HightouchEventsTest.php
  ConsumerFileTest.php
  ConsumerForkCurlTest.php
  ConsumerLibCurlTest.php
  ConsumerSocketTest.php
bin/
  htevents               # CLI binary
```

### Key Configuration Files

| File | Purpose |
|------|---------|
| `composer.json` | Dependencies, autoloading, scripts |
| `phpunit.xml` | PHPUnit test configuration |
| `phpcs.xml` | PHP_CodeSniffer rules |
| `.phplint.yml` | PHP lint configuration |
| `bootstrap.php` | Autoloader bootstrap for tests |

---

## Updating Dependencies

### 1. Pre-flight Checks

```bash
# Check PHP version
php --version

# Ensure Composer is installed
composer --version

# Ensure you're at the repository root
pwd  # Should contain composer.json
```

### 2. Establish Test Baseline

```bash
# Install dependencies
composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist

# Run all tests and record results
vendor/bin/phpunit
```

Record the number of passing tests before making any changes. This ensures you can verify nothing broke after upgrading.

### 3. Check for Security Advisories

```bash
composer audit
```

Review any vulnerabilities. Address critical issues before proceeding with other updates.

### 4. Check Outdated Packages

```bash
composer outdated
```

This shows installed vs. latest versions for all dependencies. Use `composer outdated --direct` to limit output to direct dependencies only.

### 5. Upgrade Dependencies

#### Option A: Safe Updates (within semver range)

```bash
# Update all packages within their constraints defined in composer.json
composer update
```

#### Option B: Update a Specific Package

```bash
# Update a single package
composer update phpunit/phpunit

# Update with dependencies
composer update phpunit/phpunit --with-dependencies
```

#### Option C: Major Version Updates

For major version bumps, edit `composer.json` directly, then reinstall:

```bash
# After editing version constraints in composer.json:
rm -rf vendor composer.lock
composer install
```

### 6. Run Linting and Tests

```bash
# Run code style checks
vendor/bin/phpcs

# Auto-fix code style issues where possible
vendor/bin/phpcbf

# Run the full test suite
vendor/bin/phpunit
```

Compare test results to the baseline from step 2. Fix any failures before proceeding.

### 7. Verify CI Would Pass

The CI matrix tests against PHP 7.4, 8.0, 8.1, 8.2, and 8.3. Locally, verify at least against your installed version:

```bash
# Full CI-equivalent sequence
composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist
vendor/bin/phpunit
```

If you have multiple PHP versions available (e.g., via `phpenv` or `update-alternatives`), test on the minimum supported version (7.4) and the latest (8.3) at minimum.

---

## Composer Scripts

The following scripts are defined in `composer.json`:

| Script | Command | Description |
|--------|---------|-------------|
| `composer test` | `vendor/bin/phpunit --no-coverage` | Run tests without coverage |
| `composer check` | `vendor/bin/phpcs` | Run code style checks |
| `composer cf` | `vendor/bin/phpcbf` | Auto-fix code style issues |
| `composer coverage` | `vendor/bin/phpunit` | Run tests with coverage |

---

## CI/CD

- CI config: `.github/workflows/ci.yml`
- Runs on: `ubuntu-latest`
- PHP versions tested: 7.4, 8.0, 8.1, 8.2, 8.3
- Steps: `composer install`, `vendor/bin/phpunit`

### CI Failures After Dependency Updates

1. **PHP compatibility errors**: Ensure updated packages support all PHP versions in the matrix (7.4 through 8.3)
2. **Test failures**: Review changelogs of updated packages for breaking changes
3. **Code style violations**: Run `vendor/bin/phpcbf` to auto-fix, then manually fix remaining issues

---

## Releasing

1. Update `lib/Version.php` with the new version number and commit the change.
2. Create a [GitHub Release](https://github.com/ht-sdks/events-sdk-php/releases) matching the version set in `lib/Version.php`.

Composer/Packagist automatically picks up the new tag. See the latest version at https://packagist.org/packages/ht-sdks/events-sdk-php.

### Semantic Versioning

- **PATCH** (0.0.1 -> 0.0.2): Bug fixes, dependency updates, no new features
- **MINOR** (0.0.1 -> 0.1.0): New backwards-compatible features
- **MAJOR** (0.0.1 -> 1.0.0): Breaking API changes

Dependency updates are typically **PATCH** bumps.

---

## Common Issues

### PHP Version Compatibility

When upgrading dependencies, ensure they still support PHP 7.4 (the minimum). Check `composer.json` `require.php` constraints of updated packages.

### Code Style Enforcement

This repo uses PSR-12 with additional Slevomat Coding Standard rules. Key requirements:

- `declare(strict_types=1);` at the top of every PHP file
- Alphabetically sorted `use` statements
- No unused imports
- Short array syntax (`[]` not `array()`)
- Explicit class constant visibility
- No Yoda comparisons

Run `vendor/bin/phpcs` to check and `vendor/bin/phpcbf` to auto-fix.

### Autoloading

The project uses PSR-4 autoloading:
- `Hightouch\` namespace maps to `lib/`
- `Hightouch\Test\` namespace maps to `test/`

If you add new classes, ensure they follow the namespace/directory convention. Run `composer dump-autoload` if autoloading isn't resolving correctly.

---

## Quick Reference

| Task | Command |
|------|---------|
| Install dependencies | `composer install` |
| Install (CI-style) | `composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist` |
| Install (fresh) | `rm -rf vendor composer.lock && composer install` |
| Run tests | `vendor/bin/phpunit` |
| Run tests (no coverage) | `composer test` |
| Run tests with coverage | `composer coverage` |
| Check code style | `composer check` |
| Fix code style | `composer cf` |
| Check outdated deps | `composer outdated --direct` |
| Security audit | `composer audit` |
| Update within semver | `composer update` |
| Dump autoloader | `composer dump-autoload` |
