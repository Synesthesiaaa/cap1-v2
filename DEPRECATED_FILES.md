# Deprecated Files

This document lists files that are deprecated and will be removed in a future version.

## Currently Deprecated (Still Functional)

### Log Files
- `php/insert_log.php` - **DEPRECATED**: Use `Services\LogService` instead
- `php/insert_log_monitor.php` - **DEPRECATED**: Use `Services\LogService` instead

**Migration Guide:**
```php
// Old way (deprecated)
include("insert_log.php");
insertTicketLog($ticket_id, $user_id, $user_role, $action_type, $action_details, $conn);

// New way (recommended)
use Services\LogService;
$logService = new LogService();
$logService->logTicketAction($ticket_id, $user_id, $user_role, $action_type, $action_details);
```

**Status**: These files are kept for backward compatibility only. All new code should use LogService.

## Removed Files

The following files have been removed from the codebase:

### Obsolete Files
- `views/service_ticket.php` - Marked as obsolete
- `js/service_form.js` - Marked as obsolete
- `views/dashboard-react.html` - Unused prototype

### Archived Files
Migration scripts have been moved to `archive/migrations/`:
- All `php/migrate_*.php` files

### Removed CSS
- `css/userTicketMonitor.css` - Unused stylesheet

## Timeline

- **Phase 1** (Current): Files marked as deprecated, backward compatibility maintained
- **Phase 2** (Future): Remove deprecated files after full migration verification
- **Phase 3** (Future): Complete CSS consolidation

## Questions?

If you need to use deprecated functionality, check the migration guides in:
- `MIGRATION.md`
- `MIGRATION_STATUS.md`
- `CLEANUP_COMPLETE.md`
