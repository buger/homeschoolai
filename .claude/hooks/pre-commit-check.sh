#!/bin/bash

# Pre-commit quality checks for Claude Code Stop hooks
# This runs the same checks as our git pre-commit hook

set -e  # Exit on any error

echo "🔍 Running pre-commit quality checks..."

# Run PHP linter
echo "📝 Running PHP linter..."
if ! ./vendor/bin/pint --test; then
    echo "❌ PHP linting failed. Auto-fixing..."
    ./vendor/bin/pint
    echo "✅ PHP code has been auto-formatted"
fi

# Run PHPStan static analysis
echo "🔍 Running PHPStan static analysis..."
if [ -f "./vendor/bin/phpstan" ]; then
    if ! ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress; then
        echo "❌ PHPStan found type errors. Please fix them."
        echo "💡 Run 'composer run phpstan' to see detailed errors"
        exit 1
    fi
    echo "✅ PHPStan analysis passed!"
else
    echo "⚠️  PHPStan not installed, skipping static analysis"
fi

# Run lint-staged for JS/TS files  
echo "🎨 Running lint-staged for JS/TS files..."
if ! npx lint-staged; then
    echo "❌ JavaScript/TypeScript linting failed"
    exit 1
fi

echo "✅ All pre-commit checks passed!"