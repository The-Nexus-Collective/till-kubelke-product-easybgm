#!/bin/bash
# ═══════════════════════════════════════════════════════════════════════════
# 🚀 Start Draft Product
# ═══════════════════════════════════════════════════════════════════════════

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
DRAFT_DIR="$SCRIPT_DIR/till-kubelke-product-draft"

if [ ! -f "$DRAFT_DIR/run.sh" ]; then
    echo "❌ Draft not found at $DRAFT_DIR"
    echo "   Run: git clone ... till-kubelke-product-draft"
    exit 1
fi

exec "$DRAFT_DIR/run.sh" "${@:-dev}"



