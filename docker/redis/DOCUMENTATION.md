# Redis Docker Service - Complete Documentation

This document provides comprehensive information about setting up, configuring, and using the Redis Docker service.

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

Before setting up the Redis Docker service, ensure you have the following installed:

- **Docker**: Version 20.10 or higher
  - Check installation: `docker --version`
  - Install: [Docker Installation Guide](https://docs.docker.com/get-docker/)

- **Docker Compose**: Version 2.0 or higher
  - Check installation: `docker-compose --version`
  - Install: [Docker Compose Installation Guide](https://docs.docker.com/compose/install/)

### Step-by-Step Installation

1. **Navigate to the redis directory**
   ```bash
   cd redis
   ```

2. **Review and configure environment variables**
   ```bash
   # Open the .env file in your preferred editor
   nano .env
   # or
   vim .env
   ```

3. **Update Redis configuration** (recommended for security)
   ```bash
   # Change these values in .env:
   REDIS_PASSWORD=your_secure_password
   REDIS_MAXMEMORY=512mb
   ```

4. **Build and start the service**
   ```bash
   docker-compose up -d
   ```

5. **Verify the service is running**
   ```bash
   docker-compose ps
   # Should show redis service as "Up"
   ```

6. **Check health status**
   ```bash
   docker-compose ps
   # Health status should show as "healthy"
   ```

7. **Test Redis connection**
   ```bash
   docker-compose exec redis redis-cli ping
   # Should return: PONG
   ```

### Initial Configuration Steps

1. **Wait for initialization** (first startup only)
   - The first startup is usually very fast (< 5 seconds)
   - Check logs: `docker-compose logs -f redis`

2. **Test basic operations**
   ```bash
   docker-compose exec redis redis-cli
   > SET test "Hello Redis"
   > GET test
   > EXIT
   ```

3. **Verify persistence** (if enabled)
   ```bash
   docker-compose restart redis
   docker-compose exec redis redis-cli GET test
   # Should still return "Hello Redis"
   ```

---

## Configuration

### Environment Variables

The `.env` file contains all configuration variables. Here's a complete reference:

#### Optional Variables

| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `REDIS_PASSWORD` | Redis authentication password | (empty) | `SecurePass123!` |
| `REDIS_PORT` | Host port mapping | `6379` | `6380` |
| `REDIS_APPENDONLY` | Enable AOF persistence | `yes` | `no` |
| `REDIS_MAXMEMORY` | Maximum memory limit | `256mb` | `512mb` |
| `REDIS_MAXMEMORY_POLICY` | Eviction policy | `allkeys-lru` | `volatile-lru` |

#### Memory Policies

| Policy | Description |
|--------|-------------|
| `noeviction` | Don't evict, return errors on write |
| `allkeys-lru` | Evict least recently used keys |
| `volatile-lru` | Evict least recently used keys with expire set |
| `allkeys-random` | Evict random keys |
| `volatile-random` | Evict random keys with expire set |
| `volatile-ttl` | Evict keys with shortest TTL |

### Default Values and Recommended Settings

#### Development Environment
```env
REDIS_PASSWORD=
REDIS_PORT=6379
REDIS_APPENDONLY=yes
REDIS_MAXMEMORY=256mb
REDIS_MAXMEMORY_POLICY=allkeys-lru
```

#### Production Environment
```env
REDIS_PASSWORD=<strong-random-password>
REDIS_PORT=6379
REDIS_APPENDONLY=yes
REDIS_MAXMEMORY=2gb
REDIS_MAXMEMORY_POLICY=allkeys-lru
```

### Security Best Practices

1. **Set a Strong Password**
   ```env
   REDIS_PASSWORD=your_very_strong_password_here
   ```
   - Use at least 32 characters
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
   - Don't expose Redis port publicly unless necessary
   - Use firewall rules to restrict access
   - Consider using Redis ACLs for fine-grained access control

4. **Disable Dangerous Commands**
   Edit `redis.conf`:
   ```conf
   rename-command FLUSHDB ""
   rename-command FLUSHALL ""
   rename-command CONFIG ""
   rename-command SHUTDOWN SHUTDOWN_MY_PASSWORD
   ```

### Port Configuration and Customization

#### Change Default Port

Edit `.env`:
```env
REDIS_PORT=6380
```

Then restart:
```bash
docker-compose down
docker-compose up -d
```

#### Multiple Redis Instances

To run multiple instances, create separate directories with different port mappings:
- Instance 1: `REDIS_PORT=6379`
- Instance 2: `REDIS_PORT=6380`
- Instance 3: `REDIS_PORT=6381`

### Volume and Data Persistence Setup

#### Volume Configuration

The `docker-compose.yml` includes a named volume `redis_data` that persists data:

```yaml
volumes:
  redis_data:
    driver: local
```

#### Persistence Methods

Redis supports two persistence methods:

1. **RDB (Snapshotting)**
   - Point-in-time snapshots
   - Configured in `redis.conf`:
     ```conf
     save 900 1
     save 300 10
     save 60 10000
     ```

2. **AOF (Append Only File)**
   - Logs every write operation
   - More durable, slightly slower
   - Configured via `REDIS_APPENDONLY=yes` in `.env`

#### Inspect Volume
```bash
# List volumes
docker volume ls

# Inspect redis_data volume
docker volume inspect redis_redis_data

# View volume size
docker system df -v
```

#### Backup Volume Data
```bash
# Create backup
docker run --rm -v redis_redis_data:/data -v $(pwd):/backup \
  alpine tar czf /backup/redis_backup.tar.gz -C /data .
```

#### Restore Volume Data
```bash
# Stop service
docker-compose down

# Restore backup
docker run --rm -v redis_redis_data:/data -v $(pwd):/backup \
  alpine sh -c "cd /data && tar xzf /backup/redis_backup.tar.gz"

# Start service
docker-compose up -d
```

### Redis Configuration File

The `redis.conf` file provides detailed Redis configuration. Key sections:

#### Network Configuration
```conf
bind 0.0.0.0
port 6379
protected-mode yes
```

#### Memory Management
```conf
maxmemory 256mb
maxmemory-policy allkeys-lru
```

#### Persistence
```conf
save 900 1
save 300 10
save 60 10000
appendonly yes
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
docker-compose restart redis

# Restart with rebuild
docker-compose up -d --build
```

### Connecting to Redis

#### Using redis-cli from Container
```bash
# Connect without password
docker-compose exec redis redis-cli

# Connect with password
docker-compose exec redis redis-cli -a your_password

# Execute single command
docker-compose exec redis redis-cli ping
docker-compose exec redis redis-cli GET key_name
```

#### Using Local redis-cli Client
```bash
# Install redis-cli (if not installed)
# macOS: brew install redis
# Ubuntu: sudo apt-get install redis-tools

# Connect
redis-cli -h localhost -p 6379

# Connect with password
redis-cli -h localhost -p 6379 -a your_password

# Connect and execute command
redis-cli -h localhost -p 6379 GET key_name
```

#### Using Connection String
```
redis://:password@localhost:6379/0
```

### Basic Redis Operations

#### String Operations
```bash
redis-cli
> SET mykey "Hello"
> GET mykey
> DEL mykey
> EXISTS mykey
> EXPIRE mykey 60
> TTL mykey
```

#### List Operations
```bash
> LPUSH mylist "item1"
> RPUSH mylist "item2"
> LRANGE mylist 0 -1
> LLEN mylist
```

#### Set Operations
```bash
> SADD myset "member1"
> SADD myset "member2"
> SMEMBERS myset
> SISMEMBER myset "member1"
```

#### Hash Operations
```bash
> HSET myhash field1 "value1"
> HGET myhash field1
> HGETALL myhash
> HDEL myhash field1
```

#### Sorted Set Operations
```bash
> ZADD myzset 1 "member1"
> ZADD myzset 2 "member2"
> ZRANGE myzset 0 -1
> ZSCORE myzset "member1"
```

### Backup and Restore Procedures

#### Database Backup

##### Using redis-cli
```bash
# Create RDB backup (synchronous)
docker-compose exec redis redis-cli SAVE

# Create RDB backup (asynchronous, non-blocking)
docker-compose exec redis redis-cli BGSAVE

# Check if backup is in progress
docker-compose exec redis redis-cli LASTSAVE
```

##### Using Docker Volume Backup
```bash
# Backup entire data directory
docker run --rm -v redis_redis_data:/data -v $(pwd):/backup \
  alpine tar czf /backup/redis_backup_$(date +%Y%m%d_%H%M%S).tar.gz -C /data .
```

##### Automated Backup Script
```bash
#!/bin/bash
# backup.sh
BACKUP_DIR="./backups"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Trigger Redis backup
docker-compose exec -T redis redis-cli BGSAVE

# Wait for backup to complete
sleep 5

# Copy backup files
docker run --rm -v redis_redis_data:/data -v $(pwd)/$BACKUP_DIR:/backup \
  alpine sh -c "cp /data/dump.rdb /backup/dump_$DATE.rdb && \
                cp /data/appendonly.aof /backup/appendonly_$DATE.aof 2>/dev/null || true"

echo "Backup completed: $BACKUP_DIR/dump_$DATE.rdb"
```

#### Database Restore

##### From RDB File
```bash
# Stop Redis
docker-compose stop redis

# Copy RDB file to volume
docker run --rm -v redis_redis_data:/data -v $(pwd):/backup \
  alpine sh -c "cp /backup/dump.rdb /data/dump.rdb"

# Start Redis
docker-compose start redis
```

##### From Volume Backup
```bash
# Stop service
docker-compose down

# Restore backup
docker run --rm -v redis_redis_data:/data -v $(pwd):/backup \
  alpine sh -c "cd /data && tar xzf /backup/redis_backup.tar.gz"

# Start service
docker-compose up -d
```

### Logging and Monitoring

#### View Logs
```bash
# View all logs
docker-compose logs redis

# Follow logs (real-time)
docker-compose logs -f redis

# View last 100 lines
docker-compose logs --tail=100 redis

# View logs with timestamps
docker-compose logs -t redis
```

#### Health Check
```bash
# Check container health
docker-compose ps

# Manual health check
docker-compose exec redis redis-cli ping
# Should return: PONG
```

#### Performance Monitoring
```bash
# Get Redis info
docker-compose exec redis redis-cli INFO

# Get memory info
docker-compose exec redis redis-cli INFO memory

# Get stats
docker-compose exec redis redis-cli INFO stats

# Monitor commands in real-time
docker-compose exec redis redis-cli MONITOR

# Get slow log
docker-compose exec redis redis-cli SLOWLOG GET 10
```

#### Key Statistics
```bash
# Count all keys
docker-compose exec redis redis-cli DBSIZE

# Get all keys (use with caution on large databases)
docker-compose exec redis redis-cli KEYS "*"

# Get keys matching pattern
docker-compose exec redis redis-cli KEYS "user:*"

# Get memory usage of a key
docker-compose exec redis redis-cli MEMORY USAGE mykey
```

---

## Advanced Topics

### Custom Redis Configuration

#### Method 1: Environment Variables
Add to `.env`:
```env
REDIS_PASSWORD=your_password
REDIS_MAXMEMORY=512mb
REDIS_MAXMEMORY_POLICY=allkeys-lru
```

#### Method 2: Custom redis.conf
Edit `redis.conf` directly for advanced configuration:

```conf
# Custom memory settings
maxmemory 1gb
maxmemory-policy volatile-lru

# Custom persistence
save 300 1
save 60 1000

# Custom logging
loglevel verbose
logfile /var/log/redis/redis.log

# Disable dangerous commands
rename-command FLUSHDB ""
rename-command FLUSHALL ""
```

### Performance Tuning

#### Memory Optimization
```conf
# For datasets with many small keys
hash-max-ziplist-entries 512
hash-max-ziplist-value 64
list-max-ziplist-size -2
set-max-intset-entries 512
zset-max-ziplist-entries 128
zset-max-ziplist-value 64
```

#### Network Optimization
```conf
tcp-backlog 511
tcp-keepalive 300
timeout 0
```

#### Persistence Optimization
```conf
# For better performance, less durability
appendfsync everysec
no-appendfsync-on-rewrite yes

# For maximum durability
appendfsync always
```

### Network Configuration

#### Default Network
The service uses a bridge network `redis_network` for isolation.

#### Connect Other Services
```yaml
# In another docker-compose.yml
services:
  app:
    networks:
      - redis_network

networks:
  redis_network:
    external: true
```

#### Custom Network
```yaml
networks:
  redis_network:
    driver: bridge
    ipam:
      config:
        - subnet: 172.21.0.0/16
```

### Redis Replication

#### Master-Slave Setup
```yaml
# docker-compose.yml
version: '3.8'

services:
  redis-master:
    image: redis:latest
    command: redis-server --appendonly yes
    volumes:
      - redis_master_data:/data

  redis-slave:
    image: redis:latest
    command: redis-server --slaveof redis-master 6379 --appendonly yes
    volumes:
      - redis_slave_data:/data
    depends_on:
      - redis-master
```

### Redis Sentinel (High Availability)

#### Sentinel Setup
```yaml
version: '3.8'

services:
  redis-master:
    image: redis:latest
    command: redis-server --appendonly yes

  redis-slave:
    image: redis:latest
    command: redis-server --slaveof redis-master 6379

  redis-sentinel:
    image: redis:latest
    command: redis-sentinel /usr/local/etc/redis/sentinel.conf
    volumes:
      - ./sentinel.conf:/usr/local/etc/redis/sentinel.conf
```

### Production Deployment Considerations

#### Security Checklist
- [ ] Set strong password
- [ ] Disable dangerous commands
- [ ] Use Redis ACLs for fine-grained access
- [ ] Enable protected mode
- [ ] Restrict network access
- [ ] Use SSL/TLS (Redis 6+)
- [ ] Regular security updates
- [ ] Enable audit logging
- [ ] Configure firewall rules

#### Resource Limits
Add to `docker-compose.yml`:
```yaml
services:
  redis:
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
- Use Redis Sentinel for automatic failover
- Implement Redis Cluster for horizontal scaling
- Set up automated backups
- Monitor replication lag
- Plan for disaster recovery

#### Backup Strategy
- Daily automated backups
- Weekly full backups
- Monthly archive backups
- Test restore procedures regularly
- Keep backups in multiple locations

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
docker-compose logs redis

# Common causes:
# 1. Port already in use
#    Solution: Change REDIS_PORT in .env

# 2. Volume permission issues
#    Solution: 
docker-compose down -v
docker-compose up -d

# 3. Invalid redis.conf syntax
#    Solution: Check redis.conf file for syntax errors
```

#### Issue: Can't connect to Redis

**Symptoms:**
- Connection refused errors
- Timeout errors
- Authentication failed

**Solutions:**
```bash
# 1. Verify container is running
docker-compose ps

# 2. Check port mapping
docker-compose port redis 6379

# 3. Test connection from container
docker-compose exec redis redis-cli ping

# 4. Check if password is required
docker-compose exec redis redis-cli -a your_password ping

# 5. Check firewall settings
# macOS: System Preferences > Security & Privacy > Firewall
# Linux: sudo ufw status
```

#### Issue: Out of memory

**Symptoms:**
- "OOM command not allowed" errors
- Keys being evicted unexpectedly

**Solutions:**
```bash
# 1. Check current memory usage
docker-compose exec redis redis-cli INFO memory

# 2. Increase maxmemory in .env
REDIS_MAXMEMORY=1gb

# 3. Adjust eviction policy
REDIS_MAXMEMORY_POLICY=allkeys-lru

# 4. Restart service
docker-compose restart redis
```

#### Issue: Data not persisting

**Symptoms:**
- Data lost after restart
- AOF or RDB files not created

**Solutions:**
```bash
# 1. Check persistence settings
docker-compose exec redis redis-cli CONFIG GET save
docker-compose exec redis redis-cli CONFIG GET appendonly

# 2. Enable AOF persistence
# Edit .env:
REDIS_APPENDONLY=yes

# 3. Check volume mount
docker volume inspect redis_redis_data

# 4. Verify data directory
docker-compose exec redis ls -la /data
```

#### Issue: Slow performance

**Symptoms:**
- Slow response times
- High latency

**Solutions:**
```bash
# 1. Check slow log
docker-compose exec redis redis-cli SLOWLOG GET 10

# 2. Monitor commands
docker-compose exec redis redis-cli MONITOR

# 3. Check memory usage
docker-compose exec redis redis-cli INFO memory

# 4. Check connection count
docker-compose exec redis redis-cli INFO clients

# 5. Review configuration
docker-compose exec redis redis-cli CONFIG GET "*"
```

### Log Analysis

#### Understanding Log Messages

**Common log patterns:**
```
# Successful startup
* Ready to accept connections

# Persistence
* Background saving started
* Background saving terminated with success

# Memory
* Memory limit exceeded
* Evicting keys

# Error messages
* Error: ...
* FATAL: ...
```

#### Filtering Logs
```bash
# Errors only
docker-compose logs redis | grep -i error

# Warnings
docker-compose logs redis | grep -i warn

# Recent errors
docker-compose logs --since 1h redis | grep -i error

# Persistence messages
docker-compose logs redis | grep -i "save\|aof"
```

### Connection Problems

#### Problem: Authentication failed

**Solution:**
```bash
# Verify password in .env
cat .env | grep REDIS_PASSWORD

# Test with password
docker-compose exec redis redis-cli -a your_password ping

# Update password if needed
# Edit .env and restart
docker-compose restart redis
```

#### Problem: Connection timeout

**Solution:**
```bash
# Check if service is accessible
docker-compose exec redis redis-cli ping

# Verify port is exposed
docker-compose ps

# Check network connectivity
docker network inspect redis_redis_network
```

### Data Recovery Procedures

#### Recover from RDB Backup
```bash
# 1. Stop service
docker-compose down

# 2. Remove corrupted volume
docker volume rm redis_redis_data

# 3. Start service (creates new volume)
docker-compose up -d

# 4. Wait for startup
sleep 5

# 5. Copy RDB file
docker run --rm -v redis_redis_data:/data -v $(pwd):/backup \
  alpine sh -c "cp /backup/dump.rdb /data/dump.rdb"

# 6. Restart service
docker-compose restart redis
```

#### Recover from AOF Backup
```bash
# 1. Stop service
docker-compose down

# 2. Copy AOF file
docker run --rm -v redis_redis_data:/data -v $(pwd):/backup \
  alpine sh -c "cp /backup/appendonly.aof /data/appendonly.aof"

# 3. Start service
docker-compose up -d
```

---

## Examples

### Sample Connection Strings

#### Python (redis-py)
```python
import redis

# Without password
r = redis.Redis(host='localhost', port=6379, db=0)

# With password
r = redis.Redis(host='localhost', port=6379, password='your_password', db=0)

# Test connection
r.ping()
```

#### Node.js (node_redis)
```javascript
const redis = require('redis');

// Without password
const client = redis.createClient({
  host: 'localhost',
  port: 6379
});

// With password
const client = redis.createClient({
  host: 'localhost',
  port: 6379,
  password: 'your_password'
});

client.connect();
```

#### Java (Jedis)
```java
import redis.clients.jedis.Jedis;

// Without password
Jedis jedis = new Jedis("localhost", 6379);

// With password
Jedis jedis = new Jedis("localhost", 6379);
jedis.auth("your_password");
```

#### Ruby (redis gem)
```ruby
require 'redis'

# Without password
redis = Redis.new(host: 'localhost', port: 6379)

# With password
redis = Redis.new(host: 'localhost', port: 6379, password: 'your_password')
```

#### Go (go-redis)
```go
import "github.com/go-redis/redis/v8"

// Without password
rdb := redis.NewClient(&redis.Options{
    Addr:     "localhost:6379",
    Password: "",
    DB:       0,
})

// With password
rdb := redis.NewClient(&redis.Options{
    Addr:     "localhost:6379",
    Password: "your_password",
    DB:       0,
})
```

### Common Use Cases

#### Caching
```python
import redis
r = redis.Redis(host='localhost', port=6379)

# Set cache with expiration
r.setex('user:123', 3600, 'user_data')

# Get from cache
user_data = r.get('user:123')
```

#### Session Storage
```python
import redis
r = redis.Redis(host='localhost', port=6379)

# Store session
r.setex('session:abc123', 1800, 'session_data')

# Get session
session = r.get('session:abc123')
```

#### Rate Limiting
```python
import redis
r = redis.Redis(host='localhost', port=6379)

def rate_limit(user_id, limit=100, window=3600):
    key = f'rate_limit:{user_id}'
    current = r.incr(key)
    if current == 1:
        r.expire(key, window)
    return current <= limit
```

#### Message Queue
```python
import redis
r = redis.Redis(host='localhost', port=6379)

# Producer
r.lpush('queue:tasks', 'task_data')

# Consumer
task = r.brpop('queue:tasks', timeout=10)
```

### Production Setup Example

```yaml
# docker-compose.yml
version: '3.8'

services:
  redis:
    image: redis:latest
    command: >
      redis-server
      --requirepass ${REDIS_PASSWORD}
      --appendonly yes
      --maxmemory 2gb
      --maxmemory-policy allkeys-lru
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
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

---

## Additional Resources

- [Redis Official Documentation](https://redis.io/documentation)
- [Redis Docker Image](https://hub.docker.com/_/redis)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Redis Commands Reference](https://redis.io/commands)

---

**Last Updated**: 2024
**Redis Version**: Latest
**Docker Compose Version**: 3.8

