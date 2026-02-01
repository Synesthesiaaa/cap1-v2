# Codebase Cleanup Plan

## Overview
This document outlines the cleanup strategy to remove obsolete files, redundancies, and deprecated code while maintaining functionality.

## Files to Remove

### 1. Obsolete Files (Marked as OBSOLETE)
- ✅ `views/service_ticket.php` - Marked as obsolete (Oct 3 interview)
- ✅ `js/service_form.js` - Marked as obsolete

### 2. Replaced by New Services
- ✅ `php/insert_log.php` - Replaced by `LogService`
- ✅ `php/insert_log_monitor.php` - Replaced by `LogService`

### 3. One-Time Migration Scripts (Move to Archive)
These are one-time use scripts that should be archived:
- ✅ `php/migrate_passwords.php` - Password migration (already run)
- ✅ `php/migrate_department_heads_to_new_table.php` - One-time migration
- ✅ `php/migrate_external_users_to_customer_department.php` - One-time migration
- ✅ `php/migrate_remove_evaluator_fields.php` - One-time migration
- ✅ `php/migrate_remove_evaluator_role.php` - One-time migration
- ✅ `php/migrate_product_tables.php` - One-time migration

### 4. CSS Consolidation
- Review `ticket_monitor.css` and `ticket_monitor2.css` for redundancy
- Review `userTicketMonitor.css` - may be redundant with theme.css
- Keep: `theme.css`, `components.css`, `animations.css`, `login.css`
- Consolidate: `basicTemp.css` into `theme.css` if possible

### 5. Unused Views
- ✅ `views/dashboard-react.html` - If not being used

## Files to Keep (Still in Use)

### Core Files
- `php/db.php` - Still used as fallback
- All migrated PHP endpoints (backward compatible)
- All active views

### New Structure
- All files in `src/` directory
- `bootstrap.php`
- `api/index.php`
- `routes/api.php`

## Cleanup Strategy

1. **Create Archive Directory** - Move one-time scripts to archive
2. **Remove Obsolete Files** - Delete files marked as obsolete
3. **Update References** - Ensure no broken links
4. **Consolidate CSS** - Merge redundant stylesheets
5. **Update Documentation** - Reflect cleanup in docs

## Safety Checks

Before removing files:
1. ✅ Check if file is referenced anywhere
2. ✅ Verify replacement exists
3. ✅ Test functionality after removal
4. ✅ Update .gitignore if needed
