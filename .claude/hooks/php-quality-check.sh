#!/bin/bash
# PHP Quality Check Hook for Claude Code
# Runs after PHP files are modified

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "🔍 Running PHP Quality Checks..."

# Check if there are PHP files to check
php_files=""
if [ ! -z "$CLAUDE_FILE_PATHS" ]; then
    for file in $CLAUDE_FILE_PATHS; do
        if [[ "$file" == *.php ]]; then
            php_files="$php_files $file"
        fi
    done
fi

if [ -z "$php_files" ]; then
    echo "ℹ️  No PHP files to check"
    exit 0
fi

echo "📁 Checking files:$php_files"

# 1. PHP Syntax Check
echo "🔧 Running PHP syntax check..."
for file in $php_files; do
    if [ -f "$file" ]; then
        if ! php -l "$file" > /dev/null 2>&1; then
            echo -e "${RED}❌ PHP syntax error in $file${NC}"
            php -l "$file"
            exit 1
        fi
    fi
done
echo -e "${GREEN}✅ PHP syntax check passed${NC}"

# 2. Laravel Pint (Code Style)
echo "🎨 Running Laravel Pint code formatting..."
if ! ./vendor/bin/pint --test $php_files 2>/dev/null; then
    echo -e "${YELLOW}📝 Code style issues found, auto-fixing...${NC}"
    ./vendor/bin/pint $php_files
    echo -e "${GREEN}✅ Code style fixed with Laravel Pint${NC}"
else
    echo -e "${GREEN}✅ Code style check passed${NC}"
fi

# 3. Basic Security Checks
echo "🔒 Running basic security checks..."
for file in $php_files; do
    if [ -f "$file" ]; then
        # Check for common security issues
        if grep -q "var_dump\|dd(" "$file" 2>/dev/null; then
            echo -e "${YELLOW}⚠️  Debug statements found in $file${NC}"
        fi
        
        if grep -q "echo.*\$_" "$file" 2>/dev/null; then
            echo -e "${RED}❌ Potential XSS vulnerability in $file${NC}"
            echo "Found direct output of user input. Use proper escaping."
            exit 1
        fi
    fi
done
echo -e "${GREEN}✅ Basic security check passed${NC}"

# 4. PHPStan Static Analysis (on modified files only)
echo "🧮 Running PHPStan static analysis on modified files..."
if [ -f "./vendor/bin/phpstan" ]; then
    # Filter valid PHP files
    valid_files=""
    for file in $php_files; do
        if [ -f "$file" ]; then
            valid_files="$valid_files $file"
        fi
    done
    
    if [ ! -z "$valid_files" ]; then
        if ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress --quiet $valid_files; then
            echo -e "${GREEN}✅ PHPStan analysis passed for modified files${NC}"
        else
            echo -e "${RED}❌ PHPStan found type errors in modified files${NC}"
            echo ""
            echo -e "${YELLOW}💡 Run 'composer run phpstan' to see all errors${NC}"
            echo -e "${YELLOW}💡 Or run PHPStan on specific files: ./vendor/bin/phpstan analyse$valid_files${NC}"
            exit 1
        fi
    else
        echo -e "${YELLOW}ℹ️  No valid PHP files to analyze${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  PHPStan not found, skipping static analysis${NC}"
fi

echo -e "${GREEN}🎉 All PHP quality checks passed!${NC}"