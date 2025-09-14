# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a comprehensive Laravel-based homeschool learning management application that integrates with Supabase for authentication and database, uses HTMX for dynamic frontend interactions, and includes Playwright for E2E testing.

### Core Features (Completed)

- **Multi-child Management**: Handle multiple children with different ages and independence levels
- **Curriculum Planning**: Subjects → Units → Topics → Sessions workflow
- **Flexible Scheduling**: Time blocks with commitment types (fixed/preferred/flexible)
- **Quality Heuristics**: Age-appropriate scheduling validation and recommendations
- **Spaced Repetition Reviews**: Automatic review scheduling based on performance
- **Parent/Child Views**: Different interfaces based on independence levels
- **Calendar Integration**: ICS import for external classes and activities
- **Catch-up System**: Automated handling of missed sessions
- **Performance Optimization**: Comprehensive caching system
- **Mobile Responsive**: Works on all devices

## Common Development Commands

### Running the Application

```bash
# Start development server with all services
composer run dev

# Or run services individually
php artisan serve              # Laravel server
npm run dev                     # Vite dev server for assets
php artisan queue:listen        # Queue worker
php artisan pail               # Log viewer
```

### Testing

#### **IMPORTANT: PostgreSQL-Only Setup (No SQLite)**
This project uses **PostgreSQL exclusively** via local Supabase. All SQLite references have been removed.

#### **Prerequisites for Testing**
```bash
# 1. Ensure Supabase is running locally
supabase start

# 2. Verify Supabase status
supabase status  # Should show PostgreSQL on port 54322
```

#### **🚨 CRITICAL: PHP Unit Tests - Database Protection Required**

**DANGER**: PHP tests use `RefreshDatabase` trait which **COMPLETELY WIPES THE DATABASE**!
**NEVER run tests without proper database isolation or you will lose all development data!**

```bash
# ✅ ALWAYS USE THIS METHOD (SAFE):
./scripts/safe-test.sh                    # Run all tests safely
./scripts/safe-test.sh --filter TestName  # Run specific test safely
./scripts/safe-test.sh --coverage         # Run with coverage

# Or use Makefile commands (also safe):
make test-unit                            # Runs safe-test.sh internally
make test-unit-coverage                   # Safe coverage testing

# ❌ NEVER RUN THESE (WILL WIPE YOUR DATABASE):
php artisan test                          # DANGEROUS - might use wrong database
artisan test                               # DANGEROUS
vendor/bin/phpunit                        # DANGEROUS
```

**Safety Features**:
- `safe-test.sh` forces use of `learning_app_test` database
- `TestCase.php` throws exception if wrong database detected
- `phpunit.xml` has forced test database configuration
- Development database (`learning_app`) is protected from accidental wipes

#### **E2E Tests (Infrastructure Working)**
```bash
# Run full E2E test suite with PostgreSQL isolation
npm run test:e2e              # Simplified runner, no backup files created

# Run E2E tests with browser visible
npm run test:e2e:headed       

# Run specific test or pattern
npm run test:e2e -- --grep "should display registration form"
npm run test:e2e -- <file>    

# Standard Playwright Tests (not recommended - use test:e2e instead)
npm run test                   # No database isolation
npm run test:headed           # Run tests with browser UI
npm run test:ui               # Interactive test runner

# Full Test Suite
npm run test:all              # Runs lint, type-check, and tests
```

### Code Quality

```bash
npm run lint                  # Run ESLint
npm run lint:fix             # Auto-fix ESLint issues
npm run format               # Format code with Prettier
npm run type-check           # TypeScript type checking
./vendor/bin/pint            # Format PHP code
```

### Build & Deploy

```bash
npm run build                # Build frontend assets
php artisan config:cache     # Cache configuration
php artisan route:cache      # Cache routes
```

### Database Operations

```bash
php artisan migrate          # Run migrations
php artisan migrate:fresh    # Drop all tables and re-run migrations
php artisan db:seed         # Seed the database
```

## Testing Infrastructure Details

### **Database Configuration**
- **Production**: Remote Supabase (configured in `.env`)
- **Development**: Local Supabase on port 54322 (PostgreSQL)
- **Testing**: Same local Supabase, database reset between test runs
- **NO SQLite**: All SQLite references removed from codebase

### **Test Environment Configuration**
The `.env.testing` file is properly configured with:
- `DB_CONNECTION=pgsql` 
- `DB_PORT=54322` (local Supabase PostgreSQL)
- `CACHE_STORE=array` (prevents database cache issues)
- `SESSION_DRIVER=file` (for E2E test persistence)

### **E2E Test Infrastructure Improvements**
1. **Simplified test runner** (`scripts/run-e2e-tests.sh`):
   - No more environment backup files created
   - Explicit PostgreSQL configuration
   - Proper server health checks
   - Clean database reset via `supabase db reset`

2. **Modal fixes implemented**:
   - Standardized z-index: overlays at `z-40`, content at `z-50`
   - Added `data-testid` attributes for reliable test targeting
   - Test helper classes for robust interactions

3. **Server configuration**:
   - Tests run on port 8001
   - Proper Laravel server startup with environment variables
   - Automatic cleanup on test completion

## Architecture Overview

### Tech Stack

- **Backend**: Laravel 12 with PHP 8.2+
- **Database/Auth**: Supabase (PostgreSQL with Row Level Security)
- **Frontend**: HTMX for interactivity, Alpine.js for client state, Tailwind CSS
- **Testing**: PHPUnit for backend, Playwright for E2E tests
- **Dev Tools**: Vite for asset bundling, Husky for git hooks

### Key Directories

- `app/Services/SupabaseClient.php`: Supabase integration service
- `app/Http/Controllers/`: TaskController and AuthController handle main functionality
- `app/Models/`: Task model with Supabase-backed operations
- `app/Http/Middleware/SupabaseAuth.php`: Custom auth middleware for Supabase
- `resources/views/`: Blade templates with HTMX attributes
- `tests/e2e/`: Playwright E2E test specs

### Supabase Integration Pattern

The app uses a custom SupabaseClient service that:

1. Handles authentication via Supabase Auth API
2. Provides a query builder for database operations
3. Stores auth tokens in Laravel sessions
4. Uses Row Level Security policies for data access

### HTMX Implementation

- All CRUD operations use HTMX attributes (`hx-get`, `hx-post`, etc.)
- Partial views in `resources/views/tasks/partials/` for dynamic updates
- Custom event triggers for toast notifications
- CSRF tokens automatically included via JavaScript configuration

### Authentication Flow

1. User credentials sent to Supabase Auth
2. Access token stored in Laravel session
3. SupabaseAuth middleware validates token on protected routes
4. User context available via session data

## Environment Configuration

Required `.env` variables:

```
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_ANON_KEY=your-anon-key
SUPABASE_SERVICE_KEY=your-service-key
```

## Testing Strategy

### Test Types

- **Unit Tests**: Test individual PHP classes and methods
- **Feature Tests**: Test API endpoints and controller actions
- **E2E Tests**: Full user journey testing with Playwright

### E2E Testing with Database Isolation

**Recommended**: Use `npm run test:e2e` for all E2E testing. This provides:

#### **Complete Database Isolation**:

- Uses **PostgreSQL test database** that gets reset between runs
- **Full Supabase authentication** compatibility
- **Zero interference** with development data
- **Automatic environment switching** and cleanup

#### **Multi-Environment Setup**:

- **🌍 Production**: Remote Supabase (`.env`)
- **🏠 Development**: Local Supabase with persistent data (`.env.local`)
- **🧪 Testing**: PostgreSQL test database, completely isolated (`.env.testing`)

#### **Prerequisites for E2E Tests**:

```bash
# 1. Start local Supabase
supabase start

# 2. Run E2E tests (handles environment automatically)
npm run test:e2e
```

#### **What the E2E Test Runner Does**:

1. Backs up your current environment
2. Switches to testing environment (PostgreSQL)
3. Connects to local Supabase for authentication
4. Drops and recreates test database
5. Runs fresh Laravel migrations
6. Starts isolated Laravel test server
7. Runs Playwright tests with full integration
8. Automatically restores original environment
9. Cleans up test server and data

#### **Environment Switching**:

```bash
./env-switch.sh              # Show current environment
./env-switch.sh local        # Switch to local development
./env-switch.sh production   # Switch to production
./env-switch.sh testing      # Switch to testing (manual)
```

## Milestone 6: Polish & Final Features

### ICS Calendar Import
- **Service**: `App\Services\IcsImportService` - Full ICS parsing with RRULE support
- **Controller**: `App\Http\Controllers\IcsImportController` - File upload and URL import
- **Routes**: `/calendar/import/*` - Preview, import from file, import from URL
- **Features**: Conflict detection, recurring event expansion, commitment type assignment

### Quality Heuristics System  
- **Service**: `App\Services\QualityHeuristicsService` - Age-appropriate validation
- **Integration**: Planning Board quality analysis
- **Features**: Session length validation, cognitive load analysis, capacity warnings
- **Age Groups**: Different limits for 5-8, 9-12, 13-15, 16+ year olds

### Performance Optimizations
- **Service**: `App\Services\CacheService` - Comprehensive caching layer
- **Features**: User data caching, child-specific caches, automatic invalidation
- **Optimizations**: Dashboard caching, query optimization, cache warming

### Comprehensive Testing
- **E2E Tests**: Complete user journey testing with Playwright
- **Test Files**: `homeschool-planning.spec.ts`, `review-system.spec.ts`
- **Coverage**: Planning workflow, parent/child views, session management, reviews

### Documentation
- **Setup Guide**: `SETUP_GUIDE.md` - Complete setup and user guide
- **Troubleshooting**: `TROUBLESHOOTING.md` - Common issues and solutions
- **Features**: Age-appropriate recommendations, best practices, advanced features

## Key Development Patterns

1. **Partial Rendering**: Return partial Blade views for HTMX requests
2. **Event-Driven UI**: Use HTMX events to trigger toast notifications
3. **Service Pattern**: Business logic in service classes, not controllers
4. **Query Builder**: Custom Supabase query builder mimics Laravel's Eloquent
5. **Session-Based Auth**: Supabase tokens stored in Laravel sessions
6. **Caching Strategy**: Multi-layer caching with automatic invalidation
7. **Quality Validation**: Age-appropriate heuristics throughout the system

## Debugging

### E2E Test Debugging with Screenshot Analysis

**Powerful debugging technique**: When E2E tests fail, Playwright automatically saves screenshots. Claude Code can read and analyze these screenshots to understand what went wrong!

#### How to Debug Failing E2E Tests

1. **Run the failing test**:
```bash
npm run test:e2e -- --grep "test name"
```

2. **Locate the failure screenshot**:
```bash
ls test-results/*/test-failed-*.png
```

3. **Read the screenshot with Claude Code**:
```bash
# Claude Code can visually analyze the screenshot
cat test-results/*/test-failed-1.png
```

4. **Example debugging session**:
- Test was failing with "No topics yet" message
- Screenshot showed the unit page loaded correctly but topics weren't appearing
- This visual evidence led us to discover a field name mismatch (`$topic->name` vs `$topic->title`)
- Fixed the template and tests passed!

#### Additional Debugging Tools

- **Error context files**: `test-results/*/error-context.md` contains DOM structure at failure
- **Playwright MCP**: Can use Playwright tools for interactive debugging
- **Server logs during tests**: Check Laravel logs while tests run for server-side errors
- **Modal force-close logs**: Console shows when modals are force-closed, indicating timing issues

### Traditional Debugging

For detailed debugging instructions, see [DEBUG_GUIDE.md](./DEBUG_GUIDE.md). Key points:

- Always return structured errors from external service calls
- Use standalone test scripts to verify API integrations
- Monitor Laravel logs: `tail -f storage/logs/laravel.log`
- Run server with debug mode: `APP_DEBUG=true php artisan serve`

## Current Test Suite Status (As of Latest Run)

### **✅ Working Tests**
- **Authentication**: 100% passing (6/6 tests)
  - Registration, login, validation, navigation all working
- **Basic Dashboard**: Loading and displaying correctly
- **Database Operations**: User creation, data persistence working

### **🔧 Tests Needing Fixes**
- **Homeschool Planning Workflow**: Button/modal interaction issues
- **Review System**: Missing UI elements, setup failures
- **Parent/Child View Switching**: Dynamic content loading issues

### **Test Execution Commands**
```bash
# Quick auth test (reliable)
npm run test:e2e -- --grep "Authentication Flow"

# Full suite (set longer timeout for complex tests)
npm run test:e2e  # Uses 10min timeout by default

# Debug failing test
npm run test:e2e:headed -- --grep "specific test name"
```

### **Known Issues & Workarounds**
1. **Modal Blocking**: Some tests fail due to modals overlaying buttons
   - Workaround: Tests need explicit modal dismissal
2. **Test Data Creation**: Complex workflows need robust data setup
   - Workaround: Run simpler tests first to verify infrastructure
3. **Timeout Issues**: Some tests take longer than expected
   - Workaround: Increase timeout or run specific tests

## Development Server Auto-Reload

**Important**: PHP's built-in server (`php artisan serve`) **automatically** detects file changes and reloads on the next request. You do **NOT** need to restart the server when you change PHP code.

However, you need to manually restart if you:

- Change `.env` variables (run `php artisan config:clear` first)
- Modify configuration files in `config/`
- Install new Composer packages
- Change route definitions (clear route cache with `php artisan route:clear`)

For frontend assets (CSS/JS), Vite's dev server (`npm run dev`) provides hot module replacement (HMR) and auto-reloads.

## 🔧 Claude Code Hooks (Automatic Quality Checks)

Automatic code quality checks and testing are configured via Claude Code hooks in `.claude/hooks.json`.

### What Happens Automatically

**When you modify PHP files:**

- ✅ PHP syntax validation (`php -l`)
- 🎨 Laravel Pint code formatting (auto-fix)
- 🔒 Basic security checks (XSS, debug statements)
- 🧪 Relevant PHP unit tests

**When you modify JS/TS files:**

- ✅ TypeScript type checking (for test files)
- 🔧 Basic JavaScript syntax validation
- 🎯 HTMX-focused checks (warns about DOM manipulation)
- 🔒 Essential security checks (eval usage)
- 🧪 JavaScript tests (if needed)

**For critical changes (controllers, routes, middleware):**

- 🧪 E2E tests run in background

### Hook Management

```bash
# View hook configuration
cat .claude/hooks.json

# Test hooks manually
.claude/hooks/php-quality-check.sh
.claude/hooks/js-quality-check.sh
.claude/hooks/test-runner.sh

# Disable hooks temporarily
mv .claude/hooks.json .claude/hooks.json.disabled

# Re-enable hooks
mv .claude/hooks.json.disabled .claude/hooks.json
```

### Hook Requirements

- **PHP Hooks**: Laravel Pint (✅ installed), PHPUnit
- **JS/TS Hooks**: Node.js (✅ installed), TypeScript (✅ installed for testing)
- **Test Hooks**: Playwright (✅ installed), Laravel testing framework

### Hook Configuration Details

- **Timeouts**: PHP (30s), JS/TS (20s - minimal), Tests (60s)
- **Background execution**: Tests run in background for critical changes
- **Auto-fix**: Most issues are automatically fixed when possible
- **Error handling**: Non-critical issues don't block development

See `.claude/README.md` for detailed hook documentation and customization options.

## Internationalization (i18n)

### Adding New Languages

1. **Create translation file**: Copy `lang/en.json` to `lang/{locale}.json` (e.g., `lang/es.json` for Spanish)
2. **Translate all keys**: Keep the same keys, translate the values
3. **Update language switcher**: Add language to `resources/views/components/language-switcher.blade.php`:
```php
'es' => [
    'name' => 'Spanish',
    'native' => 'Español',
    'flag' => '🇪🇸'
]
```
4. **Update controller**: Add locale to validation in `app/Http/Controllers/LocaleController.php`

### Writing Localized Code

**Blade Templates:**
```php
{{ __('Welcome back') }}                    // Simple translation
{{ __('welcome_user', ['name' => $name]) }} // With parameters
```

**PHP Controllers:**
```php
return response()->json(['message' => __('Success message')]);
```

**JavaScript:**
```javascript
window.__('Welcome back')                   // Simple translation
window.__('welcome_user', {name: 'John'})   // With parameters
```

**Best Practices:**
- Always use translation keys for user-facing text
- Keep keys descriptive and consistent
- Never hardcode text in templates or JavaScript
- Test all features in multiple languages
