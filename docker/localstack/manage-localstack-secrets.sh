#!/bin/bash

# =============================================================================
# LocalStack Secrets Management Script
# =============================================================================
# This script helps manage LocalStack secrets: create, drop, and recreate from .env
# Works with a single secret containing all environment variables.
#
# Usage (from host):
#   docker compose exec localstack bash /docker-entrypoint-init.d/manage-localstack-secrets.sh [create|drop|recreate|drop-and-recreate]
#
# Usage (from container):
#   bash /docker-entrypoint-init.d/manage-localstack-secrets.sh [create|drop|recreate|drop-and-recreate]
# =============================================================================

set -e

# Configuration
ENDPOINT="${AWS_ENDPOINT_URL:-http://localhost:4566}"
REGION="${AWS_DEFAULT_REGION:-us-east-1}"
ENVIRONMENT="${APP_ENV:-local}"

# Map environment
case "$ENVIRONMENT" in
    local|development)
        ENV_NAME="dev"
        ;;
    staging)
        ENV_NAME="staging"
        ;;
    production)
        ENV_NAME="prod"
        ;;
    *)
        ENV_NAME="dev"
        ;;
esac

# Determine secret name - try to read from aws-secret.config.json, otherwise use default
SECRET_NAME="${AWS_SECRET_NAME:-fetchit/${ENV_NAME}}"

# Try to read secret name from aws-secret.config.json if it exists
if [ -f "/var/www/html/aws-secret.config.json" ]; then
    CONFIG_FILE="/var/www/html/aws-secret.config.json"
elif [ -f "./backend/aws-secret.config.json" ]; then
    CONFIG_FILE="./backend/aws-secret.config.json"
else
    CONFIG_FILE=""
fi

if [ -n "$CONFIG_FILE" ] && [ -f "$CONFIG_FILE" ]; then
    CONFIG_SECRET_NAME=$(grep -o '"secret_name"[[:space:]]*:[[:space:]]*"[^"]*"' "$CONFIG_FILE" | cut -d'"' -f4 || echo "")
    if [ -n "$CONFIG_SECRET_NAME" ]; then
        SECRET_NAME="$CONFIG_SECRET_NAME"
    fi
fi

ACTION="${1:-drop-and-recreate}"

echo "============================================================================="
echo "LocalStack Secrets Management"
echo "============================================================================="
echo "Endpoint: $ENDPOINT"
echo "Region: $REGION"
echo "Secret Name: $SECRET_NAME"
echo "Environment: $ENV_NAME"
echo "Action: $ACTION"
echo "============================================================================="

# Function to create secret
create_secrets() {
    echo ""
    echo "Creating secret from .env..."
    bash /docker-entrypoint-init.d/init-localstack-secrets.sh
    echo "✓ Secret created"
}

# Function to drop secret
drop_secrets() {
    echo ""
    echo "Dropping secret: $SECRET_NAME"
    
    # Check if secret exists
    if aws --endpoint-url="$ENDPOINT" --region="$REGION" \
        secretsmanager describe-secret \
        --secret-id "$SECRET_NAME" \
        >/dev/null 2>&1; then
        echo -n "Deleting: $SECRET_NAME ... "
        if aws --endpoint-url="$ENDPOINT" --region="$REGION" \
            secretsmanager delete-secret \
            --secret-id "$SECRET_NAME" \
            --force-delete-without-recovery \
            >/dev/null 2>&1; then
            echo "✓"
            echo "✓ Secret deleted"
        else
            echo "✗"
            echo "Error: Failed to delete secret"
            return 1
        fi
    else
        echo "Secret not found, nothing to delete."
        return 0
    fi
}

# Function to recreate secret
recreate_secrets() {
    echo ""
    echo "Recreating secret from .env..."
    bash /docker-entrypoint-init.d/init-localstack-secrets.sh
    echo "✓ Secret recreated"
}

# Execute action
case "$ACTION" in
    create)
        create_secrets
        ;;
    drop)
        drop_secrets
        ;;
    recreate)
        recreate_secrets
        ;;
    drop-and-recreate|*)
        drop_secrets
        echo ""
        sleep 1
        recreate_secrets
        ;;
esac

echo ""
echo "============================================================================="
echo "Operation completed!"
echo "============================================================================="
echo ""
echo "To verify secret, run:"
echo "  aws --endpoint-url=$ENDPOINT --region=$REGION secretsmanager get-secret-value --secret-id $SECRET_NAME"
echo ""
