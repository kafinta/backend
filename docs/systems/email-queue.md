# Email Queue System

## Overview

The email system now uses Laravel's queue system to ensure reliable email delivery. Emails are queued for processing and will be retried automatically if they fail.

## Configuration

### Environment Variables

```env
QUEUE_CONNECTION=database
```

### Queue Priorities

- **High Priority Queue (`high`)**: Critical emails that need immediate processing
  - Email verification
  - Password reset
  - Order confirmations

- **Default Queue (`default`)**: Standard emails
  - Welcome emails
  - Notifications
  - Seller application status updates

## Queue Processing

### Manual Processing (Development)

```bash
# Process all queues
php artisan queue:work

# Process specific queue
php artisan queue:work --queue=high

# Process with custom timeout
php artisan queue:work --timeout=120

# Custom email queue processor
php artisan email:process-queue --timeout=60
```

### Production Setup

For production, use a process manager like Supervisor to keep queue workers running:

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

## Email Job Features

### Retry Logic
- **3 automatic retries** with exponential backoff
- Retry delays: 30s → 60s → 120s
- Failed jobs are logged for manual review

### Duplicate Prevention
- Uses `WithoutOverlapping` middleware
- Prevents duplicate emails to same recipient for same email type

### Timeout Protection
- 60-second timeout per email job
- Prevents hanging jobs from blocking the queue

### Comprehensive Logging
- Success and failure logging
- Attempt tracking
- Error details for debugging

## Queue Management Commands

```bash
# View queued jobs
php artisan queue:monitor

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush

# Restart queue workers (after code changes)
php artisan queue:restart
```

## Monitoring

### Queue Status
Check the `jobs` table in your database to see queued emails:

```sql
SELECT * FROM jobs ORDER BY created_at DESC;
```

### Failed Jobs
Check the `failed_jobs` table for permanently failed emails:

```sql
SELECT * FROM failed_jobs ORDER BY failed_at DESC;
```

### Logs
Monitor email processing in Laravel logs:

```bash
tail -f storage/logs/laravel.log | grep -E "(Email|Queue)"
```

## Testing

### Test Queue System

Visit: `http://localhost:8000/test-queue`

This will queue a test welcome email. Then process it with:

```bash
php artisan email:process-queue
```

### Verify Email Delivery

1. Queue an email using the test route
2. Check the `jobs` table to confirm it's queued
3. Process the queue manually
4. Check logs for success/failure
5. Verify email delivery

## Fallback Behavior

If queue system is disabled (`QUEUE_CONNECTION=sync`), emails will be sent immediately without queuing.

## Production Recommendations

1. **Use Redis** for better queue performance:
   ```env
   QUEUE_CONNECTION=redis
   ```

2. **Monitor queue workers** with Supervisor or similar

3. **Set up alerts** for failed jobs

4. **Regular cleanup** of old failed jobs

5. **Scale workers** based on email volume

## Troubleshooting

### Queue Not Processing
- Check if queue worker is running
- Verify database connection
- Check Laravel logs for errors

### Emails Not Sending
- Verify Brevo SMTP configuration
- Check failed jobs table
- Review email logs

### High Queue Backlog
- Increase number of queue workers
- Check for failing jobs causing retries
- Monitor server resources
