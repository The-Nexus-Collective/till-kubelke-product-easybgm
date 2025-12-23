#!/bin/bash
# =============================================================================
# 🔒 Security Test Runner
# =============================================================================
#
# Runs all security-related tests across the monorepo.
#
# Usage:
#   ./scripts/security/run-security-tests.sh           # Run all security tests
#   ./scripts/security/run-security-tests.sh --quick   # Run only unit tests
#   ./scripts/security/run-security-tests.sh --module marketplace  # Run for specific module
#
# =============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$(dirname "$SCRIPT_DIR")")"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Parse arguments
QUICK_MODE=false
SPECIFIC_MODULE=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --quick)
            QUICK_MODE=true
            shift
            ;;
        --module)
            SPECIFIC_MODULE="$2"
            shift 2
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

echo ""
echo -e "${BLUE}🔒 Security Test Runner${NC}"
echo "========================"
echo ""

# Function to run tests for a module
run_module_tests() {
    local module_name="$1"
    local module_path="$2"
    local test_type="${3:-all}"
    
    if [[ ! -d "$module_path" ]]; then
        echo -e "${YELLOW}⚠️  Module not found: $module_path${NC}"
        return
    fi
    
    if [[ ! -f "$module_path/phpunit.xml" ]] && [[ ! -f "$module_path/phpunit.xml.dist" ]]; then
        echo -e "${YELLOW}⚠️  No phpunit.xml found in $module_path${NC}"
        return
    fi
    
    echo -e "${BLUE}📦 Testing: $module_name${NC}"
    
    cd "$module_path"
    
    # Install dependencies if needed
    if [[ ! -d "vendor" ]]; then
        echo "   Installing dependencies..."
        composer install --quiet --no-interaction 2>/dev/null || true
    fi
    
    # Run PHPUnit with security testsuite or group
    if [[ -f "vendor/bin/phpunit" ]]; then
        ((TOTAL_TESTS++))
        
        if $QUICK_MODE; then
            # Quick mode: only unit tests with security group
            TEST_ARGS="--group security --testsuite Unit"
        else
            # Full mode: use Security testsuite if available, otherwise group
            if grep -q 'testsuite name="Security"' phpunit.xml.dist 2>/dev/null || grep -q 'testsuite name="Security"' phpunit.xml 2>/dev/null; then
                TEST_ARGS="--testsuite Security"
            else
                TEST_ARGS="--group security"
            fi
        fi
        
        echo "   Running: vendor/bin/phpunit $TEST_ARGS"
        
        if vendor/bin/phpunit $TEST_ARGS --colors=always --testdox 2>&1; then
            ((PASSED_TESTS++))
            echo -e "   ${GREEN}✓ $module_name security tests passed${NC}"
        else
            ((FAILED_TESTS++))
            echo -e "   ${RED}✗ $module_name security tests FAILED${NC}"
        fi
    fi
    
    cd "$ROOT_DIR"
    echo ""
}

# Step 1: Run static security check first
echo -e "${BLUE}Step 1: Static Security Analysis${NC}"
echo "─────────────────────────────────"
if "$SCRIPT_DIR/check-tenant-security.sh" 2>&1; then
    echo -e "${GREEN}✓ Static analysis passed${NC}"
else
    echo -e "${RED}✗ Static analysis FAILED${NC}"
    echo ""
    echo -e "${RED}🚨 Fix static analysis issues before running tests${NC}"
    exit 1
fi
echo ""

# Step 2: Run PHPUnit security tests
echo -e "${BLUE}Step 2: PHPUnit Security Tests${NC}"
echo "───────────────────────────────"
echo ""

# Define modules to test
if [[ -n "$SPECIFIC_MODULE" ]]; then
    case $SPECIFIC_MODULE in
        marketplace)
            run_module_tests "Marketplace" "$ROOT_DIR/till-kubelke-module-marketplace"
            ;;
        chat)
            run_module_tests "Chat" "$ROOT_DIR/till-kubelke-module-chat"
            ;;
        backend)
            run_module_tests "Product Backend" "$ROOT_DIR/till-kubelke-product-easybgm-backend"
            ;;
        *)
            echo -e "${RED}Unknown module: $SPECIFIC_MODULE${NC}"
            exit 1
            ;;
    esac
else
    # Run all modules
    run_module_tests "Marketplace Module" "$ROOT_DIR/till-kubelke-module-marketplace"
    run_module_tests "Chat Module" "$ROOT_DIR/till-kubelke-module-chat"
    run_module_tests "Product Backend" "$ROOT_DIR/till-kubelke-product-easybgm-backend"
    run_module_tests "Platform Foundation" "$ROOT_DIR/till-kubelke-platform-foundation"
    run_module_tests "App EasyBGM" "$ROOT_DIR/till-kubelke-app-easybgm"
fi

# Summary
echo ""
echo "========================"
echo -e "${BLUE}📊 Security Test Summary${NC}"
echo "========================"
echo ""

if [[ $FAILED_TESTS -eq 0 ]]; then
    echo -e "${GREEN}✅ ALL SECURITY TESTS PASSED${NC}"
    echo ""
    echo "Verified:"
    echo "  ✓ No insecure tenant patterns in code"
    echo "  ✓ Tenant isolation enforced"
    echo "  ✓ Privilege escalation blocked"
    echo "  ✓ Controller endpoints secured"
    echo ""
    exit 0
else
    echo -e "${RED}🚨 SECURITY TESTS FAILED${NC}"
    echo ""
    echo "Failed: $FAILED_TESTS / $TOTAL_TESTS modules"
    echo ""
    echo "Please fix the failing tests before merging."
    exit 1
fi

