# Schema Management Module

**Professional PostgreSQL multi-schema architecture for Laravel applications**

## Overview

The Schema Management module provides a comprehensive solution for organizing database tables into logical PostgreSQL schemas. This enables clean separation between master data, tenant-specific data, and framework tables while maintaining full Laravel/Eloquent compatibility.

## Features

✅ **Multi-Schema Support** - Organize tables into `core`, `customer_portal`, and `public` schemas
✅ **Automatic Schema Detection** - Models automatically use the correct schema
✅ **Migration Helpers** - Schema-aware migration utilities
✅ **Artisan Commands** - CLI tools for schema management
✅ **Cross-Schema Relations** - Full support for foreign keys across schemas
✅ **Health Checks** - Validate schema configuration
✅ **PSR-12 Compliant** - Clean, maintainable code

## Installation

The module is pre-installed as part of the FetchIt application.

### Configuration

1. **Update Environment Variables** (`.env`):

```env
DB_CONNECTION=pgsql
DB_SEARCH_PATH=public,core,customer_portal
DB_SCHEMAS_ENABLED=true
```

2. **Create Schemas**:

```bash
php artisan schema:create
```

3. **Verify Setup**:

```bash
php artisan schema:list
```

## Usage

### 1. Using in Migrations

```php
use Modules\SchemaMgr\Support\Schema as SchemaHelper;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Create table in CORE schema
        SchemaHelper::createInSchema('core', 'organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Create table with auto-detected schema
        SchemaHelper::createConfigured('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        SchemaHelper::dropFromSchema('core', 'organizations');
    }
};
```

### 2. Using in Models

```php
use Modules\SchemaMgr\Support\UsesSchema;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use UsesSchema;

    protected $table = 'organizations';
    // Schema auto-detected as 'core' from configuration
}
```

### 3. Cross-Schema Relationships

```php
// Migration
SchemaHelper::createInSchema('customer_portal', 'customer_profiles', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('organization_id');

    // Reference to core schema
    $table->foreign('organization_id')
        ->references('id')
        ->on('core.organizations');
});

// Model
class CustomerProfile extends Model
{
    use UsesSchema;

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
```

## Artisan Commands

### Create Schemas

```bash
# Create all configured schemas
php artisan schema:create

# Create specific schema
php artisan schema:create --schema=core

# Force recreate
php artisan schema:create --force
```

### List Schemas

```bash
# List all schemas
php artisan schema:list

# Show specific schema details
php artisan schema:list --schema=core

# Show all tables
php artisan schema:list --tables
```

### Check Configuration

```bash
# Validate configuration
php artisan schema:check

# Auto-fix issues
php artisan schema:check --fix
```

## Configuration

Configuration file: `config/schemas.php`

```php
return [
    'enabled' => env('DB_SCHEMAS_ENABLED', true),

    'schemas' => [
        'core' => [
            'description' => 'Master data and system-wide configurations',
            'tables' => [
                'organizations',
                'branches',
                'roles',
                // ...
            ],
        ],
        'customer_portal' => [
            'description' => 'Tenant-specific and customer data',
            'tables' => [
                'users',
                'customer_profiles',
                // ...
            ],
        ],
    ],
];
```

## Schema Organization

### CORE Schema
- Master data
- System-wide configurations
- Organizations, branches, roles, permissions
- Reference data (countries, currencies, etc.)

### CUSTOMER_PORTAL Schema
- Tenant-specific data
- User accounts and profiles
- Orders, transactions, invoices
- Audit logs

### PUBLIC Schema
- Laravel framework tables
- Migrations, cache, sessions
- Jobs, queues

## Module Structure

```
SchemaMgr/
├── Support/
│   ├── Schema.php              # Schema helper class
│   └── UsesSchema.php          # Model trait
├── Console/
│   └── Commands/
│       ├── CreateSchemasCommand.php
│       ├── ListSchemasCommand.php
│       └── CheckSchemaCommand.php
├── database/
│   └── Migrations/
│       └── 0001_01_01_000000_create_schemas.php
├── config/
│   └── schemas.php             # Schema configuration
├── Providers/
│   └── SchemaMgrServiceProvider.php
└── README.md
```

## API Reference

### Schema Helper

```php
// Create in specific schema
Schema::createInSchema('core', 'table', $callback);

// Modify table
Schema::tableInSchema('core', 'table', $callback);

// Drop table
Schema::dropFromSchema('core', 'table');

// Check existence
Schema::hasTableInSchema('core', 'table');

// Auto-detect schema
Schema::createConfigured('table', $callback);

// Get schema for table
$schema = Schema::getSchemaForTable('organizations'); // Returns: 'core'

// Cross-schema foreign key
Schema::addForeignKey($table, 'org_id', 'organizations');
```

### UsesSchema Trait

```php
// Methods available on models
$model->getTable();              // Returns: 'core.organizations'
$model->getRawTableName();       // Returns: 'organizations'
$model->getSchemaName();         // Returns: 'core'
$model->getQualifiedTableName(); // Returns: 'core.organizations'
```

## Testing

```php
use Modules\SchemaMgr\Support\Schema;

class SchemaTest extends TestCase
{
    public function test_schema_detection()
    {
        $schema = Schema::getSchemaForTable('organizations');
        $this->assertEquals('core', $schema);
    }

    public function test_model_uses_correct_schema()
    {
        $org = new Organization();
        $this->assertEquals('core', $org->getSchemaName());
    }
}
```

## Best Practices

1. **Always run `schema:create` before migrations**
2. **Add new tables to `config/schemas.php` immediately**
3. **Use `UsesSchema` trait on ALL models**
4. **Use fully qualified names for cross-schema foreign keys**
5. **Run `schema:check` regularly to validate configuration**

## Troubleshooting

### Schema Not Found

```bash
php artisan schema:create
```

### Table Not Found

Check `DB_SEARCH_PATH` in `.env`:
```env
DB_SEARCH_PATH=public,core,customer_portal
```

### Foreign Key Errors

Use fully qualified table names:
```php
->on('core.organizations')  // ✓ Good
->on('organizations')        // ✗ Bad
```

## Documentation

- [Quick Start Guide](../../../../docs/SCHEMA_QUICK_START.md)
- [Full Architecture Guide](../../../../docs/MULTI_SCHEMA_ARCHITECTURE.md)
- [Implementation Summary](../../../../docs/SCHEMAS_README.md)

## Requirements

- PHP 8.3+
- Laravel 12+
- PostgreSQL 16+

## Version

**1.0.0** - Initial release

## License

Part of the FetchIt application.

## Support

For issues or questions, run:
```bash
php artisan schema:check
```

---

**Module**: SchemaMgr
**Namespace**: `Modules\SchemaMgr`
**Type**: Core Infrastructure Module
**Status**: Production Ready ✅
