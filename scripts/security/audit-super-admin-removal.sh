#!/bin/bash

# =============================================================================
# Super-Admin Removal Audit Script
# =============================================================================
# 
# This script checks for remaining super-admin references in the EasyBGM codebase.
# After the security refactoring, there should be ZERO references in production code.
# 
# Expected result: 0 matches (excluding test files and documentation)
# 
# Run: ./scripts/security/audit-super-admin-removal.sh
# =============================================================================

echo "=============================================="
echo "  Super-Admin Removal Audit"
echo "=============================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

TOTAL_ISSUES=0

# -----------------------------------------------------------------------------
# 1. Check EasyBGM Backend (excluding tests)
# -----------------------------------------------------------------------------
echo "📦 Checking EasyBGM Backend (src/)..."

BACKEND_COUNT=$(grep -r "isSuperAdmin\|ROLE_SUPER_ADMIN\|super-admin" \
  till-kubelke-product-easybgm-backend/src/ \
  --include="*.php" \
  --exclude-dir="tests" \
  2>/dev/null | grep -v "// NOTE:" | grep -v "@deprecated" | wc -l | tr -d ' ')

if [ "$BACKEND_COUNT" -gt "0" ]; then
  echo -e "   ${YELLOW}⚠ Found $BACKEND_COUNT references${NC}"
  echo "   Details:"
  grep -rn "isSuperAdmin\|ROLE_SUPER_ADMIN\|super-admin" \
    till-kubelke-product-easybgm-backend/src/ \
    --include="*.php" \
    --exclude-dir="tests" \
    2>/dev/null | grep -v "// NOTE:" | grep -v "@deprecated" | head -20
  TOTAL_ISSUES=$((TOTAL_ISSUES + BACKEND_COUNT))
else
  echo -e "   ${GREEN}✓ No issues found${NC}"
fi

echo ""

# -----------------------------------------------------------------------------
# 2. Check EasyBGM Frontend (excluding tests)
# -----------------------------------------------------------------------------
echo "🎨 Checking EasyBGM Frontend (src/)..."

FRONTEND_COUNT=$(grep -r "isSuperAdmin\|super-admin" \
  till-kubelke-product-easybgm-frontend/src/ \
  --include="*.ts" --include="*.tsx" \
  --exclude-dir="__tests__" --exclude-dir="e2e" \
  2>/dev/null | grep -v "// NOTE:" | grep -v "// Backwards compatibility" | grep -v "@deprecated" | wc -l | tr -d ' ')

if [ "$FRONTEND_COUNT" -gt "0" ]; then
  echo -e "   ${YELLOW}⚠ Found $FRONTEND_COUNT references${NC}"
  echo "   Details:"
  grep -rn "isSuperAdmin\|super-admin" \
    till-kubelke-product-easybgm-frontend/src/ \
    --include="*.ts" --include="*.tsx" \
    --exclude-dir="__tests__" --exclude-dir="e2e" \
    2>/dev/null | grep -v "// NOTE:" | grep -v "// Backwards compatibility" | grep -v "@deprecated" | head -20
  TOTAL_ISSUES=$((TOTAL_ISSUES + FRONTEND_COUNT))
else
  echo -e "   ${GREEN}✓ No issues found${NC}"
fi

echo ""

# -----------------------------------------------------------------------------
# 3. Check Foundation (should have deprecation notices)
# -----------------------------------------------------------------------------
echo "🏛️ Checking Platform Foundation..."

FOUNDATION_COUNT=$(grep -r "isSuperAdmin()" \
  till-kubelke-platform-foundation/src/ \
  --include="*.php" \
  2>/dev/null | grep -v "// NOTE:" | wc -l | tr -d ' ')

echo "   Found $FOUNDATION_COUNT references to isSuperAdmin()"
echo "   (Foundation may still have the method for Admin Portal usage)"

echo ""

# -----------------------------------------------------------------------------
# Summary
# -----------------------------------------------------------------------------
echo "=============================================="
echo "  Summary"
echo "=============================================="

if [ "$TOTAL_ISSUES" -gt "0" ]; then
  echo -e "${RED}❌ Found $TOTAL_ISSUES potential issues${NC}"
  echo ""
  echo "Please review the above references and:"
  echo "  1. Replace isSuperAdmin() with isAdmin() or adminDetection->isAdmin()"
  echo "  2. Replace 'super-admin' role checks with 'admin' or Manager checks"
  echo "  3. Remove any remaining Super-Admin bypass logic"
  echo ""
  exit 1
else
  echo -e "${GREEN}✅ All clear! No super-admin references in EasyBGM code.${NC}"
  echo ""
  echo "The refactoring is complete. Super-Admins can now only"
  echo "access EasyBGM via Impersonate from the Admin Portal."
  exit 0
fi


