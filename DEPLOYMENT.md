# FetchIt - Deployment Guide

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Environment Configuration](#environment-configuration)
4. [Pre-Deployment Checklist](#pre-deployment-checklist)
5. [Deployment Procedures](#deployment-procedures)
6. [Post-Deployment Verification](#post-deployment-verification)
7. [Database Migrations](#database-migrations)
8. [Rollback Procedures](#rollback-procedures)
9. [Monitoring and Health Checks](#monitoring-and-health-checks)
10. [Troubleshooting](#troubleshooting)
11. [Environment Variables Reference](#environment-variables-reference)

---

## Overview

FetchIt is a Laravel 12 backend application with a modular architecture. This document provides comprehensive deployment instructions for development, staging, and production environments.

### Architecture

- **Backend**: Laravel 12 on PHP 8.5
- **Database**: PostgreSQL 16 with multi-schema architecture
- **Cache/Queue**: Redis 7
- **Storage**: MinIO (S3-compatible) or AWS S3
- **Search**: Elasticsearch 8.15
- **WebSockets**: Soketi (Pusher-compatible)
- **Reverse Proxy**: Nginx
- **Authentication**: Keycloak (optional, JWT supported)

### Deployment Methods

- **Docker Compose**: Recommended for all environments
- **Docker Swarm**: For production orchestration (optional)
- **Kubernetes**: For container orchestration (optional)

---

## Prerequisites

### System Requirements

- **Docker**: Version 20.10+ and Docker Compose 2.0+
- **Disk Space**: Minimum 20GB free space
- **Memory**: Minimum 8GB RAM (16GB recommended for production)
- **CPU**: Minimum 4 cores (8 cores recommended for production)

### Software Requirements

- Docker Desktop (Mac/Windows) or Docker Engine + Docker Compose (Linux)
- Git
- Access to container registry (if using private images)
- SSL certificates (for production)

### Access Requirements

- SSH access to deployment server
- Database access credentials
- Redis access credentials
- Storage service credentials (MinIO/AWS S3)
- Keycloak admin credentials (if using Keycloak)

---

## Environment Configuration

### Environment Types

| Environment     | File Location                               | Purpose                          |
| --------------- | ------------------------------------------- | -------------------------------- |
| **Development** | `docker/dev/docker-compose.dev.yml`         | Local development with debugging |
| **Staging**     | `docker/staging/docker-compose.staging.yml` | Pre-production testing           |
| **Production**  | `docker-compose.yml`                        | Live production environment      |

### Environment Differences

| Feature              | Development     | Staging              | Production           |
| -------------------- | --------------- | -------------------- | -------------------- |
| **APP_ENV**          | `local`         | `staging`            | `production`         |
| **APP_DEBUG**        | `true`          | `false`              | `false`              |
| **Xdebug**           | Enabled         | Available (disabled) | Available (disabled) |
| **OPcache**          | Disabled        | Enabled              | Enabled              |
| **Error Display**    | On              | Off                  | Off                  |
| **Composer Install** | Full (with dev) | Production only      | Production only      |
| **Container Names**  | Standard        | `-staging` suffix    | Standard             |

---

## Pre-Deployment Checklist

### Code Preparation

- [ ] Code reviewed and approved
- [ ] All tests passing (`php artisan test`)
- [ ] Code formatted (`./vendor/bin/pint`)
- [ ] Static analysis passed (`./vendor/bin/phpstan analyze`)
- [ ] Dependencies updated (`composer update`)
- [ ] Database migrations reviewed
- [ ] Environment variables documented

### Infrastructure

- [ ] Docker images built and tested
- [ ] Database backup created (production)
- [ ] Redis data backed up (if persistent)
- [ ] Storage service accessible
- [ ] SSL certificates valid (production)
- [ ] DNS records configured (production)
- [ ] Load balancer configured (production)

### Configuration

- [ ] Environment variables set in `.env` file
- [ ] Database credentials verified
- [ ] Redis credentials verified
- [ ] Storage credentials verified
- [ ] Keycloak configuration verified (if used)
- [ ] Rate limiting configuration reviewed
- [ ] Logging configuration verified

### Monitoring

- [ ] Health check endpoints accessible
- [ ] Monitoring tools configured
- [ ] Alerting rules configured
- [ ] Log aggregation configured

---

## Deployment Procedures

### Development Environment

#### Initial Setup

1. **Clone the repository**

   ```bash
   git clone <repository-url>
   cd fetchit-api
   ```

2. **Create environment file**

   ```bash
   cp backend/.env.example backend/.env
   # Edit backend/.env with your local configuration
   ```

3. **Build and start services**

   ```bash
   docker compose -f docker/dev/docker-compose.dev.yml up -d --build
   ```

4. **Install dependencies**

   ```bash
   docker compose -f docker/dev/docker-compose.dev.yml exec app \
     bash -lc "cd /var/www/html && composer install"
   ```

5. **Generate application key**

   ```bash
   docker compose -f docker/dev/docker-compose.dev.yml exec app \
     bash -lc "cd /var/www/html && php artisan key:generate"
   ```

6. **Create database schemas**

   ```bash
   docker compose -f docker/dev/docker-compose.dev.yml exec app \
     bash -lc "cd /var/www/html && php artisan schema:create --schema=core"

   docker compose -f docker/dev/docker-compose.dev.yml exec app \
     bash -lc "cd /var/www/html && php artisan schema:create --schema=customer_portal"
   ```

7. **Run migrations**
   ```bash
   docker compose -f docker/dev/docker-compose.dev.yml exec app \
     bash -lc "cd /var/www/html && php artisan migrate --seed"
   ```

#### Daily Development

```bash
# Start services
docker compose -f docker/dev/docker-compose.dev.yml up -d

# Stop services
docker compose -f docker/dev/docker-compose.dev.yml down

# View logs
docker compose -f docker/dev/docker-compose.dev.yml logs -f app

# Access shell
docker compose -f docker/dev/docker-compose.dev.yml exec app bash
```

### Staging Environment

#### Deployment Steps

1. **Pull latest code**

   ```bash
   git pull origin staging  # or your staging branch
   ```

2. **Update environment file**

   ```bash
   # Review and update backend/.env for staging
   # Ensure APP_ENV=staging and APP_DEBUG=false
   ```

3. **Build Docker images**

   ```bash
   docker compose -f docker/staging/docker-compose.staging.yml build app
   ```

4. **Run tests**

   ```bash
   docker compose -f docker/staging/docker-compose.staging.yml exec app \
     bash -lc "cd /var/www/html && php artisan test"
   ```

5. **Deploy services**

   ```bash
   docker compose -f docker/staging/docker-compose.staging.yml up -d
   ```

6. **Run database migrations**

   ```bash
   docker compose -f docker/staging/docker-compose.staging.yml exec app \
     bash -lc "cd /var/www/html && php artisan migrate --force"
   ```

7. **Clear caches**

   ```bash
   docker compose -f docker/staging/docker-compose.staging.yml exec app \
     bash -lc "cd /var/www/html && php artisan config:clear && \
     php artisan cache:clear && php artisan route:clear && \
     php artisan view:clear"
   ```

8. **Restart services (if needed)**
   ```bash
   docker compose -f docker/staging/docker-compose.staging.yml restart app
   ```

#### Staging-Specific Commands

```bash
# View logs
docker compose -f docker/staging/docker-compose.staging.yml logs -f

# Check service health
docker compose -f docker/staging/docker-compose.staging.yml ps

# Enable Xdebug for troubleshooting (if needed)
# Add XDEBUG_ENABLED=1 to backend/.env, then:
docker compose -f docker/staging/docker-compose.staging.yml restart app
```

### Production Environment

#### Pre-Deployment

1. **Create database backup**

   ```bash
   docker compose exec db pg_dump -U fetchit fetchit > \
     backup-$(date +%Y%m%d-%H%M%S).sql
   ```

2. **Verify current deployment**

   ```bash
   docker compose ps
   curl http://localhost/api/v1/health
   ```

3. **Prepare deployment branch**
   ```bash
   git checkout production
   git pull origin production
   ```

#### Deployment Steps

1. **Update environment file**

   ```bash
   # Review backend/.env
   # Ensure APP_ENV=production and APP_DEBUG=false
   # Verify all production credentials
   ```

2. **Build production image**

   ```bash
   docker compose build app
   ```

3. **Run tests (optional, recommended)**

   ```bash
   docker compose exec app bash -lc "cd /var/www/html && php artisan test"
   ```

4. **Deploy with zero downtime (blue-green approach)**

   ```bash
   # Option 1: Rolling update (if using multiple app instances)
   docker compose up -d --no-deps --build app

   # Option 2: Standard deployment
   docker compose up -d --build
   ```

5. **Run database migrations**

   ```bash
   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan migrate --force"
   ```

6. **Clear and optimize caches**

   ```bash
   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan config:cache && \
     php artisan route:cache && \
     php artisan view:cache && \
     php artisan event:cache"
   ```

7. **Restart queue workers**

   ```bash
   docker compose restart queue-all
   ```

8. **Restart scheduler**
   ```bash
   docker compose restart scheduler
   ```

#### Production Maintenance

```bash
# View logs
docker compose logs -f app

# Check service status
docker compose ps

# Monitor resource usage
docker stats

# Access application shell
docker compose exec app bash
```

---

## Post-Deployment Verification

### Health Checks

1. **Basic health check**

   ```bash
   curl http://localhost/api/v1/health
   # Expected: {"status":"healthy",...}
   ```

2. **Detailed health check**

   ```bash
   curl http://localhost/api/v1/health/detailed
   # Verify all services are healthy
   ```

3. **Liveness probe**

   ```bash
   curl http://localhost/api/v1/health/liveness
   # Expected: HTTP 200
   ```

4. **Readiness probe**
   ```bash
   curl http://localhost/api/v1/health/readiness
   # Expected: HTTP 200
   ```

### Service Verification

1. **Check container health**

   ```bash
   docker compose ps
   # All services should show "healthy" status
   ```

2. **Verify API endpoints**

   ```bash
   # Test authenticated endpoint (if applicable)
   curl -H "Authorization: Bearer <token>" \
     http://localhost/api/v1/wallet/balance
   ```

3. **Check database connectivity**

   ```bash
   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan tinker"
   # Then run: DB::connection()->getPdo();
   ```

4. **Verify Redis connectivity**

   ```bash
   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan tinker"
   # Then run: Redis::ping();
   ```

5. **Check queue workers**

   ```bash
   docker compose logs queue-all | tail -20
   ```

6. **Verify scheduler**
   ```bash
   docker compose logs scheduler | tail -20
   ```

### Performance Checks

1. **Response time**

   ```bash
   time curl http://localhost/api/v1/health
   ```

2. **Rate limiting**

   ```bash
   # Test rate limit headers
   curl -I http://localhost/api/v1/health
   # Should include X-RateLimit-Limit and X-RateLimit-Remaining
   ```

3. **Database query performance**
   ```bash
   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan tinker"
   # Enable query logging and test endpoints
   ```

---

## Database Migrations

### Migration Strategy

1. **Review migrations**

   ```bash
   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan migrate:status"
   ```

2. **Run migrations**

   ```bash
   # Development/Staging
   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan migrate"

   # Production (with force flag)
   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan migrate --force"
   ```

3. **Rollback (if needed)**
   ```bash
   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan migrate:rollback --step=1"
   ```

### Schema Management

1. **Create schemas**

   ```bash
   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan schema:create --schema=core"

   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan schema:create --schema=customer_portal"
   ```

2. **List schemas**

   ```bash
   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan schema:list"
   ```

3. **Check schema configuration**
   ```bash
   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan schema:check"
   ```

---

## Rollback Procedures

### Quick Rollback (Code Only)

1. **Revert to previous commit**

   ```bash
   git checkout <previous-commit-hash>
   docker compose build app
   docker compose up -d --no-deps app
   ```

2. **Clear caches**
   ```bash
   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan config:clear && php artisan cache:clear"
   ```

### Full Rollback (Including Database)

1. **Stop services**

   ```bash
   docker compose down
   ```

2. **Restore database**

   ```bash
   docker compose exec db psql -U fetchit fetchit < backup-YYYYMMDD-HHMMSS.sql
   ```

3. **Revert code**

   ```bash
   git checkout <previous-commit-hash>
   ```

4. **Rebuild and restart**

   ```bash
   docker compose build app
   docker compose up -d
   ```

5. **Verify rollback**
   ```bash
   curl http://localhost/api/v1/health
   ```

---

## Monitoring and Health Checks

### Health Check Endpoints

| Endpoint                       | Purpose                    | Authentication |
| ------------------------------ | -------------------------- | -------------- |
| `GET /api/v1/health`           | Basic health status        | None           |
| `GET /api/v1/health/detailed`  | Detailed system status     | None           |
| `GET /api/v1/health/liveness`  | Kubernetes liveness probe  | None           |
| `GET /api/v1/health/readiness` | Kubernetes readiness probe | None           |

### Monitoring Commands

```bash
# Container health
docker compose ps

# Application logs
docker compose logs -f app

# Queue worker logs
docker compose logs -f queue-all

# Scheduler logs
docker compose logs -f scheduler

# Database logs
docker compose logs -f db

# Redis logs
docker compose logs -f redis

# Nginx logs
docker compose logs -f nginx
```

### Log Locations

- **Application logs**: `backend/storage/logs/laravel.log`
- **PHP error logs**: `/var/log/php/error.log` (inside container)
- **Nginx logs**: `/var/log/nginx/` (inside container)
- **Queue logs**: Available via `docker compose logs queue-all`

### Performance Monitoring

1. **Resource usage**

   ```bash
   docker stats
   ```

2. **Database connections**

   ```bash
   docker compose exec db psql -U fetchit -c \
     "SELECT count(*) FROM pg_stat_activity;"
   ```

3. **Redis memory**
   ```bash
   docker compose exec redis redis-cli INFO memory
   ```

---

## Troubleshooting

### Common Issues

#### Services Not Starting

1. **Check service status**

   ```bash
   docker compose ps
   ```

2. **View service logs**

   ```bash
   docker compose logs <service-name>
   ```

3. **Check dependencies**
   ```bash
   docker compose ps | grep unhealthy
   ```

#### Database Connection Issues

1. **Verify database is running**

   ```bash
   docker compose exec db pg_isready -U fetchit
   ```

2. **Check environment variables**

   ```bash
   docker compose exec app env | grep DB_
   ```

3. **Test connection**
   ```bash
   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan tinker"
   # Run: DB::connection()->getPdo();
   ```

#### Redis Connection Issues

1. **Verify Redis is running**

   ```bash
   docker compose exec redis redis-cli ping
   ```

2. **Check Redis configuration**
   ```bash
   docker compose exec app env | grep REDIS_
   ```

#### Application Errors

1. **Check Laravel logs**

   ```bash
   docker compose exec app tail -f /var/www/html/storage/logs/laravel.log
   ```

2. **Check PHP error logs**

   ```bash
   docker compose exec app tail -f /var/log/php/error.log
   ```

3. **Clear caches**
   ```bash
   docker compose exec app bash -lc "cd /var/www/html && \
     php artisan config:clear && php artisan cache:clear"
   ```

#### Port Conflicts

1. **Check port usage**

   ```bash
   lsof -i :80  # or netstat -tulpn | grep :80
   ```

2. **Stop conflicting services**
   ```bash
   docker ps | grep <port>
   docker stop <container-id>
   ```

#### Permission Issues

1. **Fix storage permissions**
   ```bash
   docker compose exec app bash -lc "cd /var/www/html && \
     chmod -R 775 storage bootstrap/cache && \
     chown -R www-data:www-data storage bootstrap/cache"
   ```

---

## Environment Variables Reference

### Application Configuration

```env
# Application
APP_NAME="FetchIt"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://api.example.com
APP_TIMEZONE=UTC

# Server
SERVER_NAME=api.example.com
```

### Database Configuration

```env
# PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=fetchit
DB_USERNAME=fetchit
DB_PASSWORD=secret

# Schema Configuration
DB_SCHEMA_CORE=core
DB_SCHEMA_CUSTOMER_PORTAL=customer_portal
DB_SCHEMA_PUBLIC=public
```

### Cache and Queue

```env
# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# Queue
QUEUE_CONNECTION=redis
```

### Storage

```env
# MinIO (Development/Staging)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=minio
AWS_SECRET_ACCESS_KEY=minio123
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=fetchit-bucket
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true

# AWS S3 (Production)
# AWS_ACCESS_KEY_ID=your-access-key
# AWS_SECRET_ACCESS_KEY=your-secret-key
# AWS_DEFAULT_REGION=us-east-1
# AWS_BUCKET=your-bucket-name
# AWS_ENDPOINT=  # Leave empty for AWS S3
```

### Authentication

```env
# Keycloak (if using)
KEYCLOAK_SERVER_URL=http://keycloak:8080
KEYCLOAK_REALM=fetchit
KEYCLOAK_CLIENT_ID=fetchit-api
KEYCLOAK_CLIENT_SECRET=your-secret
KEYCLOAK_REALM_PUBLIC_KEY=your-public-key

# JWT (if using)
JWT_SECRET=your-jwt-secret
JWT_TTL=60
```

### WebSockets

```env
# Soketi/Pusher
PUSHER_APP_ID=local-app
PUSHER_APP_KEY=local-key
PUSHER_APP_SECRET=local-secret
PUSHER_HOST=soketi
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

### Mail

```env
# SMTP
MAIL_MAILER=smtp
MAIL_HOST=mailhog  # Development
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Rate Limiting

```env
# Shared Rate Limits
SHARED_RATE_LIMIT_DEFAULT_MAX=60
SHARED_RATE_LIMIT_DEFAULT_DECAY=1
SHARED_RATE_LIMIT_WALLET_MAX=100
SHARED_RATE_LIMIT_PAYMENT_MAX=100
SHARED_RATE_LIMIT_HEALTH_BASIC_MAX=3
SHARED_RATE_LIMIT_HEALTH_DETAILED_MAX=10
```

### Monitoring

```env
# Sentry (Error Tracking)
SENTRY_LARAVEL_DSN=your-sentry-dsn
SENTRY_TRACES_SAMPLE_RATE=1.0
```

### Xdebug (Development/Staging)

```env
XDEBUG_ENABLED=0  # Set to 1 to enable
XDEBUG_MODE=debug
XDEBUG_CLIENT_HOST=host.docker.internal
XDEBUG_CLIENT_PORT=9004
XDEBUG_START_WITH_REQUEST=yes
XDEBUG_IDEKEY=VSCODE
```

### AWS Secrets Manager (LocalStack for Dev)

The application uses AWS Secrets Manager to load environment variables. For development, LocalStack emulates AWS Secrets Manager.

**Configuration:**

- Secret name is configured in `backend/aws-secret.config.json` (default: `fetchit/dev`)
- Secret contains all environment variables from `.env` file
- Secrets are loaded during Laravel bootstrap via `bootstrap/secrets.php`
- Falls back to `.env` file if secret is unavailable

---

## AWS Secrets Manager (LocalStack)

### Quick Commands

**Create/Update secret from .env:**

```bash
docker compose exec localstack bash /docker-entrypoint-init.d/manage-localstack-secrets.sh create
```

**Drop secret:**

```bash
docker compose exec localstack bash /docker-entrypoint-init.d/manage-localstack-secrets.sh drop
```

**Recreate secret:**

```bash
docker compose exec localstack bash /docker-entrypoint-init.d/manage-localstack-secrets.sh recreate
```

**Drop and recreate secret:**

```bash
docker compose exec localstack bash /docker-entrypoint-init.d/manage-localstack-secrets.sh drop-and-recreate
```

**List all secrets:**

```bash
docker compose exec localstack aws --endpoint-url=http://localhost:4566 \
    --region=us-east-1 secretsmanager list-secrets
```

**View the secret:**

```bash
docker compose exec localstack aws --endpoint-url=http://localhost:4566 \
    --region=us-east-1 secretsmanager get-secret-value --secret-id fetchit/dev | jq .
```

**Reset LocalStack (drop secret and recreate):**

```bash
docker compose restart localstack
docker compose exec localstack bash /docker-entrypoint-init.d/manage-localstack-secrets.sh drop-and-recreate
```

**Note:** Secrets are automatically recreated on container startup if missing. To completely reset, remove the volume:

```bash
docker volume rm fetchit-api_localstack_data
docker compose up -d localstack
```

### How It Works

- A single secret contains all environment variables from your `.env` file
- Secret name: `fetchit/{env}` (e.g., `fetchit/dev`, `fetchit/staging`, `fetchit/prod`)
- Secret name can be customized in `backend/aws-secret.config.json`
- Secrets are automatically created on LocalStack startup if missing
- Application loads secrets during bootstrap, fails if secret unavailable

### Production: Migrate from .env to AWS Secrets Manager

**One-time migration script to populate AWS Secrets Manager from .env:**

```bash
# On production server, run:
./docker/localstack/populate-secrets-from-env.sh
# OR
bash docker/localstack/populate-secrets-from-env.sh
```

**Requirements:**

- AWS CLI installed and configured
- AWS credentials with permissions: `secretsmanager:CreateSecret` and `secretsmanager:UpdateSecret`
- `backend/aws-secret.config.json` file with `secret_name` and `region`
- `.env` file in `backend` directory

**Steps:**

1. Ensure `aws-secret.config.json` is configured with your production secret name and region
2. Install AWS CLI if not already installed: `curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip" && unzip awscliv2.zip && sudo ./aws/install`
3. Configure AWS credentials: `aws configure` (or use environment variables/IAM role)
4. Run the migration script: `./docker/localstack/populate-secrets-from-env.sh`
5. Verify the secret in AWS Secrets Manager console
6. Test your application to ensure it loads secrets correctly
7. Once verified, you can safely delete the `.env` file: `rm backend/.env`

**Note:** The script will create a new secret if it doesn't exist, or update an existing secret if it does.

---

## Additional Resources

- [Main README](README.md) - Project overview
- [Development Setup](docker/dev/README.md) - Development environment
- [Staging Setup](docker/staging/README.md) - Staging environment
- [Architecture Documentation](docs/laravel/TECHNICAL_ARCHITECTURE.md) - Technical details
- [Database Schema Documentation](docs/db/MULTI_SCHEMA_ARCHITECTURE.md) - Database architecture

---

## Support

For deployment issues or questions:

1. Check logs: `docker compose logs -f <service>`
2. Review health checks: `curl http://localhost/api/v1/health/detailed`
3. Consult troubleshooting section above
4. Contact DevOps team or create an issue

---

**Last Updated**: 2026-01-01  
**Version**: 1.0.0
