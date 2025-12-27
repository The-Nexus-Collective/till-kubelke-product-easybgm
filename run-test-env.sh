#!/bin/bash

# Nexus Platform - Isolated Test Environment Script
# ==================================================
# Starts a completely isolated test environment on separate ports:
#   - Test Database: app_test (on port 5432, database name app_test)
#   - Backend API: Port 8001 (APP_ENV=test)
#   - EasyBGM Frontend: Port 8081
#   - Admin Frontend: Port 9001

set -e

export PATH="$HOME/.symfony5/bin:/opt/homebrew/bin:$PATH"

# Load nvm to ensure correct Node.js version (20.x)
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" 2>/dev/null
nvm use 20 2>/dev/null || true

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

print_info() { echo -e "${BLUE}[TEST-ENV]${NC} $1"; }
print_success() { echo -e "${GREEN}[TEST-ENV]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[TEST-ENV]${NC} $1"; }
print_error() { echo -e "${RED}[TEST-ENV]${NC} $1"; }

# Configuration - All test ports
TEST_BACKEND_PORT=8001
TEST_EASYBGM_PORT=8081
TEST_ADMIN_PORT=9001

BASE_DIR="$(cd "$(dirname "$0")" && pwd)"
BACKEND_DIR="$BASE_DIR/till-kubelke-product-easybgm-backend"
EASYBGM_FRONTEND_DIR="$BASE_DIR/till-kubelke-product-easybgm-frontend"
ADMIN_FRONTEND_DIR="$BASE_DIR/till-kubelke-product-admin-frontend"

# PID files
PID_DIR="/tmp/nexus-test"
mkdir -p "$PID_DIR"

# Cleanup function
cleanup() {
    echo ""
    print_info "Stopping test environment..."
    
    # Stop backend
    if [ -f "$PID_DIR/backend.pid" ]; then
        PID=$(cat "$PID_DIR/backend.pid")
        if ps -p $PID > /dev/null 2>&1; then
            kill $PID 2>/dev/null || true
        fi
        rm -f "$PID_DIR/backend.pid"
    fi
    
    # Stop EasyBGM frontend
    if [ -f "$PID_DIR/easybgm-frontend.pid" ]; then
        PID=$(cat "$PID_DIR/easybgm-frontend.pid")
        if ps -p $PID > /dev/null 2>&1; then
            kill $PID 2>/dev/null || true
        fi
        rm -f "$PID_DIR/easybgm-frontend.pid"
    fi
    
    # Stop Admin frontend
    if [ -f "$PID_DIR/admin-frontend.pid" ]; then
        PID=$(cat "$PID_DIR/admin-frontend.pid")
        if ps -p $PID > /dev/null 2>&1; then
            kill $PID 2>/dev/null || true
        fi
        rm -f "$PID_DIR/admin-frontend.pid"
    fi
    
    # Kill any remaining processes on test ports
    lsof -ti:$TEST_BACKEND_PORT | xargs kill -9 2>/dev/null || true
    lsof -ti:$TEST_EASYBGM_PORT | xargs kill -9 2>/dev/null || true
    lsof -ti:$TEST_ADMIN_PORT | xargs kill -9 2>/dev/null || true
    
    print_success "Test environment stopped"
}

trap cleanup EXIT INT TERM

# Start test database (using existing postgres on different port)
start_test_database() {
    print_info "Setting up test database..."
    
    # Use existing docker container but create test database
    docker exec easybgm-postgres psql -U app -c "DROP DATABASE IF EXISTS app_test;" 2>/dev/null || true
    docker exec easybgm-postgres psql -U app -c "CREATE DATABASE app_test;" 2>/dev/null || true
    
    print_success "Test database ready (app_test on port 5432)"
}

# Setup and reset test database
setup_test_database() {
    print_info "Setting up test database schema..."
    cd "$BACKEND_DIR"
    
    # Create .env.test.local 
    # Note: Use 'app' as database name - Doctrine will automatically append '_test' in test environment
    cat > .env.test.local << ENVEOF
# Auto-generated for E2E tests - DO NOT COMMIT
DATABASE_URL="postgresql://app:app_secret@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
ENVEOF
    
    # Clear cache and run migrations (use /bin/rm to avoid shell aliases)
    /bin/rm -rf var/cache/test 2>/dev/null || true
    APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction 2>/dev/null || true
    
    # Reset test database if command exists
    APP_ENV=test php bin/console app:reset-test-database --no-interaction 2>/dev/null || print_warning "Reset command not found, using migrations only"
    
    print_success "Test database setup complete"
}

# Start test backend
start_test_backend() {
    print_info "Starting test backend on port $TEST_BACKEND_PORT..."
    cd "$BACKEND_DIR"
    
    # Check if port is in use
    if lsof -Pi :$TEST_BACKEND_PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
        print_warning "Port $TEST_BACKEND_PORT already in use, stopping..."
        lsof -ti:$TEST_BACKEND_PORT | xargs kill -9 2>/dev/null || true
        sleep 1
    fi
    
    # Clear cache to ensure fresh configuration
    print_info "Clearing test cache..."
    /bin/rm -rf var/cache/test 2>/dev/null || true
    APP_ENV=test php bin/console cache:clear --no-interaction > /dev/null 2>&1 || true
    
    # Start PHP built-in server with test environment
    # IMPORTANT: Use router.php to properly propagate APP_ENV to $_SERVER/$_ENV
    APP_ENV=test php -S 127.0.0.1:$TEST_BACKEND_PORT -t public public/router.php > "$PID_DIR/backend.log" 2>&1 &
    echo $! > "$PID_DIR/backend.pid"
    
    sleep 3
    print_success "Test backend ready on port $TEST_BACKEND_PORT"
}

# Start EasyBGM frontend
start_easybgm_frontend() {
    print_info "Starting EasyBGM frontend on port $TEST_EASYBGM_PORT..."
    cd "$EASYBGM_FRONTEND_DIR"
    
    # Check if port is in use
    if lsof -Pi :$TEST_EASYBGM_PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
        print_warning "Port $TEST_EASYBGM_PORT already in use, stopping..."
        lsof -ti:$TEST_EASYBGM_PORT | xargs kill -9 2>/dev/null || true
        sleep 1
    fi
    
    # Start Vite with test port
    VITE_SERVER_URL="http://127.0.0.1:$TEST_BACKEND_PORT" yarn dev --port $TEST_EASYBGM_PORT > "$PID_DIR/easybgm-frontend.log" 2>&1 &
    echo $! > "$PID_DIR/easybgm-frontend.pid"
    
    # Wait for frontend
    print_info "Waiting for EasyBGM frontend..."
    timeout=60
    counter=0
    while ! curl -s "http://127.0.0.1:$TEST_EASYBGM_PORT" > /dev/null 2>&1; do
        sleep 2
        counter=$((counter + 2))
        if [ $counter -ge $timeout ]; then
            print_error "EasyBGM frontend failed to start"
            exit 1
        fi
        echo -n "."
    done
    echo ""
    
    print_success "EasyBGM frontend ready on port $TEST_EASYBGM_PORT"
}

# Start Admin frontend
start_admin_frontend() {
    print_info "Starting Admin frontend on port $TEST_ADMIN_PORT..."
    cd "$ADMIN_FRONTEND_DIR"
    
    # Check if port is in use
    if lsof -Pi :$TEST_ADMIN_PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
        print_warning "Port $TEST_ADMIN_PORT already in use, stopping..."
        lsof -ti:$TEST_ADMIN_PORT | xargs kill -9 2>/dev/null || true
        sleep 1
    fi
    
    # Start Vite with test port
    VITE_SERVER_URL="http://127.0.0.1:$TEST_BACKEND_PORT" npm run dev -- --port $TEST_ADMIN_PORT > "$PID_DIR/admin-frontend.log" 2>&1 &
    echo $! > "$PID_DIR/admin-frontend.pid"
    
    # Wait for frontend
    print_info "Waiting for Admin frontend..."
    timeout=60
    counter=0
    while ! curl -s "http://127.0.0.1:$TEST_ADMIN_PORT" > /dev/null 2>&1; do
        sleep 2
        counter=$((counter + 2))
        if [ $counter -ge $timeout ]; then
            print_error "Admin frontend failed to start"
            exit 1
        fi
        echo -n "."
    done
    echo ""
    
    print_success "Admin frontend ready on port $TEST_ADMIN_PORT"
}

# Main
main() {
    echo ""
    echo -e "${CYAN}==========================================${NC}"
    echo -e "${CYAN}  Nexus Platform - Test Environment${NC}"
    echo -e "${CYAN}==========================================${NC}"
    echo ""
    
    start_test_database
    setup_test_database
    start_test_backend
    start_easybgm_frontend
    start_admin_frontend
    
    echo ""
    echo -e "${GREEN}==========================================${NC}"
    echo -e "${GREEN}  Test Environment Ready!${NC}"
    echo -e "${GREEN}==========================================${NC}"
    echo ""
    print_info "Test Backend:       http://127.0.0.1:$TEST_BACKEND_PORT"
    print_info "EasyBGM Frontend:   http://127.0.0.1:$TEST_EASYBGM_PORT"
    print_info "Admin Frontend:     http://127.0.0.1:$TEST_ADMIN_PORT"
    echo ""
    print_info "Run E2E tests:"
    print_info "  EasyBGM: cd till-kubelke-product-easybgm-frontend && yarn test:e2e"
    print_info "  Admin:   cd till-kubelke-product-admin-frontend && npx playwright test"
    print_info ""
    print_info "Press Ctrl+C to stop"
    echo ""
    
    tail -f "$PID_DIR/backend.log" "$PID_DIR/easybgm-frontend.log" "$PID_DIR/admin-frontend.log" 2>/dev/null &
    wait
}

main
