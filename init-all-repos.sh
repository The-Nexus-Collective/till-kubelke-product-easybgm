#!/bin/bash

# ═══════════════════════════════════════════════════════════════
# Init & Push All Repos to GitHub
# ═══════════════════════════════════════════════════════════════

set -e  # Exit on error

BASE_DIR="$HOME/Develop/nexus-platform"
ORG="The-Nexus-Collective"

# Array of all bundles
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

echo "🚀 Initializing all repos..."
echo ""

for BUNDLE in "${BUNDLES[@]}"; do
    BUNDLE_DIR="$BASE_DIR/$BUNDLE"
    
    if [ -d "$BUNDLE_DIR" ]; then
        echo "═══════════════════════════════════════════════════════════"
        echo "📦 $BUNDLE"
        echo "═══════════════════════════════════════════════════════════"
        
        cd "$BUNDLE_DIR"
        
        # Init git if not already
        if [ ! -d ".git" ]; then
            git init
            echo "✅ Git initialized"
        else
            echo "ℹ️  Git already initialized"
        fi
        
        # Add all files
        git add .
        
        # Commit (skip if nothing to commit)
        if git diff --cached --quiet; then
            echo "ℹ️  Nothing to commit"
        else
            git commit -m "Initial commit: $BUNDLE"
            echo "✅ Committed"
        fi
        
        # Set remote if not exists
        if ! git remote get-url origin > /dev/null 2>&1; then
            git remote add origin "git@github.com:$ORG/$BUNDLE.git"
            echo "✅ Remote added"
        else
            echo "ℹ️  Remote already exists"
        fi
        
        # Push
        git branch -M main
        git push -u origin main
        echo "✅ Pushed to GitHub"
        
        echo ""
    else
        echo "⚠️  Directory not found: $BUNDLE_DIR"
    fi
done

echo "═══════════════════════════════════════════════════════════"
echo "🎉 All repos initialized and pushed!"
echo "═══════════════════════════════════════════════════════════"
