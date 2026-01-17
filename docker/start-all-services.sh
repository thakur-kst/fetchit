#!/bin/bash

# =============================================================================
# Start All Services Script
# =============================================================================
# This script starts all Docker services for the FetchIt API
# It starts external services first, then the main application services
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

# Function to start a service
start_service() {
    local service_name=$1
    local service_dir=$2
    
    print_info "Starting ${service_name}..."
    
    if [ ! -d "${service_dir}" ]; then
        print_warning "${service_name} directory not found: ${service_dir}"
        return 1
    fi
    
    if [ ! -f "${service_dir}/docker-compose.yml" ]; then
        print_warning "${service_name} docker-compose.yml not found: ${service_dir}/docker-compose.yml"
        return 1
    fi
    
    cd "${service_dir}"
    
    if docker compose up -d 2>&1 | grep -q "error\|Error\|ERROR"; then
        print_error "Failed to start ${service_name}"
        return 1
    else
        print_success "${service_name} started successfully"
        return 0
    fi
}

# Function to check if a service is running
check_service() {
    local service_name=$1
    local container_name=$2
    
    if docker ps --format '{{.Names}}' | grep -q "^${container_name}$"; then
        return 0
    else
        return 1
    fi
}

# Function to create Docker network if it doesn't exist
create_network() {
    local network_name=$1
    
    if docker network ls --format '{{.Name}}' | grep -q "^${network_name}$"; then
        print_warning "Network '${network_name}' already exists"
        return 0
    else
        print_info "Creating network '${network_name}'..."
        if docker network create "${network_name}" >/dev/null 2>&1; then
            print_success "Network '${network_name}' created successfully"
            return 0
        else
            print_error "Failed to create network '${network_name}'"
            return 1
        fi
    fi
}

# Main execution
main() {
    print_info "=========================================="
    print_info "Starting FetchIt API Services"
    print_info "=========================================="
    echo ""
    
    # Step 0: Create required Docker networks
    print_info "Step 0: Creating required Docker networks..."
    echo ""
    
    # List of required networks
    required_networks=(
        "api_network"
    )
    
    for network_name in "${required_networks[@]}"; do
        create_network "${network_name}"
    done
    
    echo ""
    
    # Step 1: Start external infrastructure services
    print_info "Step 1: Starting infrastructure services..."
    echo ""
    
    # Start services in dependency order
    services=(
        "redis:${DOCKER_DIR}/redis:redis"
        "postgres:${DOCKER_DIR}/postgres:postgres"
        "localstack:${DOCKER_DIR}/localstack:localstack"
    )
    
    failed_services=()
    
    for service_info in "${services[@]}"; do
        IFS=':' read -r service_name service_dir container_name <<< "${service_info}"
        
        # Check if service is already running
        if check_service "${service_name}" "${container_name}"; then
            print_warning "${service_name} is already running (container: ${container_name})"
            continue
        fi
        
        if ! start_service "${service_name}" "${service_dir}"; then
            failed_services+=("${service_name}")
        fi
        
        # Small delay between services
        sleep 1
    done
    
    echo ""
    
    # Step 2: Wait for infrastructure services to be ready
    print_info "Step 2: Waiting for infrastructure services to be ready..."
    sleep 5
    
    # Step 3: Start main application services
    print_info "Step 3: Starting main application services..."
    cd "${PROJECT_ROOT}"
    
    if docker compose ps --services --status running 2>/dev/null | grep -q .; then
        print_warning "Some application services are already running"
        print_info "Starting/updating all application services..."
    fi
    
    if docker compose up -d; then
        print_success "Main application services started successfully"
    else
        print_error "Failed to start main application services"
        exit 1
    fi
    
    echo ""
    
    # Step 4: Start nginx (depends on app service network)
    print_info "Step 4: Starting nginx service..."
    if check_service "nginx" "nginx"; then
        print_warning "nginx is already running"
    else
        start_service "nginx" "${DOCKER_DIR}/nginx"
    fi
    
    echo ""
    
    # Step 5: Restart nginx to ensure proper connectivity
    print_info "Step 5: Restarting nginx service..."
    cd "${DOCKER_DIR}/nginx"
    if docker compose restart 2>&1 | grep -q "error\|Error\|ERROR"; then
        print_warning "Nginx restart had issues, but continuing..."
    else
        print_success "Nginx restarted successfully"
    fi
    sleep 2
    
    echo ""
    
    # Step 6: Wait for services to be healthy
    print_info "Step 6: Waiting for services to be healthy..."
    sleep 5
    
    # Step 7: Display status
    echo ""
    print_info "=========================================="
    print_info "Service Status"
    print_info "=========================================="
    echo ""
    
    print_info "Infrastructure Services:"
    for service_info in "${services[@]}"; do
        IFS=':' read -r service_name service_dir container_name <<< "${service_info}"
        if check_service "${service_name}" "${container_name}"; then
            print_success "  ✓ ${service_name} (${container_name})"
        else
            print_error "  ✗ ${service_name} (${container_name})"
        fi
    done
    
    echo ""
    print_info "Nginx Service:"
    if check_service "nginx" "nginx"; then
        print_success "  ✓ nginx (nginx)"
    else
        print_error "  ✗ nginx (nginx)"
    fi
    
    echo ""
    print_info "Application Services:"
    cd "${PROJECT_ROOT}"
    docker compose ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}"
    
    echo ""
    
    # Report any failed services
    if [ ${#failed_services[@]} -gt 0 ]; then
        print_warning "Some services failed to start:"
        for service in "${failed_services[@]}"; do
            print_error "  - ${service}"
        done
        echo ""
        print_info "You can try starting them manually:"
        for service in "${failed_services[@]}"; do
            print_info "  cd docker/${service} && docker compose up -d"
        done
    else
        print_success "All services started successfully!"
    fi
    
    echo ""
    print_info "=========================================="
    print_info "Services are ready!"
    print_info "=========================================="
    print_info "API: http://localhost"
    print_info "Redis: localhost:6379"
    print_info "Postgres: localhost:5432"
    print_info "LocalStack: http://localhost:4566"
    echo ""
}

# Run main function
main "$@"
