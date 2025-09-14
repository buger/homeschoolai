# 🚨 CRITICAL: Testing Safety Documentation

## ⚠️ WARNING: Tests Can Wipe Your Database!

This Laravel application uses the `RefreshDatabase` trait in tests, which **COMPLETELY DROPS AND RECREATES ALL DATABASE TABLES**. Running tests incorrectly **WILL DELETE ALL YOUR DATA**.

## Database Configuration

| Database | Purpose | Data Status |
|----------|---------|-------------|
| `learning_app` | Development work | **MUST BE PROTECTED** |
| `learning_app_test` | Testing only | Wiped on each test run |

## ✅ SAFE Testing Methods

### Method 1: Use the Safe Test Script (RECOMMENDED)
```bash
./scripts/safe-test.sh                    # Run all tests
./scripts/safe-test.sh --filter TestName  # Run specific test
./scripts/safe-test.sh --coverage         # Run with coverage
```

### Method 2: Use Makefile Commands
```bash
make test-unit                            # Runs safe-test.sh internally
make test-unit-coverage                   # Safe coverage testing
```

## ❌ DANGEROUS Commands - NEVER RUN THESE

```bash
# ALL OF THESE CAN WIPE YOUR DEVELOPMENT DATABASE:
php artisan test                          # NO DATABASE PROTECTION
artisan test                               # NO DATABASE PROTECTION
vendor/bin/phpunit                        # NO DATABASE PROTECTION
phpunit                                    # NO DATABASE PROTECTION

# Even with APP_ENV - still risky if misconfigured:
APP_ENV=testing php artisan test          # RISKY - might not load .env.testing
```

## How Protection Works

### 1. Safe Test Script (`scripts/safe-test.sh`)
- **Validates** database name isn't `learning_app`
- **Forces** `DB_DATABASE=learning_app_test`
- **Sets** all required environment variables
- **Creates** test database if it doesn't exist
- **Displays** configuration before running

### 2. TestCase.php Protection
```php
// Throws exception if wrong database detected
if ($database !== 'learning_app_test') {
    throw new \Exception("CRITICAL ERROR: Tests attempting to run on non-test database!");
}
```

### 3. phpunit.xml Configuration
```xml
<!-- Forces test database with force="true" attribute -->
<env name="DB_DATABASE" value="learning_app_test" force="true"/>
```

### 4. Makefile Integration
All `make test*` commands use `safe-test.sh` automatically.

## If Your Database Gets Wiped (Recovery)

### Step 1: Don't Panic
The structure can be restored, but data may be lost unless you have backups.

### Step 2: Restore Database Structure
```bash
# Recreate all tables
php artisan migrate

# If you have seeders, run them
php artisan db:seed

# Or use fresh command
php artisan migrate:fresh --seed
```

### Step 3: Restore Data
- Check if you have database backups
- Check if you have SQL dumps
- Manually recreate critical data

### Step 4: Prevent Future Incidents
```bash
# Create a database backup before any risky operations
pg_dump -U laravel -h localhost learning_app > backup_$(date +%Y%m%d_%H%M%S).sql

# Set up automated backups
# Add to crontab: 0 */6 * * * pg_dump -U laravel learning_app > ~/backups/learning_app_$(date +\%Y\%m\%d_\%H\%M\%S).sql
```

## Best Practices

1. **Always use `./scripts/safe-test.sh`** for running tests
2. **Never modify phpunit.xml database settings**
3. **Create regular database backups**
4. **Use separate PostgreSQL users** for dev and test databases (advanced)
5. **Review test output** - it shows which database is being used

## For CI/CD Pipelines

```yaml
# GitHub Actions example
- name: Run tests safely
  env:
    DB_DATABASE: learning_app_test  # Explicitly set test database
    APP_ENV: testing
  run: |
    ./scripts/safe-test.sh
```

## Quick Reference Card

```
✅ SAFE:  ./scripts/safe-test.sh
✅ SAFE:  make test-unit
❌ DANGER: php artisan test
❌ DANGER: vendor/bin/phpunit
```

## Emergency Contact

If you accidentally wipe your database:
1. Stop all running processes
2. Check `storage/logs/laravel.log` for any errors
3. Run recovery steps above
4. Consider implementing database replication for instant recovery

---

**Remember**: When in doubt, use `./scripts/safe-test.sh`. It's better to be safe than sorry!