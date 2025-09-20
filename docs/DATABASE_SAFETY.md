# Database Safety Documentation

## Overview

This document describes the multiple layers of protection implemented to prevent accidental database wipes during testing. These measures ensure that development data is never lost when running tests.

## Protection Layers

### 1. Environment File Structure

The project uses three separate environment files:

- **`.env`** - Contains a safe placeholder database name: `learning_app_production_DO_NOT_USE`
  - This prevents accidental use of production settings
  - Name explicitly warns developers not to use it
  
- **`.env.local`** - Development environment (git-ignored)
  - Contains actual development database: `learning_app`
  - Used by `make dev` command for local development
  
- **`.env.testing`** - Testing environment
  - Contains test database: `learning_app_test`
  - Automatically used by all test commands

### 2. Safe Test Runner Script

The `scripts/safe-test.sh` script provides critical protection:

```bash
# Critical safety check
if [ "$DB_DATABASE" == "learning_app" ]; then
    echo "❌ CRITICAL ERROR: Attempting to run tests on development database!"
    exit 1
fi

# Force test environment
export APP_ENV=testing
export DB_DATABASE=learning_app_test
```

Features:
- Prevents tests from running on development database
- Forces test environment variables
- Verifies test database exists before running
- Shows clear configuration before running tests

### 3. Makefile Safety

All test commands in the Makefile are protected:

```makefile
test-unit: ## Run PHPUnit tests (SAFE - uses test database)
    @export $(cat .env.testing | grep -v '^#' | xargs) && ./scripts/safe-test.sh
```

- Exports `.env.testing` variables before running tests
- Uses safe test runner for all PHPUnit tests
- Clear documentation about safety in help text

### 4. Composer Script Protection

The `composer.json` test script includes safety measures:

```json
"test": [
    "@putenv APP_ENV=testing",
    "@putenv DB_DATABASE=learning_app_test",
    "./scripts/safe-test.sh"
]
```

- Sets environment variables before test execution
- Uses safe test runner script
- Ensures consistent environment across all test methods

### 5. E2E Test Isolation

The `scripts/run-e2e-tests.sh` script provides complete isolation:

```bash
# Force PostgreSQL test database
export APP_ENV=testing
export DB_DATABASE=learning_app_test

# Start isolated test server
APP_ENV=testing DB_DATABASE=learning_app_test php artisan serve --port=18001
```

Features:
- Uses separate port (18001) for test server
- Explicitly sets test database
- Cleans up on exit with trap function

## Verification

### Running the Safety Verification Script

A comprehensive verification script is available:

```bash
./scripts/verify-test-safety.sh
```

This script checks:
- ✅ All environment files are configured correctly
- ✅ Test scripts force testing environment
- ✅ Makefile commands use safe runners
- ✅ Composer scripts have protection
- ✅ RefreshDatabase tests are protected

### Manual Verification Commands

```bash
# Check what database would be used (should fail with safety error)
./scripts/safe-test.sh --help

# Check with proper environment (should show test database)
source .env.testing && ./scripts/safe-test.sh --help

# Verify Makefile test command
make test-unit --dry-run

# Check E2E test environment
npm run test:e2e -- --help
```

## Common Commands

### Safe Testing Commands

```bash
# Run all tests safely
make test

# Run PHPUnit tests
make test-unit
source .env.testing && ./scripts/safe-test.sh

# Run specific test
make test-unit TEST="--filter test_name"

# Run E2E tests
npm run test:e2e
```

### Development Commands

```bash
# Start development server (uses .env.local)
make dev

# Check current environment
php artisan env

# View database configuration
php artisan config:show database.connections.pgsql
```

## Troubleshooting

### "Attempting to run tests on development database" Error

This error means the safety check is working! Solutions:

1. Use the safe test runner: `./scripts/safe-test.sh`
2. Use Makefile commands: `make test-unit`
3. Source test environment first: `source .env.testing`

### Tests Not Finding Database

Ensure the test database exists:

```bash
# Check if test database exists
PGPASSWORD=12345 psql -h 127.0.0.1 -U laravel -lqt | grep learning_app_test

# Create test database if needed
PGPASSWORD=12345 createdb -h 127.0.0.1 -U laravel learning_app_test
```

### Environment Variable Conflicts

If you have environment variables set in your shell:

```bash
# Check current environment
env | grep DB_DATABASE

# Unset conflicting variables
unset DB_DATABASE
unset APP_ENV

# Run tests with clean environment
env -i HOME=$HOME PATH=$PATH ./scripts/safe-test.sh
```

## Best Practices

1. **Always use provided test commands** - Never run `php artisan test` directly
2. **Use Makefile targets** - They include all safety measures
3. **Keep .env.local separate** - Never commit development credentials
4. **Verify before bulk operations** - Run safety check script periodically
5. **Document new test scripts** - Include safety measures in any new test runners

## Summary

The multi-layered protection system ensures:

- ❌ **Cannot** accidentally run tests on development database
- ✅ **Must** explicitly use test environment
- 🛡️ **Protected** by multiple independent safety checks
- 📋 **Clear** error messages when protection triggers
- 🔄 **Consistent** behavior across all test methods

Your development data is safe!