#!/bin/bash
#
# 🔒 Install Security Hooks
# ==========================
#
# This script installs the security pre-commit hook that prevents
# insecure tenant access patterns from being committed.
#
# Usage:
#   ./scripts/security/install-hooks.sh
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
HOOKS_DIR="$PROJECT_ROOT/.git/hooks"

echo "🔒 Installing security hooks..."
echo ""

# Make scripts executable
chmod +x "$SCRIPT_DIR/check-tenant-security.sh"
chmod +x "$SCRIPT_DIR/pre-commit-hook"

# Check if .git/hooks exists
if [[ ! -d "$HOOKS_DIR" ]]; then
    echo "❌ Error: .git/hooks directory not found."
    echo "   Are you in the root of a git repository?"
    exit 1
fi

# Install pre-commit hook
PRE_COMMIT_HOOK="$HOOKS_DIR/pre-commit"

if [[ -f "$PRE_COMMIT_HOOK" ]]; then
    # Backup existing hook
    cp "$PRE_COMMIT_HOOK" "$PRE_COMMIT_HOOK.backup"
    echo "📋 Backed up existing pre-commit hook to pre-commit.backup"
fi

# Create symlink
ln -sf "../../scripts/security/pre-commit-hook" "$PRE_COMMIT_HOOK"

echo "✅ Pre-commit hook installed!"
echo ""
echo "The security check will now run on every commit."
echo "It will block commits that contain insecure tenant access patterns."
echo ""
echo "To run manually:"
echo "  ./scripts/security/check-tenant-security.sh"
echo ""
echo "To skip the check (emergency only!):"
echo "  git commit --no-verify"



