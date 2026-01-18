# Database Foundation Module

**Centralized database migrations and schema management for FetchIt**

## Overview

The DBCore module is the single source of truth for all database structure definitions in the FetchIt application. It uses the **public schema only** for all application and Laravel framework tables.

## Module Purpose

This module consolidates ALL database migrations into one professional, well-organized location:

- ✅ **Application Migrations** - All tables (users, orders, gmail_accounts, Laravel framework, etc.) in the **public** schema

## Directory Structure

```
DBCore/
├── Database/
│   └── Migrations/
│       ├── 2026_01_17_000000_create_fetchit_schema.php    # No-op (public schema only)
│       │
│       └── Core/                                    # Public schema migrations
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

### Public Schema Migrations
**Directory**: `Migrations/Core/`
**Schema**: `public` (only)
**Tables**:
- `users` - User accounts (Google OAuth)
- `refresh_tokens` - JWT refresh tokens
- `gmail_accounts` - Linked Gmail accounts
- `gmail_sync_jobs` - Gmail sync job tracking
- `orders` - Parsed orders from emails
- `cache`, `cache_locks`, `jobs`, `password_reset_tokens`, etc. - Laravel framework tables

## Installation

1. The module is automatically loaded via `bootstrap/providers.php`
2. Run migrations: `php artisan migrate`

## Configuration

**File**: `config/dbcore.php`

```php
'fetchit_schema' => env('DB_SCHEMA_FETCHIT', 'public'),
```

**File**: `config/database.php`

```php
'search_path' => env('DB_SEARCH_PATH', 'public'),
```

## Creating New Migrations

1. Place migration files in `app/Modules/DBCore/Database/Migrations/Core/`
2. Use `SchemaHelper::createInSchema()` with `public` (or `Config::get('dbcore.fetchit_schema', 'public')`):

```php
use Modules\SchemaMgr\Support\Schema as SchemaHelper;
use Illuminate\Support\Facades\Config;

$schema = Config::get('dbcore.fetchit_schema', 'public');

SchemaHelper::createInSchema($schema, 'table_name', function (Blueprint $table) {
    // Table definition
});
```

## Models

All FetchIt models are in `app/Modules/DBCore/Models/Core/`:
- `User` - User model with Google OAuth support (uses `public` schema via `UsesSchema`)

## Best Practices

1. **Always use SchemaHelper** for schema-aware migrations
2. **Keep all tables in the `public` schema**
3. **Use descriptive migration names** with timestamps
4. **Test migrations** on a fresh database before deploying
