# Agent Instructions

This file documents the dependency update workflow for this PHP repository.

- **PHP Versions**: 7.4, 8.0, 8.1, 8.2, 8.3
- **Package Manager**: Composer
- **Testing**: PHPUnit 9.x (`vendor/bin/phpunit`)
- **Linting**: PHP_CodeSniffer (`vendor/bin/phpcs`) with PSR-12 + Slevomat Coding Standard
- **CI Config**: `.github/workflows/ci.yml`

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

## Troubleshooting Dependency Updates

### PHP Version Compatibility

When upgrading dependencies, ensure they still support PHP 7.4 (the minimum). Check `composer.json` `require.php` constraints of updated packages.

### CI Failures After Dependency Updates

1. **PHP compatibility errors**: Ensure updated packages support all PHP versions in the matrix (7.4 through 8.3)
2. **Test failures**: Review changelogs of updated packages for breaking changes
3. **Code style violations**: Run `vendor/bin/phpcbf` to auto-fix, then manually fix remaining issues
