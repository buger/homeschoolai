#!/bin/bash

# Pre-commit quality checks with FULL output for Claude Code hooks
# This version shows all errors and blocks on failures

echo "🔍 Running pre-commit quality checks with full output..."

# Track if any checks fail
has_errors=0

# Run PHP linter
echo "📝 Running PHP linter (Pint)..."
if ! ./vendor/bin/pint --test; then
    echo "" >&2
    echo "❌ PHP LINTING FAILED - Running auto-fix and showing changes:" >&2
    ./vendor/bin/pint
    echo "" >&2
    echo "✅ PHP code has been auto-formatted. Please review the changes." >&2
    has_errors=1
else
    echo "✅ PHP linting passed!"
fi

# Run PHPStan static analysis with FULL output
echo ""
echo "🔍 Running PHPStan static analysis..."
if [ -f "./vendor/bin/phpstan" ]; then
    # Run PHPStan and capture both stdout and stderr
    phpstan_output=$(./vendor/bin/phpstan analyse --memory-limit=512M 2>&1)
    phpstan_exit_code=$?
    
    # Always show the output (send to stderr so it appears in hook feedback)
    echo "$phpstan_output" >&2
    
    if [ $phpstan_exit_code -ne 0 ]; then
        echo "" >&2
        echo "❌ PHPSTAN FOUND TYPE ERRORS!" >&2
        echo "Please fix the errors above before proceeding." >&2
        has_errors=1
    else
        echo "✅ PHPStan analysis passed!"
    fi
else
    echo "⚠️  PHPStan not installed, skipping static analysis"
fi

# Run lint-staged for JS/TS files  
echo ""
echo "🎨 Running lint-staged for JS/TS files..."
if ! npx lint-staged; then
    echo "" >&2
    echo "❌ JAVASCRIPT/TYPESCRIPT LINTING FAILED!" >&2
    echo "Please fix the errors above before proceeding." >&2
    has_errors=1
else
    echo "✅ JavaScript/TypeScript linting passed!"
fi

echo ""
if [ $has_errors -eq 1 ]; then
    echo "❌ PRE-COMMIT CHECKS FAILED - Please fix the errors above!" >&2
    # Exit code 2 blocks Claude Code Stop hooks
    exit 2
else
    echo "✅ All pre-commit checks passed!"
    exit 0
fi