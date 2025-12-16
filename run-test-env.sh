#!/bin/bash

# Nexus Platform - Isolated Test Environment Script
# ==================================================
# Starts a completely isolated test environment on separate ports:
#   - Test Database: Port 5433 (app_test)
#   - Backend API: Port 8001 (APP_ENV=test)
#   - Frontend: Port 8081

set -e

export PATH="$HOME/.symfony5/bin:/opt/homebrew/bin:$PATH"

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

# Configuration
TEST_DB_PORT=5433
TEST_BACKEND_PORT=8001
TEST_FRONTEND_PORT=8081

BASE_DIR="$(cd "$(dirname "$0")" && pwd)"
BACKEND_DIR="$BASE_DIR/till-kubelke-product-easybgm-backend"
FRONTEND_DIR="$BASE_DIR/till-kubelke-product-easybgm-frontend"

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
    
    # Stop frontend
    if [ -f "$PID_DIR/frontend.pid" ]; then
        PID=$(cat "$PID_DIR/frontend.pid")
        if ps -p $PID > /dev/null 2>&1; then
            kill $PID 2>/dev/null || true
        fi
        rm -f "$PID_DIR/frontend.pid"
    fi
    
    # Kill any remaining processes on test ports
    lsof -ti:$TEST_BACKEND_PORT | xargs kill -9 2>/dev/null || true
    lsof -ti:$TEST_FRONTEND_PORT | xargs kill -9 2>/dev/null || true
    
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
    print_info "Setting up test database..."
    cd "$BACKEND_DIR"
    
    # Create .env.test.local 
    # Note: Use 'app' as database name - Doctrine will automatically append '_test' in test environment
    cat > .env.test.local << ENVEOF
# Auto-generated for E2E tests - DO NOT COMMIT
DATABASE_URL="postgresql://app:app_secret@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
ENVEOF
    
    # Clear cache and run migrations
    rm -rf var/cache/test
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
    rm -rf var/cache/test 2>/dev/null || true
    APP_ENV=test php bin/console cache:clear --no-interaction > /dev/null 2>&1 || true
    
    # Start PHP built-in server with test environment
    # IMPORTANT: Use router.php to properly propagate APP_ENV to $_SERVER/$_ENV
    # The PHP built-in server only sets env vars via getenv(), but Symfony needs $_SERVER
    APP_ENV=test php -S 127.0.0.1:$TEST_BACKEND_PORT -t public public/router.php > "$PID_DIR/backend.log" 2>&1 &
    echo $! > "$PID_DIR/backend.pid"
    
    sleep 3
    print_success "Test backend ready on port $TEST_BACKEND_PORT"
}

# Start test frontend
start_test_frontend() {
    print_info "Starting test frontend on port $TEST_FRONTEND_PORT..."
    cd "$FRONTEND_DIR"
    
    # Check if port is in use
    if lsof -Pi :$TEST_FRONTEND_PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
        print_warning "Port $TEST_FRONTEND_PORT already in use, stopping..."
        lsof -ti:$TEST_FRONTEND_PORT | xargs kill -9 2>/dev/null || true
        sleep 1
    fi
    
    # Start Vite with test port
    VITE_SERVER_URL="http://127.0.0.1:$TEST_BACKEND_PORT" yarn dev --port $TEST_FRONTEND_PORT > "$PID_DIR/frontend.log" 2>&1 &
    echo $! > "$PID_DIR/frontend.pid"
    
    # Wait for frontend
    print_info "Waiting for test frontend..."
    timeout=60
    counter=0
    while ! curl -s "http://127.0.0.1:$TEST_FRONTEND_PORT" > /dev/null 2>&1; do
        sleep 2
        counter=$((counter + 2))
        if [ $counter -ge $timeout ]; then
            print_error "Test frontend failed to start"
            exit 1
        fi
        echo -n "."
    done
    echo ""
    
    print_success "Test frontend ready on port $TEST_FRONTEND_PORT"
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
    start_test_frontend
    
    echo ""
    echo -e "${GREEN}==========================================${NC}"
    echo -e "${GREEN}  Test Environment Ready!${NC}"
    echo -e "${GREEN}==========================================${NC}"
    echo ""
    print_info "Test Backend:   http://127.0.0.1:$TEST_BACKEND_PORT"
    print_info "Test Frontend:  http://127.0.0.1:$TEST_FRONTEND_PORT"
    echo ""
    print_info "Run E2E tests: cd till-kubelke-product-easybgm-frontend && yarn test:e2e"
    print_info "Press Ctrl+C to stop"
    echo ""
    
    tail -f "$PID_DIR/backend.log" "$PID_DIR/frontend.log" 2>/dev/null &
    wait
}

main
