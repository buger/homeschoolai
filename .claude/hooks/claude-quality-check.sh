#!/bin/bash

# Quality checks for Claude Code hooks (non-blocking version)
# This runs similar checks as git pre-commit but doesn't exit with error codes

echo "🔍 Running Claude Code quality checks..."

# Run PHP linter
echo "📝 Running PHP linter..."
if ! ./vendor/bin/pint --test; then
    echo "❌ PHP linting issues found. Auto-fixing..."
    ./vendor/bin/pint
    echo "✅ PHP code has been auto-formatted"
else
    echo "✅ PHP linting passed!"
fi

# Run PHPStan static analysis (non-blocking)
echo "🔍 Running PHPStan static analysis..."
if [ -f "./vendor/bin/phpstan" ]; then
    if ! ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress; then
        echo "⚠️  PHPStan found type errors (non-blocking for Claude Code)"
        echo "💡 Run 'composer run phpstan' to see detailed errors"
        echo "💡 Consider fixing these before committing to git"
    else
        echo "✅ PHPStan analysis passed!"
    fi
else
    echo "⚠️  PHPStan not installed, skipping static analysis"
fi

# Run lint-staged for JS/TS files (non-blocking)  
echo "🎨 Running lint-staged for JS/TS files..."
if ! npx lint-staged; then
    echo "⚠️  JavaScript/TypeScript linting issues found (non-blocking)"
else
    echo "✅ JavaScript/TypeScript linting passed!"
fi

echo "✅ Claude Code quality checks completed!"

# Always exit with 0 to be non-blocking for Claude Code
exit 0