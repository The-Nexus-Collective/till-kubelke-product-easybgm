#!/bin/bash
set -e

# ═══════════════════════════════════════════════════════════════════════════
# 🚀 Nexus Platform - Development Environment Runner
# ═══════════════════════════════════════════════════════════════════════════

BASE_DIR="$(cd "$(dirname "$0")" && pwd)"
BACKEND_DIR="$BASE_DIR/till-kubelke-product-easybgm-backend"
FRONTEND_DIR="$BASE_DIR/till-kubelke-product-easybgm-frontend"

# Add symfony and composer to PATH
export PATH="$HOME/.symfony5/bin:/opt/homebrew/bin:$PATH"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

print_header() {
    echo ""
    echo -e "${PURPLE}═══════════════════════════════════════════════════════════════════════════${NC}"
    echo -e "${PURPLE}  🚀 Nexus Platform - Development Environment${NC}"
    echo -e "${PURPLE}═══════════════════════════════════════════════════════════════════════════${NC}"
    echo ""
}

print_usage() {
    echo -e "${CYAN}Usage:${NC} ./run.sh [command]"
    echo ""
    echo -e "${YELLOW}Commands:${NC}"
    echo "  start         Start all services (Docker + Backend + Frontend)"
    echo "  stop          Stop all services"
    echo "  restart       Restart all services"
    echo ""
    echo "  docker        Start Docker services only (PostgreSQL, Redis, Mailpit)"
    echo "  docker-stop   Stop Docker services"
    echo ""
    echo "  backend       Start Symfony backend only"
    echo "  frontend      Start Vite frontend only"
    echo ""
    echo "  test          Run all tests"
    echo "  test-backend  Run PHPUnit tests"
    echo "  test-frontend Run Vitest tests"
    echo "  test-e2e      Run Playwright E2E tests"
    echo ""
    echo "  install       Install all dependencies"
    echo "  update        Update all dependencies"
    echo "  pull          Git pull all repositories"
    echo "  push          Git push all repositories"
    echo "  status        Show git status for all repositories"
    echo ""
    echo "  logs          Show Docker logs"
    echo "  db            Open PostgreSQL CLI"
    echo "  redis         Open Redis CLI"
    echo "  mail          Open Mailpit in browser"
    echo ""
    echo "  clean         Clean all caches and build artifacts"
    echo "  reset-db      Reset database (drop + create + migrate)"
    echo ""
}

# ═══════════════════════════════════════════════════════════════════════════
# Docker Commands
# ═══════════════════════════════════════════════════════════════════════════

start_docker() {
    echo -e "${BLUE}📦 Starting Docker services...${NC}"
    cd "$BACKEND_DIR"
    docker-compose up -d postgres redis mailpit
    
    echo -e "${GREEN}✅ Docker services started:${NC}"
    echo "   PostgreSQL: localhost:5432"
    echo "   Redis:      localhost:6379"
    echo "   Mailpit:    http://localhost:8025"
}

stop_docker() {
    echo -e "${BLUE}📦 Stopping Docker services...${NC}"
    cd "$BACKEND_DIR"
    docker-compose down
    echo -e "${GREEN}✅ Docker services stopped${NC}"
}

show_logs() {
    cd "$BACKEND_DIR"
    docker-compose logs -f
}

# ═══════════════════════════════════════════════════════════════════════════
# Backend Commands
# ═══════════════════════════════════════════════════════════════════════════

start_backend() {
    echo -e "${BLUE}🐘 Starting Symfony backend...${NC}"
    cd "$BACKEND_DIR"
    
    if [ ! -d "vendor" ]; then
        echo -e "${YELLOW}Installing dependencies...${NC}"
        composer install
    fi
    
    # Wait for PostgreSQL
    echo -e "${YELLOW}Waiting for PostgreSQL...${NC}"
    until docker exec easybgm-postgres pg_isready -U app -d app 2>/dev/null; do
        sleep 1
    done
    
    # Run migrations
    echo -e "${YELLOW}Running migrations...${NC}"
    php bin/console doctrine:migrations:migrate --no-interaction 2>/dev/null || true
    
    echo -e "${GREEN}✅ Backend starting on http://localhost:8000${NC}"
    symfony server:start --no-tls --port=8000
}

# ═══════════════════════════════════════════════════════════════════════════
# Frontend Commands
# ═══════════════════════════════════════════════════════════════════════════

start_frontend() {
    echo -e "${BLUE}⚛️  Starting Vite frontend...${NC}"
    cd "$FRONTEND_DIR"
    
    if [ ! -d "node_modules" ]; then
        echo -e "${YELLOW}Installing dependencies...${NC}"
        npm install
    fi
    
    echo -e "${GREEN}✅ Frontend starting on http://localhost:8080${NC}"
    npm run dev
}

# ═══════════════════════════════════════════════════════════════════════════
# Start All
# ═══════════════════════════════════════════════════════════════════════════

start_all() {
    print_header
    
    # Start Docker
    start_docker
    
    # Start backend in background
    echo ""
    echo -e "${BLUE}🐘 Starting Symfony backend in background...${NC}"
    cd "$BACKEND_DIR"
    
    if [ ! -d "vendor" ]; then
        composer install
    fi
    
    # Wait for PostgreSQL
    until docker exec easybgm-postgres pg_isready -U app -d app 2>/dev/null; do
        sleep 1
    done
    
    php bin/console doctrine:migrations:migrate --no-interaction 2>/dev/null || true
    symfony server:start --no-tls --port=8000 -d
    
    # Start frontend in background
    echo ""
    echo -e "${BLUE}⚛️  Starting Vite frontend in background...${NC}"
    cd "$FRONTEND_DIR"
    
    if [ ! -d "node_modules" ]; then
        npm install
    fi
    
    npm run dev &
    FRONTEND_PID=$!
    echo $FRONTEND_PID > "$BASE_DIR/.frontend.pid"
    
    echo ""
    echo -e "${GREEN}═══════════════════════════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}  ✅ All services started!${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════════════════════════════════════${NC}"
    echo ""
    echo "  🌐 Frontend:   http://localhost:8080"
    echo "  🔌 Backend:    http://localhost:8000"
    echo "  📧 Mailpit:    http://localhost:8025"
    echo "  🐘 PostgreSQL: localhost:5432"
    echo "  🔴 Redis:      localhost:6379"
    echo ""
    echo -e "${YELLOW}Press Ctrl+C to stop frontend, then run './run.sh stop' to stop all${NC}"
    echo ""
    
    wait $FRONTEND_PID
}

stop_all() {
    echo -e "${BLUE}🛑 Stopping all services...${NC}"
    
    # Stop Symfony server
    symfony server:stop 2>/dev/null || true
    
    # Kill any remaining php processes on port 8000
    lsof -ti:8000 | xargs kill -9 2>/dev/null || true
    
    # Stop frontend
    if [ -f "$BASE_DIR/.frontend.pid" ]; then
        kill $(cat "$BASE_DIR/.frontend.pid") 2>/dev/null || true
        rm "$BASE_DIR/.frontend.pid"
    fi
    
    # Kill any remaining node processes on port 8080
    lsof -ti:8080 | xargs kill -9 2>/dev/null || true
    
    # Stop Docker
    stop_docker
    
    echo -e "${GREEN}✅ All services stopped${NC}"
}

# ═══════════════════════════════════════════════════════════════════════════
# Test Commands
# ═══════════════════════════════════════════════════════════════════════════

run_tests() {
    echo -e "${BLUE}🧪 Running all tests...${NC}"
    run_backend_tests
    run_frontend_tests
}

run_backend_tests() {
    echo -e "${BLUE}🧪 Running PHPUnit tests...${NC}"
    cd "$BACKEND_DIR"
    ./vendor/bin/phpunit
}

run_frontend_tests() {
    echo -e "${BLUE}🧪 Running Vitest tests...${NC}"
    cd "$FRONTEND_DIR"
    npm run test
}

run_e2e_tests() {
    echo -e "${BLUE}🧪 Running Playwright E2E tests...${NC}"
    cd "$FRONTEND_DIR"
    npx playwright test
}

# ═══════════════════════════════════════════════════════════════════════════
# Git Commands
# ═══════════════════════════════════════════════════════════════════════════

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

git_pull_all() {
    echo -e "${BLUE}📥 Pulling all repositories...${NC}"
    for BUNDLE in "${BUNDLES[@]}"; do
        BUNDLE_DIR="$BASE_DIR/$BUNDLE"
        if [ -d "$BUNDLE_DIR/.git" ]; then
            echo -e "${CYAN}  $BUNDLE${NC}"
            cd "$BUNDLE_DIR"
            git pull --rebase || true
        fi
    done
    echo -e "${GREEN}✅ Done${NC}"
}

git_push_all() {
    echo -e "${BLUE}📤 Pushing all repositories...${NC}"
    for BUNDLE in "${BUNDLES[@]}"; do
        BUNDLE_DIR="$BASE_DIR/$BUNDLE"
        if [ -d "$BUNDLE_DIR/.git" ]; then
            cd "$BUNDLE_DIR"
            if ! git diff --quiet || ! git diff --cached --quiet; then
                echo -e "${CYAN}  $BUNDLE${NC}"
                git add .
                git commit -m "Update $(date +%Y-%m-%d)" || true
                git push
            fi
        fi
    done
    echo -e "${GREEN}✅ Done${NC}"
}

git_status_all() {
    echo -e "${BLUE}📊 Git status for all repositories...${NC}"
    echo ""
    for BUNDLE in "${BUNDLES[@]}"; do
        BUNDLE_DIR="$BASE_DIR/$BUNDLE"
        if [ -d "$BUNDLE_DIR/.git" ]; then
            cd "$BUNDLE_DIR"
            CHANGES=$(git status --porcelain | wc -l | tr -d ' ')
            if [ "$CHANGES" -gt 0 ]; then
                echo -e "${YELLOW}📦 $BUNDLE ($CHANGES changes)${NC}"
                git status --short
                echo ""
            else
                echo -e "${GREEN}📦 $BUNDLE (clean)${NC}"
            fi
        fi
    done
}

# ═══════════════════════════════════════════════════════════════════════════
# Install/Update Commands
# ═══════════════════════════════════════════════════════════════════════════

install_all() {
    echo -e "${BLUE}📦 Installing all dependencies...${NC}"
    
    echo -e "${CYAN}Backend:${NC}"
    cd "$BACKEND_DIR"
    composer install
    
    echo -e "${CYAN}Frontend:${NC}"
    cd "$FRONTEND_DIR"
    npm install
    
    echo -e "${GREEN}✅ All dependencies installed${NC}"
}

update_all() {
    echo -e "${BLUE}📦 Updating all dependencies...${NC}"
    
    echo -e "${CYAN}Backend:${NC}"
    cd "$BACKEND_DIR"
    composer update
    
    echo -e "${CYAN}Frontend:${NC}"
    cd "$FRONTEND_DIR"
    npm update
    
    echo -e "${GREEN}✅ All dependencies updated${NC}"
}

# ═══════════════════════════════════════════════════════════════════════════
# Database Commands
# ═══════════════════════════════════════════════════════════════════════════

open_db() {
    echo -e "${BLUE}🐘 Opening PostgreSQL CLI...${NC}"
    docker exec -it easybgm-postgres psql -U app -d app
}

open_redis() {
    echo -e "${BLUE}🔴 Opening Redis CLI...${NC}"
    docker exec -it easybgm-redis redis-cli
}

open_mail() {
    echo -e "${BLUE}📧 Opening Mailpit...${NC}"
    open http://localhost:8025 2>/dev/null || xdg-open http://localhost:8025 2>/dev/null || echo "Open http://localhost:8025"
}

reset_database() {
    echo -e "${RED}⚠️  This will delete all data! Continue? (y/N)${NC}"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        echo -e "${BLUE}🗑️  Resetting database...${NC}"
        cd "$BACKEND_DIR"
        php bin/console doctrine:database:drop --force --if-exists
        php bin/console doctrine:database:create
        php bin/console doctrine:migrations:migrate --no-interaction
        echo -e "${GREEN}✅ Database reset complete${NC}"
    else
        echo "Cancelled"
    fi
}

# ═══════════════════════════════════════════════════════════════════════════
# Clean Commands
# ═══════════════════════════════════════════════════════════════════════════

clean_all() {
    echo -e "${BLUE}🧹 Cleaning all caches...${NC}"
    
    cd "$BACKEND_DIR"
    rm -rf var/cache/* var/log/* 2>/dev/null || true
    php bin/console cache:clear 2>/dev/null || true
    
    cd "$FRONTEND_DIR"
    rm -rf node_modules/.cache dist .vite 2>/dev/null || true
    
    echo -e "${GREEN}✅ Caches cleared${NC}"
}

# ═══════════════════════════════════════════════════════════════════════════
# Main
# ═══════════════════════════════════════════════════════════════════════════

case "$1" in
    start)      start_all ;;
    stop)       stop_all ;;
    restart)    stop_all; start_all ;;
    docker)     start_docker ;;
    docker-stop) stop_docker ;;
    backend)    start_backend ;;
    frontend)   start_frontend ;;
    test)       run_tests ;;
    test-backend) run_backend_tests ;;
    test-frontend) run_frontend_tests ;;
    test-e2e)   run_e2e_tests ;;
    install)    install_all ;;
    update)     update_all ;;
    pull)       git_pull_all ;;
    push)       git_push_all ;;
    status)     git_status_all ;;
    logs)       show_logs ;;
    db)         open_db ;;
    redis)      open_redis ;;
    mail)       open_mail ;;
    clean)      clean_all ;;
    reset-db)   reset_database ;;
    *)          print_header; print_usage ;;
esac
