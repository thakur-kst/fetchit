# pgAdmin Dockerfile
# Based on official pgAdmin image
FROM dpage/pgadmin4:latest

# Set environment variables (can be overridden by docker-compose)
ENV PGADMIN_DEFAULT_EMAIL=admin@admin.com
ENV PGADMIN_DEFAULT_PASSWORD=admin
ENV PGADMIN_CONFIG_SERVER_MODE=False
ENV PGADMIN_CONFIG_MASTER_PASSWORD_REQUIRED=False

# Expose pgAdmin port
EXPOSE 80

