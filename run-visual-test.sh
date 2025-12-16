#!/bin/bash

# Nexus Platform - Visual E2E Test Runner
# =========================================
# Starts the Platform Journey E2E test in headed mode for visual observation.
#
# This test plays through the entire platform visually and can be
# incrementally expanded. It runs in parallel to technical E2E tests.
#
# Usage:
#   ./run-visual-test.sh
#   ./run-visual-test.sh --slow-mo=1000  # Slower execution for better observation
#
# Prerequisites:
#   1. Docker must be running (./run.sh start)
#   2. Development environment must be running (./run.sh start)

set -e

export PATH="$HOME/.symfony5/bin:/opt/homebrew/bin:$PATH"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

print_info() { echo -e "${BLUE}[VISUAL-TEST]${NC} $1"; }
print_success() { echo -e "${GREEN}[VISUAL-TEST]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[VISUAL-TEST]${NC} $1"; }
print_error() { echo -e "${RED}[VISUAL-TEST]${NC} $1"; }

# Configuration
# Visual tests run against DEV environment (8080) for stability - demo users always exist
DEV_FRONTEND_PORT=8080
DEV_BACKEND_PORT=8000
FRONTEND_DIR="till-kubelke-product-easybgm-frontend"

# Parse arguments
SLOW_MO=""
if [[ "$*" == *"--slow-mo"* ]]; then
    SLOW_MO="--slow-mo=$(echo "$*" | grep -oP '--slow-mo=\K[0-9]+' || echo '1000')"
fi

# Check if Docker is running
check_docker() {
    if ! docker ps > /dev/null 2>&1; then
        print_error "Docker is not running!"
        print_info "Please start Docker first:"
        print_info "  ./run.sh start"
        exit 1
    fi
    print_success "Docker is running"
}

# Check if development environment is running
check_dev_environment() {
    print_info "Checking development environment..."
    
    # Check backend
    if ! curl -s http://127.0.0.1:$DEV_BACKEND_PORT/api/health > /dev/null 2>&1; then
        print_error "Backend is not running on port $DEV_BACKEND_PORT!"
        print_info "Please start the development environment first:"
        print_info "  ./run.sh start"
        exit 1
    fi
    print_success "Backend is running on port $DEV_BACKEND_PORT"
    
    # Check frontend
    if ! curl -s http://127.0.0.1:$DEV_FRONTEND_PORT > /dev/null 2>&1; then
        print_error "Frontend is not running on port $DEV_FRONTEND_PORT!"
        print_info "Please start the development environment first:"
        print_info "  ./run.sh start"
        exit 1
    fi
    print_success "Frontend is running on port $DEV_FRONTEND_PORT"
}

# Run the visual test
run_visual_test() {
    print_info "Starting Platform Journey visual test..."
    print_info "The browser will open and you can watch the test execution."
    
    if [ -n "$SLOW_MO" ]; then
        print_info "Slow motion mode: $SLOW_MO ms delay between actions"
    fi
    
    cd "$FRONTEND_DIR"
    
    # Run the test with platform-journey project (no auth.setup dependency!)
    # This uses the authenticatedPage fixture which handles login internally.
    if [ -n "$SLOW_MO" ]; then
        npm run test:e2e:headed -- --project=platform-journey "$SLOW_MO"
    else
        npm run test:e2e:headed -- --project=platform-journey
    fi
    
    local exit_code=$?
    cd ..
    
    if [ $exit_code -eq 0 ]; then
        print_success "Visual test completed successfully!"
    else
        print_error "Visual test failed with exit code $exit_code"
        exit $exit_code
    fi
}

# Main execution
main() {
    print_info "=========================================="
    print_info "Nexus Platform - Visual E2E Test Runner"
    print_info "=========================================="
    echo ""
    
    check_docker
    check_dev_environment
    echo ""
    
    run_visual_test
}

# Run main function
main "$@"

