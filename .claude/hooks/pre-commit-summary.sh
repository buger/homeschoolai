#!/bin/bash

# Pre-commit quality checks with summary output for Claude Code hooks

# Don't use set -e since we want to control exit codes carefully

echo "🔍 Running pre-commit quality checks..."

# Run PHP linter
echo "📝 Running PHP linter..."
if ! ./vendor/bin/pint --test >/dev/null 2>&1; then
    echo "❌ PHP linting failed. Auto-fixing..."
    ./vendor/bin/pint
    echo "✅ PHP code has been auto-formatted"
else
    echo "✅ PHP linting passed!"
fi

# Run PHPStan static analysis with summary
echo "🔍 Running PHPStan static analysis..."
if [ -f "./vendor/bin/phpstan" ]; then
    phpstan_output=$(./vendor/bin/phpstan analyse --memory-limit=512M --no-progress 2>&1 || true)
    error_count=$(echo "$phpstan_output" | grep -o '\[ERROR\] Found [0-9]* errors' | grep -o '[0-9]*' || echo "0")
    
    if [ "$error_count" -gt 0 ]; then
        # Output to stderr for Claude Code to display
        echo "⚠️ PHPStan found $error_count type errors (non-blocking)" >&2
        echo "💡 Run 'composer run phpstan' locally to see detailed errors" >&2
        echo "💡 Most common issues:" >&2
        echo "$phpstan_output" | grep -E "property\.notFound|method\.notFound|arguments\.count|argument\.type" | head -5 >&2
        # Exit 0 to warn but not block
        # Change to exit 2 when you want to enforce blocking
        exit 0
    else
        echo "✅ PHPStan analysis passed!"
    fi
else
    echo "⚠️  PHPStan not installed, skipping static analysis"
fi

# Run lint-staged for JS/TS files  
echo "🎨 Running lint-staged for JS/TS files..."
if ! npx lint-staged >/dev/null 2>&1; then
    echo "❌ JavaScript/TypeScript linting failed" >&2
    # Exit code 2 blocks Claude Code Stop hooks
    exit 2
else
    echo "✅ JavaScript/TypeScript linting passed!"
fi

echo "✅ All pre-commit checks passed!"