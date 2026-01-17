#!/bin/bash

# =============================================================================
# Restart All Services Script
# =============================================================================
    # This script restarts all Docker services for the FetchIt API
# =============================================================================

# Script directory (docker folder)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Colors for output
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_info "=========================================="
print_info "Restarting FetchIt API Services"
print_info "=========================================="
echo ""

# Stop all services
print_info "Stopping all services..."
"${SCRIPT_DIR}/stop-all-services.sh"

echo ""
print_info "Waiting 3 seconds before restarting..."
sleep 3

echo ""
# Start all services
print_info "Starting all services..."
"${SCRIPT_DIR}/start-all-services.sh"

