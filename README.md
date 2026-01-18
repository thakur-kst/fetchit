# FetchIt API

Modern Laravel 12 API backend with modular architecture, built for Gmail-based order sync and management.

## 🚀 Overview

FetchIt is a modular Laravel 12 backend that provides a RESTful API for:

- **Google OAuth + JWT** authentication
- **Gmail account linking** and OAuth flows
- **Gmail sync** to fetch, parse, and store orders from email
- **Order** CRUD and filtering

It uses Docker Compose for local development and a single-schema PostgreSQL design.

### Tech Stack

- **Backend**: Laravel 12 on PHP 8.5
- **Authentication**: Google OAuth + JWT (tymon/jwt-auth)
- **Database**: PostgreSQL 16, single-schema (`fetchit` + `public`)
- **Cache/Queue**: Redis 7, Laravel Horizon
- **Secrets**: AWS Secrets Manager (LocalStack for local dev)
- **Parser**: Node.js Express service (email → order extraction)
- **Reverse Proxy**: Nginx

## 📋 Prerequisites

- Docker Desktop (or Docker Engine + Docker Compose)
- Git

## 🏗️ Architecture

### Modules

| Module | Description |
|--------|-------------|
| **Auth** | Google OAuth, JWT access/refresh tokens, logout |
| **DBCore** | Core models and migrations (`fetchit` schema) |
| **GmailAccounts** | Gmail OAuth, link/unlink accounts, token refresh |
| **GmailSync** | Sync jobs, email parsing jobs, status polling |
| **HealthCheck** | Liveness, readiness, detailed health |
| **Orders** | Order CRUD and filtering |
| **SchemaMgr** | Schema utilities and Artisan commands |
| **Shared** | Shared helpers and traits |

### Database

- **`fetchit` schema**: `users`, `refresh_tokens`, `gmail_accounts`, `gmail_sync_jobs`, `orders`
- **`public` schema**: Laravel framework tables (cache, jobs, sessions, etc.)

## 🚀 Quick Start

### First-Time Setup

1. **Clone and enter the project**
   ```bash
   git clone <repository-url>
   cd fetchit-api
   ```

2. **Environment**
   ```bash
   cp backend/.env.example backend/.env
   ```
   Edit `backend/.env`: set `APP_KEY`, `DB_*`, `REDIS_*`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `PARSER_SERVICE_URL`, `JWT_SECRET`, and `DB_SCHEMA_FETCHIT=fetchit` as needed.

3. **Start services**
   ```bash
   ./docker/start-all-services.sh
   ```
   This starts Redis, Postgres, LocalStack, the main app (app, queue, scheduler), and Nginx.

4. **Backend setup**
   ```bash
   docker compose exec app composer install --no-interaction
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate
   ```

5. **Google OAuth** (optional for local dev)
   - Create a project in [Google Cloud Console](https://console.cloud.google.com/).
   - Enable the Google+ API (or People API) and create OAuth 2.0 credentials.
   - Set `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in `backend/.env`.

6. **Parser service** (for Gmail sync)
   - See `parser-service/README.md` for install and run (e.g. `npm install`, then `node server.js` or PM2).

### Day-to-Day

**Start:**
```bash
./docker/start-all-services.sh
# or: docker compose up -d
```

**Stop:**
```bash
./docker/stop-all-services.sh
# or: docker compose down
```

**Logs:**
```bash
docker compose logs -f app
```

**App shell:**
```bash
docker compose exec app bash
```

## 🌐 Service URLs

- **API**: http://localhost
- **PostgreSQL**: localhost:5432 (db: `fetchit`, user: `fetchit`, password: from `.env`)
- **Redis**: localhost:6379
- **LocalStack**: http://localhost:4566 (Secrets Manager, etc.)
- **Parser service**: See `parser-service/README.md` (run separately)
- **Horizon**: http://localhost/horizon (when enabled and routes configured)

## 📡 API Endpoints

### Health

- `GET /api/v1/health` – Basic health
- `GET /api/v1/health/liveness` – Liveness probe
- `GET /api/v1/health/readiness` – Readiness (DB, Redis, etc.)
- `GET /api/v1/health/detailed` – Detailed checks
- `GET /api/v1/` – Ping

### Auth (Google OAuth + JWT)

- `POST /api/v1/auth/google/verify` – Verify Google ID token, return user + JWT (`idToken`, optional `deviceName`, `deviceId`)
- `POST /api/v1/auth/refresh` – Refresh access token (`refreshToken`)
- `GET /api/v1/auth/me` – Current user *(auth)*
- `POST /api/v1/auth/logout` – Logout *(auth)*

### Gmail Accounts *(auth)*

- Link, unlink, list Gmail accounts; OAuth callback and token refresh. See `app/Modules/GmailAccounts/routes/api.php`.

### Gmail Sync *(auth)*

- Start sync, poll status. See `app/Modules/GmailSync/routes/api.php`.

### Orders *(auth)*

- CRUD and filtering. See `app/Modules/Orders/routes/api.php`.

## 🐳 Docker

### Main services (from `docker compose`)

- **app** – Laravel (PHP 8.5 FPM)
- **queue** – `php artisan queue:work` (e.g. `email-parsing`)
- **scheduler** – `php artisan schedule:run`
- **nginx** – Serves Laravel, reverse proxy

### Infrastructure (from `./docker/start-all-services.sh`)

- **redis** – Cache and queues
- **postgres** – PostgreSQL 16
- **localstack** – AWS APIs (e.g. Secrets Manager) for local dev

See `docker-compose.yml` and `docker/start-all-services.sh` for details.

## 🧪 Testing

```bash
docker compose exec app php artisan test
```

Filter by module:

```bash
docker compose exec app php artisan test --filter HealthCheck
docker compose exec app php artisan test --filter Auth
```

## 🛠️ Development

### New module

```bash
docker compose exec app php artisan make:module {ModuleName} [--api-version=v1]
```

### Schema

- **List**: `php artisan schema:list`
- **Create**: `php artisan schema:create` (if used; `fetchit` is created by migrations)
- **Check**: `php artisan schema:check`

### Code quality

```bash
docker compose exec app ./vendor/bin/pint
docker compose exec app ./vendor/bin/phpstan analyze
```

## 📁 Project Structure

```
fetchit-api/
├── backend/                    # Laravel app
│   ├── app/Modules/
│   │   ├── Auth/               # Google OAuth, JWT
│   │   ├── DBCore/              # Models, fetchit migrations
│   │   ├── GmailAccounts/      # Gmail OAuth, link/unlink
│   │   ├── GmailSync/          # Sync jobs, ParseEmailJob
│   │   ├── HealthCheck/
│   │   ├── Orders/
│   │   ├── SchemaMgr/
│   │   └── Shared/
│   ├── config/
│   ├── routes/
│   └── bootstrap/
│       ├── app.php
│       └── secrets.php         # AWS Secrets Manager (LocalStack in dev)
├── parser-service/             # Node.js email parser
│   ├── server.js
│   ├── src/parsers/
│   └── README.md
├── docker/
│   ├── start-all-services.sh
│   ├── stop-all-services.sh
│   ├── restart-all-services.sh
│   ├── localstack/
│   ├── nginx/
│   ├── postgres/
│   ├── php/
│   ├── redis/
│   └── supervisor/
├── docker-compose.yml
├── Dockerfile
└── README.md
```

## 🔧 Configuration

### Google OAuth

In `backend/.env`:

```env
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
```

### Parser service

```env
PARSER_SERVICE_URL=http://parser:3000
```

Run the parser separately (see `parser-service/README.md`); in Docker you can add a `parser` service and point this URL to it.

### JWT

`config/jwt.php` and `JWT_SECRET` in `.env` (from `php artisan jwt:secret` or manual).

### Secrets (LocalStack)

- `bootstrap/secrets.php` loads from AWS Secrets Manager.
- Local: endpoint `http://localstack:4566`, secret e.g. `fetchit/dev`.
- If Secrets Manager is unavailable, the app falls back to `.env` (see `bootstrap/secrets.php`).

## 🐛 Debugging

- **Shell**: `docker compose exec app bash`
- **Logs**: `docker compose exec app tail -f storage/logs/laravel.log`
- **Services**: `docker compose ps` and `docker compose logs SERVICE`

## 📝 License

This project is licensed under the MIT License.
