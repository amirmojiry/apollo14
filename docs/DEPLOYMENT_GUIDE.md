# Apollo14 Hetzner Server Deployment Guide

This guide will help you deploy your Apollo14 Laravel backend to a Hetzner server with GitHub integration for automated deployments.

## Prerequisites

- Hetzner Cloud server (Ubuntu 22.04 recommended)
- Domain name pointing to your server
- GitHub repository with your code
- SSH access to your server

## Quick Start

### 1. Server Setup

1. **Create Hetzner Cloud Server**
   - Choose Ubuntu 22.04
   - Minimum: 2GB RAM, 1 CPU
   - Recommended: 4GB RAM, 2 CPU

2. **Connect to Server**
   ```bash
   ssh root@YOUR_SERVER_IP
   ```

3. **Create Non-Root User**
   ```bash
   adduser deploy
   usermod -aG sudo deploy
   su - deploy
   ```

4. **Run Deployment Script**
   ```bash
   # Upload deploy.sh to server
   chmod +x deploy.sh
   ./deploy.sh
   ```

### 2. GitHub Integration

1. **Add GitHub Secrets**
   Go to your GitHub repository → Settings → Secrets and variables → Actions
   
   Add these secrets:
   - `HETZNER_HOST`: Your server IP address
   - `HETZNER_USERNAME`: Your server username (deploy)
   - `HETZNER_SSH_KEY`: Your private SSH key

2. **Generate SSH Key Pair**
   ```bash
   ssh-keygen -t rsa -b 4096 -C "your-email@example.com"
   # Copy public key to server
   ssh-copy-id deploy@YOUR_SERVER_IP
   # Use private key as HETZNER_SSH_KEY secret
   ```

### 3. Database Configuration

1. **Run Database Setup**
   ```bash
   chmod +x scripts/setup-database.sh
   ./scripts/setup-database.sh
   ```

2. **Update Environment File**
   ```bash
   cp backend/.env.production backend/.env
   nano backend/.env
   # Update database credentials and other settings
   ```

### 4. Domain and SSL Setup

1. **Update Nginx Configuration**
   ```bash
   sudo nano /etc/nginx/sites-available/apollo14
   # Replace 'your-domain.com' with your actual domain
   ```

2. **Set Up SSL Certificate**
   ```bash
   chmod +x scripts/setup-ssl.sh
   ./scripts/setup-ssl.sh
   ```

### 5. Final Configuration

1. **Generate Application Key**
   ```bash
   cd /var/www/apollo14/backend
   php artisan key:generate
   ```

2. **Run Migrations**
   ```bash
   php artisan migrate --force
   ```

3. **Generate Swagger Documentation**
   ```bash
   php artisan l5-swagger:generate
   ```

4. **Restart Services**
   ```bash
   sudo systemctl restart nginx
   sudo systemctl restart php8.2-fpm
   ```

## Automated Deployment

Once set up, every push to the `main` branch will automatically:

1. Pull latest code from GitHub
2. Install/update dependencies
3. Run database migrations
4. Clear and cache configuration
5. Generate Swagger documentation
6. Reload Nginx

## File Structure

```
apollo14/
├── deploy.sh                 # Main deployment script
├── .github/workflows/
│   └── deploy.yml            # GitHub Actions workflow
├── scripts/
│   ├── setup-database.sh     # Database setup
│   ├── setup-ssl.sh          # SSL certificate setup
│   └── backup.sh             # Backup script
├── backend/
│   ├── .env.production       # Production environment template
│   └── ...
└── DEPLOYMENT_GUIDE.md       # This file
```

## Configuration Files

### Nginx Configuration
- Location: `/etc/nginx/sites-available/apollo14`
- Features: Security headers, Gzip compression, Static file caching

### PHP-FPM Configuration
- Location: `/etc/php/8.2/fpm/pool.d/www.conf`
- Optimized for production with proper process management

### Supervisor Configuration
- Location: `/etc/supervisor/conf.d/apollo14-worker.conf`
- Manages Laravel queue workers

## Monitoring and Maintenance

### Log Files
- Application logs: `/var/www/apollo14/backend/storage/logs/`
- Nginx logs: `/var/log/nginx/`
- PHP-FPM logs: `/var/log/php8.2-fpm.log`

### Backup System
Set up automated backups:
```bash
# Add to crontab
crontab -e
# Add: 0 2 * * * /var/www/apollo14/scripts/backup.sh
```

### Health Checks
- API Health: `https://your-domain.com/api/health`
- Swagger Documentation: `https://your-domain.com/api/documentation`

## Troubleshooting

### Common Issues

1. **Permission Errors**
   ```bash
   sudo chown -R www-data:www-data /var/www/apollo14/backend/storage
   sudo chmod -R 775 /var/www/apollo14/backend/storage
   ```

2. **Database Connection Issues**
   - Check MySQL service: `sudo systemctl status mysql`
   - Verify credentials in `.env` file
   - Test connection: `mysql -u apollo14_user -p apollo14`

3. **Nginx Configuration Issues**
   ```bash
   sudo nginx -t  # Test configuration
   sudo systemctl reload nginx
   ```

4. **PHP-FPM Issues**
   ```bash
   sudo systemctl status php8.2-fpm
   sudo systemctl restart php8.2-fpm
   ```

### Useful Commands

```bash
# Check service status
sudo systemctl status nginx php8.2-fpm mysql redis-server

# View logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/www/apollo14/backend/storage/logs/laravel.log

# Restart services
sudo systemctl restart nginx php8.2-fpm

# Check disk usage
df -h

# Check memory usage
free -h
```

## Security Considerations

1. **Firewall Setup**
   ```bash
   sudo ufw allow ssh
   sudo ufw allow 'Nginx Full'
   sudo ufw enable
   ```

2. **SSH Security**
   - Disable root login
   - Use key-based authentication
   - Change default SSH port

3. **Regular Updates**
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

## Performance Optimization

1. **Enable OPcache**
   ```bash
   sudo nano /etc/php/8.2/fpm/conf.d/10-opcache.ini
   # Uncomment and configure opcache settings
   ```

2. **Redis Configuration**
   ```bash
   sudo nano /etc/redis/redis.conf
   # Optimize memory and persistence settings
   ```

3. **MySQL Optimization**
   ```bash
   sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
   # Configure buffer sizes and other optimizations
   ```

## Support

For issues or questions:
1. Check the logs first
2. Verify all services are running
3. Test individual components
4. Review this guide for common solutions

Your Apollo14 API should now be accessible at `https://your-domain.com/api/` with full Swagger documentation at `https://your-domain.com/api/documentation`.
