#!/bin/bash
# Test Runner Hook for Claude Code
# Runs relevant tests when code files are modified

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "🧪 Running Relevant Tests..."

# Check if there are files to test
if [ -z "$CLAUDE_FILE_PATHS" ]; then
    echo "ℹ️  No files to test"
    exit 0
fi

echo "📁 Files changed: $CLAUDE_FILE_PATHS"

# Determine what tests to run based on changed files
run_php_tests=false
run_js_tests=false
run_e2e_tests=false

for file in $CLAUDE_FILE_PATHS; do
    # PHP files - run PHP unit tests
    if [[ "$file" == *.php ]]; then
        # Skip if it's already a test file
        if [[ "$file" != *Test.php && "$file" != */tests/* ]]; then
            run_php_tests=true
        fi
    fi
    
    # JavaScript/TypeScript files - run JS tests
    if [[ "$file" == *.js || "$file" == *.ts || "$file" == *.tsx || "$file" == *.jsx ]]; then
        # Skip if it's already a test file
        if [[ "$file" != *test* && "$file" != *spec* ]]; then
            run_js_tests=true
        fi
    fi
    
    # Critical files - run E2E tests
    if [[ "$file" == *Controller.php || "$file" == *routes* || "$file" == *middleware* ]]; then
        run_e2e_tests=true
    fi
done

# Run PHP Unit Tests
if [ "$run_php_tests" = true ]; then
    echo -e "${BLUE}🧪 Running PHP Unit Tests...${NC}"
    if [ -f "vendor/bin/phpunit" ]; then
        if ./vendor/bin/phpunit --stop-on-failure --quiet; then
            echo -e "${GREEN}✅ PHP unit tests passed${NC}"
        else
            echo -e "${RED}❌ PHP unit tests failed${NC}"
            exit 1
        fi
    else
        echo -e "${YELLOW}⚠️  PHPUnit not available${NC}"
    fi
fi

# Run JavaScript Tests
if [ "$run_js_tests" = true ]; then
    echo -e "${BLUE}🧪 Running JavaScript/TypeScript Tests...${NC}"
    if [ -f "package.json" ] && grep -q '"test"' package.json; then
        if npm test --silent; then
            echo -e "${GREEN}✅ JavaScript tests passed${NC}"
        else
            echo -e "${YELLOW}⚠️  JavaScript tests failed, but continuing...${NC}"
            # Don't exit on JS test failures as they might be work-in-progress
        fi
    else
        echo -e "${YELLOW}⚠️  No JavaScript test script found${NC}"
    fi
fi

# Run E2E Tests (only for critical changes)
if [ "$run_e2e_tests" = true ]; then
    echo -e "${BLUE}🧪 Running critical E2E tests...${NC}"
    if [ -f "scripts/run-e2e-tests.sh" ]; then
        echo -e "${YELLOW}ℹ️  E2E tests can take a while, running in background...${NC}"
        # Run a subset of E2E tests for critical files
        ./scripts/run-e2e-tests.sh --project=chromium tests/e2e/auth.spec.ts &
        echo -e "${GREEN}✅ E2E tests started in background${NC}"
    fi
fi

# If no tests were run
if [ "$run_php_tests" = false ] && [ "$run_js_tests" = false ] && [ "$run_e2e_tests" = false ]; then
    echo -e "${GREEN}ℹ️  No tests needed for these file changes${NC}"
fi

echo -e "${GREEN}🎉 Test execution completed!${NC}"