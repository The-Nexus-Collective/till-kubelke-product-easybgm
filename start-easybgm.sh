#!/bin/bash
# ═══════════════════════════════════════════════════════════════════════════
# 🚀 Start EasyBGM Product
# ═══════════════════════════════════════════════════════════════════════════

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
EASYBGM_DIR="$SCRIPT_DIR/till-kubelke-product-easybgm-frontend"

if [ ! -f "$EASYBGM_DIR/run.sh" ]; then
    echo "❌ EasyBGM not found at $EASYBGM_DIR"
    echo "   Run: git clone ... till-kubelke-product-easybgm-frontend"
    exit 1
fi

exec "$EASYBGM_DIR/run.sh" "${@:-start-all}"



