# Elasticsearch Docker Service - Complete Documentation

This document provides comprehensive information about setting up, configuring, and using the Elasticsearch Docker service.

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

Before setting up the Elasticsearch Docker service, ensure you have the following installed:

- **Docker**: Version 20.10 or higher
  - Check installation: `docker --version`
  - Install: [Docker Installation Guide](https://docs.docker.com/get-docker/)

- **Docker Compose**: Version 2.0 or higher
  - Check installation: `docker-compose --version`
  - Install: [Docker Compose Installation Guide](https://docs.docker.com/compose/install/)

- **System Requirements**:
  - Minimum 2GB RAM (4GB+ recommended)
  - At least 1GB free disk space
  - Virtual memory settings configured (see below)

### System Configuration

#### Linux: Increase Virtual Memory

Elasticsearch requires increased virtual memory limits:

```bash
# Check current limit
sysctl vm.max_map_count

# Set permanently
echo "vm.max_map_count=262144" | sudo tee -a /etc/sysctl.conf
sudo sysctl -p

# Or set temporarily
sudo sysctl -w vm.max_map_count=262144
```

#### macOS/Windows

Docker Desktop handles this automatically, but ensure Docker Desktop has sufficient resources allocated:
- Memory: At least 4GB (8GB+ recommended)
- CPU: At least 2 cores

### Step-by-Step Installation

1. **Navigate to the elasticsearch directory**
   ```bash
   cd elasticsearch
   ```

2. **Review and configure environment variables**
   ```bash
   # Open the .env file in your preferred editor
   nano .env
   # or
   vim .env
   ```

3. **Configure memory settings** (important!)
   ```bash
   # Adjust based on available system memory
   # Recommended: 50% of available memory, max 32GB
   ES_JAVA_OPTS=-Xms1g -Xmx1g  # For 2GB system
   ES_JAVA_OPTS=-Xms2g -Xmx2g  # For 4GB system
   ```

4. **Build and start the service**
   ```bash
   docker-compose up -d
   ```

5. **Verify the service is running**
   ```bash
   docker-compose ps
   # Should show elasticsearch service as "Up"
   ```

6. **Check health status**
   ```bash
   docker-compose ps
   # Health status should show as "healthy"
   ```

7. **Test Elasticsearch**
   ```bash
   curl http://localhost:9200
   # Should return cluster information
   ```

### Initial Configuration Steps

1. **Wait for initialization** (first startup only)
   - The first startup may take 30-60 seconds
   - Check logs: `docker-compose logs -f elasticsearch`
   - Wait for "started" message in logs

2. **Verify cluster health**
   ```bash
   curl http://localhost:9200/_cluster/health?pretty
   # Should return status: "green" or "yellow"
   ```

3. **Check node information**
   ```bash
   curl http://localhost:9200/_nodes?pretty
   ```

---

## Configuration

### Environment Variables

The `.env` file contains all configuration variables. Here's a complete reference:

#### Required Variables

| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `ES_JAVA_OPTS` | Java heap size | `-Xms512m -Xmx512m` | `-Xms2g -Xmx2g` |

#### Optional Variables

| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `ELASTICSEARCH_DISCOVERY_TYPE` | Discovery type | `single-node` | `single-node` |
| `ELASTICSEARCH_SECURITY_ENABLED` | Enable security | `false` | `true` |
| `ELASTICSEARCH_HTTP_PORT` | HTTP API port | `9200` | `9200` |
| `ELASTICSEARCH_TRANSPORT_PORT` | Transport port | `9300` | `9300` |
| `ELASTICSEARCH_CLUSTER_NAME` | Cluster name | `docker-cluster` | `my-cluster` |
| `ELASTICSEARCH_NODE_NAME` | Node name | (auto-generated) | `node-1` |
| `ELASTICSEARCH_NETWORK_HOST` | Network host | `0.0.0.0` | `0.0.0.0` |

### Default Values and Recommended Settings

#### Development Environment
```env
ES_JAVA_OPTS=-Xms512m -Xmx512m
ELASTICSEARCH_DISCOVERY_TYPE=single-node
ELASTICSEARCH_SECURITY_ENABLED=false
ELASTICSEARCH_HTTP_PORT=9200
```

#### Production Environment
```env
ES_JAVA_OPTS=-Xms4g -Xmx4g
ELASTICSEARCH_DISCOVERY_TYPE=single-node
ELASTICSEARCH_SECURITY_ENABLED=true
ELASTICSEARCH_HTTP_PORT=9200
ELASTICSEARCH_CLUSTER_NAME=production-cluster
```

### Memory Configuration

#### Java Heap Size Guidelines

- **Minimum**: 256MB
- **Recommended**: 50% of available RAM
- **Maximum**: 32GB (31GB recommended)
- **Rule**: Heap size should not exceed 50% of available RAM

#### Examples

```env
# Small system (2GB RAM)
ES_JAVA_OPTS=-Xms512m -Xmx512m

# Medium system (4GB RAM)
ES_JAVA_OPTS=-Xms1g -Xmx1g

# Large system (8GB RAM)
ES_JAVA_OPTS=-Xms2g -Xmx2g

# Very large system (16GB+ RAM)
ES_JAVA_OPTS=-Xms4g -Xmx4g
```

### Security Best Practices

1. **Enable Security for Production**
   ```env
   ELASTICSEARCH_SECURITY_ENABLED=true
   ```

2. **Set Strong Passwords**
   ```bash
   # After enabling security, set passwords
   docker-compose exec elasticsearch elasticsearch-setup-passwords interactive
   ```

3. **Environment File Security**
   ```bash
   # Add .env to .gitignore
   echo ".env" >> .gitignore
   
   # Set proper file permissions
   chmod 600 .env
   ```

4. **Network Security**
   - Use Docker networks to isolate services
   - Don't expose Elasticsearch port publicly unless necessary
   - Use firewall rules to restrict access
   - Consider using reverse proxy with SSL/TLS

5. **Disable Security for Development**
   ```env
   ELASTICSEARCH_SECURITY_ENABLED=false
   ```

### Port Configuration and Customization

#### Change Default Ports

Edit `.env`:
```env
ELASTICSEARCH_HTTP_PORT=9201
ELASTICSEARCH_TRANSPORT_PORT=9301
```

Then restart:
```bash
docker-compose down
docker-compose up -d
```

#### Multiple Elasticsearch Instances

To run multiple instances, create separate directories with different port mappings:
- Instance 1: `ELASTICSEARCH_HTTP_PORT=9200`
- Instance 2: `ELASTICSEARCH_HTTP_PORT=9201`
- Instance 3: `ELASTICSEARCH_HTTP_PORT=9202`

### Volume and Data Persistence Setup

#### Volume Configuration

The `docker-compose.yml` includes a named volume `elasticsearch_data` that persists data:

```yaml
volumes:
  elasticsearch_data:
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

# Inspect elasticsearch_data volume
docker volume inspect elasticsearch_elasticsearch_data

# View volume size
docker system df -v
```

#### Backup Volume Data
```bash
# Create backup
docker run --rm -v elasticsearch_elasticsearch_data:/data -v $(pwd):/backup \
  alpine tar czf /backup/elasticsearch_backup.tar.gz -C /data .
```

#### Restore Volume Data
```bash
# Stop service
docker-compose down

# Restore backup
docker run --rm -v elasticsearch_elasticsearch_data:/data -v $(pwd):/backup \
  alpine sh -c "cd /data && tar xzf /backup/elasticsearch_backup.tar.gz"

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
docker-compose restart elasticsearch

# Restart with rebuild
docker-compose up -d --build
```

### Connecting to Elasticsearch

#### Using curl
```bash
# Basic connection test
curl http://localhost:9200

# With authentication (if enabled)
curl -u elastic:password http://localhost:9200
```

#### Using Elasticsearch API
```bash
# Cluster health
curl http://localhost:9200/_cluster/health?pretty

# Node info
curl http://localhost:9200/_nodes?pretty

# Cluster stats
curl http://localhost:9200/_cluster/stats?pretty
```

#### Using Elasticsearch Clients

See [Examples](#examples) section for client libraries in various languages.

### Basic Elasticsearch Operations

#### Create an Index
```bash
curl -X PUT "localhost:9200/my_index?pretty"
```

#### Index a Document
```bash
curl -X POST "localhost:9200/my_index/_doc" -H 'Content-Type: application/json' -d'
{
  "title": "Test Document",
  "content": "This is a test document",
  "timestamp": "2024-01-01T00:00:00"
}
'
```

#### Get a Document
```bash
curl -X GET "localhost:9200/my_index/_doc/1?pretty"
```

#### Search Documents
```bash
curl -X GET "localhost:9200/my_index/_search?pretty" -H 'Content-Type: application/json' -d'
{
  "query": {
    "match": {
      "content": "test"
    }
  }
}
'
```

#### Delete an Index
```bash
curl -X DELETE "localhost:9200/my_index?pretty"
```

### Backup and Restore Procedures

#### Using Elasticsearch Snapshot API

##### Create Snapshot Repository
```bash
curl -X PUT "localhost:9200/_snapshot/my_backup" -H 'Content-Type: application/json' -d'
{
  "type": "fs",
  "settings": {
    "location": "/usr/share/elasticsearch/backups/my_backup"
  }
}
'
```

##### Create Snapshot
```bash
curl -X PUT "localhost:9200/_snapshot/my_backup/snapshot_1?wait_for_completion=true"
```

##### Restore Snapshot
```bash
curl -X POST "localhost:9200/_snapshot/my_backup/snapshot_1/_restore"
```

#### Using Docker Volume Backup
```bash
# Create backup
docker run --rm -v elasticsearch_elasticsearch_data:/data -v $(pwd):/backup \
  alpine tar czf /backup/elasticsearch_backup_$(date +%Y%m%d_%H%M%S).tar.gz -C /data .
```

#### Automated Backup Script
```bash
#!/bin/bash
# backup.sh
BACKUP_DIR="./backups"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Create snapshot
curl -X PUT "localhost:9200/_snapshot/my_backup/snapshot_$DATE?wait_for_completion=true"

# Or backup volume directly
docker run --rm -v elasticsearch_elasticsearch_data:/data -v $(pwd)/$BACKUP_DIR:/backup \
  alpine tar czf /backup/elasticsearch_backup_$DATE.tar.gz -C /data .

echo "Backup completed: $BACKUP_DIR/elasticsearch_backup_$DATE.tar.gz"
```

### Logging and Monitoring

#### View Logs
```bash
# View all logs
docker-compose logs elasticsearch

# Follow logs (real-time)
docker-compose logs -f elasticsearch

# View last 100 lines
docker-compose logs --tail=100 elasticsearch

# View logs with timestamps
docker-compose logs -t elasticsearch
```

#### Health Check
```bash
# Check container health
docker-compose ps

# Manual health check
curl http://localhost:9200/_cluster/health

# Detailed health
curl http://localhost:9200/_cluster/health?pretty
```

#### Performance Monitoring
```bash
# Cluster stats
curl http://localhost:9200/_cluster/stats?pretty

# Node stats
curl http://localhost:9200/_nodes/stats?pretty

# Index stats
curl http://localhost:9200/_stats?pretty

# Cat API (human-readable)
curl http://localhost:9200/_cat/indices?v
curl http://localhost:9200/_cat/nodes?v
curl http://localhost:9200/_cat/health?v
```

#### Common Cat API Commands
```bash
# List all indices
curl http://localhost:9200/_cat/indices?v

# List all nodes
curl http://localhost:9200/_cat/nodes?v

# Cluster health
curl http://localhost:9200/_cat/health?v

# List all shards
curl http://localhost:9200/_cat/shards?v

# List all aliases
curl http://localhost:9200/_cat/aliases?v
```

---

## Advanced Topics

### Custom Elasticsearch Configuration

#### Method 1: Environment Variables
Add to `.env`:
```env
ELASTICSEARCH_CLUSTER_NAME=my-cluster
ELASTICSEARCH_NODE_NAME=node-1
```

#### Method 2: Custom elasticsearch.yml
1. Create `elasticsearch.yml` in elasticsearch directory
2. Add custom settings:
   ```yaml
   cluster.name: my-cluster
   node.name: node-1
   network.host: 0.0.0.0
   discovery.type: single-node
   ```

3. Mount in `docker-compose.yml`:
   ```yaml
   volumes:
     - ./elasticsearch.yml:/usr/share/elasticsearch/config/elasticsearch.yml:ro
   ```

### Performance Tuning

#### Memory Settings
```env
# For 8GB system
ES_JAVA_OPTS=-Xms2g -Xmx2g

# For 16GB system
ES_JAVA_OPTS=-Xms4g -Xmx4g
```

#### Index Settings
```json
{
  "settings": {
    "number_of_shards": 1,
    "number_of_replicas": 0,
    "refresh_interval": "30s"
  }
}
```

#### Thread Pool Settings
Configure in `elasticsearch.yml`:
```yaml
thread_pool:
  write:
    size: 4
    queue_size: 200
  search:
    size: 4
    queue_size: 1000
```

### Network Configuration

#### Default Network
The service uses a bridge network `elasticsearch_network` for isolation.

#### Connect Other Services
```yaml
# In another docker-compose.yml
services:
  app:
    networks:
      - elasticsearch_network

networks:
  elasticsearch_network:
    external: true
```

#### Custom Network
```yaml
networks:
  elasticsearch_network:
    driver: bridge
    ipam:
      config:
        - subnet: 172.22.0.0/16
```

### Multi-Node Cluster Setup

#### Example: 3-Node Cluster
```yaml
version: '3.8'

services:
  elasticsearch-1:
    image: docker.elastic.co/elasticsearch/elasticsearch:latest
    environment:
      - discovery.seed_hosts=elasticsearch-2,elasticsearch-3
      - cluster.initial_master_nodes=elasticsearch-1,elasticsearch-2,elasticsearch-3
      - node.name=elasticsearch-1
      - cluster.name=es-cluster
      - "ES_JAVA_OPTS=-Xms1g -Xmx1g"
    volumes:
      - es1_data:/usr/share/elasticsearch/data

  elasticsearch-2:
    image: docker.elastic.co/elasticsearch/elasticsearch:latest
    environment:
      - discovery.seed_hosts=elasticsearch-1,elasticsearch-3
      - cluster.initial_master_nodes=elasticsearch-1,elasticsearch-2,elasticsearch-3
      - node.name=elasticsearch-2
      - cluster.name=es-cluster
      - "ES_JAVA_OPTS=-Xms1g -Xmx1g"
    volumes:
      - es2_data:/usr/share/elasticsearch/data

  elasticsearch-3:
    image: docker.elastic.co/elasticsearch/elasticsearch:latest
    environment:
      - discovery.seed_hosts=elasticsearch-1,elasticsearch-2
      - cluster.initial_master_nodes=elasticsearch-1,elasticsearch-2,elasticsearch-3
      - node.name=elasticsearch-3
      - cluster.name=es-cluster
      - "ES_JAVA_OPTS=-Xms1g -Xmx1g"
    volumes:
      - es3_data:/usr/share/elasticsearch/data

volumes:
  es1_data:
  es2_data:
  es3_data:
```

### Production Deployment Considerations

#### Security Checklist
- [ ] Enable security features
- [ ] Set strong passwords
- [ ] Use SSL/TLS for transport
- [ ] Restrict network access
- [ ] Regular security updates
- [ ] Enable audit logging
- [ ] Configure firewall rules
- [ ] Use role-based access control (RBAC)

#### Resource Limits
Add to `docker-compose.yml`:
```yaml
services:
  elasticsearch:
    deploy:
      resources:
        limits:
          cpus: '4'
          memory: 4G
        reservations:
          cpus: '2'
          memory: 2G
```

#### High Availability
- Use multi-node cluster (minimum 3 nodes)
- Configure proper shard allocation
- Set up automated snapshots
- Monitor cluster health
- Plan for disaster recovery

#### Backup Strategy
- Daily automated snapshots
- Weekly full snapshots
- Monthly archive snapshots
- Test restore procedures regularly
- Keep snapshots in multiple locations

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
docker-compose logs elasticsearch

# Common causes:
# 1. Port already in use
#    Solution: Change ELASTICSEARCH_HTTP_PORT in .env

# 2. Insufficient memory
#    Solution: Increase Docker Desktop memory or reduce ES_JAVA_OPTS

# 3. Virtual memory limit (Linux)
#    Solution: Increase vm.max_map_count
sudo sysctl -w vm.max_map_count=262144

# 4. Volume permission issues
#    Solution: 
docker-compose down -v
docker-compose up -d
```

#### Issue: Can't connect to Elasticsearch

**Symptoms:**
- Connection refused errors
- Timeout errors

**Solutions:**
```bash
# 1. Verify container is running
docker-compose ps

# 2. Check port mapping
docker-compose port elasticsearch 9200

# 3. Test connection from container
docker-compose exec elasticsearch curl http://localhost:9200

# 4. Check if service is ready
docker-compose logs elasticsearch | grep "started"

# 5. Check firewall settings
```

#### Issue: Out of memory

**Symptoms:**
- Container killed
- "OutOfMemoryError" in logs

**Solutions:**
```bash
# 1. Check current memory usage
docker stats elasticsearch

# 2. Reduce heap size in .env
ES_JAVA_OPTS=-Xms256m -Xmx256m

# 3. Increase Docker Desktop memory allocation
# Docker Desktop > Settings > Resources > Memory

# 4. Restart service
docker-compose restart elasticsearch
```

#### Issue: Cluster status yellow or red

**Symptoms:**
- Health check shows yellow/red status
- Unassigned shards

**Solutions:**
```bash
# 1. Check cluster health
curl http://localhost:9200/_cluster/health?pretty

# 2. Check unassigned shards
curl http://localhost:9200/_cat/shards?v | grep UNASSIGNED

# 3. For single-node, reduce replicas
curl -X PUT "localhost:9200/_settings" -H 'Content-Type: application/json' -d'
{
  "index": {
    "number_of_replicas": 0
  }
}
'

# 4. Check disk space
docker system df
```

#### Issue: Slow performance

**Symptoms:**
- Slow query responses
- High CPU usage

**Solutions:**
```bash
# 1. Check cluster stats
curl http://localhost:9200/_cluster/stats?pretty

# 2. Check node stats
curl http://localhost:9200/_nodes/stats?pretty

# 3. Check thread pool stats
curl http://localhost:9200/_cat/thread_pool?v

# 4. Review index settings
curl http://localhost:9200/_settings?pretty

# 5. Check for large indices
curl http://localhost:9200/_cat/indices?v | sort -k9 -n
```

### Log Analysis

#### Understanding Log Messages

**Common log patterns:**
```
# Successful startup
started

# Cluster formed
cluster state updated

# Error messages
ERROR: ...
WARN: ...
FATAL: ...
```

#### Filtering Logs
```bash
# Errors only
docker-compose logs elasticsearch | grep -i error

# Warnings
docker-compose logs elasticsearch | grep -i warn

# Recent errors
docker-compose logs --since 1h elasticsearch | grep -i error

# Startup messages
docker-compose logs elasticsearch | grep -i "started\|cluster"
```

### Connection Problems

#### Problem: Connection refused

**Solution:**
```bash
# 1. Check if service is running
docker-compose ps

# 2. Check if port is exposed
docker-compose port elasticsearch 9200

# 3. Test from container
docker-compose exec elasticsearch curl http://localhost:9200

# 4. Check network
docker network inspect elasticsearch_elasticsearch_network
```

#### Problem: Authentication failed

**Solution:**
```bash
# 1. Check if security is enabled
curl http://localhost:9200

# 2. If security is enabled, use credentials
curl -u elastic:password http://localhost:9200

# 3. Reset passwords
docker-compose exec elasticsearch elasticsearch-setup-passwords interactive
```

### Data Recovery Procedures

#### Recover from Snapshot
```bash
# 1. List snapshots
curl http://localhost:9200/_snapshot/my_backup/_all?pretty

# 2. Restore snapshot
curl -X POST "localhost:9200/_snapshot/my_backup/snapshot_1/_restore?pretty"
```

#### Recover from Volume Backup
```bash
# 1. Stop service
docker-compose down

# 2. Remove corrupted volume
docker volume rm elasticsearch_elasticsearch_data

# 3. Start service (creates new volume)
docker-compose up -d

# 4. Wait for startup
sleep 30

# 5. Restore backup
docker run --rm -v elasticsearch_elasticsearch_data:/data -v $(pwd):/backup \
  alpine sh -c "cd /data && tar xzf /backup/elasticsearch_backup.tar.gz"

# 6. Restart service
docker-compose restart elasticsearch
```

---

## Examples

### Sample Connection Strings

#### Python (elasticsearch-py)
```python
from elasticsearch import Elasticsearch

# Without authentication
es = Elasticsearch(['http://localhost:9200'])

# With authentication
es = Elasticsearch(
    ['http://localhost:9200'],
    http_auth=('elastic', 'password')
)

# Test connection
print(es.info())
```

#### Node.js (@elastic/elasticsearch)
```javascript
const { Client } = require('@elastic/elasticsearch');

// Without authentication
const client = new Client({
  node: 'http://localhost:9200'
});

// With authentication
const client = new Client({
  node: 'http://localhost:9200',
  auth: {
    username: 'elastic',
    password: 'password'
  }
});

// Test connection
client.info().then(console.log);
```

#### Java (Elasticsearch Java Client)
```java
import co.elastic.clients.elasticsearch.ElasticsearchClient;
import co.elastic.clients.json.jackson.JacksonJsonpMapper;
import co.elastic.clients.transport.ElasticsearchTransport;
import co.elastic.clients.transport.rest_client.RestClientTransport;
import org.apache.http.HttpHost;
import org.elasticsearch.client.RestClient;

RestClient restClient = RestClient.builder(
    new HttpHost("localhost", 9200)).build();

ElasticsearchTransport transport = new RestClientTransport(
    restClient, new JacksonJsonpMapper());

ElasticsearchClient client = new ElasticsearchClient(transport);
```

#### Ruby (elasticsearch-ruby)
```ruby
require 'elasticsearch'

# Without authentication
client = Elasticsearch::Client.new(
  url: 'http://localhost:9200'
)

# With authentication
client = Elasticsearch::Client.new(
  url: 'http://localhost:9200',
  user: 'elastic',
  password: 'password'
)

# Test connection
puts client.info
```

#### Go (olivere/elastic)
```go
import "github.com/olivere/elastic/v7"

// Without authentication
client, err := elastic.NewClient(
    elastic.SetURL("http://localhost:9200"),
)

// With authentication
client, err := elastic.NewClient(
    elastic.SetURL("http://localhost:9200"),
    elastic.SetBasicAuth("elastic", "password"),
)
```

### Common Use Cases

#### Full-Text Search
```python
from elasticsearch import Elasticsearch

es = Elasticsearch(['http://localhost:9200'])

# Index a document
es.index(
    index='articles',
    body={
        'title': 'Elasticsearch Guide',
        'content': 'This is a comprehensive guide to Elasticsearch',
        'tags': ['search', 'database', 'tutorial']
    }
)

# Search
result = es.search(
    index='articles',
    body={
        'query': {
            'match': {
                'content': 'guide'
            }
        }
    }
)
```

#### Log Aggregation
```python
# Index log entry
es.index(
    index='logs-2024-01-01',
    body={
        'timestamp': '2024-01-01T10:00:00',
        'level': 'ERROR',
        'message': 'Database connection failed',
        'service': 'api'
    }
)

# Aggregate by level
result = es.search(
    index='logs-*',
    body={
        'aggs': {
            'by_level': {
                'terms': {
                    'field': 'level'
                }
            }
        }
    }
)
```

#### Analytics Dashboard
```python
# Get statistics
result = es.search(
    index='events',
    body={
        'size': 0,
        'aggs': {
            'events_over_time': {
                'date_histogram': {
                    'field': 'timestamp',
                    'calendar_interval': 'day'
                }
            },
            'top_users': {
                'terms': {
                    'field': 'user_id',
                    'size': 10
                }
            }
        }
    }
)
```

### Production Setup Example

```yaml
# docker-compose.yml
version: '3.8'

services:
  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:latest
    environment:
      - discovery.type=single-node
      - xpack.security.enabled=true
      - "ES_JAVA_OPTS=-Xms4g -Xmx4g"
      - bootstrap.memory_lock=true
    ulimits:
      memlock:
        soft: -1
        hard: -1
    ports:
      - "9200:9200"
    volumes:
      - elasticsearch_data:/usr/share/elasticsearch/data
    restart: always
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"
    deploy:
      resources:
        limits:
          cpus: '4'
          memory: 4G
```

---

## Additional Resources

- [Elasticsearch Official Documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html)
- [Elasticsearch Docker Image](https://www.docker.elastic.co/r/elasticsearch)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Elasticsearch Best Practices](https://www.elastic.co/guide/en/elasticsearch/reference/current/tune-for-search-speed.html)

---

**Last Updated**: 2024
**Elasticsearch Version**: Latest
**Docker Compose Version**: 3.8

