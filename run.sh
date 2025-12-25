#!/bin/bash
# ═══════════════════════════════════════════════════════════════════════════
# 🚀 Nexus Platform - Quick Start
# ═══════════════════════════════════════════════════════════════════════════
#
# Dieses Skript dient als Einstiegspunkt für die Plattform.
# Jedes Produkt hat sein eigenes run.sh mit vollständiger Funktionalität.
#
# ═══════════════════════════════════════════════════════════════════════════

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Colors
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo ""
echo -e "${PURPLE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${PURPLE}  🚀 Nexus Platform${NC}"
echo -e "${PURPLE}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""

case "$1" in
    easybgm|start-easybgm)
        exec "$SCRIPT_DIR/start-easybgm.sh" "${@:2}"
        ;;
    draft|start-draft)
        exec "$SCRIPT_DIR/start-draft.sh" "${@:2}"
        ;;
    *)
        echo -e "${CYAN}Quick Start:${NC}"
        echo ""
        echo -e "  ${GREEN}./start-easybgm.sh${NC}     Startet EasyBGM (Full Stack + Dashboard)"
        echo -e "  ${GREEN}./start-draft.sh${NC}       Startet Draft Product (PoC)"
        echo ""
        echo -e "${CYAN}Alternativ:${NC}"
        echo ""
        echo -e "  ${YELLOW}./run.sh easybgm${NC}       → start-easybgm.sh"
        echo -e "  ${YELLOW}./run.sh draft${NC}         → start-draft.sh"
        echo ""
        echo -e "${CYAN}Produkt-spezifische Befehle:${NC}"
        echo ""
        echo "  cd till-kubelke-product-easybgm-frontend && ./run.sh --help"
        echo "  cd till-kubelke-product-draft && ./run.sh --help"
        echo ""
        ;;
esac
