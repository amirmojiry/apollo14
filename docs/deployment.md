# Apollo14 Deployment Guide

## Overview

This guide covers deploying the Apollo14 application across different environments, from local development to production cloud deployment.

## Architecture Overview

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   React Frontend │    │  Laravel Backend │    │ Python Service  │
│   (Vercel/CDN)   │◄──►│   (AWS EC2)      │◄──►│  (AWS Lambda)   │
│                 │    │                 │    │                 │
│  - Static Files  │    │  - API Gateway   │    │ - Data Processing│
│  - PWA Support   │    │  - Database      │    │ - ML Models     │
│  - CDN Caching   │    │  - Redis Cache   │    │ - TEMPO Client  │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
                    ┌─────────────────┐
                    │   PostgreSQL    │
                    │   (AWS RDS)     │
                    │                 │
                    │ - User Data     │
                    │ - Submissions   │
                    │ - Air Quality   │
                    └─────────────────┘
```

## Prerequisites

### Development Environment
- Node.js 18+
- PHP 8.1+
- Python 3.9+
- PostgreSQL 13+
- Redis 6+
- Docker (optional)

### Production Environment
- Cloud provider account (AWS, Google Cloud, Azure)
- Domain name and SSL certificate
- API keys for external services

## Local Development Setup

### 1. Clone Repository
```bash
git clone https://github.com/apollo14/nasa-space-apps-2025.git
cd nasa-space-apps-2025
```

### 2. Backend Setup (Laravel)
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### 3. Frontend Setup (React)
```bash
cd frontend
npm install
npm start
```

### 4. Python Service Setup
```bash
cd python-service
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install -r requirements.txt
python main.py
```

### 5. Database Setup
```bash
# Create PostgreSQL database
createdb apollo14_air_quality

# Run migrations
cd backend
php artisan migrate
```

## Docker Development Environment

### Docker Compose Configuration
```yaml
version: '3.8'

services:
  frontend:
    build: ./frontend
    ports:
      - "3000:3000"
    environment:
      - REACT_APP_API_URL=http://localhost:8000/api
    volumes:
      - ./frontend:/app
      - /app/node_modules

  backend:
    build: ./backend
    ports:
      - "8000:8000"
    environment:
      - DB_HOST=postgres
      - REDIS_HOST=redis
    depends_on:
      - postgres
      - redis
    volumes:
      - ./backend:/var/www/html

  python-service:
    build: ./python-service
    ports:
      - "5000:5000"
    environment:
      - NASA_TEMPO_API_KEY=${NASA_TEMPO_API_KEY}
      - WEATHER_API_KEY=${WEATHER_API_KEY}
    volumes:
      - ./python-service:/app

  postgres:
    image: postgres:13
    environment:
      - POSTGRES_DB=apollo14_air_quality
      - POSTGRES_USER=postgres
      - POSTGRES_PASSWORD=password
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:6-alpine
    ports:
      - "6379:6379"

volumes:
  postgres_data:
```

### Running with Docker
```bash
docker-compose up -d
```

## Production Deployment

### Option 1: AWS Deployment

#### 1. Frontend (Vercel/Netlify)
```bash
# Install Vercel CLI
npm i -g vercel

# Deploy frontend
cd frontend
vercel --prod
```

#### 2. Backend (AWS EC2)
```bash
# Launch EC2 instance (Ubuntu 20.04 LTS)
# Install dependencies
sudo apt update
sudo apt install nginx php8.1-fpm php8.1-cli php8.1-mysql php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip composer postgresql-client

# Clone and setup application
git clone https://github.com/apollo14/nasa-space-apps-2025.git
cd nasa-space-apps-2025/backend
composer install --no-dev --optimize-autoloader
cp .env.production .env
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configure Nginx
sudo nano /etc/nginx/sites-available/apollo14
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name api.apollo14.nasa.gov;
    root /var/www/apollo14/backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

#### 3. Python Service (AWS Lambda)
```bash
# Install AWS CLI and SAM CLI
pip install awscli aws-sam-cli

# Package Python service
cd python-service
sam build
sam deploy --guided
```

#### 4. Database (AWS RDS)
```bash
# Create RDS PostgreSQL instance
aws rds create-db-instance \
    --db-instance-identifier apollo14-db \
    --db-instance-class db.t3.micro \
    --engine postgres \
    --master-username postgres \
    --master-user-password your-password \
    --allocated-storage 20
```

### Option 2: Google Cloud Deployment

#### 1. Frontend (Firebase Hosting)
```bash
# Install Firebase CLI
npm install -g firebase-tools

# Deploy frontend
cd frontend
npm run build
firebase deploy
```

#### 2. Backend (Cloud Run)
```bash
# Create Dockerfile for backend
cd backend
gcloud builds submit --tag gcr.io/PROJECT_ID/apollo14-backend
gcloud run deploy apollo14-backend --image gcr.io/PROJECT_ID/apollo14-backend --platform managed --region us-central1
```

#### 3. Python Service (Cloud Functions)
```bash
# Deploy Python service
cd python-service
gcloud functions deploy air-quality-service --runtime python39 --trigger-http --allow-unauthenticated
```

### Option 3: Azure Deployment

#### 1. Frontend (Azure Static Web Apps)
```bash
# Install Azure CLI
npm install -g @azure/static-web-apps-cli

# Deploy frontend
cd frontend
npm run build
swa deploy --app-location ./build --deployment-token YOUR_TOKEN
```

#### 2. Backend (Azure App Service)
```bash
# Create App Service
az webapp create --resource-group apollo14-rg --plan apollo14-plan --name apollo14-backend --runtime "PHP|8.1"

# Deploy backend
az webapp deployment source config --name apollo14-backend --resource-group apollo14-rg --repo-url https://github.com/apollo14/nasa-space-apps-2025.git --branch main --manual-integration
```

## Environment Configuration

### Production Environment Variables

#### Backend (.env.production)
```env
APP_NAME=Apollo14
APP_ENV=production
APP_KEY=base64:your-production-key
APP_DEBUG=false
APP_URL=https://api.apollo14.nasa.gov

DB_CONNECTION=pgsql
DB_HOST=your-rds-endpoint.amazonaws.com
DB_PORT=5432
DB_DATABASE=apollo14_air_quality
DB_USERNAME=postgres
DB_PASSWORD=your-secure-password

REDIS_HOST=your-redis-endpoint.cache.amazonaws.com
REDIS_PASSWORD=your-redis-password
REDIS_PORT=6379

NASA_TEMPO_API_KEY=your-tempo-api-key
WEATHER_API_KEY=your-weather-api-key
OPENAQ_API_KEY=your-openaq-api-key

PYTHON_SERVICE_URL=https://your-lambda-url.amazonaws.com

VAPID_PUBLIC_KEY=your-vapid-public-key
VAPID_PRIVATE_KEY=your-vapid-private-key
VAPID_SUBJECT=mailto:admin@apollo14.nasa.gov
```

#### Frontend (.env.production)
```env
REACT_APP_API_URL=https://api.apollo14.nasa.gov/api
REACT_APP_MAPS_API_KEY=your-maps-api-key
REACT_APP_PUSH_VAPID_KEY=your-vapid-public-key
REACT_APP_ANALYTICS_ID=your-analytics-id
```

## SSL/TLS Configuration

### Let's Encrypt (Free SSL)
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d api.apollo14.nasa.gov -d app.apollo14.nasa.gov

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

### Cloudflare SSL
1. Add domain to Cloudflare
2. Update nameservers
3. Enable SSL/TLS encryption
4. Configure page rules for caching

## Monitoring and Logging

### Application Monitoring
```bash
# Install monitoring tools
npm install -g pm2
pip install sentry-sdk

# Configure PM2 for backend
pm2 start ecosystem.config.js
pm2 startup
pm2 save
```

### Log Management
```bash
# Configure log rotation
sudo nano /etc/logrotate.d/apollo14

# Log rotation configuration
/var/www/apollo14/backend/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

### Health Checks
```bash
# Create health check script
cat > /usr/local/bin/apollo14-health.sh << 'EOF'
#!/bin/bash
curl -f http://localhost:8000/api/health || exit 1
curl -f http://localhost:5000/health || exit 1
EOF

chmod +x /usr/local/bin/apollo14-health.sh

# Add to crontab
echo "*/5 * * * * /usr/local/bin/apollo14-health.sh" | crontab -
```

## Performance Optimization

### Frontend Optimization
```bash
# Build optimized bundle
npm run build

# Enable compression
# Add to nginx config:
gzip on;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
```

### Backend Optimization
```bash
# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Configure OPcache
sudo nano /etc/php/8.1/fpm/conf.d/10-opcache.ini
```

### Database Optimization
```sql
-- Create indexes for better performance
CREATE INDEX idx_submissions_user_date ON submissions(user_id, submitted_at);
CREATE INDEX idx_air_quality_location_time ON air_quality_data(location_lat, location_lng, timestamp);
CREATE INDEX idx_submissions_location ON submissions USING GIST(ST_Point(location_lng, location_lat));
```

## Security Configuration

### Firewall Setup
```bash
# Configure UFW
sudo ufw enable
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

### Database Security
```sql
-- Create restricted database user
CREATE USER apollo14_app WITH PASSWORD 'secure-password';
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO apollo14_app;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO apollo14_app;
```

### API Security
```php
// Rate limiting configuration
// In app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        'throttle:1000,1', // 1000 requests per hour
    ],
];
```

## Backup and Recovery

### Database Backup
```bash
# Create backup script
cat > /usr/local/bin/apollo14-backup.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/apollo14"
DATE=$(date +%Y%m%d_%H%M%S)
pg_dump apollo14_air_quality > $BACKUP_DIR/apollo14_$DATE.sql
find $BACKUP_DIR -name "apollo14_*.sql" -mtime +7 -delete
EOF

chmod +x /usr/local/bin/apollo14-backup.sh

# Schedule daily backups
echo "0 2 * * * /usr/local/bin/apollo14-backup.sh" | crontab -
```

### File Backup
```bash
# Backup uploaded files
rsync -av /var/www/apollo14/backend/storage/app/public/ /var/backups/apollo14/files/
```

## Scaling Considerations

### Horizontal Scaling
- **Load Balancer**: Use AWS ALB or CloudFlare Load Balancer
- **Multiple Backend Instances**: Deploy multiple Laravel instances
- **Database Read Replicas**: Use read replicas for read-heavy operations
- **CDN**: Use CloudFlare or AWS CloudFront for static assets

### Vertical Scaling
- **Database**: Upgrade to larger RDS instance
- **Backend**: Increase EC2 instance size
- **Cache**: Use ElastiCache for Redis
- **Storage**: Use S3 for file storage

## Troubleshooting

### Common Issues
1. **Database Connection**: Check RDS security groups
2. **API Timeouts**: Increase timeout settings
3. **Memory Issues**: Monitor memory usage and optimize
4. **SSL Errors**: Verify certificate configuration

### Debug Commands
```bash
# Check application status
pm2 status
pm2 logs

# Check database connection
php artisan tinker
DB::connection()->getPdo();

# Check Redis connection
redis-cli ping
```

## Cost Optimization

### AWS Cost Optimization
- Use Spot Instances for non-critical workloads
- Enable S3 lifecycle policies
- Use CloudWatch for monitoring and alerting
- Implement auto-scaling policies

### General Cost Tips
- Use CDN for static assets
- Implement caching strategies
- Optimize database queries
- Use serverless where appropriate

## Maintenance

### Regular Maintenance Tasks
- **Weekly**: Update dependencies, check logs
- **Monthly**: Security patches, performance review
- **Quarterly**: Backup testing, disaster recovery drill

### Monitoring Checklist
- [ ] Application health checks
- [ ] Database performance
- [ ] API response times
- [ ] Error rates
- [ ] Resource utilization
- [ ] Security scans

## Support and Documentation

### Internal Documentation
- API documentation: `/docs/api.md`
- Data sources: `/docs/data-sources.md`
- Deployment guide: `/docs/deployment.md`

### External Resources
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [React Deployment](https://create-react-app.dev/docs/deployment)
- [AWS Documentation](https://docs.aws.amazon.com/)
- [NASA TEMPO Mission](https://tempo.si.edu/)
