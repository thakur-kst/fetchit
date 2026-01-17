# PostgreSQL Docker Service

A complete Docker setup for running PostgreSQL in a containerized environment.

## Quick Start

1. **Configure your environment**
   ```bash
   # Edit the .env file with your desired credentials
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

4. **Connect to the database**
   ```bash
   # Using psql from the container
   docker-compose exec postgres psql -U postgres -d postgres
   
   # Or using a local psql client
   psql -h localhost -p 5432 -U postgres -d postgres
   ```

## What's Included

- **Dockerfile** - PostgreSQL container configuration
- **docker-compose.yml** - Service orchestration with volumes and networking
- **.env** - Environment variables for configuration
- **init-scripts/** - Folder for SQL initialization scripts
- **DOCUMENTATION.md** - Comprehensive setup and usage guide

## Features

- ✅ PostgreSQL Latest (16)
- ✅ Persistent data storage via Docker volumes
- ✅ Automatic initialization scripts execution
- ✅ Health checks configured
- ✅ Environment-based configuration
- ✅ Network isolation

## Basic Commands

```bash
# Start the service
docker-compose up -d

# Stop the service
docker-compose down

# View logs
docker-compose logs -f postgres

# Stop and remove volumes (WARNING: deletes data)
docker-compose down -v

# Restart the service
docker-compose restart postgres
```

## Initialization Scripts

Place any `.sql`, `.sh`, or `.sql.gz` files in the `init-scripts/` folder. These will be executed automatically when the database is first initialized (only on first startup).

## Configuration

Edit the `.env` file to customize:
- Database name
- Username and password
- Port mapping
- Additional PostgreSQL settings

## Documentation

For detailed information, see [DOCUMENTATION.md](DOCUMENTATION.md) which includes:
- Complete setup instructions
- Configuration options
- Advanced usage
- Troubleshooting guide
- Examples and best practices

## Support

For issues or questions, refer to the troubleshooting section in DOCUMENTATION.md.

