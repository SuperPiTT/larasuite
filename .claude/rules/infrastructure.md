---
paths: "docker-compose*.yml, Dockerfile*, docker/**/*"
description: Docker and infrastructure patterns
---
# Infrastructure Patterns

## Docker Services

```yaml
services:
  php:        # PHP 8.4-FPM
  nginx:      # Web server
  postgres:   # Database (per-tenant schemas)
  redis:      # Cache, sessions, queues
  horizon:    # Queue worker
  soketi:     # WebSockets
  minio:      # S3-compatible storage (dev)
  mailpit:    # Email testing (dev)
```

## Dockerfile Best Practices

```dockerfile
# Multi-stage build
FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci && npm run build

FROM php:8.4-fpm-alpine AS production
# Non-root user
RUN adduser -D -u 1000 larasuite
USER larasuite

# Health check
HEALTHCHECK --interval=30s CMD php-fpm-healthcheck || exit 1
```

## Docker Compose

```yaml
services:
  php:
    depends_on:
      postgres:
        condition: service_healthy
    deploy:
      resources:
        limits:
          memory: 1G

  postgres:
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME}"]
      interval: 10s
```

## Security

- Non-root containers
- Health checks on all services
- Internal networks for databases
- Secrets via Docker secrets (not env vars in prod)
- Read-only filesystems where possible

## PHP Production Config

```ini
display_errors = Off
opcache.enable = On
opcache.validate_timestamps = Off
expose_php = Off
```

## PostgreSQL

- Connection pooling with PgBouncer
- Indexes on foreign keys and filtered columns
- VACUUM ANALYZE regularly
- Point-in-time recovery enabled

## Redis

- Separate databases: cache (1), sessions (2), queues (3)
- `maxmemory-policy allkeys-lru`
- AOF persistence enabled
