# Hostinger Shared Hosting Deployment Guide

## File Ready
**Deployment File:** `agristock_shared_hosting_deployment.zip` (37MB)

This package includes:
✅ Complete Laravel backend (app/, routes/, config/, etc.)
✅ Complete frontend (resources/, public/build/ with CSS/JS assets)
✅ Database migrations
✅ Production .env configuration
✅ Correct .htaccess for shared hosting
✅ All dependencies (vendor/)

---

## Upload Instructions for Hostinger Shared Hosting

### Step 1: Upload ZIP to Hostinger
1. Login to **Hostinger cPanel**
2. Go to **File Manager**
3. Navigate to **public_html/**
4. Click **Upload** → Select `agristock_shared_hosting_deployment.zip`
5. Wait for upload to complete

### Step 2: Extract ZIP File
1. Right-click on `agristock_shared_hosting_deployment.zip`
2. Select **Extract**
3. Wait for extraction to complete
4. You should now have a folder: `agristock_shared_hosting_20251118_174116/`

### Step 3: Move Files to public_html Root
1. Open the extracted folder: `agristock_shared_hosting_20251118_174116/`
2. Select **ALL** files inside (Ctrl+A)
3. Cut them (Ctrl+X)
4. Go back to `public_html/`
5. Paste them (Ctrl+V)
6. Delete the now-empty `agristock_shared_hosting_20251118_174116/` folder
7. Delete the original `agristock_shared_hosting_deployment.zip` file

### Step 4: Verify File Structure
Your `public_html/` should now contain:
```
public_html/
├── .env (production configuration)
├── .htaccess (routing rules)
├── index.php
├── artisan
├── app/
├── public/
│   └── build/ (contains CSS/JS from Vite)
├── resources/
├── routes/
├── config/
├── storage/
├── bootstrap/
└── vendor/
```

### Step 5: Run Database Migrations via SSH

1. Go to **Hostinger Terminal** or **SSH**
   - In cPanel, look for **Terminal** or **SSH Shell Access**
2. Run these commands:

```bash
# Navigate to public_html
cd ~/public_html

# Run migrations to create database tables
php artisan migrate --force

# (Optional) Seed test data if needed
php artisan db:seed --force

# Clear cache
php artisan cache:clear

# Done!
echo "✓ Deployment complete"
```

### Step 6: Test Your Site
1. Visit: **https://agristock.fraiszo.com**
2. You should see the login page with styling (CSS/JS loaded)
3. Login with your credentials
4. Dashboard should be fully functional

---

## If You Get 500 Error

### Quick Fixes
1. **Verify directory structure** - Make sure files are directly in `public_html/`, not in a subdirectory
2. **Check .env file:**
   ```bash
   cat ~/public_html/.env | grep DB_
   ```
   Should show Hostinger database credentials

3. **Check storage permissions:**
   ```bash
   chmod -R 755 ~/public_html/storage/
   chmod -R 755 ~/public_html/bootstrap/cache/
   ```

4. **View error logs:**
   ```bash
   tail -50 ~/public_html/storage/logs/laravel.log
   ```

5. **Test database connection:**
   ```bash
   mysql -h auth-db693.hstgr.io \
         -u u495079612_agristock_user \
         -pDev5555! \
         u495079612_agristock_db \
         -e "SHOW TABLES;"
   ```

---

## Database Configuration
**Host:** auth-db693.hstgr.io
**Database:** u495079612_agristock_db
**Username:** u495079612_agristock_user
**Password:** Dev5555!

---

## Features Included

### Frontend (Vite Built)
- ✅ Tailwind CSS styling
- ✅ Modern JavaScript bundle
- ✅ Responsive design
- ✅ All assets optimized for production

### Backend (Laravel)
- ✅ Complete application code
- ✅ Database migrations ready to run
- ✅ Email sending (Gmail SMTP configured)
- ✅ French localization (default language)
- ✅ Language switcher (🇫🇷 → 🇬🇧)
- ✅ User authentication
- ✅ All models and controllers

### Security
- ✅ `.env` protected (denied by .htaccess)
- ✅ `composer.json` protected
- ✅ Production settings configured
- ✅ Debug mode disabled

---

## Support Information

**Deployment Package:** `agristock_shared_hosting_deployment.zip`
**Size:** 37MB
**Included:** Backend + Frontend + All Assets + Dependencies

If you encounter any issues:
1. Check the error logs: `tail -50 ~/public_html/storage/logs/laravel.log`
2. Verify database connection
3. Confirm .env file is present and readable
4. Ensure storage/ directories have 755 permissions

---

## Next Steps After Deployment

1. ✅ Upload and extract ZIP
2. ✅ Move files to public_html root
3. ✅ Run `php artisan migrate --force`
4. ✅ Visit https://agristock.fraiszo.com
5. ✅ Test login and core functionality
6. ✅ Monitor logs for any errors

**Estimated Time:** 5-10 minutes

