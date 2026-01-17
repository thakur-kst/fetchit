# Shared Module

Centralized foundation module for cross-cutting concerns: rate limiting, IP whitelisting, caching, audit logging, and repository pattern.

## Overview

The Shared module provides reusable, centralized implementations for common concerns that apply across multiple modules:

- **Rate Limiting**: Flexible configuration (per endpoint, per module, per user role)
- **IP Whitelisting**: Centralized IP whitelist management
- **Caching**: Opt-in caching with decorator pattern
- **Audit Logging**: Opt-in audit logging service
- **Queue Priority**: Centralized queue priority management with 5 priority levels
- **ETags**: Entity tags for efficient caching and conditional requests
- **Repository Pattern**: Base repository interfaces and implementations

## Installation

The module is automatically registered via service provider. Ensure it's listed in your `composer.json` autoload section.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=shared-config
```

Or configure via environment variables (see `config/shared.php` for all options).

## Usage

### Rate Limiting

Apply rate limiting to routes using middleware:

```php
use Modules\Shared\Http\Middleware\RateLimitMiddleware;

// Per endpoint
Route::middleware([RateLimitMiddleware::class . ':wallet.credit'])->group(function () {
    Route::post('/credit', [WalletController::class, 'credit']);
});

// Per module
Route::middleware([RateLimitMiddleware::class . ':null:wallet'])->group(function () {
    // All wallet routes
});

// Using alias
Route::middleware(['shared.rate_limit:wallet.credit'])->group(function () {
    // Routes
});
```

**Configuration Priority:**
1. Endpoint-specific (highest priority)
2. Module-specific
3. Role-based
4. Default (lowest priority)

### IP Whitelisting

Apply IP whitelisting to routes:

```php
use Modules\Shared\Http\Middleware\IPWhitelistMiddleware;

Route::middleware([IPWhitelistMiddleware::class . ':wallet'])->group(function () {
    Route::post('/credit', [WalletController::class, 'credit']);
});

// Using alias
Route::middleware(['shared.ip_whitelist:wallet'])->group(function () {
    // Routes
});
```

### Caching

Use the cache decorator pattern for repositories:

```php
use Modules\Shared\Repositories\CacheableRepository;
use Modules\Wallet\Repositories\EloquentWalletRepository;

// In service provider
$repository = new EloquentWalletRepository();
if (config('shared.caching.enabled')) {
    $repository = new CacheableRepository($repository, 'wallet', 'wallet');
}
$this->app->singleton(WalletRepositoryInterface::class, fn() => $repository);
```

Or use CacheService directly:

```php
use Modules\Shared\Services\CacheService;
use Modules\Shared\Support\CacheKeyGenerator;

$cacheService = app(CacheService::class);
$key = CacheKeyGenerator::entityByUuid('wallet', 'wallet', $uuid);

$wallet = $cacheService->remember($key, function () use ($uuid) {
    return Wallet::where('uuid', $uuid)->first();
});
```

### Audit Logging

#### Option 1: Middleware (Automatic)

```php
use Modules\Shared\Http\Middleware\AuditLogMiddleware;

Route::middleware([AuditLogMiddleware::class . ':wallet:Wallet'])->group(function () {
    Route::apiResource('wallets', WalletController::class);
});

// Using alias
Route::middleware(['shared.audit:wallet:Wallet'])->group(function () {
    // Routes
});
```

#### Option 2: Trait (Model-level)

```php
use Modules\Shared\Traits\Auditable;

class Wallet extends Model
{
    use Auditable;
    
    // Automatic logging on create, update, delete
}
```

#### Option 3: Service (Manual)

```php
use Modules\Shared\Services\AuditService;

$auditService = app(AuditService::class);

$auditService->log(
    'wallet',
    'Wallet',
    $wallet->uuid,
    'credited',
    ['balance' => $oldBalance],
    ['balance' => $newBalance],
    $request
);
```

### Queue Priority

The Shared module provides a centralized queue priority system with 5 priority levels: `critical`, `high`, `medium`, `default`, and `low`.

#### Option 1: Using QueueService

```php
use Modules\Shared\Services\QueueService;
use Modules\Shared\Support\QueuePriority;

$queueService = app(QueueService::class);

// Dispatch to specific priority
$queueService->dispatchToPriority(new MyJob(), QueuePriority::CRITICAL);

// Or use convenience methods
$queueService->dispatchCritical(new MyJob());
$queueService->dispatchHigh(new MyJob());
$queueService->dispatchMedium(new MyJob());
$queueService->dispatchDefault(new MyJob());
$queueService->dispatchLow(new MyJob());
```

#### Option 2: Using QueueablePriority Trait

```php
use Modules\Shared\Traits\QueueablePriority;
use Modules\Shared\Support\QueuePriority;

class SyncDTDCBalanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use QueueablePriority;
    
    // Define queuePriority property in your job class
    public string $queuePriority = QueuePriority::MEDIUM;
    
    public function __construct()
    {
        // Initialize queue priority in constructor
        $this->initializeQueuePriority();
    }
    
    // Job will automatically dispatch to 'medium' queue
}
```

#### Option 3: Direct Dispatch with Constants

```php
use Modules\Shared\Support\QueuePriority;

// Using Laravel's dispatch with onQueue
dispatch(new MyJob())->onQueue(QueuePriority::CRITICAL);
```

**Priority Levels:**
- `QueuePriority::CRITICAL` - Highest priority, most urgent jobs (e.g., payment processing)
- `QueuePriority::HIGH` - Important jobs that should be processed quickly (e.g., order processing)
- `QueuePriority::MEDIUM` - Standard background jobs (e.g., balance syncs, data synchronization)
- `QueuePriority::DEFAULT` - Standard queue priority (default if not specified)
- `QueuePriority::LOW` - Non-urgent background tasks (e.g., analytics, reporting, cleanup)

### ETags

ETags (Entity Tags) enable efficient caching, reduce bandwidth, and support conditional requests with optimistic locking.

#### Option 1: Using ETaggableResource (Recommended)

```php
use Modules\Shared\Http\Resources\ETaggableResource;

class PaymentResource extends ETaggableResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'amount' => $this->amount,
            // ... other fields
        ];
    }
    
    // ETag automatically generated from model's updated_at
    // Can override getETag() for custom logic
}
```

#### Option 2: Using ETagService Directly

```php
use Modules\Shared\Services\ETagService;

$etagService = app(ETagService::class);

// Generate ETag from model
$etag = $etagService->generateFromModel($payment);

// Generate ETag from content
$etag = $etagService->generateFromContent($responseData);

// Validate ETag
$isValid = $etagService->validate($requestEtag, $currentEtag);
```

#### Option 3: Using Middleware

```php
// In routes/api.php
Route::middleware(['shared.etag'])->group(function () {
    Route::apiResource('payments', PaymentController::class);
});

// Or per route
Route::get('/payments/{id}', [PaymentController::class, 'show'])
    ->middleware('shared.etag');
```

**How It Works:**

1. **GET Requests with If-None-Match:**
   - Client sends: `If-None-Match: "etag-value"`
   - Server responds:
     - `304 Not Modified` if ETag matches (resource unchanged)
     - `200 OK` with new ETag if different

2. **PUT/PATCH/DELETE Requests with If-Match:**
   - Client sends: `If-Match: "etag-value"`
   - Server validates:
     - `412 Precondition Failed` if ETag doesn't match (resource was modified)
     - Proceeds with update if ETag matches

**Example Client Usage:**

```javascript
// GET request with caching
const response = await fetch('/api/payments/123', {
  headers: {
    'If-None-Match': cachedETag // From previous response
  }
});

if (response.status === 304) {
  // Use cached data
} else {
  // Resource changed, use new data
  const newETag = response.headers.get('ETag');
}

// PUT request with optimistic locking
const response = await fetch('/api/payments/123', {
  method: 'PUT',
  headers: {
    'If-Match': currentETag, // From GET response
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ amount: 1000 })
});

if (response.status === 412) {
  // Resource was modified, refresh and retry
}
```

### Repository Pattern

Extend BaseRepository for your module repositories:

```php
use Modules\Shared\Repositories\BaseRepository;
use Modules\Shared\Contracts\RepositoryInterface;

class WalletRepository extends BaseRepository implements WalletRepositoryInterface
{
    protected string $model = Wallet::class;
    
    // Module-specific methods
    public function findForUser(int $userId): ?Wallet
    {
        return Wallet::where('user_id', $userId)->first();
    }
}
```

## Configuration Examples

### Rate Limiting

```php
// config/shared.php
'rate_limits' => [
    'modules' => [
        'wallet' => ['max_attempts' => 100, 'decay_minutes' => 1],
    ],
    'endpoints' => [
        'wallet.credit' => ['max_attempts' => 10, 'decay_minutes' => 1],
    ],
    'roles' => [
        'admin' => ['max_attempts' => 1000, 'decay_minutes' => 1],
    ],
],
```

### IP Whitelisting

```php
// config/shared.php
'ip_whitelist' => [
    'enabled' => true,
    'ips' => ['192.168.1.100', '10.0.0.50'],
    'by_module' => [
        'wallet' => [
            'ips' => ['192.168.1.100'],
            'routes' => ['wallet.credit', 'wallet.debit'],
        ],
    ],
],
```

### Caching

```php
// config/shared.php
'caching' => [
    'enabled' => true,
    'default_ttl' => 300, // 5 minutes
],
```

### Audit Logging

```php
// config/shared.php
'audit' => [
    'enabled' => true,
    'log_reads' => false,
    'modules' => ['wallet', 'payment'], // Empty = all modules
],
```

### Queue Priority

```php
// config/shared.php
'queues' => [
    'priorities' => [
        'critical' => 'critical',
        'high' => 'high',
        'medium' => 'medium',
        'default' => 'default',
        'low' => 'low',
    ],
    'default_priority' => 'default',
],
```

### ETags

```php
// config/shared.php
'etags' => [
    'enabled' => true,
    'weak_etags' => false, // Use weak ETags for collections
    'strategy' => 'model', // model|content|hybrid
    'excluded_routes' => [], // Routes to exclude from ETag processing
],
```

## Environment Variables

```env
# Rate Limiting
SHARED_RATE_LIMIT_DEFAULT_MAX=60
SHARED_RATE_LIMIT_DEFAULT_DECAY=1
SHARED_RATE_LIMIT_WALLET_MAX=100
SHARED_RATE_LIMIT_WALLET_CREDIT_MAX=10

# IP Whitelisting
SHARED_IP_WHITELIST_ENABLED=false
SHARED_IP_WHITELIST=192.168.1.100,10.0.0.50
SHARED_IP_WHITELIST_WALLET=192.168.1.100

# Caching
SHARED_CACHE_ENABLED=true
SHARED_CACHE_TTL=300

# Audit Logging
SHARED_AUDIT_ENABLED=false
SHARED_AUDIT_LOG_READS=false
SHARED_AUDIT_MODULES=wallet,payment

# Queue Priority
QUEUE_PRIORITY_CRITICAL=critical
QUEUE_PRIORITY_HIGH=high
QUEUE_PRIORITY_MEDIUM=medium
QUEUE_PRIORITY_DEFAULT=default
QUEUE_PRIORITY_LOW=low
QUEUE_DEFAULT_PRIORITY=default

# ETags
SHARED_ETAG_ENABLED=true
SHARED_ETAG_WEAK=false
SHARED_ETAG_STRATEGY=model
SHARED_ETAG_EXCLUDED_ROUTES=
```
<｜tool▁calls▁begin｜><｜tool▁call▁begin｜>
read_lints

## Migration Strategy

### For New Modules

New modules should use Shared module services from the start:

```php
// In OrderServiceProvider
use Modules\Shared\Repositories\BaseRepository;
use Modules\Shared\Http\Middleware\RateLimitMiddleware;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    protected string $model = Order::class;
}

// In routes
Route::middleware([RateLimitMiddleware::class . ':order'])->group(...);
```

### For Existing Modules

Existing modules can gradually adopt Shared services:

1. **Keep existing implementations** - No breaking changes
2. **Opt-in to specific services** - Use only what you need
3. **Gradual migration** - Migrate one service at a time

Example: Wallet module can use Shared audit logging while keeping its own rate limiting:

```php
// Use Shared audit logging
Route::middleware(['shared.audit:wallet:Wallet'])->group(function () {
    // Routes
});

// Keep existing rate limiting
Route::middleware([WalletRateLimitMiddleware::class])->group(function () {
    // Routes
});
```

## Benefits

1. **DRY Principle**: No code duplication across modules
2. **Consistency**: Same behavior across all modules
3. **Maintainability**: Fix bugs/improvements in one place
4. **Flexibility**: Opt-in approach, modules choose what to use
5. **Gradual Migration**: No breaking changes to existing modules
6. **Testability**: Centralized services are easier to test

## Testing

Run Test case :
docker compose exec app php artisan test app/Modules/Shared/tests/Unit/Support/HelperTest.php

```php
use Modules\Shared\Services\RateLimitService;
use Modules\Shared\Services\CacheService;

// Test rate limiting
$service = app(RateLimitService::class);
$service->hit($request, 'wallet.credit');

// Test caching
$cacheService = app(CacheService::class);
$cacheService->put('test:key', 'value');
```

## Support

For issues or questions, please contact the development team.

