# FetchIt API

Modern Laravel 12 API backend with modular architecture, built for scalability and maintainability.

## 🚀 Overview

FetchIt is a modular Laravel 12 backend application that provides a RESTful API. It uses Docker Compose for local development and follows a modular architecture with PostgreSQL multi-schema database design.

### Tech Stack

- **Backend**: Laravel 12 on PHP 8.5
- **Authentication**: Keycloak (Identity and Access Management) - *configured but module in development*
- **Database**: PostgreSQL 16 with multi-schema architecture (core, customer_portal, public)
- **Cache/Queue**: Redis 7
- **Storage**: MinIO (S3-compatible)
- **Search**: Elasticsearch 8.15
- **WebSockets**: Soketi (Pusher-compatible)
- **Reverse Proxy**: Nginx
- **Mail**: Mailhog (development)
- **UUID**: Ramsey UUID v5.0

## 📋 Prerequisites

- Docker Desktop (or Docker Engine + Docker Compose)
- Git

## 🏗️ Architecture

The backend follows a **modular architecture** with self-contained modules:

- **Modules**: Located in `app/Modules/{ModuleName}/`
  - Each module is package-ready and self-contained
  - Modules include their own routes, controllers, services, models, migrations
  - Service providers auto-register routes and dependencies

### Current Modules

#### ✅ Production Ready

1. **HealthCheck** - Health monitoring and status endpoints
   - Basic health checks
   - Liveness and readiness probes
   - Detailed health status with system checks (database, cache, Redis)
   - Health check logging

2. **DatabaseFoundation** - Centralized database migrations and schema management
   - Multi-schema organization (core, customer_portal, public)
   - 17 migrations organized by schema
   - Core schema: organizations, branches, roles, permissions
   - Customer Portal schema: users, customer_profiles, audit logs (legacy naming)
   - Framework schema: Laravel system tables

3. **SchemaManagement** - PostgreSQL multi-schema utilities
   - Schema creation and management
   - Schema-aware migrations
   - Model trait for automatic schema detection
   - Artisan commands for schema operations

#### 🚧 In Development

4. **Customer** - Customer management functionality
5. **Tenancy** - Multi-tenant support

### Database Architecture

The application uses **PostgreSQL multi-schema architecture**:

- **`core` schema**: Master data, organizations, branches, roles, permissions
- **`customer_portal` schema**: Tenant-specific data, users, customer profiles, audit logs
- **`public` schema**: Laravel framework tables (cache, jobs, sessions)



## 🚀 Quick Start

### First-Time Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd fetchit-api
   ```

2. **Create environment file**
   ```bash
   cp .env.example backend/.env  # if .env.example exists
   ```
   (Adjust secrets if needed)

3. **Build and start Docker containers**
   ```bash
   docker compose up -d --build
   ```
  

4. **Install backend dependencies**
   ```bash
   docker compose exec app bash -lc "cd /var/www/html && composer install"
   docker compose exec app bash -lc "cd /var/www/html && php artisan key:generate"
   ```

5. **Create PostgreSQL schemas**
  
   To create the schemas, run:

   ```bash
     docker compose exec app bash
     php artisan schema:create --schema=core
     php artisan schema:create --schema=customer_portal

    ```

6. **Run database migrations**
   ```bash
   docker compose exec app bash -lc "cd /var/www/html && php artisan migrate --seed"
   ```

7. **Configure Keycloak in Laravel** (if using Keycloak)
   Add Keycloak environment variables to `backend/.env`:
   ```env
   KEYCLOAK_SERVER_URL=http://keycloak:8080
   KEYCLOAK_REALM=fetchit
   KEYCLOAK_CLIENT_ID=fetchit-api
   KEYCLOAK_CLIENT_SECRET=<secret-from-init-script>
   KEYCLOAK_REALM_PUBLIC_KEY=<public-key>
   ```
### Day-to-Day Usage

**Start the stack:**
```bash
docker compose up -d
```

**Stop the stack:**
```bash
docker compose down
```

**View logs:**
```bash
docker compose logs -f [service-name]

```

**Access app shell:**
```bash
docker compose exec app bash
```

## 🌐 Service URLs

Once the stack is running, access services at:

- **Application**: http://localhost
- **Keycloak Admin**: http://localhost:8080 (admin / admin) - *if configured*
- **Keycloak**: http://localhost:8080 (direct access) - *if configured*
- **PostgreSQL**: localhost:54321 (db: `fetchit`, user: `fetchit`, password: `secret`)
- **pgAdmin**: http://localhost:5050 (email: `admin@example.com`, password: `admin`)
- **Redis**: localhost:6379
- **Mailhog UI**: http://localhost:8025
- **MinIO Console**: http://localhost:9001 (user: `minio`, password: `minio123`)
- **MinIO S3 API**: http://localhost:9000
- **Elasticsearch**: http://localhost:9200
- **Soketi WebSocket**: ws://localhost:6001/app/local-key
- **Kafka**: localhost:9092 (broker)
- **Kafka UI**: http://localhost:8089 (web interface)
- **Zookeeper**: localhost:2181

## 📡 API Endpoints

### Health Check

- `GET /api/v1/health` - Basic health check
- `GET /api/v1/health/liveness` - Liveness probe (always returns 200)
- `GET /api/v1/health/readiness` - Readiness probe (checks system dependencies)
- `GET /api/v1/health/detailed` - Detailed health status with system checks
- `GET /api/v1/` - Simple ping endpoint

All health check endpoints are publicly accessible (no authentication required).

### Authentication

Authentication endpoints are mentioned in routes but the Auth module is currently in development. Keycloak is configured in Docker but the Laravel integration module is not yet implemented.


## 🐳 Docker Services

All services share a single Docker network `fetchit`:

- **app**: PHP 8.5 FPM, Laravel 12 backend
- **keycloak**: Identity and Access Management (IAM) server - *configured but module in development*
- **nginx**: Serves Laravel from `/public`, proxies Keycloak auth endpoints and Soketi websockets
- **queue**: Runs `php artisan queue:work`
- **scheduler**: Runs `php artisan schedule:run` every 60s
- **soketi**: Pusher-compatible WebSocket server
- **db**: PostgreSQL 16
- **pgadmin**: UI for managing PostgreSQL
- **redis**: Cache + queue backing store
- **mailhog**: Development SMTP + web UI
- **minio**: S3-compatible storage
- **elasticsearch**: Single-node ES for search
- **kafka**: Apache Kafka message broker
- **zookeeper**: ZooKeeper (required by Kafka)
- **kafka-ui**: Web UI for Kafka management

See `docker-compose.yml` for exact configuration.

## 🧪 Testing

Run the test suite:

```bash
docker compose exec app bash -lc "cd /var/www/html && php artisan test"
```

Run specific module tests:

```bash
docker compose exec app bash -lc "cd /var/www/html && php artisan test --filter HealthCheck"
docker compose exec app bash -lc "cd /var/www/html && php artisan test --filter DatabaseFoundation"
docker compose exec app bash -lc "cd /var/www/html && php artisan test --filter SchemaManagement"
```

## 🛠️ Development

### Creating a New Module

Use the module generator:

```bash
docker compose exec app bash -lc "cd /var/www/html && php artisan make:module {ModuleName} [--api-version=v1]"
```

This scaffolds:
- Module directory structure
- Routes, controllers, services
- Module service provider
- Configuration file

### Database Schema Management

**Create schemas:**
```bash
docker compose exec app bash -lc "cd /var/www/html && php artisan schema:create"
```

**List schemas:**
```bash
docker compose exec app bash -lc "cd /var/www/html && php artisan schema:list"
```

**Check schema configuration:**
```bash
docker compose exec app bash -lc "cd /var/www/html && php artisan schema:check"
```

### Code Quality

**Format code:**
```bash
docker compose exec app bash -lc "cd /var/www/html && ./vendor/bin/pint"
```

**Run static analysis:**
```bash
docker compose exec app bash -lc "cd /var/www/html && ./vendor/bin/phpstan analyze"
```

## 📁 Project Structure

```
fetchit-api/
├── backend/                 # Laravel application
│   ├── app/
│   │   ├── Modules/         # Self-contained modules
│   │   │   ├── HealthCheck/     # Health monitoring
│   │   │   ├── DatabaseFoundation/  # Database migrations
│   │   │   ├── SchemaManagement/    # Schema utilities
│   │   │   ├── Customer/           # In development
│   │   │   └── Tenancy/            # In development
│   │   ├── Console/
│   │   ├── Http/
│   │   └── Providers/
│   ├── config/
│   ├── database/
│   ├── docs/                # Architecture documentation
│   ├── routes/
│   └── tests/
├── docker/                  # Docker configuration files
│   ├── nginx/               # Nginx configuration
│   │   └── conf.d/
│   │       └── default.conf
│   ├── keycloak/              # Keycloak configuration
│   │   ├── init-keycloak-db.sh
│   │   ├── keycloak-entrypoint.sh
│   │   └── keycloak-init.sh
│   ├── localstack/            # LocalStack configuration
│   │   ├── init-localstack-secrets.sh
│   │   ├── localstack-startup.sh
│   │   ├── manage-localstack-secrets.sh
│   │   └── populate-secrets-from-env.sh
│   └── minio/                 # MinIO configuration
│       └── init-minio-bucket.sh
├── docs/                    # Project documentation
├── docker-compose.yml       # Docker Compose configuration
├── Dockerfile              # PHP-FPM container definition
├── Makefile                # Development shortcuts
└── README.md               # This file
```

## 🔧 Additional Services

### WebSockets via Soketi

- `soketi` is Pusher-compatible and exposed on port `6001`
- Laravel config matches: `PUSHER_APP_ID=local-app`, `PUSHER_APP_KEY=local-key`, `PUSHER_APP_SECRET=local-secret`
- Nginx forwards `/app/*` requests to `soketi:6001`

### MinIO Integration

- Service: `minio` (port 9000 S3 API, 9001 console)
- Laravel `.env` uses `FILESYSTEM_DISK=s3` and AWS-style keys/endpoint pointing at MinIO
- Create bucket: `./docker/minio/init-minio-bucket.sh fetchit-bucket`

### Logging & Observability

- Laravel logs to `backend/storage/logs` (inside `app` container)
- Container logs: `docker compose logs -f app`
- Elasticsearch runs in single-node mode for local dev
- Health check logs stored in `customer_portal.health_check_logs` table

### Queues & Scheduler

- **Queue**: Runs `php artisan queue:work --verbose --tries=3 --timeout=90`
- **Scheduler**: Runs `php artisan schedule:run --verbose --no-interaction` every 60 seconds

## 🐛 Debugging

- **Shell into app container:**
  ```bash
  docker compose exec app bash
  ```

- **Check Laravel logs:**
  ```bash
  docker compose exec app bash -lc "cd /var/www/html && tail -f storage/logs/laravel.log"
  ```

- **Check service health:**
  ```bash
  docker compose ps
  docker compose logs SERVICE_NAME
  ```

- **Check database schemas:**
  ```bash
  docker compose exec app bash -lc "cd /var/www/html && php artisan schema:list --tables"
  ```

## 🤝 Contributing

1. Create a feature branch from `dev`
2. Make your changes following the module structure
3. Write tests for new functionality
4. Ensure all tests pass
5. Update documentation
6. Submit a merge request

## 📝 License

This project is licensed under the MIT License.
