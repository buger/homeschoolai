#!/bin/bash
# Minimal JavaScript/TypeScript Quality Check Hook for HTMX-focused Laravel

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "Running Minimal JS/TS Quality Checks (HTMX-focused)..."

# Check if there are JS/TS files to check
js_files=""
test_files=""
if [ ! -z "$CLAUDE_FILE_PATHS" ]; then
    for file in $CLAUDE_FILE_PATHS; do
        if [[ "$file" == *.js || "$file" == *.ts || "$file" == *.tsx || "$file" == *.jsx ]]; then
            js_files="$js_files $file"
            if [[ "$file" == *test* || "$file" == *spec* || "$file" == tests/* ]]; then
                test_files="$test_files $file"
            fi
        fi
    done
fi

if [ -z "$js_files" ]; then
    echo "No JavaScript/TypeScript files to check"
    exit 0
fi

echo "Checking files: $js_files"

# TypeScript Type Check for test files only
if [ ! -z "$test_files" ]; then
    echo "Running TypeScript type check for test files..."
    if ! npm run type-check --silent 2>/dev/null; then
        echo -e "${YELLOW}TypeScript type check failed - please fix test types${NC}"
    else
        echo -e "${GREEN}TypeScript type check passed${NC}"
    fi
fi

# Basic JavaScript syntax check
for file in $js_files; do
    if [[ "$file" == *.js ]] && [ -f "$file" ]; then
        echo "Checking JavaScript syntax for $file..."
        if ! node -c "$file" 2>/dev/null; then
            echo -e "${RED}JavaScript syntax error in $file${NC}"
            exit 1
        fi
    fi
done

# HTMX-focused checks (skip test files)
for file in $js_files; do
    if [ -f "$file" ] && [[ "$file" != *test* && "$file" != *spec* ]]; then
        if grep -q "addEventListener\|querySelector\|getElementById" "$file" 2>/dev/null; then
            echo -e "${YELLOW}Found DOM manipulation in $file - consider HTMX attributes${NC}"
        fi
        if grep -q "jQuery" "$file" 2>/dev/null; then
            echo -e "${YELLOW}Found jQuery in $file - HTMX can handle most cases${NC}"
        fi
        if grep -q "eval(" "$file" 2>/dev/null; then
            echo -e "${RED}Dangerous eval() found in $file${NC}"
            exit 1
        fi
    fi
done

echo -e "${GREEN}All checks passed - HTMX simplicity maintained${NC}"
