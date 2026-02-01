# Quick Start Guide

Get up and running with the refactored ticket system in minutes!

## Prerequisites Check

- ✅ PHP >= 7.4 installed
- ✅ MySQL/MariaDB running
- ✅ Composer installed (optional but recommended)

## 5-Minute Setup

### Step 1: Install Dependencies (2 minutes)

```bash
cd c:\xampp\htdocs\Capstone\dev-env
composer install
```

### Step 2: Configure Environment (1 minute)

```bash
# Copy example file
copy .env.example .env

# Edit .env with your database credentials
# DB_HOST=localhost
# DB_USER=root
# DB_PASS=your_password
# DB_NAME=ts_isc
```

### Step 3: Migrate Passwords (1 minute)

```bash
php php/migrate_passwords.php
```

### Step 4: Test (1 minute)

1. Open browser: `http://localhost/Capstone/dev-env/views/login.php`
2. Login with existing credentials
3. Create a test ticket

## ✅ You're Done!

The system is now using the new architecture while maintaining full backward compatibility.

## What Changed?

### ✅ New Features Available

1. **Better Security**
   - Password hashing
   - CSRF protection
   - Environment-based config

2. **Modern Architecture**
   - Service layer
   - Repository pattern
   - Query builder

3. **Better Logging**
   - Centralized logging
   - Structured logs
   - Error tracking

### ✅ What Still Works

- ✅ All existing endpoints
- ✅ All existing functionality
- ✅ All existing views
- ✅ All existing JavaScript

## Using New Features

### Option 1: Use New API Endpoints

```javascript
// Old way (still works)
fetch('../php/save_ticket.php', { ... })

// New way (recommended)
fetch('../api/tickets', { ... })
```

### Option 2: Use New Services in PHP

```php
// Old way (still works)
include('db.php');
$sql = "SELECT * FROM tbl_ticket...";

// New way (recommended)
use Services\TicketService;
$service = new TicketService();
$tickets = $service->getTickets($filters);
```

## Troubleshooting

### "Class not found" errors
```bash
composer dump-autoload
```

### Database connection errors
- Check `.env` file
- Verify MySQL is running
- Check database exists

### Old code not working
- Old code still works!
- Check if you're using new endpoints
- Verify session is started

## Next Steps

1. ✅ System is ready to use
2. 📖 Read `README.md` for full documentation
3. 📋 Check `MIGRATION_STATUS.md` for migration progress
4. 🚀 Start using new features gradually

## Support

- Check `logs/app.log` for errors
- Review `MIGRATION_COMPLETE.md` for details
- See `INSTALLATION.md` for detailed setup

---

**You're all set!** The system is production-ready with the new architecture. 🎉
