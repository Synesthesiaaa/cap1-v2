# Ticket Management System

Interconnect Solutions Company (ISC) Ticket Management System - A comprehensive refactored solution for managing support tickets.

## Features

- **Secure Authentication**: Password hashing, CSRF protection, session management
- **RESTful API**: Standardized API endpoints
- **MVC Architecture**: Clean separation of concerns
- **Service Layer**: Business logic abstraction
- **Repository Pattern**: Database abstraction
- **Query Builder**: Fluent database queries
- **Centralized Logging**: Monolog integration
- **Error Handling**: Global exception handler

## Requirements

- PHP >= 7.4
- MySQL/MariaDB
- Composer
- Node.js (for frontend build)

## Installation

### 1. Clone the repository

```bash
git clone <repository-url>
cd dev-env
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Configure environment

Copy `env.example` to `.env` and update with your database credentials:

```bash
cp env.example .env
```

Edit `.env`:
```
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=ts_isc
```

### 4. Run password migration (if upgrading from old system)

```bash
php php/migrate_passwords.php
```

### 5. Install frontend dependencies (optional)

```bash
npm install
```

### 6. Set up web server

Point your web server document root to the project directory.

## Project Structure

```
dev-env/
├── api/                 # API entry points
├── assets/              # Static assets (images, etc.)
├── css/                 # Stylesheets
├── includes/            # Shared includes (navbar, etc.)
├── js/                  # JavaScript files
├── logs/                # Application logs
├── php/                 # Legacy PHP files (being migrated)
├── routes/              # Route definitions
├── src/                 # New refactored code
│   ├── App/
│   │   ├── Controllers/ # Controllers
│   │   └── Router.php   # Router class
│   ├── Database/        # Database layer
│   ├── Exceptions/      # Exception handlers
│   ├── Middleware/      # Middleware classes
│   ├── Models/          # Data models
│   ├── Repositories/    # Repository pattern
│   └── Services/        # Business logic services
├── uploads/             # File uploads
├── utils/               # Utility functions
└── views/               # View templates
```

## Usage

### API Endpoints

#### Authentication
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/check` - Check authentication status

#### Tickets
- `GET /api/tickets` - List tickets
- `POST /api/tickets` - Create ticket
- `GET /api/tickets/{reference}` - Get ticket by reference
- `POST /api/tickets/{reference}/resolve` - Resolve ticket

### Using Services

```php
use Services\TicketService;
use Services\AuthService;

// Create ticket
$ticketService = new TicketService();
$result = $ticketService->create($ticketData, $userId, $userRole);

// Authenticate user
$authService = new AuthService();
$userData = $authService->authenticate($email, $password);
```

### Using Repositories

```php
use Repositories\TicketRepository;

$repository = new TicketRepository();
$ticket = $repository->findByReference('TKT-20250125-ABC123');
```

### Using Query Builder

```php
use Database\QueryBuilder;
use Database\Connection;

$conn = Connection::getInstance()->getConnection();
$builder = new QueryBuilder($conn, 'tbl_ticket');

$tickets = $builder->where('status', 'pending')
                   ->where('priority', 'high')
                   ->orderBy('created_at', 'DESC')
                   ->limit(10)
                   ->get();
```

## Migration Guide

### From Old System

The system maintains backward compatibility. Old endpoints continue to work while new code uses the refactored structure.

### Migrating Existing Code

1. **Replace direct database queries** with QueryBuilder or Repository
2. **Move business logic** from PHP files to Services
3. **Use Controllers** for API endpoints
4. **Replace error_log()** with Logger service
5. **Add CSRF protection** to all forms

## Security

- ✅ Password hashing (bcrypt)
- ✅ CSRF protection
- ✅ Prepared statements (SQL injection prevention)
- ✅ Input sanitization
- ✅ Session management
- ✅ Environment-based configuration

## Development

### Running Tests

```bash
composer test
```

### Code Style

```bash
composer cs-fix
```

### Static Analysis

```bash
composer stan
```

## Troubleshooting

### Composer autoload not working

Run:
```bash
composer dump-autoload
```

### Database connection errors

Check `.env` file and ensure database credentials are correct.

### CSRF token errors

Ensure CSRF middleware is properly initialized and tokens are included in forms.

## License

Proprietary - Interconnect Solutions Company

## Support

For issues and questions, contact the development team.
