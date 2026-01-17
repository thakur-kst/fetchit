# Changelog

All notable changes to the DBCore module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-01-04

### Added
- Initial release of DBCore module
- Centralized all database migrations into organized structure
- Schema-based organization (Core, CustomerPortal, Framework)
- Centralized Eloquent models by schema
- Automatic UsesSchema trait integration for all models
- Comprehensive module documentation

### Migrations
- **Schema Creation** (1): Creates PostgreSQL schemas
- **Core Schema** (7): Master data migrations
  - UUID v7 function
  - PostGIS extension
  - Organizations table
  - Branches table
  - Roles table
  - Permissions table
  - Role-permissions mapping
- **Customer Portal Schema** (6): Tenant data migrations
  - Users table alterations for tenancy
  - Customer profiles table
  - User roles table
  - User branches table
  - Audit logs table
  - Health check logs table
- **Framework** (3): Laravel system tables
  - Users base table
  - Cache tables
  - Jobs tables

### Models
- **CustomerPortal Models** (1):
  - HealthCheckLog - with UsesSchema trait integration

### Features
- Automatic schema detection for models via UsesSchema trait
- Cross-schema relationship support
- Clean separation between master data and tenant data
- Framework table isolation
- Migration ordering by schema dependency

### Documentation
- Complete module README
- Migration organization guide
- Model usage examples
- Best practices documentation
- Changelog

### Technical Details
- PHP 8.3+ required
- Laravel 12+ required
- PostgreSQL 16+ required
- PSR-4 autoloading
- Depends on SchemaMgr module

## [Unreleased]

### Planned Features
- Additional core schema models (Organization, Branch, Role, Permission)
- Additional customer portal models (CustomerProfile, UserRole, etc.)
- Model factories for all models
- Database seeders for test data
- Migration rollback testing utilities
- Schema migration validation tools

---

**Module**: DBCore
**Version**: 1.0.0
**Status**: Production Ready ✅
