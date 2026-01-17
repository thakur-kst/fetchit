#!/bin/bash

# =============================================================================
# Stop All Services Script
# =============================================================================
# This script stops all Docker services for the FetchIt API
# It stops main application services first, then external services
# =============================================================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script directory (docker folder)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
DOCKER_DIR="${SCRIPT_DIR}"

# Function to print colored messages
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to stop a service
stop_service() {
    local service_name=$1
    local service_dir=$2
    
    print_info "Stopping ${service_name}..."
    
    if [ ! -d "${service_dir}" ]; then
        print_warning "${service_name} directory not found: ${service_dir}"
        return 1
    fi
    
    if [ ! -f "${service_dir}/docker-compose.yml" ]; then
        print_warning "${service_name} docker-compose.yml not found: ${service_dir}/docker-compose.yml"
        return 1
    fi
    
    cd "${service_dir}"
    
    if docker compose down 2>&1 | grep -q "error\|Error\|ERROR"; then
        print_error "Failed to stop ${service_name}"
        return 1
    else
        print_success "${service_name} stopped successfully"
        return 0
    fi
}

# Main execution
main() {
    print_info "=========================================="
    print_info "Stopping FetchIt API Services"
    print_info "=========================================="
    echo ""
    
    # Step 1: Stop main application services
    print_info "Step 1: Stopping main application services..."
    cd "${PROJECT_ROOT}"
    
    if docker compose ps --services --status running 2>/dev/null | grep -q .; then
        if docker compose down; then
            print_success "Main application services stopped successfully"
        else
            print_error "Failed to stop main application services"
            exit 1
        fi
    else
        print_warning "No main application services are running"
    fi
    
    echo ""
    
    # Step 2: Stop nginx
    print_info "Step 2: Stopping nginx service..."
    stop_service "nginx" "${DOCKER_DIR}/nginx"
    
    echo ""
    
    # Step 3: Stop external infrastructure services
    print_info "Step 3: Stopping infrastructure services..."
    echo ""
    
    # Stop services in reverse dependency order
    services=(
        "localstack:${DOCKER_DIR}/localstack"
        "postgres:${DOCKER_DIR}/postgres"
        "redis:${DOCKER_DIR}/redis"
    )
    
    failed_services=()
    
    for service_info in "${services[@]}"; do
        IFS=':' read -r service_name service_dir <<< "${service_info}"
        
        if ! stop_service "${service_name}" "${service_dir}"; then
            failed_services+=("${service_name}")
        fi
        
        # Small delay between services
        sleep 1
    done
    
    echo ""
    
    # Report any failed services
    if [ ${#failed_services[@]} -gt 0 ]; then
        print_warning "Some services failed to stop:"
        for service in "${failed_services[@]}"; do
            print_error "  - ${service}"
        done
    else
        print_success "All services stopped successfully!"
    fi
    
    echo ""
    print_info "=========================================="
    print_info "All services stopped"
    print_info "=========================================="
    echo ""
}

# Run main function
main "$@"
