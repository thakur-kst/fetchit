# Docker Services Management

This directory contains all Docker service configurations for the Channel Engine API. Each service has its own `docker-compose.yml` file and can be run independently.

## Quick Start

For automated startup, use the provided scripts:

```bash
# Start all services
./start-all-services.sh

# Stop all services
./stop-all-services.sh

# Restart all services
./restart-all-services.sh
```

## Manual Service Startup Order

If you prefer to start services manually, follow this order to ensure all dependencies are met:

### Step 1: Infrastructure Services (Foundation)

These services create the networks and provide core infrastructure that other services depend on.

#### 1.1 Redis (Cache & Queue Backend)
```bash
cd redis
docker compose up -d
```
- **Purpose**: Provides caching and queue backend for Laravel
- **Port**: `6379`
- **Network**: Creates `redis_redis_network`
- **Dependencies**: None
- **Why First**: Other services may need Redis for caching/queues

#### 1.2 PostgreSQL (Database)
```bash
cd postgres
docker compose up -d
```
- **Purpose**: Main database for the application
- **Port**: `5432`
- **Network**: Creates `postgres_postgres_network`
- **Dependencies**: None
- **Why Second**: Database must be ready before application starts

#### 1.3 Soketi (WebSocket Server)
```bash
cd soketi
docker compose up -d
```
- **Purpose**: WebSocket server for real-time features (Pusher protocol)
- **Port**: `6001`
- **Network**: Creates `soketi_soketi_network`
- **Dependencies**: None
- **Why Third**: Provides real-time communication infrastructure

#### 1.4 Keycloak (Authentication)
```bash
cd keycloak
docker compose up -d
```
- **Purpose**: Identity and access management
- **Ports**: `8080` (HTTP), `8443` (HTTPS)
- **Network**: Creates `keycloak_keycloak_network`
- **Dependencies**: PostgreSQL (if using external DB)
- **Why Fourth**: Authentication service needed by application

#### 1.5 MinIO (Object Storage)
```bash
cd minio
docker compose up -d
```
- **Purpose**: S3-compatible object storage
- **Ports**: `9000-9001`
- **Network**: Creates `minio_minio_network`
- **Dependencies**: None
- **Why Fifth**: File storage service

#### 1.6 Elasticsearch (Search Engine) - Optional
```bash
cd elasticsearch
docker compose up -d
```
- **Purpose**: Full-text search and analytics
- **Port**: `9200`
- **Network**: Creates `elasticsearch_elasticsearch_network`
- **Dependencies**: None
- **Why Sixth**: Optional search service

#### 1.7 MailHog (Email Testing) - Optional
```bash
cd mailhog
docker compose up -d
```
- **Purpose**: Email testing tool for development
- **Port**: `8025` (Web UI), `1025` (SMTP)
- **Network**: Creates `mailhog_mailhog_network`
- **Dependencies**: None
- **Why Seventh**: Optional email testing service

#### 1.8 pgAdmin (Database Management) - Optional
```bash
cd pgadmin
docker compose up -d
```
- **Purpose**: Web-based PostgreSQL administration tool
- **Port**: `5050`
- **Network**: Uses `postgres_postgres_network`
- **Dependencies**: PostgreSQL
- **Why Eighth**: Optional database management tool

### Step 2: Application Services

These services depend on the infrastructure services above.

#### 2.1 Main Application Services
```bash
cd ../..  # Go back to project root
docker compose up -d
```
- **Services Started**:
  - `channel-engine-app` - PHP-FPM application container
  - `channel-engine-localstack` - AWS services emulator
  - `channel-engine-queue-critical-high` - Queue worker (critical/high priority)
  - `channel-engine-queue-medium-default` - Queue worker (medium/default priority)
  - `channel-engine-queue-low` - Queue worker (low priority)
  - `channel-engine-scheduler` - Laravel scheduler
- **Dependencies**: 
  - Redis network
  - PostgreSQL network
  - Soketi network
  - Keycloak network
  - MinIO network
- **Why After Infrastructure**: Application needs all infrastructure services running

#### 2.2 Nginx (Web Server)
```bash
cd nginx
docker compose up -d
```
- **Purpose**: Reverse proxy and web server
- **Ports**: `80` (HTTP), `443` (HTTPS)
- **Networks**: 
  - `ce-api_channel-engine` (connects to app)
  - `soketi_soketi_network` (for WebSocket proxy)
  - `keycloak_keycloak_network` (for auth proxy)
- **Dependencies**: 
  - App service must be running and healthy
  - All networks must exist
- **Why Last**: Nginx proxies to the app service, so app must be ready first

## Service Dependencies Diagram

```
┌─────────────┐
│   Redis     │ (No dependencies)
└─────────────┘
       │
       ├─────────────────┐
       │                 │
┌─────────────┐   ┌─────────────┐
│ PostgreSQL  │   │   Soketi    │ (No dependencies)
└─────────────┘   └─────────────┘
       │                 │
       │                 │
┌─────────────┐   ┌─────────────┐
│  Keycloak   │   │   MinIO     │ (No dependencies)
└─────────────┘   └─────────────┘
       │                 │
       └────────┬────────┘
                │
         ┌──────────────┐
         │ Main App      │ (Depends on all above)
         │ Services      │
         └──────────────┘
                │
         ┌──────────────┐
         │    Nginx     │ (Depends on app)
         └──────────────┘
```

## Verification Steps

After starting services, verify they're running:

### 1. Check All Containers
```bash
docker ps
```

### 2. Check Specific Service
```bash
# Check if a specific container is running
docker ps | grep <container-name>

# Examples:
docker ps | grep channel-engine-app
docker ps | grep channel-engine-nginx
docker ps | grep redis
```

### 3. Check Service Health
```bash
# Check application health
curl http://localhost/api/v1/health

# Check nginx
curl http://localhost

# Check Keycloak
curl http://localhost:8080

# Check Redis
redis-cli -h localhost -p 6379 ping

# Check PostgreSQL
psql -h localhost -p 5432 -U postgres -c "SELECT 1;"
```

### 4. Check Service Logs
```bash
# View logs for a specific service
docker logs <container-name>

# Follow logs
docker logs -f <container-name>

# Examples:
docker logs channel-engine-app
docker logs channel-engine-nginx
docker logs redis
```

## Network Requirements

All services use external networks. The networks are created automatically when you start each service:

- `redis_redis_network` - Created by redis service
- `postgres_postgres_network` - Created by postgres service
- `soketi_soketi_network` - Created by soketi service
- `keycloak_keycloak_network` - Created by keycloak service
- `minio_minio_network` - Created by minio service
- `ce-api_channel-engine` - Created by main docker-compose.yml

**Important**: Services that depend on these networks must be started after the network-creating services.

## Common Issues

### Issue: "network declared as external, but could not be found"

**Solution**: Start the service that creates the network first. For example:
- If `redis_redis_network` is missing, start the redis service first
- If `soketi_soketi_network` is missing, start the soketi service first

### Issue: "Connection refused" when accessing application

**Possible Causes**:
1. Nginx is not running - Start nginx service
2. App service is not healthy - Check app logs: `docker logs channel-engine-app`
3. Network connectivity issue - Verify networks exist: `docker network ls`

**Solution**: 
```bash
# Check if nginx is running
docker ps | grep nginx

# If not, start it
cd docker/nginx
docker compose up -d

# Check app health
docker logs channel-engine-app
```

### Issue: Service fails to start

**Solution**:
1. Check service logs: `docker logs <container-name>`
2. Verify dependencies are running
3. Check port conflicts: `lsof -i :<port>` or `netstat -an | grep <port>`
4. Verify environment variables are set correctly

## Stopping Services

To stop services manually, reverse the startup order:

1. Stop nginx: `cd docker/nginx && docker compose down`
2. Stop main app services: `cd ../.. && docker compose down`
3. Stop infrastructure services (in reverse order):
   - pgAdmin
   - MailHog
   - Elasticsearch
   - MinIO
   - Keycloak
   - Soketi
   - PostgreSQL
   - Redis

Or use the provided script: `./stop-all-services.sh`

## Service Ports Summary

| Service | Port(s) | URL |
|---------|---------|-----|
| Nginx (Main API) | 80, 443 | http://localhost |
| Keycloak | 8080, 8443 | http://localhost:8080 |
| Soketi | 6001 | http://localhost:6001 |
| Redis | 6379 | localhost:6379 |
| PostgreSQL | 5432 | localhost:5432 |
| MinIO | 9000-9001 | http://localhost:9000 |
| LocalStack | 4566 | http://localhost:4566 |
| Elasticsearch | 9200 | http://localhost:9200 |
| MailHog UI | 8025 | http://localhost:8025 |
| MailHog SMTP | 1025 | localhost:1025 |
| pgAdmin | 5050 | http://localhost:5050 |

## Additional Resources

- Individual service READMEs are available in each service directory
- For automated management, use the provided scripts:
  - `start-all-services.sh` - Start all services
  - `stop-all-services.sh` - Stop all services
  - `restart-all-services.sh` - Restart all services

## Notes

- Services marked as "Optional" are not required for basic application functionality
- Always start infrastructure services before application services
- Wait for services to be healthy before starting dependent services
- Use `docker compose ps` to check service status
- Use `docker compose logs` to troubleshoot issues

