# PostgreSQL Docker Service - Complete Documentation

This document provides comprehensive information about setting up, configuring, and using the PostgreSQL Docker service.

## Table of Contents

1. [Setup & Installation](#setup--installation)
2. [Configuration](#configuration)
3. [Usage Guide](#usage-guide)
4. [Advanced Topics](#advanced-topics)
5. [Troubleshooting](#troubleshooting)
6. [Examples](#examples)

---

## Setup & Installation

### Prerequisites

Before setting up the PostgreSQL Docker service, ensure you have the following installed:

- **Docker**: Version 20.10 or higher
  - Check installation: `docker --version`
  - Install: [Docker Installation Guide](https://docs.docker.com/get-docker/)

- **Docker Compose**: Version 2.0 or higher
  - Check installation: `docker-compose --version`
  - Install: [Docker Compose Installation Guide](https://docs.docker.com/compose/install/)

### Step-by-Step Installation

1. **Navigate to the postgres directory**
   ```bash
   cd postgres
   ```

2. **Review and configure environment variables**
   ```bash
   # Open the .env file in your preferred editor
   nano .env
   # or
   vim .env
   ```

3. **Update database credentials** (recommended for security)
   ```bash
   # Change these values in .env:
   POSTGRES_DB=your_database_name
   POSTGRES_USER=your_username
   POSTGRES_PASSWORD=your_secure_password
   ```

4. **Build and start the service**
   ```bash
   docker-compose up -d
   ```

5. **Verify the service is running**
   ```bash
   docker-compose ps
   # Should show postgres service as "Up"
   ```

6. **Check health status**
   ```bash
   docker-compose ps
   # Health status should show as "healthy"
   ```

### Initial Configuration Steps

1. **Wait for initialization** (first startup only)
   - The first startup may take 30-60 seconds
   - Initialization scripts in `init-scripts/` will run automatically
   - Check logs: `docker-compose logs -f postgres`

2. **Test database connection**
   ```bash
   docker-compose exec postgres psql -U postgres -d postgres -c "SELECT version();"
   ```

3. **Verify initialization scripts executed**
   ```bash
   docker-compose logs postgres | grep "running"
   ```

---

## Configuration

### Environment Variables

The `.env` file contains all configuration variables. Here's a complete reference:

#### Required Variables

| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `POSTGRES_DB` | Name of the default database | `postgres` | `myapp_db` |
| `POSTGRES_USER` | Database superuser name | `postgres` | `admin` |
| `POSTGRES_PASSWORD` | Database superuser password | `postgres` | `SecurePass123!` |

#### Optional Variables

| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `POSTGRES_PORT` | Host port mapping | `5432` | `5433` |
| `POSTGRES_INITDB_ARGS` | Additional initdb arguments | - | `--encoding=UTF8 --locale=C` |
| `POSTGRES_INITDB_WALDIR` | WAL directory location | - | `/var/lib/postgresql/wal` |
| `POSTGRES_HOST_AUTH_METHOD` | Authentication method | `md5` | `scram-sha-256` |

### Default Values and Recommended Settings

#### Development Environment
```env
POSTGRES_DB=dev_db
POSTGRES_USER=dev_user
POSTGRES_PASSWORD=dev_password
POSTGRES_PORT=5432
```

#### Production Environment
```env
POSTGRES_DB=production_db
POSTGRES_USER=prod_admin
POSTGRES_PASSWORD=<strong-random-password>
POSTGRES_PORT=5432
POSTGRES_HOST_AUTH_METHOD=scram-sha-256
```

### Security Best Practices

1. **Strong Passwords**
   - Use at least 16 characters
   - Include uppercase, lowercase, numbers, and special characters
   - Never commit passwords to version control

2. **Environment File Security**
   ```bash
   # Add .env to .gitignore
   echo ".env" >> .gitignore
   
   # Set proper file permissions
   chmod 600 .env
   ```

3. **Network Security**
   - Use Docker networks to isolate services
   - Don't expose PostgreSQL port publicly unless necessary
   - Use firewall rules to restrict access

4. **Authentication Methods**
   - Use `scram-sha-256` for production (more secure than `md5`)
   - Consider using password files for sensitive deployments

### Port Configuration and Customization

#### Change Default Port

Edit `.env`:
```env
POSTGRES_PORT=5433
```

Then restart:
```bash
docker-compose down
docker-compose up -d
```

#### Multiple PostgreSQL Instances

To run multiple instances, create separate directories with different port mappings:
- Instance 1: `POSTGRES_PORT=5432`
- Instance 2: `POSTGRES_PORT=5433`
- Instance 3: `POSTGRES_PORT=5434`

### Volume and Data Persistence Setup

#### Volume Configuration

The `docker-compose.yml` includes a named volume `postgres_data` that persists data:

```yaml
volumes:
  postgres_data:
    driver: local
```

#### Volume Location

Docker stores volumes in:
- **Linux**: `/var/lib/docker/volumes/`
- **macOS/Windows**: Managed by Docker Desktop

#### Inspect Volume
```bash
# List volumes
docker volume ls

# Inspect postgres_data volume
docker volume inspect postgres_postgres_data

# View volume size
docker system df -v
```

#### Backup Volume Data
```bash
# Create backup
docker run --rm -v postgres_postgres_data:/data -v $(pwd):/backup \
  alpine tar czf /backup/postgres_backup.tar.gz -C /data .
```

#### Restore Volume Data
```bash
# Stop service
docker-compose down

# Restore backup
docker run --rm -v postgres_postgres_data:/data -v $(pwd):/backup \
  alpine sh -c "cd /data && tar xzf /backup/postgres_backup.tar.gz"

# Start service
docker-compose up -d
```

---

## Usage Guide

### Starting and Stopping the Service

#### Start Service
```bash
# Start in detached mode (background)
docker-compose up -d

# Start with logs visible
docker-compose up

# Start and rebuild if needed
docker-compose up -d --build
```

#### Stop Service
```bash
# Stop containers (keeps data)
docker-compose stop

# Stop and remove containers (keeps data)
docker-compose down

# Stop and remove containers and volumes (DELETES DATA)
docker-compose down -v
```

#### Restart Service
```bash
# Restart service
docker-compose restart postgres

# Restart with rebuild
docker-compose up -d --build
```

### Connecting to the Database

#### Using psql from Container
```bash
# Connect to default database
docker-compose exec postgres psql -U postgres -d postgres

# Connect to specific database
docker-compose exec postgres psql -U postgres -d your_database

# Execute single command
docker-compose exec postgres psql -U postgres -d postgres -c "SELECT version();"
```

#### Using Local psql Client
```bash
# Install psql client (if not installed)
# macOS: brew install postgresql
# Ubuntu: sudo apt-get install postgresql-client

# Connect
psql -h localhost -p 5432 -U postgres -d postgres
```

#### Using Connection String
```
postgresql://username:password@localhost:5432/database_name
```

#### Using GUI Tools
- **pgAdmin**: [Download pgAdmin](https://www.pgadmin.org/download/)
- **DBeaver**: [Download DBeaver](https://dbeaver.io/download/)
- **TablePlus**: [Download TablePlus](https://tableplus.com/)

Connection settings:
- Host: `localhost`
- Port: `5432` (or value from `.env`)
- Database: Value from `POSTGRES_DB`
- Username: Value from `POSTGRES_USER`
- Password: Value from `POSTGRES_PASSWORD`

### Running Initialization Scripts

#### How It Works

1. Scripts in `init-scripts/` are mounted to `/docker-entrypoint-initdb.d/`
2. Scripts execute only on first database initialization
3. Scripts run in alphabetical order
4. Supported formats: `.sql`, `.sh`, `.sql.gz`

#### Adding Initialization Scripts

1. **Create SQL file**
   ```bash
   nano init-scripts/01-create-tables.sql
   ```

2. **Add SQL commands**
   ```sql
   CREATE TABLE users (
       id SERIAL PRIMARY KEY,
       username VARCHAR(50) UNIQUE NOT NULL,
       email VARCHAR(100) NOT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   ```

3. **Restart with fresh database** (if needed)
   ```bash
   docker-compose down -v
   docker-compose up -d
   ```

#### Script Execution Order

Name files with prefixes to control execution order:
- `01-init-schema.sql`
- `02-seed-data.sql`
- `03-create-indexes.sql`

### Backup and Restore Procedures

#### Database Backup

##### Using pg_dump
```bash
# Backup single database
docker-compose exec postgres pg_dump -U postgres database_name > backup.sql

# Backup all databases
docker-compose exec postgres pg_dumpall -U postgres > backup_all.sql

# Backup with custom format (compressed)
docker-compose exec postgres pg_dump -U postgres -Fc database_name > backup.dump
```

##### Automated Backup Script
```bash
#!/bin/bash
# backup.sh
BACKUP_DIR="./backups"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

docker-compose exec -T postgres pg_dump -U postgres postgres | gzip > "$BACKUP_DIR/backup_$DATE.sql.gz"
echo "Backup created: $BACKUP_DIR/backup_$DATE.sql.gz"
```

#### Database Restore

##### From SQL File
```bash
# Restore from SQL file
docker-compose exec -T postgres psql -U postgres -d postgres < backup.sql

# Restore to different database
docker-compose exec -T postgres psql -U postgres -d new_database < backup.sql
```

##### From Custom Format
```bash
# Restore from custom format
docker-compose exec -T postgres pg_restore -U postgres -d postgres < backup.dump
```

### Logging and Monitoring

#### View Logs
```bash
# View all logs
docker-compose logs postgres

# Follow logs (real-time)
docker-compose logs -f postgres

# View last 100 lines
docker-compose logs --tail=100 postgres

# View logs with timestamps
docker-compose logs -t postgres
```

#### Health Check
```bash
# Check container health
docker-compose ps

# Manual health check
docker-compose exec postgres pg_isready -U postgres
```

#### Performance Monitoring
```bash
# View running queries
docker-compose exec postgres psql -U postgres -c "
SELECT pid, usename, application_name, state, query 
FROM pg_stat_activity 
WHERE state != 'idle';
"

# View database size
docker-compose exec postgres psql -U postgres -c "
SELECT pg_database.datname, 
       pg_size_pretty(pg_database_size(pg_database.datname)) AS size 
FROM pg_database;
"

# View connection statistics
docker-compose exec postgres psql -U postgres -c "
SELECT * FROM pg_stat_database WHERE datname = 'postgres';
"
```

---

## Advanced Topics

### Custom PostgreSQL Configuration

#### Method 1: Environment Variables
Add to `.env`:
```env
POSTGRES_INITDB_ARGS=--encoding=UTF8 --locale=en_US.UTF-8
```

#### Method 2: Custom postgresql.conf
1. Create `postgresql.conf` in postgres directory
2. Add custom settings:
   ```conf
   max_connections = 200
   shared_buffers = 256MB
   effective_cache_size = 1GB
   maintenance_work_mem = 64MB
   checkpoint_completion_target = 0.9
   wal_buffers = 16MB
   default_statistics_target = 100
   random_page_cost = 1.1
   effective_io_concurrency = 200
   work_mem = 4MB
   min_wal_size = 1GB
   max_wal_size = 4GB
   ```

3. Mount in `docker-compose.yml`:
   ```yaml
   volumes:
     - ./postgresql.conf:/etc/postgresql/postgresql.conf
   ```

4. Add command override:
   ```yaml
   command: postgres -c config_file=/etc/postgresql/postgresql.conf
   ```

### Performance Tuning

#### Memory Settings
For a container with 2GB RAM:
```conf
shared_buffers = 512MB
effective_cache_size = 1536MB
maintenance_work_mem = 128MB
work_mem = 10MB
```

#### Connection Settings
```conf
max_connections = 100
superuser_reserved_connections = 3
```

#### Write-Ahead Logging (WAL)
```conf
wal_level = replica
max_wal_senders = 3
wal_keep_segments = 16
```

### Network Configuration

#### Default Network
The service uses a bridge network `postgres_network` for isolation.

#### Connect Other Services
```yaml
# In another docker-compose.yml
services:
  app:
    networks:
      - postgres_network

networks:
  postgres_network:
    external: true
```

#### Custom Network
```yaml
networks:
  postgres_network:
    driver: bridge
    ipam:
      config:
        - subnet: 172.20.0.0/16
```

### Multi-Container Setups

#### Example: PostgreSQL + pgAdmin
```yaml
version: '3.8'

services:
  postgres:
    # ... existing postgres config ...
    networks:
      - db_network

  pgadmin:
    image: dpage/pgadmin4
    environment:
      PGADMIN_DEFAULT_EMAIL: admin@admin.com
      PGADMIN_DEFAULT_PASSWORD: admin
    ports:
      - "5050:80"
    networks:
      - db_network
    depends_on:
      - postgres

networks:
  db_network:
    driver: bridge
```

### Production Deployment Considerations

#### Security Checklist
- [ ] Change all default passwords
- [ ] Use strong, unique passwords
- [ ] Enable SSL/TLS connections
- [ ] Restrict network access
- [ ] Use secrets management
- [ ] Regular security updates
- [ ] Enable audit logging
- [ ] Configure firewall rules

#### Resource Limits
Add to `docker-compose.yml`:
```yaml
services:
  postgres:
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 2G
        reservations:
          cpus: '1'
          memory: 1G
```

#### High Availability
- Use PostgreSQL replication
- Implement automated backups
- Set up monitoring and alerting
- Plan for disaster recovery

#### Backup Strategy
- Daily automated backups
- Weekly full backups
- Monthly archive backups
- Test restore procedures regularly

---

## Troubleshooting

### Common Issues and Solutions

#### Issue: Container won't start

**Symptoms:**
- Container exits immediately
- Status shows "Exited (1)"

**Solutions:**
```bash
# Check logs
docker-compose logs postgres

# Common causes:
# 1. Port already in use
#    Solution: Change POSTGRES_PORT in .env

# 2. Volume permission issues
#    Solution: 
docker-compose down -v
docker-compose up -d

# 3. Invalid environment variables
#    Solution: Check .env file syntax
```

#### Issue: Can't connect to database

**Symptoms:**
- Connection refused errors
- Timeout errors

**Solutions:**
```bash
# 1. Verify container is running
docker-compose ps

# 2. Check port mapping
docker-compose port postgres 5432

# 3. Test connection from container
docker-compose exec postgres pg_isready -U postgres

# 4. Check firewall settings
# macOS: System Preferences > Security & Privacy > Firewall
# Linux: sudo ufw status
```

#### Issue: Initialization scripts not running

**Symptoms:**
- Tables/functions from init scripts missing
- No logs about script execution

**Solutions:**
```bash
# 1. Scripts only run on first initialization
#    Solution: Remove volume and restart
docker-compose down -v
docker-compose up -d

# 2. Check script file permissions
chmod +x init-scripts/*.sh

# 3. Verify script syntax
docker-compose exec postgres bash -c "cat /docker-entrypoint-initdb.d/your_script.sql"
```

#### Issue: Out of disk space

**Symptoms:**
- Database operations fail
- "No space left on device" errors

**Solutions:**
```bash
# 1. Check disk usage
docker system df

# 2. Clean up unused resources
docker system prune -a

# 3. Remove old volumes (WARNING: deletes data)
docker volume prune

# 4. Check volume size
docker volume inspect postgres_postgres_data
```

#### Issue: Performance problems

**Symptoms:**
- Slow queries
- High CPU/memory usage

**Solutions:**
```bash
# 1. Check active connections
docker-compose exec postgres psql -U postgres -c "
SELECT count(*) FROM pg_stat_activity;
"

# 2. Analyze query performance
docker-compose exec postgres psql -U postgres -c "
EXPLAIN ANALYZE SELECT * FROM your_table;
"

# 3. Check for locks
docker-compose exec postgres psql -U postgres -c "
SELECT * FROM pg_locks WHERE NOT granted;
"

# 4. Review configuration
docker-compose exec postgres psql -U postgres -c "
SHOW ALL;
"
```

### Log Analysis

#### Understanding Log Messages

**Common log patterns:**
```
# Successful startup
database system is ready to accept connections

# Connection established
connection received: host=...

# Error messages
ERROR: ...
FATAL: ...
```

#### Filtering Logs
```bash
# Errors only
docker-compose logs postgres | grep -i error

# Warnings
docker-compose logs postgres | grep -i warn

# Recent errors
docker-compose logs --since 1h postgres | grep -i error
```

### Connection Problems

#### Problem: Authentication failed

**Solution:**
```bash
# Verify credentials in .env
cat .env | grep POSTGRES

# Reset password
docker-compose exec postgres psql -U postgres -c "
ALTER USER postgres WITH PASSWORD 'new_password';
"
# Update .env and restart
```

#### Problem: Connection timeout

**Solution:**
```bash
# Check if service is accessible
docker-compose exec postgres pg_isready

# Verify port is exposed
docker-compose ps

# Check network connectivity
docker network inspect postgres_postgres_network
```

### Data Recovery Procedures

#### Recover from Backup
```bash
# 1. Stop service
docker-compose down

# 2. Remove corrupted volume
docker volume rm postgres_postgres_data

# 3. Start service (creates new volume)
docker-compose up -d

# 4. Wait for initialization
sleep 10

# 5. Restore backup
docker-compose exec -T postgres psql -U postgres -d postgres < backup.sql
```

#### Recover from Volume Backup
```bash
# 1. Stop service
docker-compose down

# 2. Restore volume
docker run --rm -v postgres_postgres_data:/data -v $(pwd):/backup \
  alpine sh -c "cd /data && tar xzf /backup/postgres_backup.tar.gz"

# 3. Start service
docker-compose up -d
```

#### Access Data from Stopped Container
```bash
# 1. Find volume
docker volume ls | grep postgres

# 2. Mount volume in temporary container
docker run --rm -it -v postgres_postgres_data:/data \
  postgres:latest bash

# 3. Access data directory
cd /data
ls -la
```

---

## Examples

### Sample Connection Strings

#### Python (psycopg2)
```python
import psycopg2

conn = psycopg2.connect(
    host="localhost",
    port=5432,
    database="postgres",
    user="postgres",
    password="postgres"
)
```

#### Python (SQLAlchemy)
```python
from sqlalchemy import create_engine

engine = create_engine(
    'postgresql://postgres:postgres@localhost:5432/postgres'
)
```

#### Node.js (pg)
```javascript
const { Client } = require('pg');

const client = new Client({
  host: 'localhost',
  port: 5432,
  database: 'postgres',
  user: 'postgres',
  password: 'postgres'
});

client.connect();
```

#### Java (JDBC)
```java
String url = "jdbc:postgresql://localhost:5432/postgres";
String user = "postgres";
String password = "postgres";
Connection conn = DriverManager.getConnection(url, user, password);
```

#### Ruby (pg gem)
```ruby
require 'pg'

conn = PG.connect(
  host: 'localhost',
  port: 5432,
  dbname: 'postgres',
  user: 'postgres',
  password: 'postgres'
)
```

#### Go (lib/pq)
```go
import (
    "database/sql"
    _ "github.com/lib/pq"
)

db, err := sql.Open("postgres", 
    "host=localhost port=5432 user=postgres password=postgres dbname=postgres sslmode=disable")
```

#### PHP (PDO)
```php
$dsn = "pgsql:host=localhost;port=5432;dbname=postgres";
$user = "postgres";
$password = "postgres";

$pdo = new PDO($dsn, $user, $password);
```

### Example Initialization Scripts

#### Create Schema
```sql
-- init-scripts/01-schema.sql
CREATE SCHEMA IF NOT EXISTS app_schema;

CREATE TABLE app_schema.users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE app_schema.posts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES app_schema.users(id),
    title VARCHAR(200) NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_posts_user_id ON app_schema.posts(user_id);
CREATE INDEX idx_posts_created_at ON app_schema.posts(created_at);
```

#### Seed Data
```sql
-- init-scripts/02-seed-data.sql
INSERT INTO app_schema.users (username, email, password_hash) VALUES
    ('admin', 'admin@example.com', 'hashed_password_here'),
    ('user1', 'user1@example.com', 'hashed_password_here'),
    ('user2', 'user2@example.com', 'hashed_password_here');

INSERT INTO app_schema.posts (user_id, title, content) VALUES
    (1, 'Welcome Post', 'This is the first post'),
    (2, 'User Post', 'Content from user1');
```

#### Create Functions
```sql
-- init-scripts/03-functions.sql
CREATE OR REPLACE FUNCTION app_schema.update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_users_updated_at 
    BEFORE UPDATE ON app_schema.users
    FOR EACH ROW
    EXECUTE FUNCTION app_schema.update_updated_at_column();
```

#### Shell Script Example
```bash
#!/bin/bash
# init-scripts/04-setup.sh
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
    CREATE EXTENSION IF NOT EXISTS "pgcrypto";
    GRANT ALL PRIVILEGES ON DATABASE $POSTGRES_DB TO $POSTGRES_USER;
EOSQL
```

### Common Use Cases and Patterns

#### Development Environment
```bash
# Quick start for development
docker-compose up -d

# Access database
docker-compose exec postgres psql -U postgres

# Reset database
docker-compose down -v
docker-compose up -d
```

#### Testing Environment
```yaml
# docker-compose.test.yml
version: '3.8'
services:
  postgres_test:
    image: postgres:latest
    environment:
      POSTGRES_DB: test_db
      POSTGRES_USER: test_user
      POSTGRES_PASSWORD: test_password
    ports:
      - "5433:5432"
    tmpfs:
      - /var/lib/postgresql/data  # In-memory for faster tests
```

#### Production-like Setup
```yaml
# Enhanced docker-compose.yml for production
services:
  postgres:
    # ... existing config ...
    restart: always
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 2G
```

#### Backup Automation
```bash
#!/bin/bash
# scripts/backup.sh
BACKUP_DIR="./backups"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=7

mkdir -p $BACKUP_DIR

# Create backup
docker-compose exec -T postgres pg_dump -U postgres postgres | gzip > "$BACKUP_DIR/backup_$DATE.sql.gz"

# Remove old backups
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup completed: backup_$DATE.sql.gz"
```

#### Health Check Script
```bash
#!/bin/bash
# scripts/health-check.sh
if docker-compose exec -T postgres pg_isready -U postgres > /dev/null 2>&1; then
    echo "PostgreSQL is healthy"
    exit 0
else
    echo "PostgreSQL is not responding"
    exit 1
fi
```

---

## Additional Resources

- [PostgreSQL Official Documentation](https://www.postgresql.org/docs/)
- [Docker PostgreSQL Image](https://hub.docker.com/_/postgres)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [PostgreSQL Best Practices](https://wiki.postgresql.org/wiki/Don%27t_Do_This)

---

**Last Updated**: 2024
**PostgreSQL Version**: Latest (16)
**Docker Compose Version**: 3.8

