#!/bin/bash

# =============================================================================
# Populate AWS Secrets Manager from .env file
# =============================================================================
# This script reads all environment variables from .env file and
# creates/updates a secret in AWS Secrets Manager (production).
#
# Usage:
#   ./docker/localstack/populate-secrets-from-env.sh
#   OR
#   bash docker/localstack/populate-secrets-from-env.sh
#
# Requirements:
#   - AWS CLI installed and configured
#   - AWS credentials with secretsmanager:CreateSecret and secretsmanager:UpdateSecret permissions
#   - aws-secret.config.json file with secret_name and region
#   - .env file in backend directory
# =============================================================================

set -e

# Get script directory and project root
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
BACKEND_DIR="$PROJECT_ROOT/backend"

# Configuration file path
CONFIG_FILE="$BACKEND_DIR/aws-secret.config.json"
ENV_FILE="$BACKEND_DIR/.env"

echo "============================================================================="
echo "Populate AWS Secrets Manager from .env"
echo "============================================================================="

# Check if config file exists
if [ ! -f "$CONFIG_FILE" ]; then
    echo "Error: aws-secret.config.json not found at: $CONFIG_FILE"
    echo "Please create the config file with 'secret_name' and 'region' fields."
    exit 1
fi

# Read secret name and region from config file
SECRET_NAME=$(grep -o '"secret_name"[[:space:]]*:[[:space:]]*"[^"]*"' "$CONFIG_FILE" | cut -d'"' -f4 || echo "")
REGION=$(grep -o '"region"[[:space:]]*:[[:space:]]*"[^"]*"' "$CONFIG_FILE" | cut -d'"' -f4 || echo "")

if [ -z "$SECRET_NAME" ]; then
    echo "Error: 'secret_name' is required in aws-secret.config.json"
    exit 1
fi

if [ -z "$REGION" ]; then
    echo "Error: 'region' is required in aws-secret.config.json"
    exit 1
fi

# Check if .env file exists
if [ ! -f "$ENV_FILE" ]; then
    echo "Error: .env file not found at: $ENV_FILE"
    exit 1
fi

echo "Secret Name: $SECRET_NAME"
echo "Region: $REGION"
echo "Env File: $ENV_FILE"
echo "============================================================================="
echo ""

# Check if AWS CLI is installed
if ! command -v aws &> /dev/null; then
    echo "Error: AWS CLI is not installed or not in PATH"
    echo "Please install AWS CLI: https://aws.amazon.com/cli/"
    exit 1
fi

# Check AWS credentials
if ! aws sts get-caller-identity &> /dev/null; then
    echo "Error: AWS credentials not configured"
    echo "Please configure AWS credentials using one of these methods:"
    echo "  1. AWS CLI: aws configure"
    echo "  2. Environment variables: AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY"
    echo "  3. IAM role (if running on EC2)"
    exit 1
fi

echo "AWS credentials verified"
echo ""

# Read .env file and build JSON object
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

if [ ${#ENV_VARS[@]} -eq 0 ]; then
    echo "Error: No environment variables found in .env file"
    exit 1
fi

echo "Loaded ${#ENV_VARS[@]} environment variables"
echo ""

# Build JSON object from all env variables
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

# Check if secret exists
echo "Checking if secret exists..."
if aws secretsmanager describe-secret \
    --secret-id "$SECRET_NAME" \
    --region "$REGION" \
    >/dev/null 2>&1; then
    SECRET_EXISTS=true
    echo "Secret '$SECRET_NAME' already exists, will update it."
else
    SECRET_EXISTS=false
    echo "Secret '$SECRET_NAME' does not exist, will create new secret."
fi

echo ""

# Create or update secret
if [ "$SECRET_EXISTS" = true ]; then
    echo "Updating secret: $SECRET_NAME"
    if aws secretsmanager update-secret \
        --secret-id "$SECRET_NAME" \
        --secret-string "$json" \
        --region "$REGION" \
        >/dev/null 2>&1; then
        echo "✓ Secret updated successfully!"
    else
        echo "✗ Failed to update secret"
        exit 1
    fi
else
    echo "Creating secret: $SECRET_NAME"
    if aws secretsmanager create-secret \
        --name "$SECRET_NAME" \
        --secret-string "$json" \
        --description "Environment variables migrated from .env file" \
        --region "$REGION" \
        >/dev/null 2>&1; then
        echo "✓ Secret created successfully!"
    else
        echo "✗ Failed to create secret"
        exit 1
    fi
fi

echo ""
echo "============================================================================="
echo "Success! Secret '$SECRET_NAME' has been populated with ${#ENV_VARS[@]} environment variables."
echo "============================================================================="
echo ""
echo "Next steps:"
echo "1. Verify the secret in AWS Secrets Manager console"
echo "2. Test your application to ensure it loads secrets correctly"
echo "3. Once verified, you can safely delete the .env file"
echo ""

