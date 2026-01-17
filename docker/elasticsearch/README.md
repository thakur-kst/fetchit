# Elasticsearch Docker Service

A complete Docker setup for running Elasticsearch, a distributed search and analytics engine.

## Quick Start

1. **Configure your environment**
   ```bash
   # Edit the .env file with your desired configuration
   nano .env
   ```

2. **Start the service**
   ```bash
   docker-compose up -d
   ```

3. **Verify it's running**
   ```bash
   docker-compose ps
   ```

4. **Test Elasticsearch**
   ```bash
   # Check cluster health
   curl http://localhost:9200/_cluster/health
   
   # Get cluster info
   curl http://localhost:9200
   ```

## What's Included

- **Dockerfile** - Elasticsearch container configuration
- **docker-compose.yml** - Service orchestration with volumes and networking
- **.env** - Environment variables for configuration
- **DOCUMENTATION.md** - Comprehensive setup and usage guide

## Features

- ✅ Elasticsearch Latest
- ✅ Persistent data storage via Docker volumes
- ✅ Single-node configuration (development)
- ✅ Health checks configured
- ✅ Environment-based configuration
- ✅ Network isolation
- ✅ Memory limits configured

## Basic Commands

```bash
# Start the service
docker-compose up -d

# Stop the service
docker-compose down

# View logs
docker-compose logs -f elasticsearch

# Stop and remove volumes (WARNING: deletes data)
docker-compose down -v

# Restart the service
docker-compose restart elasticsearch
```

## Configuration

Edit the `.env` file to customize:
- Java heap size (memory allocation)
- Discovery type (single-node or cluster)
- Security settings
- Port mappings

## Documentation

For detailed information, see [DOCUMENTATION.md](DOCUMENTATION.md) which includes:
- Complete setup instructions
- Configuration options
- Advanced usage
- Troubleshooting guide
- Examples and best practices

## Support

For issues or questions, refer to the troubleshooting section in DOCUMENTATION.md.

