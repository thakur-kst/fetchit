# Nginx Service

Nginx reverse proxy and web server for the Channel Engine API.

## Overview

This service provides:
- Reverse proxy for PHP-FPM (Laravel application)
- Web server for static assets
- Proxy for Keycloak authentication endpoints
- WebSocket proxy for Soketi (Pusher protocol)

## Usage

### Start the service

```bash
cd docker/nginx
docker compose up -d
```

### Stop the service

```bash
cd docker/nginx
docker compose down
```

### View logs

```bash
docker logs channel-engine-nginx
docker logs -f channel-engine-nginx  # Follow logs
```

## Configuration

- **Configuration files**: `conf.d/default.conf`
- **Backend code**: Mounted from `../../backend` (read-only)
- **Ports**: 
  - `80` - HTTP
  - `443` - HTTPS (if configured)

## Networks

The nginx service connects to the following external networks:
- `channel-engine` - Main application network (connects to app service)
- `soketi_network` - WebSocket service network
- `keycloak_network` - Authentication service network

## Dependencies

The nginx service requires:
- The `app` service (PHP-FPM) to be running on the `channel-engine` network
- The `channel-engine` network to be created (usually by the main docker-compose.yml)

## Health Check

The service includes a health check that verifies the API endpoint:
- Default URL: `http://localhost/api/v1/health`
- Customizable via `NGINX_HEALTHCHECK_URL` environment variable

## Volumes

- `nginx_cache` - Nginx cache storage
- `nginx_logs` - Nginx log files

