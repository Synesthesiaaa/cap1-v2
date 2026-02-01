# Installation Guide

Complete step-by-step installation instructions for the Ticket Management System.

## Prerequisites

- PHP >= 7.4 with extensions: mysqli, mbstring, json
- MySQL/MariaDB >= 5.7
- Composer (https://getcomposer.org/)
- Node.js >= 14.x (optional, for frontend build)
- Web server (Apache/Nginx) or PHP built-in server

## Step 1: Clone/Download Project

```bash
cd c:\xampp\htdocs\Capstone\dev-env
```

## Step 2: Install PHP Dependencies

```bash
composer install
```

This will install:
- vlucas/phpdotenv (environment configuration)
- monolog/monolog (logging)
- symfony/validator (validation)
- phpunit/phpunit (testing)

## Step 3: Configure Environment

1. Copy the example environment file:
   ```bash
   copy .env.example .env
   ```

2. Edit `.env` file with your database credentials:
   ```
   DB_HOST=localhost
   DB_USER=root
   DB_PASS=your_password
   DB_NAME=ts_isc
   ```

3. Configure application settings:
   ```
   APP_ENV=development  # or 'production'
   APP_DEBUG=true      # false in production
   ```

## Step 4: Database Setup

1. Create the database (if not exists):
   ```sql
   CREATE DATABASE ts_isc CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   ```

2. Import the schema:
   ```bash
   mysql -u root -p ts_isc < "Schema ts_isc.sql"
   ```

3. Run password migration (if upgrading from old system):
   ```bash
   php php/migrate_passwords.php
   ```

## Step 5: Set Permissions

Ensure these directories are writable:
- `logs/` - For application logs
- `uploads/` - For file uploads

On Windows:
```bash
icacls logs /grant Users:F
icacls uploads /grant Users:F
```

On Linux:
```bash
chmod 755 logs uploads
```

## Step 6: Web Server Configuration

### Option A: XAMPP (Windows)

1. Ensure XAMPP is running
2. Access via: `http://localhost/Capstone/dev-env/`

### Option B: PHP Built-in Server

```bash
php -S localhost:8000 -t .
```

Access via: `http://localhost:8000`

### Option C: Apache Virtual Host

Add to `httpd-vhosts.conf`:
```apache
<VirtualHost *:80>
    ServerName ticket-system.local
    DocumentRoot "C:/xampp/htdocs/Capstone/dev-env"
    <Directory "C:/xampp/htdocs/Capstone/dev-env">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Step 7: Frontend Dependencies (Optional)

```bash
npm install
```

## Step 8: Verify Installation

1. Check Composer autoload:
   ```bash
   composer dump-autoload
   ```

2. Test database connection:
   - Try accessing `http://localhost/Capstone/dev-env/views/login.php`
   - Check `logs/app.log` for any errors

3. Run tests:
   ```bash
   composer test
   ```

## Troubleshooting

### Composer Issues

**Problem**: `composer: command not found`
**Solution**: Install Composer from https://getcomposer.org/

**Problem**: `Class not found` errors
**Solution**: Run `composer dump-autoload`

### Database Issues

**Problem**: `Database connection failed`
**Solution**: 
- Check `.env` file credentials
- Verify MySQL is running
- Check database exists

**Problem**: `Access denied for user`
**Solution**: Verify database user has proper permissions

### Permission Issues

**Problem**: `Cannot write to logs/`
**Solution**: Check directory permissions (see Step 5)

### Session Issues

**Problem**: Sessions not working
**Solution**: 
- Check `php.ini` session settings
- Ensure `session.save_path` is writable

## Next Steps

After installation:

1. **Create Admin User**: Use the database or create a user via the system
2. **Configure Email**: Update email settings in `.env` if needed
3. **Review Security**: Change default passwords, review CSRF settings
4. **Read Documentation**: See `README.md` and `MIGRATION.md`

## Production Deployment

For production:

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false` in `.env`
3. Use strong database passwords
4. Enable HTTPS
5. Configure proper file permissions
6. Set up log rotation
7. Configure backup strategy

## Support

For installation issues, check:
- `logs/app.log` for errors
- PHP error logs
- Web server error logs

Contact the development team for assistance.
