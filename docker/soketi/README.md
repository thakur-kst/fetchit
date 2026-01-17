# Soketi Docker Service

A complete Docker setup for running Soketi, a Pusher-compatible WebSocket server for development.

## Quick Start

1. **Start the service**
   ```bash
   cd /Users/kritika/code/docker/soketi
   docker-compose up -d
   ```

2. **Access Soketi**
   - WebSocket Port: `6001`
   - The service will be available at `localhost:6001` or `soketi:6001` from Docker networks

3. **Configure your application**
   - Set `PUSHER_HOST=soketi` (or `localhost` from host)
   - Set `PUSHER_PORT=6001`
   - Configure `PUSHER_APP_ID`, `PUSHER_APP_KEY`, and `PUSHER_APP_SECRET` in your `.env` file

## What's Included

- **Dockerfile** - Soketi container configuration
- **docker-compose.yml** - Service orchestration with networking
- **README.md** - This file

## Features

- ✅ Soketi Latest (16-alpine)
- ✅ Pusher-compatible WebSocket server
- ✅ Health checks configured
- ✅ Environment-based configuration
- ✅ Network isolation
- ✅ Resource limits configured

## Basic Commands

```bash
# Start the service
docker-compose up -d

# Stop the service
docker-compose down

# View logs
docker-compose logs -f soketi

# Restart the service
docker-compose restart soketi
```

## Configuration

The service reads configuration from the main application's `.env` file:
- `PUSHER_APP_ID` - Application ID (default: `app-id`)
- `PUSHER_APP_KEY` - Application key (default: `app-key`)
- `PUSHER_APP_SECRET` - Application secret (default: `app-secret`)
- `SOKETI_PORT` - Port to expose (default: `6001`)
- `SOKETI_DEBUG` - Enable debug mode (default: `1`)

## Network

The service creates a `soketi_network` that can be used by other Docker services to connect to Soketi. The network name is `soketi_soketi_network` when used as an external network.

## Support

For issues or questions, refer to the [Soketi documentation](https://docs.soketi.app/).

