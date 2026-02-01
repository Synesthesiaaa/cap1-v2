# Migration Guide

This document outlines the migration from the old system to the new refactored architecture.

## Overview

The refactoring maintains backward compatibility. Old endpoints continue to work while new code uses the modern architecture.

## Phase 1: Security & Foundation ✅

### Completed
- ✅ Password hashing implementation
- ✅ CSRF protection middleware
- ✅ Environment configuration (.env)
- ✅ Composer setup
- ✅ PSR-4 autoloading
- ✅ Centralized logging (Monolog)
- ✅ Global exception handler
- ✅ Debug code removal

### Migration Steps

1. **Password Migration**
   ```bash
   php php/migrate_passwords.php
   ```

2. **Environment Setup**
   - Copy `env.example` to `.env`
   - Update database credentials

3. **Install Dependencies**
   ```bash
   composer install
   ```

## Phase 2: Architecture ✅

### Completed
- ✅ MVC structure
- ✅ Database layer (QueryBuilder, Repositories)
- ✅ Service layer
- ✅ Router system

### Using New Architecture

#### Old Way (Still Works)
```php
// Direct SQL
$sql = "SELECT * FROM tbl_ticket WHERE ticket_id = ?";
$stmt = $conn->prepare($sql);
// ...
```

#### New Way (Recommended)
```php
// Repository
$repository = new TicketRepository();
$ticket = $repository->findById($id);

// Or QueryBuilder
$builder = new QueryBuilder($conn, 'tbl_ticket');
$ticket = $builder->where('ticket_id', $id)->first();
```

## Phase 3: Code Consolidation ✅

### Completed
- ✅ LogService (merged insert_log.php and insert_log_monitor.php)
- ✅ Service layer (TicketService, AuthService, NotificationService)

### Migrating Log Calls

#### Old Way
```php
require_once 'insert_log.php';
insertTicketLog($ticket_id, $user_id, $role, 'created', 'Ticket created', $conn);
```

#### New Way
```php
use Services\LogService;

$logService = new LogService();
$logService->logTicketAction($ticketId, $userId, $userRole, 'created', 'Ticket created');
```

## Phase 4: API Migration (In Progress)

### Migrating Endpoints

#### Old Endpoint
`php/save_ticket.php` - Direct file access

#### New Endpoint
`POST /api/tickets` - RESTful API

### Example Migration

**Old: save_ticket.php**
```php
<?php
include("db.php");
// ... 255 lines of code
```

**New: Using Controller**
```php
// routes/api.php
$router->post('/api/tickets', [TicketController::class, 'create']);

// src/App/Controllers/TicketController.php
public function create() {
    $ticketService = new TicketService();
    // Clean, testable code
}
```

## Backward Compatibility

All old endpoints continue to work. Migration can be done gradually:

1. **Keep old files** - They still work
2. **Create new endpoints** - Add alongside old ones
3. **Update frontend** - Point to new endpoints
4. **Remove old files** - Once migration is complete

## Testing Migration

1. **Test old endpoints** - Ensure they still work
2. **Test new endpoints** - Verify functionality
3. **Compare results** - Ensure consistency
4. **Update frontend** - Switch to new endpoints
5. **Monitor logs** - Check for errors

## Rollback Plan

If issues occur:

1. Old endpoints remain functional
2. Revert frontend to old endpoints
3. Check logs for errors
4. Fix issues in new code
5. Re-attempt migration

## Next Steps

1. Migrate remaining endpoints to new structure
2. Update frontend to use new API
3. Add comprehensive tests
4. Remove deprecated code
5. Update documentation

## Questions?

Contact the development team for assistance with migration.
