# Redis Docker Service

A complete Docker setup for running Redis, an in-memory data structure store.

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

4. **Connect to Redis**
   ```bash
   # Using redis-cli from the container
   docker-compose exec redis redis-cli
   
   # Or using a local redis-cli client
   redis-cli -h localhost -p 6379
   
   # If password is set
   redis-cli -h localhost -p 6379 -a your_password
   ```

## What's Included

- **Dockerfile** - Redis container configuration
- **docker-compose.yml** - Service orchestration with volumes and networking
- **.env** - Environment variables for configuration
- **redis.conf** - Redis configuration file
- **DOCUMENTATION.md** - Comprehensive setup and usage guide

## Features

- ✅ Redis Latest
- ✅ Persistent data storage via Docker volumes
- ✅ Configurable password authentication
- ✅ Health checks configured
- ✅ Environment-based configuration
- ✅ Network isolation
- ✅ Custom Redis configuration file

## Basic Commands

```bash
# Start the service
docker-compose up -d

# Stop the service
docker-compose down

# View logs
docker-compose logs -f redis

# Stop and remove volumes (WARNING: deletes data)
docker-compose down -v

# Restart the service
docker-compose restart redis
```

## Configuration

Edit the `.env` file to customize:
- Redis password
- Port mapping
- Memory limits
- Persistence settings

Edit `redis.conf` for advanced Redis configuration options.

## Documentation

For detailed information, see [DOCUMENTATION.md](DOCUMENTATION.md) which includes:
- Complete setup instructions
- Configuration options
- Advanced usage
- Troubleshooting guide
- Examples and best practices

## Support

For issues or questions, refer to the troubleshooting section in DOCUMENTATION.md.

