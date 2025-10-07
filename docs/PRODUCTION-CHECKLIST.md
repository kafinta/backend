# Production Deployment Checklist

## 🔒 Security Configuration

### Environment Variables
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate new `APP_KEY` for production
- [ ] Use strong database passwords
- [ ] Configure production email credentials
- [ ] Set proper `APP_URL` for production domain

### Database Security
- [ ] Use dedicated database user with minimal permissions
- [ ] Enable SSL for database connections
- [ ] Regular database backups configured
- [ ] Database credentials secured

### Email Configuration
- [ ] Brevo SMTP credentials configured
- [ ] Sender email domain verified
- [ ] Email rate limits configured
- [ ] Queue system enabled (`QUEUE_CONNECTION=database`)

## 🚀 Performance Optimization

### Laravel Optimization
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Queue Workers
- [ ] Supervisor configured for queue workers
- [ ] Queue monitoring set up
- [ ] Failed job handling configured
- [ ] Worker restart mechanism in place

### File Permissions
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 📁 File System

### Storage Setup
- [ ] `php artisan storage:link` executed
- [ ] Seeder images copied: `php artisan seed:copy-images`
- [ ] File upload directories writable
- [ ] Backup strategy for uploaded files

### Database Setup
- [ ] Migrations run: `php artisan migrate`
- [ ] Seeders run: `php artisan db:seed`
- [ ] Database indexes optimized

## 🔧 System Configuration

### Web Server
- [ ] HTTPS enabled with valid SSL certificate
- [ ] Proper CORS headers configured
- [ ] Rate limiting configured
- [ ] Security headers set

### Monitoring
- [ ] Application logs monitored
- [ ] Queue worker logs monitored
- [ ] Email delivery monitoring
- [ ] Error tracking configured

## 🧪 Testing

### API Testing
- [ ] All authentication endpoints tested
- [ ] Product discovery endpoints tested
- [ ] Cart functionality tested
- [ ] Order placement tested
- [ ] Email delivery tested

### Security Testing
- [ ] Authentication flows tested
- [ ] Authorization checks verified
- [ ] Input validation tested
- [ ] Rate limiting tested

## 📧 Email System

### Brevo Configuration
- [ ] SMTP credentials verified
- [ ] Sender domain authenticated
- [ ] Email templates tested
- [ ] Queue system operational

### Email Types Verified
- [ ] Registration verification emails
- [ ] Password reset emails
- [ ] Welcome emails
- [ ] Order confirmation emails
- [ ] Notification emails

## 🔄 Backup & Recovery

### Database Backups
- [ ] Automated daily backups
- [ ] Backup restoration tested
- [ ] Off-site backup storage

### File Backups
- [ ] User uploaded files backed up
- [ ] Application files backed up
- [ ] Configuration files secured

## 📊 Monitoring & Alerts

### Application Monitoring
- [ ] Uptime monitoring
- [ ] Performance monitoring
- [ ] Error rate monitoring
- [ ] Queue length monitoring

### Alerts Configured
- [ ] Application errors
- [ ] Queue failures
- [ ] Email delivery failures
- [ ] High resource usage

## 🌐 Frontend Integration

### CORS Configuration
- [ ] Production frontend domains added
- [ ] Credentials support enabled
- [ ] Proper headers configured

### API Documentation
- [ ] Frontend team has access to API docs
- [ ] Environment-specific endpoints documented
- [ ] Authentication flow documented

## 🔍 Final Verification

### Functionality Tests
- [ ] User registration and verification
- [ ] Login and logout
- [ ] Password reset flow
- [ ] Product browsing and search
- [ ] Cart operations
- [ ] Order placement
- [ ] Email notifications

### Performance Tests
- [ ] API response times acceptable
- [ ] Database query performance
- [ ] File upload/download speeds
- [ ] Email delivery times

### Security Tests
- [ ] Authentication bypass attempts
- [ ] SQL injection protection
- [ ] XSS protection
- [ ] CSRF protection

## 📝 Documentation

### For Operations Team
- [ ] Deployment procedures documented
- [ ] Monitoring procedures documented
- [ ] Backup/restore procedures documented
- [ ] Troubleshooting guide available

### For Development Team
- [ ] API documentation updated
- [ ] Environment setup documented
- [ ] Testing procedures documented
- [ ] Code review checklist

## 🚨 Emergency Procedures

### Rollback Plan
- [ ] Previous version backup available
- [ ] Rollback procedure documented
- [ ] Database rollback plan
- [ ] DNS rollback plan

### Incident Response
- [ ] Contact information updated
- [ ] Escalation procedures defined
- [ ] Communication plan established
- [ ] Post-incident review process

## ✅ Sign-off

### Technical Review
- [ ] Code review completed
- [ ] Security review completed
- [ ] Performance review completed
- [ ] Documentation review completed

### Business Review
- [ ] Functionality requirements met
- [ ] User acceptance testing completed
- [ ] Business stakeholder approval
- [ ] Go-live approval obtained

---

## Quick Commands Reference

### Deployment Commands
```bash
# Optimize for production
php artisan optimize

# Clear all caches
php artisan optimize:clear

# Copy seeder images
php artisan seed:copy-images

# Process email queue
php artisan email:process-queue

# Monitor queue
php artisan queue:monitor
```

### Health Check Endpoints
- `GET /api/categories` - Basic API functionality
- `GET /api/user/profile` - Authentication (requires login)
- `GET /api/products` - Product discovery
- `GET /api/cart` - Cart functionality

### Log Files to Monitor
- `storage/logs/laravel.log` - Application logs
- `storage/logs/worker.log` - Queue worker logs
- Web server access/error logs
