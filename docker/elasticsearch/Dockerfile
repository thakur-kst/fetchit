# Elasticsearch Dockerfile
# Based on official Elasticsearch image
FROM elasticsearch:8.11.0

# Set environment variables (can be overridden by docker-compose)
ENV discovery.type=single-node
ENV xpack.security.enabled=false
ENV xpack.security.enrollment.enabled=false

# Expose Elasticsearch ports
EXPOSE 9200 9300

# Create data directory
RUN mkdir -p /usr/share/elasticsearch/data && \
    chown -R elasticsearch:elasticsearch /usr/share/elasticsearch/data

