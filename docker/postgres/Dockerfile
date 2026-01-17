# PostgreSQL Dockerfile
# Based on official PostgreSQL image
FROM postgres:latest

# Set environment variables (can be overridden by docker-compose)
ENV POSTGRES_DB=postgres
ENV POSTGRES_USER=postgres
ENV POSTGRES_PASSWORD=postgres

# Expose PostgreSQL port
EXPOSE 5432

# The official postgres image automatically runs any scripts in /docker-entrypoint-initdb.d/
# These scripts are mounted via docker-compose from the init-scripts folder

