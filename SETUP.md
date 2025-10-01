# SEO Optimizer Plugin - Development Setup

## Quick Start

### 1. Start Local WordPress
```bash
docker-compose up -d
```

### 2. Access WordPress
- **WordPress Site**: http://localhost:8080
- **Admin Panel**: http://localhost:8080/wp-admin
  - Username: `admin`
  - Password: `admin` (set during initial setup)
- **phpMyAdmin**: http://localhost:8081

### 3. Activate Plugin
1. Go to http://localhost:8080/wp-admin/plugins.php
2. Find "SEO Optimizer"
3. Click "Activate"

### 4. Development Workflow
1. Edit files in Cursor
2. Save (changes appear instantly in browser)
3. Refresh browser to test
4. When working, commit to Git

### 5. Stop WordPress
```bash
docker-compose down
```

## Commands

```bash
# Start WordPress
docker-compose up -d

# View logs
docker-compose logs -f wordpress

# Stop WordPress
docker-compose down

# Stop and remove all data (fresh start)
docker-compose down -v

# Restart WordPress
docker-compose restart
```

## Troubleshooting

### Port already in use
```bash
# Change port in docker-compose.yml
ports:
  - "8888:80"  # Change 8080 to 8888
```

### Plugin not showing
```bash
# Check if plugin is mounted
docker-compose exec wordpress ls -la /var/www/html/wp-content/plugins/
```

### Database connection error
```bash
# Restart everything
docker-compose down
docker-compose up -d
```
