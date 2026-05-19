# Ticketing System

A Laravel-based ticketing system with a two-level admin approval workflow, external webservice integration, and real-time notifications.

## Install and Usage

### Prerequisites

- PHP 8.4+
- Composer
- Node.js & npm
- SQLite (default) or MySQL

### Installation

1. **Clone the repository**
   ```bash
   git clone git@github.com:alibayat73/rahweb.git
   cd rahweb
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Setup environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run migrations and seeders**
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Build frontend assets**
   ```bash
   npm run build
   ```

6. **Start the development server**
   ```bash
   composer dev
   ```

   This starts:
   - Laravel server
   - Queue worker
   - Pail (log viewer)
   - Vite dev server

### Admin Credentials

Two admin accounts are created via the seeder (no registration):

| Role          | Email                  | Password   |
|---------------|------------------------|------------|
| Admin Level 1 | `admin_l1@example.com` | `password` |
| Admin Level 2 | `admin_l2@example.com` | `password` |

## Architecture

### Clean Architecture Structure

```
app/
├── Contracts/                    
│   ├── DeliveryClientInterface   # Webservice client contract
│   └── DeliveryResult            # Structure Delivery API calls
├── Domain/
│   ├── Enums/                    # PHP backed enums
│   │   ├── TicketStatus          # Ticket lifecycle states
│   │   ├── AdminRole             # User roles
│   │   └── Decision              # Approve/Reject decisions
│   └── Services/                 # Business logic
│       ├── TicketWorkflowService # State transitions
│       └── TicketDeliveryService
├── Infrastructure/
│   ├── Adapters/                 # Strategy/Adapter pattern
│       └── MockWebserviceAdapter # Implements DeliveryClientInterface
├── Http/
│   ├── Controllers/
│   ├── Requests/                 # Form request validation
│   └── Policies/                 # Authorization policies
├── Jobs/                         # Queue jobs
│   └── SendTicketToWebservice    # Retries every hour until success
├── Notifications/                # Email + DB notifications
│   ├── TicketApprovedNotification
│   └── TicketRejectedNotification
└── Models/                       # Eloquent models
    ├── User
    ├── Ticket
    ├── TicketDecision
    └── DeliveryAttempt
```

### Design Patterns Used

1. **Strategy/Adapter Pattern**: `DeliveryClientInterface` with `MockWebserviceAdapter` allows swapping webservice implementations without changing business logic.

2. **Enum-based State Management**: `TicketStatus` enum enforces valid state transitions with methods like `canBeApprovedByLevel1()`, `canBeApprovedByLevel2()`, and `isTerminal()`.

3. **Service Layer**: Domain services (`TicketWorkflowService`, `TicketDeliveryService`, etc.) encapsulate business logic separately from controllers.

4. **Dependency Injection**: All dependencies are bound in `AppServiceProvider` and injected via constructor.

### Ticket Workflow

```
User Submits → Pending
    ↓
Admin L1 Approves → Approved L1
    ↓
Admin L2 Approves → Approved L2 → Webservice Delivery
    ↓                              ↓
    Rejected L1/2             Success → Delivered
                              Failure → Delivery Failed (retry hourly)
```

### Database Schema

- **users**: Standard Laravel auth + `role` column (enum: `user`, `admin_l1`, `admin_l2`)
- **tickets**: `id`, `user_id`, `title`, `description`, `attachment_path`, `status`, `timestamps`
- **ticket_decisions**: `id`, `ticket_id`, `admin_id`, `decision`, `note`, `level`, `timestamps`
- **delivery_attempts**: `id`, `ticket_id`, `status`, `response_code`, `response_message`, `attempted_at`, `timestamps`

## Software Abilities

### User Features
- Register and login (via Laravel Fortify)
- Submit tickets with title, description, and required attachment (PDF or image)
- View own tickets and their status
- Receive email and in-app notifications on approval/rejection

### Admin Features
- View all tickets with filtering and search
- Approve or reject tickets with required notes
- Bulk approve multiple tickets at once
- Admin L1 handles pending tickets
- Admin L2 handles L1-approved tickets

### System Features
- Automatic webservice delivery after L2 approval
- Retry failed deliveries every hour until success
- Log all delivery attempts (success and failure) in database
- Email notifications for all approval/rejection events
- Database notifications stored for in-app viewing
- Queue-based job processing for reliable delivery

### Frontend
- Vue.js 3 with Inertia.js for SPA experience
- Responsive UI with Tailwind CSS v4
- shadcn-vue components
- Wayfinder-generated route helpers
- Tickets list with filtering and pagination
- Ticket detail with decision history and delivery logs
- Admin dashboard with bulk actions

## Assumptions

1. **Email Configuration**: Default mailer is `log` for development. Emails are written to `storage/logs/laravel.log`. Configure SMTP for production.

2. **File Storage**: Attachments are stored in `storage/app/public/tickets/`. Run `php artisan storage:link` to make them publicly accessible.

3. **Queue Worker**: The queue worker must be running (`php artisan queue:work`) for delivery retries to function. The `composer dev` command starts it automatically.

4. **Mock Webservice**: The `/api/mock-webservice` endpoint randomly returns 200 or 500. In production, replace `MockWebserviceAdapter` with a real API client.

5. **SQLite Default**: The application uses SQLite for tests and MySQL for the main DB. Configure them by updating `.env`.

6. **No User Registration for Admins**: Admin accounts are only created via seeder. Regular users can register through the UI.

7. **Required Notes**: Admin decisions require a note for audit trail purposes.

8. **Single Attachment**: Each ticket supports one attachment (PDF or image). Maximum file size: 10MB.

## Testing

Run the test suite:

```bash
php artisan test
```

Or with minimal output:

```bash
php artisan test --compact
```

### Test Coverage

- **Unit Tests**: State transitions, workflow validation, terminal states
- **Feature Tests**: Full ticket flow, approval/rejection, bulk approve, authorization, delivery success/failure, notification dispatch

## Code Quality

- **PHP Lint/Format**: `composer lint:check` / `composer lint`
- **Frontend Lint**: `npm run lint:check` / `npm run lint`
- **Frontend Format**: `npm run format`
- **Type Check**: `npm run types:check`
