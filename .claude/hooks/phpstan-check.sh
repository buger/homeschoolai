#!/bin/bash
# PHPStan Static Analysis Hook for Claude Code
# Runs PHPStan to check for type errors

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "🔍 Running PHPStan Static Analysis..."

# Check if PHPStan is available
if [ ! -f "./vendor/bin/phpstan" ]; then
    echo -e "${YELLOW}⚠️  PHPStan not found. Skipping static analysis.${NC}"
    exit 0
fi

# Check if there are PHP files to analyze
php_files=""
if [ ! -z "$CLAUDE_FILE_PATHS" ]; then
    for file in $CLAUDE_FILE_PATHS; do
        if [[ "$file" == *.php ]]; then
            php_files="$php_files $file"
        fi
    done
fi

# If no PHP files were modified, skip PHPStan
if [ -z "$php_files" ]; then
    echo "ℹ️  No PHP files modified, skipping PHPStan"
    exit 0
fi

echo "📁 Analyzing files:$php_files"

# Run PHPStan on the entire app directory (not just modified files)
# This ensures we catch any issues that may have been introduced by changes
echo "🧮 Running PHPStan analysis..."
if ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress --quiet; then
    echo -e "${GREEN}✅ PHPStan analysis passed${NC}"
else
    echo -e "${RED}❌ PHPStan found type errors${NC}"
    echo ""
    echo -e "${YELLOW}💡 To fix PHPStan issues:${NC}"
    echo "1. Run: composer run phpstan"
    echo "2. Fix the reported type errors"
    echo "3. Re-run the analysis to verify fixes"
    echo ""
    echo -e "${YELLOW}ℹ️  Common fixes:${NC}"
    echo "- Add proper return type hints to methods"
    echo "- Use union types (e.g., View|RedirectResponse) for methods that can return multiple types"
    echo "- Add proper type hints to method parameters"
    echo "- Fix undefined method calls by checking instance types"
    echo ""
    exit 1
fi

echo -e "${GREEN}🎉 PHPStan static analysis passed!${NC}"