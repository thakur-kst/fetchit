#!/bin/bash

# Setup Test Database for Laravel Testing
# This script creates a separate PostgreSQL database for testing

set -e

echo "================================================"
echo "Setting up Test Database for Laravel Tests"
echo "================================================"
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Database credentials (from .env)
DB_HOST="db"
DB_USER="fetchit"
DB_PASSWORD="secret"
DB_NAME="fetchit_test"

echo -e "${YELLOW}Step 1: Checking if PostgreSQL container is running...${NC}"
if docker compose ps db | grep -q "Up"; then
    echo -e "${GREEN}✓ PostgreSQL container is running${NC}"
else
    echo -e "${RED}✗ PostgreSQL container is not running${NC}"
    echo "Starting PostgreSQL container..."
    docker compose up -d db
    sleep 5
fi

echo ""
echo -e "${YELLOW}Step 2: Checking if test database exists...${NC}"

# Check if database exists
DB_EXISTS=$(docker compose exec -T db psql -U $DB_USER -lqt | cut -d \| -f 1 | grep -w $DB_NAME | wc -l)

if [ "$DB_EXISTS" -eq 1 ]; then
    echo -e "${YELLOW}Test database '$DB_NAME' already exists.${NC}"
    read -p "Do you want to drop and recreate it? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Dropping existing test database..."
        docker compose exec -T db psql -U $DB_USER -c "DROP DATABASE IF EXISTS $DB_NAME;"
        echo -e "${GREEN}✓ Test database dropped${NC}"
    else
        echo "Keeping existing test database."
    fi
fi

echo ""
echo -e "${YELLOW}Step 3: Creating test database...${NC}"
docker compose exec -T db psql -U $DB_USER -c "CREATE DATABASE $DB_NAME OWNER $DB_USER;" 2>/dev/null || echo "Database already exists or creation skipped"
echo -e "${GREEN}✓ Test database '$DB_NAME' created${NC}"

echo ""
echo -e "${YELLOW}Step 4: Verifying test database connection...${NC}"
if docker compose exec -T db psql -U $DB_USER -d $DB_NAME -c "SELECT 1;" > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Successfully connected to test database${NC}"
else
    echo -e "${RED}✗ Failed to connect to test database${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 5: Running migrations on test database...${NC}"
docker compose exec app bash -lc "cd /var/www/html && php artisan migrate --database=pgsql --env=testing --force"
echo -e "${GREEN}✓ Migrations completed${NC}"

echo ""
echo "================================================"
echo -e "${GREEN}Test Database Setup Complete!${NC}"
echo "================================================"
echo ""
echo "Database Details:"
echo "  Host:     $DB_HOST"
echo "  Database: $DB_NAME"
echo "  User:     $DB_USER"
echo "  Port:     5432"
echo ""
echo "You can now run tests with:"
echo "  docker compose exec app php artisan test"
echo ""
echo "Or run specific test files:"
echo "  docker compose exec app php artisan test app/Modules/Payment/tests"
echo ""
