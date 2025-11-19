#!/bin/bash

# Cool AgriStock - Subdomain Deployment Script
# Creates a clean deployment package for subdomain hosting

echo "🚀 Starting subdomain deployment package creation..."

# Create timestamp for unique directory name
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DEPLOY_DIR="agristock_subdomain_${TIMESTAMP}"

# Create deployment directory
echo "📁 Creating deployment directory: $DEPLOY_DIR"
mkdir -p "$DEPLOY_DIR"

# Copy all Laravel files except problematic ones
echo "📋 Copying Laravel application files..."
rsync -av \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='.env' \
    --exclude='bootstrap/cache/config.php' \
    --exclude='bootstrap/cache/packages.php' \
    --exclude='bootstrap/cache/services.php' \
    --exclude='bootstrap/cache/routes-v7.php' \
    --exclude='storage/logs/*.log' \
    --exclude='storage/framework/cache/data/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='public/hot' \
    --exclude='vendor/*/tests' \
    --exclude='vendor/*/Tests' \
    --exclude='tests' \
    ./ "$DEPLOY_DIR/"

echo "🎨 Building fresh frontend assets..."
npm run build

echo "📁 Setting up proper Laravel structure..."

# Create necessary directories
mkdir -p "$DEPLOY_DIR/storage/logs"
mkdir -p "$DEPLOY_DIR/storage/framework/cache/data"
mkdir -p "$DEPLOY_DIR/storage/framework/sessions"
mkdir -p "$DEPLOY_DIR/storage/framework/views"
mkdir -p "$DEPLOY_DIR/bootstrap/cache"

# Set proper permissions structure
find "$DEPLOY_DIR/storage" -type d -exec chmod 755 {} \;
find "$DEPLOY_DIR/bootstrap/cache" -type d -exec chmod 755 {} \;

echo "⚙️ Creating subdomain environment file..."
cat > "$DEPLOY_DIR/.env" << 'EOF'
APP_NAME="Cool AgriStock"
APP_ENV=production
APP_KEY=base64:lAOOI+D74FMqZ2fTHxTBmAy/F8TjVY2qyxj1ASFNEEo=
APP_DEBUG=false
APP_URL=https://api.agristock.fraiszo.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=212.1.209.193
DB_PORT=3306
DB_DATABASE=u495079612_agristock_db
DB_USERNAME=u495079612_agristock_user
DB_PASSWORD=Dev5555!

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=globeguruapp@gmail.com
MAIL_PASSWORD=yiimkhtghjsgrhhj
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@agristock.fraiszo.com
MAIL_FROM_NAME=CoolAgriStock

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
EOF

echo "🔧 Creating subdomain-optimized .htaccess..."
cat > "$DEPLOY_DIR/public/.htaccess" << 'EOF'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Security Headers for API
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # CORS Headers for API access
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Origin, X-Requested-With, Content-Type, Accept, Authorization"
</IfModule>

# Disable server signature
ServerSignature Off

# File size limits
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
php_value memory_limit 256M
EOF

echo "🔧 Creating root .htaccess for subdomain..."
cat > "$DEPLOY_DIR/.htaccess" << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Redirect all requests to public folder
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ /public/$1 [L,QSA]
    
    # Handle requests that are already in public folder
    RewriteCond %{REQUEST_URI} ^/public/
    RewriteRule ^public/(.*)$ /public/$1 [L,QSA]
</IfModule>

# Security settings
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.json">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.lock">
    Order allow,deny
    Deny from all
</Files>

<Files "package.json">
    Order allow,deny
    Deny from all
</Files>
EOF

echo "📋 Creating subdomain deployment instructions..."
cat > "$DEPLOY_DIR/SUBDOMAIN_DEPLOYMENT_GUIDE.md" << 'EOF'
# Cool AgriStock - Subdomain Deployment Guide

## 📦 Package Contents
This deployment package is configured for subdomain hosting (e.g., api.agristock.fraiszo.com)

## 🚀 Deployment Steps

### 1. Upload Package
- Upload `agristock_subdomain_deployment.zip` to your Hostinger account
- Extract it in your subdomain's root directory

### 2. File Structure
```
your-subdomain-root/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/          # Laravel public directory
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env            # Production environment
├── .htaccess       # Root redirects to public/
└── composer.json
```

### 3. Set Permissions
Run these commands in your subdomain directory:
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod 644 .env
```

### 4. Database Setup
The package uses your existing Hostinger database:
- Database: u495079612_agristock_db
- Host: 212.1.209.193
- User: u495079612_agristock_user

### 5. Configuration Notes
- **APP_URL**: Set to https://api.agristock.fraiszo.com (update as needed)
- **APP_ENV**: production
- **APP_DEBUG**: false (security)
- **LOG_LEVEL**: error (production logging)

### 6. SSL Certificate
Ensure your subdomain has SSL enabled in Hostinger control panel.

### 7. Test Deployment
Visit your subdomain URL to verify the application is working.

## 🔧 Troubleshooting

### Common Issues:
1. **500 Error**: Check file permissions and .env configuration
2. **Database Connection**: Verify database credentials
3. **Missing Assets**: Ensure public/ directory is accessible

### Log Files:
- Laravel logs: `storage/logs/laravel.log`
- Server logs: Check Hostinger control panel

## 🛡️ Security Features
- Production environment settings
- Security headers configured
- File upload restrictions
- Hidden sensitive files
- CORS headers for API access

## 📞 Support
If you encounter issues, check the Laravel log files and Hostinger error logs.
EOF

echo "🎯 Setting proper permissions..."
find "$DEPLOY_DIR" -type f -exec chmod 644 {} \;
find "$DEPLOY_DIR" -type d -exec chmod 755 {} \;
chmod 755 "$DEPLOY_DIR/artisan"
chmod 644 "$DEPLOY_DIR/.env"

echo "✅ Subdomain deployment package created: $DEPLOY_DIR"
echo "📦 Ready for ZIP creation and upload to subdomain!"

echo ""
echo "📊 Subdomain Deployment Summary:"
echo "├── Laravel Backend: ✓ Clean production config"
echo "├── Frontend Assets: ✓ Built and optimized"
echo "├── Database Config: ✓ Hostinger credentials"
echo "├── Subdomain Setup: ✓ Optimized for api.agristock.fraiszo.com"
echo "├── Security: ✓ Production .htaccess & headers"
echo "└── Documentation: ✓ Complete deployment guide"