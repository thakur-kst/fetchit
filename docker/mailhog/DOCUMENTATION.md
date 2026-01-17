# MailHog Docker Service - Complete Documentation

This document provides comprehensive information about setting up, configuring, and using the MailHog Docker service.

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

Before setting up the MailHog Docker service, ensure you have the following installed:

- **Docker**: Version 20.10 or higher
  - Check installation: `docker --version`
  - Install: [Docker Installation Guide](https://docs.docker.com/get-docker/)

- **Docker Compose**: Version 2.0 or higher
  - Check installation: `docker-compose --version`
  - Install: [Docker Compose Installation Guide](https://docs.docker.com/compose/install/)

### Step-by-Step Installation

1. **Navigate to the mailhog directory**
   ```bash
   cd mailhog
   ```

2. **Review and configure environment variables**
   ```bash
   # Open the .env file in your preferred editor
   nano .env
   # or
   vim .env
   ```

3. **Configure ports** (if needed)
   ```bash
   # Default ports are usually fine
   MAILHOG_WEB_PORT=8025
   MAILHOG_SMTP_PORT=1025
   ```

4. **Build and start the service**
   ```bash
   docker-compose up -d
   ```

5. **Verify the service is running**
   ```bash
   docker-compose ps
   # Should show mailhog service as "Up"
   ```

6. **Access MailHog Web UI**
   - Open browser: `http://localhost:8025` (or port from `.env`)

---

## Configuration

### Environment Variables

The `.env` file contains all configuration variables. Here's a complete reference:

#### Optional Variables

| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `MAILHOG_WEB_PORT` | Web UI port | `8025` | `8026` |
| `MAILHOG_SMTP_PORT` | SMTP server port | `1025` | `1026` |

### Port Configuration and Customization

#### Change Default Ports

Edit `.env`:
```env
MAILHOG_WEB_PORT=8026
MAILHOG_SMTP_PORT=1026
```

Then restart:
```bash
docker-compose down
docker-compose up -d
```

#### Multiple MailHog Instances

To run multiple instances, create separate directories with different port mappings:
- Instance 1: `MAILHOG_WEB_PORT=8025`, `MAILHOG_SMTP_PORT=1025`
- Instance 2: `MAILHOG_WEB_PORT=8026`, `MAILHOG_SMTP_PORT=1026`

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
# Stop containers
docker-compose stop

# Stop and remove containers
docker-compose down
```

#### Restart Service
```bash
# Restart service
docker-compose restart mailhog

# Restart with rebuild
docker-compose up -d --build
```

### Accessing MailHog Web UI

#### Basic Access
1. Open web browser
2. Navigate to: `http://localhost:8025` (or port from `.env`)
3. View all captured emails in the web interface

#### Features
- View email headers
- View email body (HTML and plain text)
- Download email attachments
- Search emails
- Delete emails

### Configuring Your Application

#### SMTP Configuration

Use these settings in your application:

- **SMTP Host**: `localhost` (from host) or `mailhog` (from Docker network)
- **SMTP Port**: `1025` (or value from `.env`)
- **Username**: (not required)
- **Password**: (not required)
- **Encryption**: None (plain text)

#### Connection Examples

**From Host Machine:**
```
SMTP Host: localhost
SMTP Port: 1025
```

**From Docker Container (same network):**
```
SMTP Host: mailhog
SMTP Port: 1025
```

### Logging and Monitoring

#### View Logs
```bash
# View all logs
docker-compose logs mailhog

# Follow logs (real-time)
docker-compose logs -f mailhog

# View last 100 lines
docker-compose logs --tail=100 mailhog

# View logs with timestamps
docker-compose logs -t mailhog
```

#### Health Check
```bash
# Check container health
docker-compose ps

# Manual health check
curl http://localhost:8025
```

---

## Advanced Topics

### Network Configuration

#### Default Network
The service uses a bridge network `mailhog_network` for isolation.

#### Connect Other Services
```yaml
# In another docker-compose.yml
services:
  app:
    networks:
      - mailhog_network

networks:
  mailhog_network:
    external: true
```

### Production Deployment Considerations

**Note**: MailHog is designed for development and testing only. Do not use in production.

For production, use:
- AWS SES
- SendGrid
- Mailgun
- Postmark
- Other production email services

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
docker-compose logs mailhog

# Common causes:
# 1. Port already in use
#    Solution: Change MAILHOG_WEB_PORT or MAILHOG_SMTP_PORT in .env

# 2. Invalid configuration
#    Solution: Check .env file syntax
```

#### Issue: Can't access web UI

**Symptoms:**
- Connection refused errors
- Timeout errors

**Solutions:**
```bash
# 1. Verify container is running
docker-compose ps

# 2. Check port mapping
docker-compose port mailhog 8025

# 3. Test connection
curl http://localhost:8025

# 4. Check firewall settings
```

#### Issue: Emails not being captured

**Symptoms:**
- Emails sent but not appearing in MailHog

**Solutions:**
```bash
# 1. Verify SMTP configuration in your application
#    Host: localhost (or mailhog)
#    Port: 1025

# 2. Check if MailHog is receiving connections
docker-compose logs mailhog

# 3. Test SMTP connection
telnet localhost 1025
# or
nc -zv localhost 1025
```

---

## Examples

### Sample Configuration

#### Laravel (.env)
```env
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### Node.js (nodemailer)
```javascript
const nodemailer = require('nodemailer');

const transporter = nodemailer.createTransport({
  host: 'localhost',
  port: 1025,
  secure: false, // true for 465, false for other ports
  auth: {
    user: null,
    pass: null
  }
});
```

#### Python (smtplib)
```python
import smtplib
from email.mime.text import MIMEText

smtp = smtplib.SMTP('localhost', 1025)
msg = MIMEText('Test email')
msg['Subject'] = 'Test'
msg['From'] = 'test@example.com'
msg['To'] = 'recipient@example.com'
smtp.send_message(msg)
smtp.quit()
```

#### PHP
```php
$transport = (new Swift_SmtpTransport('localhost', 1025))
    ->setUsername(null)
    ->setPassword(null);
```

### Integration with Docker Compose

#### Example: MailHog + Application
```yaml
version: '3.8'

services:
  app:
    image: your-app:latest
    environment:
      MAIL_HOST: mailhog
      MAIL_PORT: 1025
    networks:
      - app_network

  mailhog:
    image: mailhog/mailhog:latest
    ports:
      - "8025:8025"
      - "1025:1025"
    networks:
      - app_network

networks:
  app_network:
    driver: bridge
```

---

## Additional Resources

- [MailHog GitHub Repository](https://github.com/mailhog/MailHog)
- [MailHog Docker Image](https://hub.docker.com/r/mailhog/mailhog)
- [Docker Compose Documentation](https://docs.docker.com/compose/)

---

**Last Updated**: 2024
**MailHog Version**: Latest
**Docker Compose Version**: 3.8

