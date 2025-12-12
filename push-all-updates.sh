#!/bin/bash
set -e

BASE_DIR="$HOME/Develop/nexus-platform"

BUNDLES=(
    "till-kubelke-platform-foundation"
    "till-kubelke-platform-ui"
    "till-kubelke-module-survey"
    "till-kubelke-module-chat"
    "till-kubelke-module-ai-buddy"
    "till-kubelke-module-hr-integration"
    "till-kubelke-app-easybgm"
    "till-kubelke-product-easybgm-backend"
    "till-kubelke-product-easybgm-frontend"
)

echo "🚀 Pushing all updates..."
echo ""

for BUNDLE in "${BUNDLES[@]}"; do
    BUNDLE_DIR="$BASE_DIR/$BUNDLE"
    
    if [ -d "$BUNDLE_DIR/.git" ]; then
        echo "═══════════════════════════════════════════════════════════"
        echo "📦 $BUNDLE"
        echo "═══════════════════════════════════════════════════════════"
        
        cd "$BUNDLE_DIR"
        
        if git diff --quiet && git diff --cached --quiet; then
            echo "ℹ️  No changes to commit"
        else
            git add .
            git commit -m "Migrate code from bgm-portal"
            git push
            echo "✅ Pushed"
        fi
        
        echo ""
    fi
done

echo "🎉 All updates pushed!"
