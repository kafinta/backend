# Environment Setup Guide

## Required Environment Variables

### Application Configuration
```env
APP_NAME=Kafinta
APP_ENV=local
APP_KEY=base64:your-app-key-here
APP_DEBUG=true
APP_URL=http://localhost:8000
```

### Database Configuration
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kafinta
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Email Configuration (Brevo SMTP)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=your_brevo_smtp_username
MAIL_PASSWORD=your_brevo_smtp_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your_verified_sender@domain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Queue Configuration
```env
QUEUE_CONNECTION=database
```

### Session Configuration
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
```

### Cache Configuration
```env
CACHE_DRIVER=file
```

### Frontend URLs (CORS)
```env
FRONTEND_URL=http://localhost:3000
ADMIN_URL=http://localhost:3001
```

## Development Setup

### 1. Clone and Install
```bash
git clone <repository-url>
cd kafinta
composer install
```

### 2. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 4. Storage Setup
```bash
php artisan storage:link
```

### 5. Queue Setup (Development)
```bash
# Run queue worker manually
php artisan queue:work

# Or use the email-specific processor
php artisan email:process-queue
```

## Production Setup

### 1. Environment Variables
Update `.env` with production values:
- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Use production database credentials
- Use production email credentials
- Set proper frontend URLs

### 2. Optimization
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 3. Queue Workers (Production)
Use Supervisor to keep queue workers running:

```ini
[program:kafinta-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/kafinta/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/kafinta/storage/logs/worker.log
stopwaitsecs=3600
```

### 4. File Permissions
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Security Considerations

### 1. Environment File Security
- Never commit `.env` to version control
- Use strong, unique passwords
- Rotate API keys regularly

### 2. Database Security
- Use dedicated database user with minimal permissions
- Enable SSL for database connections in production
- Regular backups

### 3. Email Security
- Use verified sender domains
- Monitor email sending limits
- Implement rate limiting

## Monitoring

### 1. Logs
Monitor these log files:
- `storage/logs/laravel.log` - Application logs
- `storage/logs/worker.log` - Queue worker logs

### 2. Queue Monitoring
```bash
# Check queue status
php artisan queue:monitor

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### 3. Health Checks
Create health check endpoints for:
- Database connectivity
- Email service status
- Queue worker status
- Storage accessibility

## Troubleshooting

### Common Issues

#### 1. Email Not Sending
- Check Brevo credentials
- Verify sender email is authenticated
- Check queue worker is running
- Review email logs

#### 2. Images Not Loading
- Verify storage link exists: `php artisan storage:link`
- Check file permissions
- Ensure seeder images are copied

#### 3. Queue Jobs Failing
- Check database connection
- Verify queue worker is running
- Review failed jobs table
- Check worker logs

#### 4. CORS Issues
- Verify frontend URLs in CORS configuration
- Check request headers
- Ensure credentials are included in requests

### Debug Commands
```bash
# Clear all caches
php artisan optimize:clear

# Check configuration
php artisan config:show

# Test email sending
php artisan tinker
>>> Mail::raw('Test', function($m) { $m->to('test@example.com')->subject('Test'); });

# Check queue status
php artisan queue:work --once

# View routes
php artisan route:list
```

## Performance Optimization

### 1. Caching
- Enable OPcache in production
- Use Redis for sessions and cache in production
- Implement query caching where appropriate

### 2. Database
- Add indexes for frequently queried columns
- Optimize database queries
- Use database connection pooling

### 3. Assets
- Minify CSS/JS assets
- Use CDN for static assets
- Implement proper caching headers

## Backup Strategy

### 1. Database Backups
```bash
# Daily database backup
mysqldump -u username -p kafinta > backup_$(date +%Y%m%d).sql
```

### 2. File Backups
- Backup `storage/app` directory
- Backup uploaded images
- Backup configuration files

### 3. Code Backups
- Use git for version control
- Tag releases
- Maintain staging environment
