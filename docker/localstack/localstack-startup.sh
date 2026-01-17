#!/bin/bash

# LocalStack startup script that ensures secret is created
# This runs after LocalStack is fully started

set -e

ENDPOINT="${AWS_ENDPOINT_URL:-http://localhost:4566}"
REGION="${AWS_DEFAULT_REGION:-us-east-1}"
ENVIRONMENT="${APP_ENV:-local}"

# Map environment
case "$ENVIRONMENT" in
    local|development) ENV_NAME="dev" ;;
    staging) ENV_NAME="staging" ;;
    production) ENV_NAME="prod" ;;
    *) ENV_NAME="dev" ;;
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

echo "Waiting for LocalStack to be ready..."
MAX_RETRIES=30
RETRY_COUNT=0
while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if curl -sf "${ENDPOINT}/_localstack/health" >/dev/null 2>&1; then
        echo "LocalStack is ready!"
        break
    fi
    RETRY_COUNT=$((RETRY_COUNT + 1))
    sleep 2
done

if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
    echo "Error: LocalStack did not become ready"
    exit 1
fi

# Wait a bit more for services to be fully initialized
sleep 3

# Check if secret exists
if aws --endpoint-url="${ENDPOINT}" --region="${REGION}" \
    secretsmanager describe-secret \
    --secret-id "$SECRET_NAME" \
    >/dev/null 2>&1; then
    echo "Secret '$SECRET_NAME' exists, skipping creation"
else
    echo "Secret '$SECRET_NAME' not found, creating from .env..."
    bash /docker-entrypoint-init.d/init-localstack-secrets.sh
fi
