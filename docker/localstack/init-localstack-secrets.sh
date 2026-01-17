#!/bin/bash

# =============================================================================
# LocalStack Secrets Manager Initialization Script
# =============================================================================
# This script automatically scans .env file and creates a single secret in LocalStack
# containing all environment variables from .env file.
#
# Usage:
#   docker compose exec localstack bash /docker-entrypoint-init.d/init-localstack-secrets.sh
#   OR
#   ./docker/localstack/init-localstack-secrets.sh
# =============================================================================

set -e

# Configuration
ENDPOINT="${AWS_ENDPOINT_URL:-http://localhost:4566}"
REGION="${AWS_DEFAULT_REGION:-us-east-1}"

# Determine .env file path
if [ -f "/var/www/html/.env" ]; then
    ENV_FILE="/var/www/html/.env"
elif [ -f "./backend/.env" ]; then
    ENV_FILE="./backend/.env"
else
    ENV_FILE="${ENV_FILE:-./backend/.env}"
fi

# Map Laravel environments to secret environment names
ENVIRONMENT="${APP_ENV:-local}"
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

echo "============================================================================="
echo "Initializing LocalStack Secrets Manager from .env file"
echo "============================================================================="
echo "Endpoint: $ENDPOINT"
echo "Region: $REGION"
echo "Secret Name: $SECRET_NAME"
echo "Environment: $ENV_NAME"
echo "Env File: $ENV_FILE"
echo "============================================================================="

# Wait for LocalStack to be ready
echo "Waiting for LocalStack to be ready..."
MAX_RETRIES=30
RETRY_COUNT=0
while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if curl -sf "${ENDPOINT}/_localstack/health" >/dev/null 2>&1; then
        echo "LocalStack is ready!"
        break
    fi
    RETRY_COUNT=$((RETRY_COUNT + 1))
    echo "Waiting for LocalStack... ($RETRY_COUNT/$MAX_RETRIES)"
    sleep 2
done

if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
    echo "Warning: LocalStack did not become ready in time, proceeding anyway..."
fi

# Check if .env file exists
if [ ! -f "$ENV_FILE" ]; then
    for path in "/var/www/html/.env" "./backend/.env" "../backend/.env" ".env"; do
        if [ -f "$path" ]; then
            ENV_FILE="$path"
            break
        fi
    done
    
    if [ ! -f "$ENV_FILE" ]; then
        echo "Error: .env file not found. Please specify ENV_FILE environment variable."
        exit 1
    fi
fi

# Read .env file and build JSON object with all variables
declare -A ENV_VARS

echo "Loading .env file..."
while IFS= read -r line || [ -n "$line" ]; do
    # Skip comments and empty lines
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    [[ -z "${line// }" ]] && continue
    
    # Extract key=value
    if [[ "$line" =~ ^([^=]+)=(.*)$ ]]; then
        key="${BASH_REMATCH[1]}"
        value="${BASH_REMATCH[2]}"
        # Remove leading/trailing whitespace
        key=$(echo "$key" | xargs)
        value=$(echo "$value" | xargs)
        # Remove quotes if present
        value="${value%\"}"
        value="${value#\"}"
        value="${value%\'}"
        value="${value#\'}"
        ENV_VARS["$key"]="$value"
    fi
done < "$ENV_FILE"

echo "Loaded ${#ENV_VARS[@]} environment variables"
echo ""

# Build JSON object from all env variables
echo "Creating secret: $SECRET_NAME"

json_parts=()
for key in "${!ENV_VARS[@]}"; do
    value="${ENV_VARS[$key]}"
    # Escape JSON special characters
    value=$(echo "$value" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g')
    # Handle null/empty values
    if [ "$value" = "null" ] || [ -z "$value" ]; then
        json_parts+=("\"$key\":null")
    else
        json_parts+=("\"$key\":\"$value\"")
    fi
done

# Build JSON string
json="{"
first=true
for part in "${json_parts[@]}"; do
    if [ "$first" = false ]; then
        json+=","
    fi
    json+="$part"
    first=false
done
json+="}"

if [ "$json" = "{}" ]; then
    echo "Error: No environment variables found in .env file"
    exit 1
fi

echo -n "Creating/updating secret ... "

# Create or update secret
if aws --endpoint-url="$ENDPOINT" --region="$REGION" \
    secretsmanager create-secret \
    --name "$SECRET_NAME" \
    --secret-string "$json" \
    --description "All environment variables from .env (auto-generated)" \
    >/dev/null 2>&1 || \
   aws --endpoint-url="$ENDPOINT" --region="$REGION" \
    secretsmanager update-secret \
    --secret-id "$SECRET_NAME" \
    --secret-string "$json" \
    >/dev/null 2>&1; then
    echo "✓"
else
    echo "✗"
    echo "Error: Failed to create/update secret"
    exit 1
fi

echo ""
echo "============================================================================="
echo "Secrets initialization completed!"
echo "============================================================================="
echo ""
echo "Secret created: $SECRET_NAME"
echo "Variables: ${#ENV_VARS[@]}"
echo ""
