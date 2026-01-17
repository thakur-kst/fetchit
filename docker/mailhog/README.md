# MailHog Docker Service

A complete Docker setup for running MailHog, an email testing tool for development.

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

3. **Access MailHog Web UI**
   - Open your browser and navigate to: `http://localhost:8025`

4. **Configure your application**
   - SMTP Host: `localhost` (or `mailhog` from Docker network)
   - SMTP Port: `1025`
   - No authentication required

## What's Included

- **Dockerfile** - MailHog container configuration
- **docker-compose.yml** - Service orchestration with networking
- **.env** - Environment variables for configuration
- **DOCUMENTATION.md** - Comprehensive setup and usage guide

## Features

- ✅ MailHog Latest
- ✅ Web UI for viewing emails
- ✅ SMTP server for testing
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
docker-compose logs -f mailhog

# Restart the service
docker-compose restart mailhog
```

## Configuration

Edit the `.env` file to customize:
- Web UI port (default: 8025)
- SMTP port (default: 1025)

## Documentation

For detailed information, see [DOCUMENTATION.md](DOCUMENTATION.md) which includes:
- Complete setup instructions
- Configuration options
- Advanced usage
- Troubleshooting guide
- Examples and best practices

## Support

For issues or questions, refer to the troubleshooting section in DOCUMENTATION.md.

