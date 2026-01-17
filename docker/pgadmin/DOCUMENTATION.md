# pgAdmin Docker Service - Complete Documentation

This document provides comprehensive information about setting up, configuring, and using the pgAdmin Docker service.

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

Before setting up the pgAdmin Docker service, ensure you have the following installed:

- **Docker**: Version 20.10 or higher
  - Check installation: `docker --version`
  - Install: [Docker Installation Guide](https://docs.docker.com/get-docker/)

- **Docker Compose**: Version 2.0 or higher
  - Check installation: `docker-compose --version`
  - Install: [Docker Compose Installation Guide](https://docs.docker.com/compose/install/)

- **Web Browser**: Modern browser (Chrome, Firefox, Safari, Edge)

### Step-by-Step Installation

1. **Navigate to the pgadmin directory**
   ```bash
   cd pgadmin
   ```

2. **Review and configure environment variables**
   ```bash
   # Open the .env file in your preferred editor
   nano .env
   # or
   vim .env
   ```

3. **Update default credentials** (recommended for security)
   ```bash
   # Change these values in .env:
   PGADMIN_DEFAULT_EMAIL=your_email@example.com
   PGADMIN_DEFAULT_PASSWORD=your_secure_password
   ```

4. **Build and start the service**
   ```bash
   docker-compose up -d
   ```

5. **Verify the service is running**
   ```bash
   docker-compose ps
   # Should show pgadmin service as "Up"
   ```

6. **Check health status**
   ```bash
   docker-compose ps
   # Health status should show as "healthy"
   ```

7. **Access pgAdmin web interface**
   - Open browser: `http://localhost:5050` (or port from `.env`)
   - Login with credentials from `.env` file

### Initial Configuration Steps

1. **Wait for initialization** (first startup only)
   - The first startup may take 30-60 seconds
   - Check logs: `docker-compose logs -f pgadmin`

2. **First Login**
   - Use email and password from `.env` file
   - You may be prompted to set a master password (optional)

3. **Connect to PostgreSQL Server**
   - Right-click "Servers" → "Register" → "Server"
   - Fill in connection details
   - Save the connection

---

## Configuration

### Environment Variables

The `.env` file contains all configuration variables. Here's a complete reference:

#### Required Variables

| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `PGADMIN_DEFAULT_EMAIL` | Default login email | `admin@admin.com` | `admin@example.com` |
| `PGADMIN_DEFAULT_PASSWORD` | Default login password | `admin` | `SecurePass123!` |

#### Optional Variables

| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `PGADMIN_PORT` | Host port mapping | `5050` | `8080` |
| `PGADMIN_CONFIG_SERVER_MODE` | Enable server mode (multi-user) | `False` | `True` |
| `PGADMIN_CONFIG_MASTER_PASSWORD_REQUIRED` | Require master password | `False` | `True` |
| `PGADMIN_LISTEN_ADDRESS` | Listen address | `0.0.0.0` | `127.0.0.1` |
| `PGADMIN_CONFIG_ENHANCED_COOKIE_PROTECTION` | Enhanced cookie protection | `True` | `False` |
| `PGADMIN_CONFIG_WTF_CSRF_ENABLED` | CSRF protection | `True` | `False` |
| `PGADMIN_CONFIG_SESSION_COOKIE_SECURE` | Secure session cookies | `False` | `True` |

### Default Values and Recommended Settings

#### Development Environment
```env
PGADMIN_DEFAULT_EMAIL=admin@admin.com
PGADMIN_DEFAULT_PASSWORD=admin
PGADMIN_PORT=5050
PGADMIN_CONFIG_SERVER_MODE=False
```

#### Production Environment
```env
PGADMIN_DEFAULT_EMAIL=admin@yourdomain.com
PGADMIN_DEFAULT_PASSWORD=<strong-random-password>
PGADMIN_PORT=5050
PGADMIN_CONFIG_SERVER_MODE=True
PGADMIN_CONFIG_MASTER_PASSWORD_REQUIRED=True
PGADMIN_CONFIG_SESSION_COOKIE_SECURE=True
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
   - Don't expose pgAdmin port publicly unless necessary
   - Use firewall rules to restrict access
   - Consider using reverse proxy with SSL/TLS

4. **Server Mode vs Desktop Mode**
   - **Desktop Mode** (False): Single user, simpler setup
   - **Server Mode** (True): Multi-user, requires master password, better for production

### Port Configuration and Customization

#### Change Default Port

Edit `.env`:
```env
PGADMIN_PORT=8080
```

Then restart:
```bash
docker-compose down
docker-compose up -d
```

#### Multiple pgAdmin Instances

To run multiple instances, create separate directories with different port mappings:
- Instance 1: `PGADMIN_PORT=5050`
- Instance 2: `PGADMIN_PORT=5051`
- Instance 3: `PGADMIN_PORT=5052`

### Volume and Data Persistence Setup

#### Volume Configuration

The `docker-compose.yml` includes a named volume `pgadmin_data` that persists data:

```yaml
volumes:
  pgadmin_data:
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

# Inspect pgadmin_data volume
docker volume inspect pgadmin_pgadmin_data

# View volume size
docker system df -v
```

#### Backup Volume Data
```bash
# Create backup
docker run --rm -v pgadmin_pgadmin_data:/data -v $(pwd):/backup \
  alpine tar czf /backup/pgadmin_backup.tar.gz -C /data .
```

#### Restore Volume Data
```bash
# Stop service
docker-compose down

# Restore backup
docker run --rm -v pgadmin_pgadmin_data:/data -v $(pwd):/backup \
  alpine sh -c "cd /data && tar xzf /backup/pgadmin_backup.tar.gz"

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
docker-compose restart pgadmin

# Restart with rebuild
docker-compose up -d --build
```

### Accessing pgAdmin Web Interface

#### Basic Access
1. Open web browser
2. Navigate to: `http://localhost:5050` (or port from `.env`)
3. Login with email and password from `.env`

#### First Time Setup
1. Enter email and password
2. If master password is required, set it (remember this!)
3. You'll be taken to the pgAdmin dashboard

### Connecting to PostgreSQL Servers

#### Add New Server Connection

1. **Right-click "Servers"** in the left panel
2. **Select "Register" → "Server"**
3. **General Tab:**
   - Name: `My PostgreSQL Server` (any name)
4. **Connection Tab:**
   - Host name/address: `postgres` (if using Docker network) or `localhost`
   - Port: `5432` (or your PostgreSQL port)
   - Maintenance database: `postgres`
   - Username: PostgreSQL username
   - Password: PostgreSQL password
   - Save password: ✓ (optional)
5. **Click "Save"**

#### Connect to PostgreSQL in Docker Network

If PostgreSQL is running in the same Docker network:
- Host: `postgres` (service name)
- Port: `5432`

#### Connect to Local PostgreSQL

If PostgreSQL is running on host machine:
- Host: `host.docker.internal` (macOS/Windows) or `172.17.0.1` (Linux)
- Port: `5432`

### Managing Databases

#### View Database Objects
- Expand server → Databases → Your database
- Browse tables, views, functions, etc.

#### Execute SQL Queries
1. Right-click database → "Query Tool"
2. Enter SQL query
3. Click "Execute" (F5) or press F5

#### Create New Database
1. Right-click "Databases" → "Create" → "Database"
2. Enter database name
3. Configure options as needed
4. Click "Save"

#### Backup Database
1. Right-click database → "Backup..."
2. Configure backup options
3. Select file location
4. Click "Backup"

#### Restore Database
1. Right-click database → "Restore..."
2. Select backup file
3. Configure restore options
4. Click "Restore"

### Logging and Monitoring

#### View Logs
```bash
# View all logs
docker-compose logs pgadmin

# Follow logs (real-time)
docker-compose logs -f pgadmin

# View last 100 lines
docker-compose logs --tail=100 pgadmin

# View logs with timestamps
docker-compose logs -t pgadmin
```

#### Health Check
```bash
# Check container health
docker-compose ps

# Manual health check
curl http://localhost:5050/misc/ping
```

---

## Advanced Topics

### Custom pgAdmin Configuration

#### Method 1: Environment Variables
Add to `.env`:
```env
PGADMIN_CONFIG_SERVER_MODE=True
PGADMIN_CONFIG_MASTER_PASSWORD_REQUIRED=True
```

#### Method 2: Custom config_local.py
1. Create `config_local.py` in pgadmin directory
2. Add custom settings:
   ```python
   MASTER_PASSWORD_REQUIRED = True
   SERVER_MODE = True
   SESSION_COOKIE_SECURE = True
   ```

3. Mount in `docker-compose.yml`:
   ```yaml
   volumes:
     - ./config_local.py:/pgadmin4/config_local.py
   ```

### Network Configuration

#### Default Network
The service uses a bridge network `pgadmin_network` for isolation.

#### Connect to PostgreSQL Service
To connect pgAdmin to PostgreSQL in another docker-compose:

1. **Use external network:**
   ```yaml
   networks:
     pgadmin_network:
       external: true
       name: postgres_postgres_network
   ```

2. **Or create shared network:**
   ```yaml
   networks:
     shared_network:
       external: true
       name: shared_db_network
   ```

### Multi-Container Setups

#### Example: pgAdmin + PostgreSQL
```yaml
version: '3.8'

services:
  postgres:
    image: postgres:latest
    environment:
      POSTGRES_DB: mydb
      POSTGRES_USER: user
      POSTGRES_PASSWORD: password
    networks:
      - db_network

  pgadmin:
    image: dpage/pgadmin4:latest
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
- [ ] Enable server mode for multi-user
- [ ] Set master password
- [ ] Enable HTTPS/SSL
- [ ] Use reverse proxy (nginx, traefik)
- [ ] Restrict network access
- [ ] Regular security updates
- [ ] Enable audit logging
- [ ] Configure firewall rules

#### Resource Limits
Add to `docker-compose.yml`:
```yaml
services:
  pgadmin:
    deploy:
      resources:
        limits:
          cpus: '1'
          memory: 1G
        reservations:
          cpus: '0.5'
          memory: 512M
```

#### Reverse Proxy Setup (nginx)
```nginx
server {
    listen 80;
    server_name pgadmin.yourdomain.com;

    location / {
        proxy_pass http://localhost:5050;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

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
docker-compose logs pgadmin

# Common causes:
# 1. Port already in use
#    Solution: Change PGADMIN_PORT in .env

# 2. Volume permission issues
#    Solution: 
docker-compose down -v
docker-compose up -d

# 3. Invalid environment variables
#    Solution: Check .env file syntax
```

#### Issue: Can't access web interface

**Symptoms:**
- Connection refused errors
- Timeout errors
- 502 Bad Gateway

**Solutions:**
```bash
# 1. Verify container is running
docker-compose ps

# 2. Check port mapping
docker-compose port pgadmin 80

# 3. Test connection
curl http://localhost:5050/misc/ping

# 4. Check firewall settings
# macOS: System Preferences > Security & Privacy > Firewall
# Linux: sudo ufw status

# 5. Check if service is ready
docker-compose logs pgadmin | grep "Booting"
```

#### Issue: Can't connect to PostgreSQL

**Symptoms:**
- Connection timeout
- Authentication failed

**Solutions:**
```bash
# 1. Verify PostgreSQL is running
docker-compose ps postgres  # if in same compose
# or
docker ps | grep postgres

# 2. Test network connectivity
docker-compose exec pgadmin ping postgres

# 3. Check PostgreSQL connection from pgAdmin container
docker-compose exec pgadmin psql -h postgres -U postgres -d postgres

# 4. Verify credentials
# Check PostgreSQL .env file for correct username/password
```

#### Issue: Master password required

**Symptoms:**
- Prompted for master password on login
- Can't remember master password

**Solutions:**
```bash
# 1. Reset by removing volume (WARNING: deletes all pgAdmin data)
docker-compose down -v
docker-compose up -d

# 2. Or set master password requirement to False
# Edit .env:
PGADMIN_CONFIG_MASTER_PASSWORD_REQUIRED=False
docker-compose restart pgadmin
```

#### Issue: Session expired / Logged out frequently

**Symptoms:**
- Frequently logged out
- Session cookies not persisting

**Solutions:**
```bash
# 1. Increase session timeout
# Edit docker-compose.yml, add environment variable:
PGADMIN_CONFIG_SESSION_EXPIRATION_TIME=43200  # 12 hours in seconds

# 2. Check cookie settings
# Ensure SESSION_COOKIE_SECURE matches your setup
```

### Log Analysis

#### Understanding Log Messages

**Common log patterns:**
```
# Successful startup
Booting pgAdmin 4...
Starting pgAdmin 4. Application Server

# Connection established
Connection to server established

# Error messages
ERROR: ...
FATAL: ...
```

#### Filtering Logs
```bash
# Errors only
docker-compose logs pgadmin | grep -i error

# Warnings
docker-compose logs pgadmin | grep -i warn

# Recent errors
docker-compose logs --since 1h pgadmin | grep -i error

# Startup messages
docker-compose logs pgadmin | grep -i "booting\|starting"
```

### Connection Problems

#### Problem: Can't connect to PostgreSQL in Docker

**Solution:**
```bash
# 1. Ensure both services are on same network
docker network ls
docker network inspect pgadmin_pgadmin_network

# 2. Use service name as hostname
# In pgAdmin connection: Host = "postgres" (service name)

# 3. Test connectivity
docker-compose exec pgadmin ping postgres
```

#### Problem: Connection timeout to external PostgreSQL

**Solution:**
```bash
# 1. For host machine PostgreSQL
# Use host.docker.internal (macOS/Windows) or 172.17.0.1 (Linux)

# 2. Check PostgreSQL allows external connections
# Edit postgresql.conf: listen_addresses = '*'

# 3. Check pg_hba.conf for allowed hosts
```

### Data Recovery Procedures

#### Recover from Backup
```bash
# 1. Stop service
docker-compose down

# 2. Remove corrupted volume
docker volume rm pgadmin_pgadmin_data

# 3. Start service (creates new volume)
docker-compose up -d

# 4. Restore backup
docker run --rm -v pgadmin_pgadmin_data:/data -v $(pwd):/backup \
  alpine sh -c "cd /data && tar xzf /backup/pgadmin_backup.tar.gz"

# 5. Restart service
docker-compose up -d
```

#### Export Server Connections
1. In pgAdmin: File → Preferences → Browser → Servers
2. Right-click server → Export
3. Save as JSON file

#### Import Server Connections
1. In pgAdmin: File → Preferences → Browser → Servers
2. Click "Import" button
3. Select JSON file

---

## Examples

### Basic Usage Example

#### Start pgAdmin
```bash
cd pgadmin
docker-compose up -d
```

#### Access and Connect
1. Open `http://localhost:5050`
2. Login with credentials from `.env`
3. Register PostgreSQL server
4. Start managing databases

### Integration with PostgreSQL Docker Service

#### Connect pgAdmin to PostgreSQL Service

1. **Ensure both services are running:**
   ```bash
   # Terminal 1: Start PostgreSQL
   cd ../postgres
   docker-compose up -d

   # Terminal 2: Start pgAdmin
   cd ../pgadmin
   docker-compose up -d
   ```

2. **Create shared network:**
   ```bash
   # Create network
   docker network create db_network

   # Connect PostgreSQL to network
   docker network connect db_network postgres

   # Connect pgAdmin to network
   docker network connect db_network pgadmin
   ```

3. **In pgAdmin, use hostname:**
   - Host: `postgres` (PostgreSQL service name)
   - Port: `5432`

### Custom Configuration Example

#### Enable Server Mode
```env
# .env
PGADMIN_CONFIG_SERVER_MODE=True
PGADMIN_CONFIG_MASTER_PASSWORD_REQUIRED=True
```

#### Custom Port
```env
# .env
PGADMIN_PORT=8080
```

### Production Setup Example

```yaml
# docker-compose.yml
version: '3.8'

services:
  pgadmin:
    image: dpage/pgadmin4:latest
    environment:
      PGADMIN_DEFAULT_EMAIL: admin@company.com
      PGADMIN_DEFAULT_PASSWORD: ${PGADMIN_PASSWORD}
      PGADMIN_CONFIG_SERVER_MODE: True
      PGADMIN_CONFIG_MASTER_PASSWORD_REQUIRED: True
      PGADMIN_CONFIG_SESSION_COOKIE_SECURE: True
    ports:
      - "5050:80"
    volumes:
      - pgadmin_data:/var/lib/pgadmin
    restart: always
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"
    deploy:
      resources:
        limits:
          cpus: '1'
          memory: 1G
```

---

## Additional Resources

- [pgAdmin Official Documentation](https://www.pgadmin.org/docs/)
- [pgAdmin Docker Image](https://hub.docker.com/r/dpage/pgadmin4)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

---

**Last Updated**: 2024
**pgAdmin Version**: Latest (4)
**Docker Compose Version**: 3.8

