# pgAdmin Docker Service

A complete Docker setup for running pgAdmin 4, a web-based administration tool for PostgreSQL.

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

3. **Access pgAdmin**
   - Open your browser and navigate to: `http://localhost:5050`
   - Login with credentials from `.env` file

4. **Connect to PostgreSQL**
   - In pgAdmin, right-click "Servers" → "Register" → "Server"
   - Use your PostgreSQL connection details

## What's Included

- **Dockerfile** - pgAdmin container configuration
- **docker-compose.yml** - Service orchestration with volumes and networking
- **.env** - Environment variables for configuration
- **DOCUMENTATION.md** - Comprehensive setup and usage guide

## Features

- ✅ pgAdmin 4 Latest
- ✅ Persistent data storage via Docker volumes
- ✅ Web-based GUI for PostgreSQL administration
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
docker-compose logs -f pgadmin

# Stop and remove volumes (WARNING: deletes data)
docker-compose down -v

# Restart the service
docker-compose restart pgadmin
```

## Configuration

Edit the `.env` file to customize:
- Default email and password
- Port mapping
- Server mode settings
- Additional pgAdmin settings

## Documentation

For detailed information, see [DOCUMENTATION.md](DOCUMENTATION.md) which includes:
- Complete setup instructions
- Configuration options
- Advanced usage
- Troubleshooting guide
- Examples and best practices

## Support

For issues or questions, refer to the troubleshooting section in DOCUMENTATION.md.

