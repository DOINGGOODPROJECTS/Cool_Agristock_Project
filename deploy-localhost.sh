#!/bin/bash

# Cool AgriStock - Updated Deployment Script for Shared Hosting
# Database on localhost (127.0.0.1)

echo "🚀 Starting updated deployment package creation..."

# Create timestamp for unique directory name
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DEPLOY_DIR="agristock_localhost_${TIMESTAMP}"

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
    --exclude='agristock_*' \
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

echo "⚙️ Creating production environment file with localhost database..."
cat > "$DEPLOY_DIR/.env" << 'EOF'
APP_NAME="Cool AgriStock"
APP_ENV=production
APP_KEY=base64:lAOOI+D74FMqZ2fTHxTBmAy/F8TjVY2qyxj1ASFNEEo=
APP_DEBUG=false
APP_URL=https://agristock.fraiszo.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
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

echo "🔧 Creating production-optimized .htaccess..."
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

# Security Headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Disable server signature
ServerSignature Off

# File size limits for shared hosting
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
php_value memory_limit 256M

# Error reporting for production
php_flag display_errors Off
php_flag log_errors On
EOF

echo "🔧 Creating root .htaccess for shared hosting..."
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
EOF

echo "📋 Creating updated deployment instructions..."
cat > "$DEPLOY_DIR/SHARED_HOSTING_DEPLOYMENT_GUIDE.md" << 'EOF'
# Cool AgriStock - Shared Hosting Deployment Guide

## 📦 Database Configuration
✅ **Updated for Localhost Database**
- Database Host: 127.0.0.1 (localhost)
- Database: u495079612_agristock_db
- User: u495079612_agristock_user
- Password: Dev5555!

## 🚀 Deployment Steps

### 1. Upload Package
- Upload the deployment ZIP to your Hostinger account
- Extract it in your domain's root directory (public_html)

### 2. File Structure After Extraction
```
public_html/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/          # Laravel public directory
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env            # Production environment (localhost DB)
├── .htaccess       # Root redirects to public/
└── composer.json
```

### 3. Set Permissions (Important!)
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod 644 .env
```

### 4. Troubleshooting 500 Errors

If you still get 500 errors, check:

1. **File Permissions**: Ensure storage and bootstrap/cache are writable
2. **Database Connection**: Test database credentials in Hostinger panel
3. **Error Logs**: Check Hostinger error logs for specific issues
4. **Laravel Logs**: Check storage/logs/laravel.log

### 5. Test Database Connection
Add this to your domain temporarily to test DB connection:

```php
<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=u495079612_agristock_db', 'u495079612_agristock_user', 'Dev5555!');
    echo "Database connection successful!";
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
```

### 6. Production Settings
- **APP_ENV**: production
- **APP_DEBUG**: false (for security)
- **DB_HOST**: 127.0.0.1 (localhost)
- **LOG_LEVEL**: error

## 🔍 Common Issues & Solutions

1. **500 Internal Server Error**
   - Check file permissions (755 for directories, 644 for files)
   - Verify .env file has correct database credentials
   - Check error logs in Hostinger panel

2. **Database Connection Refused**
   - Confirm DB_HOST=127.0.0.1
   - Verify database name and credentials in Hostinger panel
   - Check if database exists and user has proper permissions

3. **Missing Dependencies**
   - All Composer dependencies are included in vendor/
   - Frontend assets are pre-built in public/build/

## 📞 Support
Check Laravel logs in storage/logs/ for detailed error information.
EOF

echo "🎯 Setting proper permissions..."
find "$DEPLOY_DIR" -type f -exec chmod 644 {} \;
find "$DEPLOY_DIR" -type d -exec chmod 755 {} \;
chmod 755 "$DEPLOY_DIR/artisan"
chmod 644 "$DEPLOY_DIR/.env"

echo "✅ Updated deployment package created: $DEPLOY_DIR"
echo "📦 Ready for ZIP creation with localhost database configuration!"

echo ""
echo "📊 Updated Deployment Summary:"
echo "├── Laravel Backend: ✓ Clean production config"
echo "├── Frontend Assets: ✓ Built and optimized"
echo "├── Database Config: ✓ Localhost (127.0.0.1)"
echo "├── Production Setup: ✓ Optimized for agristock.fraiszo.com"
echo "├── Security: ✓ Production .htaccess & headers"
echo "└── Documentation: ✓ Shared hosting deployment guide"