# Database Foundation Module

**Centralized database migrations and schema management for FetchIt**

## Overview

The DBCore module is the single source of truth for all database structure definitions in the FetchIt application. It uses a single-schema architecture (`fetchit`) for simplicity and better performance.

## Module Purpose

This module consolidates ALL database migrations into one professional, well-organized location:

- ✅ **FetchIt Schema Migrations** - All application tables (users, orders, gmail_accounts, etc.)
- ✅ **Public Schema Migrations** - Laravel framework tables (cache, jobs, sessions, etc.)
- ✅ **Schema Creation** - PostgreSQL schema initialization

## Directory Structure

```
DBCore/
├── Database/
│   └── Migrations/
│       ├── 2026_01_17_000000_create_fetchit_schema.php    # FetchIt schema creation
│       │
│       └── Core/                                    # FetchIt schema migrations
│           ├── 2025_01_01_000004_create_users_table.php
│           ├── 2026_01_16_000002_create_refresh_tokens_table.php
│           ├── 2026_01_16_000003_create_gmail_accounts_table.php
│           ├── 2026_01_16_000004_create_gmail_sync_jobs_table.php
│           ├── 2026_01_16_000005_create_orders_table.php
│           ├── 2025_01_01_000015_create_cache_table.php (public schema)
│           ├── 2025_01_01_000016_create_cache_locks_table.php (public schema)
│           ├── 2025_01_01_000017_create_jobs_table.php (public schema)
│           └── ... (other Laravel framework tables in public schema)
│
├── Models/
│   └── Core/
│       └── User.php
│
├── Providers/
│   └── DBCoreServiceProvider.php
│
└── README.md
```

## Migration Organization

### Schema Creation
**File**: `2026_01_17_000000_create_fetchit_schema.php`
- Creates `fetchit` PostgreSQL schema
- Sets up permissions
- **Must run first** before any FetchIt table migrations

### FetchIt Schema Migrations
**Directory**: `Migrations/Core/`
**Schema**: `fetchit`
**Tables**:
- `users` - User accounts (Google OAuth)
- `refresh_tokens` - JWT refresh tokens
- `gmail_accounts` - Linked Gmail accounts
- `gmail_sync_jobs` - Gmail sync job tracking
- `orders` - Parsed orders from emails

**Purpose**: All FetchIt application data.

### Public Schema Migrations
**Directory**: `Migrations/Core/` (Laravel framework tables)
**Schema**: `public`
**Tables**:
- `cache`, `cache_locks` - Laravel cache
- `jobs`, `job_batches`, `failed_jobs` - Laravel queues
- `password_reset_tokens` - Password resets
- `telescope_entries*` - Laravel Telescope (if enabled)

**Purpose**: Laravel framework system tables.

## Architecture Decision: Single Schema

FetchIt uses a **single schema architecture** (`fetchit`) instead of multi-schema for the following reasons:

1. **Simplicity**: Easier to understand and maintain
2. **Performance**: No schema qualification overhead in queries
3. **Tooling**: Better support from database tools and ORMs
4. **Scalability**: PostgreSQL handles millions of rows efficiently in a single schema with proper indexing
5. **Single-Tenant**: FetchIt is a single-tenant application (users manage their own Gmail accounts)

Multi-schema is beneficial for:
- Multi-tenant SaaS applications
- Strict security isolation requirements
- Logical separation of unrelated domains

Since FetchIt doesn't have these requirements, single schema is the ideal choice.

## Installation

1. The module is automatically loaded via `bootstrap/providers.php`
2. Run migrations: `php artisan migrate`
3. The `fetchit` schema will be created automatically

## Configuration

**File**: `config/dbcore.php`

```php
'fetchit_schema' => env('DB_SCHEMA_FETCHIT', 'fetchit'),
```

**File**: `config/database.php`

```php
'search_path' => env('DB_SEARCH_PATH', 'public,fetchit'),
```

## Creating New Migrations

1. Place migration files in `app/Modules/DBCore/Database/Migrations/Core/`
2. Use `SchemaHelper::createInSchema()` for FetchIt tables:

```php
use Modules\SchemaMgr\Support\Schema as SchemaHelper;
use Illuminate\Support\Facades\Config;

$fetchitSchema = Config::get('dbcore.fetchit_schema', 'fetchit');

SchemaHelper::createInSchema($fetchitSchema, 'table_name', function (Blueprint $table) {
    // Table definition
});
```

3. For Laravel framework tables, use `public` schema:

```php
SchemaHelper::createInSchema('public', 'table_name', function (Blueprint $table) {
    // Table definition
});
```

## Models

All FetchIt models are in `app/Modules/DBCore/Models/Core/`:
- `User` - User model with Google OAuth support

Models automatically use the `fetchit` schema via the `UsesSchema` trait.

## Best Practices

1. **Always use SchemaHelper** for schema-aware migrations
2. **Keep FetchIt tables in `fetchit` schema**
3. **Keep Laravel framework tables in `public` schema**
4. **Use descriptive migration names** with timestamps
5. **Test migrations** on fresh database before deploying
