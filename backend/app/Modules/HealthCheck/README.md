# HealthCheck Module

A self-contained, package-ready health check module for Laravel applications. This module provides comprehensive health monitoring endpoints with logging capabilities.

## 📦 Package-Ready Structure

```
app/Modules/HealthCheck/
├── DTOs/                            # Data Transfer Objects
│
├── Services/                        # Services
│   ├── Checkers/                   # Health checker implementations
│   └── HealthCheckApplicationService.php
│
├── ValueObjects/                    # Value objects
│
├── Http/                            # HTTP Layer
│   ├── Controllers/                 # API controllers
│   │   └── Api/V1/
│   └── Middleware/                  # Middleware
│
├── Models/                          # Eloquent Models
│
├── Console/                         # Console Commands
│
├── Exceptions/                      # Custom Exceptions
│
├── database/                        # Database Layer
│   ├── Migrations/                  # Database migrations
│   ├── Factories/                   # Model factories
│   └── Seeders/                     # Database seeders
│
├── routes/                          # Routes
│   ├── api.php                      # API routes
│   ├── web.php                      # Web routes
│   ├── console.php                  # Console routes
│   └── channels.php                 # Broadcast channels
│
├── config/                          # Configuration
│   └── healthcheck.php              # Module configuration
│
├── Providers/                       # Service Providers
│   └── HealthCheckServiceProvider.php
│
├── composer.json                    # Package definition
└── README.md                        # This file
```

## ✨ Features

- **4 Health Check Endpoints**:
  - `GET /api/v1/health` - Basic health status
  - `GET /api/v1/health/detailed` - Detailed health with all checks
  - `GET /api/v1/health/readiness` - Readiness probe (for Kubernetes)
  - `GET /api/v1/health/liveness` - Liveness probe (for Kubernetes)

- **Health Checkers**:
  - Application Checker (PHP version, Laravel version, etc.)
  - Database Checker (connection and query test)
  - Cache Checker (cache operations)
  - Redis Checker (connection test)

- **Logging Middleware**:
  - Request/Response logging
  - Execution time tracking
  - Smart log levels (INFO/WARNING/ERROR)

- **Database Logging** (Optional):
  - Historical health check data
  - Performance metrics
  - Failure tracking

## 🚀 Installation

### As Part of Main Application

The module is already integrated and will auto-register via the service provider.

### As Standalone Package

To extract this module as a standalone package:

1. **Copy the module directory**:
```bash
cp -r app/Modules/HealthCheck /path/to/new/package
```

2. **Update composer.json** in the package:
```json
{
    "name": "your-org/healthcheck",
    "description": "HealthCheck Module",
    "autoload": {
        "psr-4": {
            "Modules\\HealthCheck\\": "src/"
        }
    }
}
```

3. **Move files to src/ directory**:
```bash
mkdir src
mv DTOs Services ValueObjects Http Models Console Exceptions database routes config Providers src/
```

4. **Install in your Laravel app**:
```bash
composer require your-org/healthcheck
```

5. **Register the service provider** in `bootstrap/providers.php`:
```php
Modules\HealthCheck\Providers\HealthCheckServiceProvider::class,
```

## ⚙️ Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=healthcheck-config
```

Edit `config/healthcheck.php`:

```php
return [
    'enabled' => env('HEALTHCHECK_ENABLED', true),

    'checkers' => [
        'application' => true,
        'database' => true,
        'cache' => true,
        'redis' => true,
    ],

    'logging' => [
        'enabled' => true,
        'channel' => 'stack',
    ],

    'thresholds' => [
        'slow_check' => 1000,  // ms
        'warning_check' => 500, // ms
    ],
];
```

## 📊 Usage

### Basic Health Check

```bash
curl http://localhost:8000/api/v1/health
```

Response:
```json
{
    "status": "healthy",
    "timestamp": "2024-11-16T07:38:33+00:00",
    "service": "CustomerPortal",
    "environment": "local"
}
```

### Detailed Health Check

```bash
curl http://localhost:8000/api/v1/health/detailed
```

Response:
```json
{
    "status": "healthy",
    "timestamp": "2024-11-16T07:38:33+00:00",
    "service": "CustomerPortal",
    "environment": "local",
    "checks": {
        "application": {
            "status": "healthy",
            "message": "Application is running",
            "details": {
                "php_version": "8.4.14",
                "laravel_version": "12.38.1"
            }
        },
        "database": {
            "status": "healthy",
            "message": "Database connection successful"
        }
    }
}
```

### Kubernetes Probes

**Readiness Probe**:
```yaml
readinessProbe:
  httpGet:
    path: /api/v1/health/readiness
    port: 8000
  initialDelaySeconds: 5
  periodSeconds: 10
```

**Liveness Probe**:
```yaml
livenessProbe:
  httpGet:
    path: /api/v1/health/liveness
    port: 8000
  initialDelaySeconds: 15
  periodSeconds: 20
```

## 🧪 Testing

Run the health check seeder:

```bash
php artisan db:seed --class=Modules\\HealthCheck\\Database\\Seeders\\HealthCheckLogSeeder
```

Create test data with factories:

```php
use Modules\HealthCheck\Models\HealthCheckLog;

// Create successful health check logs
HealthCheckLog::factory()->count(10)->successful()->create();

// Create failed health check logs
HealthCheckLog::factory()->count(5)->failed()->create();

// Create slow health check logs
HealthCheckLog::factory()->count(3)->slow()->create();

// Create logs for specific endpoint
HealthCheckLog::factory()
    ->count(10)
    ->forEndpoint('api/v1/health/detailed')
    ->create();
```

## 🏗️ Architecture

This module follows a **simplified layered architecture**:

### Services Layer
- **Checkers** in `Services/Checkers/` for health check implementations
- **Services** orchestrate health checks and return DTOs
- **ValueObjects** for type-safe data structures
- **DTOs** for data transfer between layers

### HTTP Layer
- **Thin controllers** in `Http/Controllers/`
- **Middleware** for cross-cutting concerns in `Http/Middleware/`
- **Versioned API structure** (`Api/V1/`)

## 📝 Logging

All health check requests are automatically logged:

```
[2024-11-16 07:38:33] local.INFO: HealthCheck Request
{
    "endpoint": "api/v1/health",
    "method": "GET",
    "ip": "127.0.0.1",
    "timestamp": "2024-11-16T07:38:33+00:00"
}

[2024-11-16 07:38:33] local.INFO: HealthCheck Response
{
    "endpoint": "api/v1/health",
    "status_code": 200,
    "execution_time_ms": 20.1,
    "health_status": "healthy",
    "timestamp": "2024-11-16T07:38:33+00:00"
}
```

## 🔧 Extending

### Add Custom Health Checker

1. Create checker class implementing `HealthCheckerInterface`:

```php
namespace Modules\HealthCheck\Services\Checkers;

class CustomChecker implements HealthCheckerInterface
{
    public function check(): CheckResult
    {
        // Your health check logic
        return new CheckResult(
            new CheckName('custom'),
            CheckStatus::healthy(),
            new SuccessMessage('Custom check passed')
        );
    }

    public function name(): CheckName
    {
        return new CheckName('custom');
    }
}
```

2. Register in `HealthCheckService`:

```php
$this->checkers = [
    new ApplicationChecker(),
    new DatabaseChecker(),
    new CacheChecker(),
    new RedisChecker(),
    new CustomChecker(), // Your custom checker
];
```

## 📦 Package Extraction Checklist

When extracting this module as a standalone package:

- [ ] Create new repository
- [ ] Copy module files to `src/` directory
- [ ] Update `composer.json` with correct namespace
- [ ] Add `README.md` with installation instructions
- [ ] Add `LICENSE` file
- [ ] Add tests in `tests/` directory
- [ ] Set up CI/CD pipeline
- [ ] Publish to Packagist
- [ ] Tag stable version (e.g., v1.0.0)

## 📄 License

MIT License

## 👥 Authors

FetchIt Team

## 🔗 Links

- Main Application: FetchIt
- Framework: Laravel 12
- PHP Version: 8.3+
