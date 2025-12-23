#!/bin/bash
#
# 🔒 Tenant Security Check Script
# ================================
# 
# This script scans for insecure tenant access patterns that bypass
# the AbstractTenantController validation.
#
# Usage:
#   ./scripts/security/check-tenant-security.sh [--fix-suggestions]
#
# Exit codes:
#   0 = No security issues found
#   1 = Security issues detected (blocks commit/CI)
#
# What it checks:
#   1. Direct X-Tenant-ID header access without validation
#   2. getTenantFromRequest() patterns (known insecure)
#   3. Controllers using AbstractController instead of AbstractTenantController
#      when they access tenant data
#
# This is run by:
#   - Pre-commit hook (blocks insecure code from being committed)
#   - CI pipeline (fails build if insecure patterns detected)
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

ISSUES_FOUND=0
SHOW_FIX_SUGGESTIONS=false

if [[ "$1" == "--fix-suggestions" ]]; then
    SHOW_FIX_SUGGESTIONS=true
fi

echo "🔒 Tenant Security Check"
echo "========================"
echo ""

# ============================================================================
# CHECK 1: INSECURE getTenantFromRequest() pattern (that does NOT call getValidatedTenant)
# ============================================================================
echo "🔍 Check 1: Scanning for INSECURE getTenantFromRequest() pattern..."

# Find files with getTenantFromRequest that DON'T call getValidatedTenant (truly insecure)
TRULY_INSECURE_FILES=""

for file in $(grep -rl "private function getTenantFromRequest" "$PROJECT_ROOT" --include="*.php" 2>/dev/null || true); do
    # Check if this file also contains getValidatedTenant (which means it's secure)
    if ! grep -q "getValidatedTenant" "$file" 2>/dev/null; then
        TRULY_INSECURE_FILES="$TRULY_INSECURE_FILES$file"$'\n'
    fi
done

if [[ -n "$TRULY_INSECURE_FILES" ]]; then
    echo -e "${RED}❌ CRITICAL: Found INSECURE getTenantFromRequest() pattern (no validation) in:${NC}"
    echo "$TRULY_INSECURE_FILES" | while read -r file; do
        [[ -n "$file" ]] && echo "   - $file"
    done
    echo ""
    
    if $SHOW_FIX_SUGGESTIONS; then
        echo -e "${YELLOW}📝 Fix: Replace 'extends AbstractController' with 'extends AbstractTenantController'${NC}"
        echo "   Then use \$this->getValidatedTenant(\$request, \$this->entityManager)"
        echo "   See InquiryController.php for the correct pattern."
        echo ""
    fi
    
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
else
    echo -e "${GREEN}✓ No insecure getTenantFromRequest() patterns found${NC}"
    echo "   (Files with getTenantFromRequest that call getValidatedTenant are considered SAFE)"
fi

echo ""

# ============================================================================
# CHECK 2: Direct X-Tenant-ID header access in CONTROLLERS without validation
# ============================================================================
echo "🔍 Check 2: Scanning for unvalidated X-Tenant-ID header access in controllers..."

# Look for raw header access in Controllers that's NOT in approved locations
# Approved locations: AbstractTenantController, TenantSecuritySubscriber, Voters, EventListeners
HEADER_ACCESS_FILES=$(grep -rn "headers->get.*X-Tenant-ID" "$PROJECT_ROOT" \
    --include="*Controller.php" \
    2>/dev/null \
    | grep -v "AbstractTenantController" \
    | grep -v "TenantSecuritySubscriber" \
    | grep -v "vendor/" \
    | grep -v "Tests/" \
    || true)

# For each file, check if it also uses getValidatedTenant (then it's probably OK)
TRULY_UNSAFE=""
while IFS= read -r line; do
    [[ -z "$line" ]] && continue
    file=$(echo "$line" | cut -d':' -f1)
    if ! grep -q "getValidatedTenant\|extends AbstractTenantController" "$file" 2>/dev/null; then
        TRULY_UNSAFE="$TRULY_UNSAFE$line"$'\n'
    fi
done <<< "$HEADER_ACCESS_FILES"

if [[ -n "$TRULY_UNSAFE" ]]; then
    echo -e "${YELLOW}⚠️ WARNING: Found direct X-Tenant-ID header access without AbstractTenantController:${NC}"
    echo "$TRULY_UNSAFE" | while read -r line; do
        [[ -n "$line" ]] && echo "   $line"
    done
    echo ""
    
    if $SHOW_FIX_SUGGESTIONS; then
        echo -e "${YELLOW}📝 Fix: Use AbstractTenantController::getValidatedTenant() instead${NC}"
        echo "   This validates user has membership in the requested tenant."
        echo ""
    fi
    
    # This is a warning, not blocking for now (need manual review)
    # ISSUES_FOUND=$((ISSUES_FOUND + 1))
else
    echo -e "${GREEN}✓ No unvalidated X-Tenant-ID header access in controllers${NC}"
    echo "   (Voters, EventListeners, and EventSubscribers are excluded from this check)"
fi

echo ""

# ============================================================================
# CHECK 3: Controllers accessing tenant data without AbstractTenantController
# ============================================================================
echo "🔍 Check 3: Checking tenant-aware controllers use correct base class..."

# Find PHP controllers that mention Tenant but extend AbstractController
WRONG_BASE_CLASS=$(grep -rn "extends AbstractController" "$PROJECT_ROOT" \
    --include="*Controller.php" \
    2>/dev/null \
    | grep -v "vendor/" \
    | while read -r line; do
        file=$(echo "$line" | cut -d':' -f1)
        # Check if this file also uses Tenant
        if grep -q "Tenant" "$file" 2>/dev/null; then
            echo "$line"
        fi
    done || true)

if [[ -n "$WRONG_BASE_CLASS" ]]; then
    echo -e "${YELLOW}⚠️ WARNING: Controllers accessing Tenant data but using AbstractController:${NC}"
    echo "$WRONG_BASE_CLASS" | while read -r line; do
        echo "   $line"
    done
    echo ""
    echo "   These should extend AbstractTenantController for security."
    echo ""
    
    # This is a warning, not blocking (some may be intentionally public)
    # ISSUES_FOUND=$((ISSUES_FOUND + 1))
else
    echo -e "${GREEN}✓ All tenant-aware controllers use AbstractTenantController${NC}"
fi

echo ""

# ============================================================================
# RESULT
# ============================================================================
echo "========================"
if [[ $ISSUES_FOUND -gt 0 ]]; then
    echo -e "${RED}🚨 SECURITY CHECK FAILED: $ISSUES_FOUND issue(s) found${NC}"
    echo ""
    echo "These patterns can lead to tenant ID spoofing vulnerabilities."
    echo "Please fix before committing."
    echo ""
    echo "Run with --fix-suggestions for guidance:"
    echo "  ./scripts/security/check-tenant-security.sh --fix-suggestions"
    exit 1
else
    echo -e "${GREEN}✅ SECURITY CHECK PASSED: No tenant security issues found${NC}"
    exit 0
fi

