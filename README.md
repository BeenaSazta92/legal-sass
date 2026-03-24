# Legal SaaS Platform - Multi-Tenant Document Sharing

A multi-tenant SaaS platform built with Laravel that enables law firms to securely manage and share documents with their clients.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Database Design](#database-design)
3. [Security Decisions](#security-decisions)
4. [Multi-Tenancy Strategy](#multi-tenancy-strategy)
5. [Subscription Limits Enforcement](#subscription-limits-enforcement)
6. [API Documentation](#api-documentation)
7. [Setup Instructions](#setup-instructions)
8. [Scalability Considerations](#scalability-considerations)
9. [Future Enhancements](#future-enhancements)

---

## Architecture Overview

### Directory Structure

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           └── V1/                    # Version 1 API Controllers
│               ├── AuthController.php
│               └── LawFirmController.php
                └── DocumentController.php

├── Models/
│   ├── User.php
│   ├── LawFirm.php
│   ├── Subscription.php
│   ├── Document.php
│   ├── DocumentShare.php
│   └── AuditLog.php

database/
├── migrations/                      # All schema definitions
├── factories/
│   ├── UserFactory.php
│   └── LawFirmFactory.php
└── seeders/
    ├── SubscriptionSeeder.php
    └── DatabaseSeeder.php

routes/
└── api.php                         # API routes (versioned)
```

### Versioning Strategy

The API is structured to support multiple versions easily:
- **Current Version**: `api/v1/`
- **Future Versions**: Create new controllers in `Api/V2/`, `Api/V3/`, etc.
- **Route Organization**: All versions are defined in `routes/api.php` with clear prefixes

**Benefits:**
- Backward compatibility maintained
- Old versions continue to work while new features are in v2/v3
- Easy A/B testing and gradual migrations
- Clear separation of concerns

---

## Database Design

### Entity Relationship Diagram

```
subscriptions
    ↓ (one-to-many)
law_firms
    ├→ users (one-to-many)
    ├→ documents (one-to-many)
    ├→ document_shares (one-to-many)
    └→ audit_logs (one-to-many)

users
    ├→ documents (owns as owner_id)
    ├→ document_shares (shared with via shared_with_user_id)
    └→ audit_logs (one-to-many)

documents
    ├→ owner (belongs to user)
    ├→ firm (belongs to law_firm)
    ├→ document_shares
    └→ sharedWithUsers (many-to-many via document_shares)

document_shares
    ├→ document
    ├→ shared_with_user
    └→ firm (denormalized for query efficiency)

audit_logs
    ├→ user
    ├→ firm
    └→ entity tracking (polymorphic-like with entity_type)
```

### Key Design Decisions

1. **Foreign Key Constraints**: All tables use `onDelete('cascade')` for proper cleanup
2. **Firm ID Denormalization**: 
   - Included in `document_shares` and `audit_logs` for efficient queries
   - Allows filtering by firm without joins
   - Improves query performance for multi-tenant scenarios

3. **Enum Types for Roles**: 
   - Database-level validation with ENUM
   - Reduces invalid data entry
   - Values: `SYSTEM_ADMIN`, `ADMIN`, `LAWYER`, `CLIENT`

4. **Nullable firm_id for SYSTEM_ADMIN**:
   - System admins can exist without being assigned to a firm
   - Enables platform-wide operations

---

## Security Decisions

### Authentication & Authorization

1. **Laravel Sanctum for API Authentication**
   - Token-based authentication for stateless API
   - Each token represents a session
   - Tokens stored in `personal_access_tokens` table
   - Support for token expiration (configurable in `config/sanctum.php`)

2. **Password Security**
   - Passwords hashed using bcrypt (configured in `.env`: `BCRYPT_ROUNDS=12`)
   - Hashing done automatically via Eloquent mutator
   - No plaintext passwords stored

3. **Role-Based Authorization**
   - Checked at controller level via `authorizeSystemAdmin()` method
   - Can be extended with middleware for route-level protection
   - Roles: SYSTEM_ADMIN, ADMIN, LAWYER, CLIENT

### Tenant Isolation

1. **Firm-Based Isolation**
   - All user operations scoped to their assigned firm
   - `firm_id` required for non-system-admin users
   - Middleware (future) will enforce tenant context

2. **Database-Level Constraints**
   - Foreign keys ensure data integrity
   - Cascading deletes prevent orphaned records
   - Enum types prevent invalid role assignments

3. **Query Scoping** (To implement in next phase)
   - All queries automatically scoped to user's firm
   - Prevent accidental data leakage
   - Middleware-based tenant identification

### File Upload Security (Future Implementation)

- Validation: File type, size
- Storage: Private S3 bucket or encrypted local storage
---

## Multi-Tenancy Strategy

### Tenant Identification

**Current Approach**: Database-level isolation
- Each user belongs to ONE firm via `firm_id`
- System admins have `firm_id = NULL`
- Every data operation is scoped to firm

**Future Considerations**:
- Domain-based routing: `firmname.legal-saas.com`
- Header-based tenant identification for mobile apps

### Data Isolation Layers

1. **Application Layer**
   ```php
   // Controllers check user's firm context
   if (Auth::user()->firm_id !== $document->firm_id) {
       abort(403); // Prevent cross-firm access
   }
   ```

2. **Database Layer** (To implement)
   ```php
   // Scope all queries to tenant
   Model::where('firm_id', Auth::user()->firm_id)
   ```

3. **Query Middleware** (Future)
   - Automatic query scoping
   - Prevents accidental queries across tenants
   - Applies to all Eloquent queries

### Subscription-Based Limits

Each firm has a `subscription_id` that defines:
- Maximum admins per firm
- Maximum lawyers per firm
- Maximum clients per firm
- Maximum documents per user

---

## Subscription Limits Enforcement

### Current Plans

```
┌─────────┬────────┬─────────┬─────────┬──────────────────────┐
│ Plan    │ Admins │ Lawyers │ Clients │ Docs per User        │
├─────────┼────────┼─────────┼─────────┼──────────────────────┤
│ Free    │ 1      │ 2       │ 10      │ 20                   │
│ Starter │ 2      │ 5       │ 50      │ 100                  │
│ Pro     │ 5      │ 20      │ 200     │ 500                  │
└─────────┴────────┴─────────┴─────────┴──────────────────────┘
```

### Enforcement Points

1. **User Registration** (Future Controller: `UserController`)
   ```php
   // Before creating new user, check firm's subscription limits
   if (LawFirm::find($firm_id)->users()->where('role', $role)->count() >= $limit) {
       throw new SubscriptionLimitException();
   }
   ```

2. **Document Upload** (Future Controller: `DocumentController`)
   ```php
   // Check documents per user limit
   if ($user->documents()->count() >= $subscription->max_documents_per_user) {
       throw new DocumentLimitException();
   }
   ```

### Future Enforcement

- Real-time limit checking on API endpoints
- Graceful error messages (`422 Unprocessable Entity`)
- Upgrade prompts in response body
- Analytics dashboard showing limit usage

---

## API Documentation

### Base URL
```
http://localhost:8000/api/v1
```

### Authentication
All protected endpoints require bearer token:
```
Authorization: Bearer {token}
```

---

### Authentication Endpoints

#### 1. Register User
```http
POST /auth/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "role": "ADMIN",
    "firm_id": 1
}

Response (201):
{
    "message": "User registered successfully",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "ADMIN",
        "firm_id": 1
    },
    "token": "1|ABC123..."
}
```

#### 2. Login
```http
POST /auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}

Response (200):
{
    "message": "Logged in successfully",
    "user": {...},
    "token": "1|ABC123..."
}
```

#### 3. Get Current User
```http
GET /auth/me
Authorization: Bearer {token}

Response (200):
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "ADMIN",
    "firm_id": 1,
    "firm": {
        "id": 1,
        "name": "Smith & Associates",
        "status": "active",
        "subscription_id": 1
    }
}
```

#### 4. Logout
```http
POST /auth/logout
Authorization: Bearer {token}

Response (200):
{
    "message": "Logged out successfully"
}
```

---

### Law Firm Endpoints (System Admin Only)

#### 1. List All Firms
```http
GET /firms?page=1
Authorization: Bearer {system_admin_token}

Response (200):
{
    "data": [...],
    "links": {...},
    "meta": {...}
}
```

#### 2. Create Firm
```http
POST /firms
Authorization: Bearer {system_admin_token}
Content-Type: application/json

{
    "name": "New Law Firm Inc",
    "subscription_id": 2
}

Response (201):
{
    "message": "Law firm created successfully",
    "data": {
        "id": 1,
        "name": "New Law Firm Inc",
        "subscription_id": 2,
        "status": "active",
        "created_at": "2026-03-17T..."
    }
}
```

#### 3. Get Firm Details
```http
GET /firms/{id}
Authorization: Bearer {system_admin_token}

Response (200):
{
    "id": 1,
    "name": "New Law Firm Inc",
    "subscription_id": 2,
    "status": "active",
    "subscription": {...},
    "users": [...],
    "documents": [...]
}
```

#### 4. Update Firm
```http
PUT /firms/{id}
Authorization: Bearer {system_admin_token}
Content-Type: application/json

{
    "status": "suspended"
}

Response (200):
{
    "message": "Law firm updated successfully",
    "data": {...}
}
```

#### 5. Delete Firm
```http
DELETE /firms/{id}
Authorization: Bearer {system_admin_token}

Response (200):
{
    "message": "Law firm deleted successfully"
}
```

---

## Setup Instructions

### Prerequisites
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js (optional, for frontend)

### Installation

1. **Clone and Install Dependencies**
```bash
cd legal-saas
composer install
npm install
```

2. **Environment Setup**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Database Configuration**
Edit `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=legal_sass
DB_USERNAME=root
DB_PASSWORD=
```

4. **Run Migrations**
```bash
php artisan migrate:fresh --seed
```

5. **Start Development Server**
```bash
php artisan serve
```

API will be available at `http://localhost:8000/api/v1`

### Test Credentials

After seeding:

**System Admin**
```
Email: admin@legal-saas.com
Password: password
Role: SYSTEM_ADMIN
```

**Firm Admin** (auto-generated)
- Email: Various (check database)
- Password: password
- Role: ADMIN
- Firm: Assigned to one of 3 sample firms

---

## Scalability Considerations

### Current Optimizations

1. **Database Indexing** (To implement in next phase)
   ```php
   // Add indexes in migration
   $table->index('firm_id');           // Filter by firm
   $table->index(['firm_id', 'role']); // Compound index for user lookups
   $table->index('email');             // Unique email checks
   ```

2. **Query Pagination**
   - All list endpoints return paginated results
   - Default: 15 items per page
   - Reduces memory usage

3. **Eager Loading** (To enhance)
   ```php
   // Prevent N+1 queries
   LawFirm::with('subscription', 'users', 'documents')->get()
   ```

### Future Scaling Strategies

1. **Caching**
   - Cache subscription plans (rarely change)
   - Cache firm details per request
   - Use Redis for distributed cache

2. **Database Optimization**
   ```sql
   -- Add indexes for frequently queried columns
   CREATE INDEX idx_users_firm_role ON users(firm_id, role);
   CREATE INDEX idx_documents_firm ON documents(firm_id, created_at);
   CREATE INDEX idx_audit_logs_firm ON audit_logs(firm_id, created_at);
   ```

3. **API Rate Limiting**
   - Prevent abuse
   - Per-user rate limits
   - Configurable per endpoint

4. **Storage Architecture**
   - Local: Development only
   - S3: Production (scalable, reliable)
   - CDN: Document delivery (future)

5. **Queue Processing**
   - Document uploads → Queue job
   - Email notifications → Queue
   - Audit logging → Queue
   - Configure in `.env`: `QUEUE_CONNECTION=database`

6. **Search Functionality** (Future)
   - Full-text search on documents
   - Elasticsearch for complex queries
   - Solr alternative

---

## Future Enhancements

### Phase 2: User Management
- [ ] User controller with role-based access
- [ ] User creation with subscription limit enforcement
- [ ] User deactivation/deletion
- [ ] User profile management

### Phase 3: Document Management
- [ ] Document upload with validation
- [ ] Document metadata extraction
- [ ] File storage to S3
- [ ] Document versioning

### Phase 4: Sharing & Collaboration
- [ ] Share documents with permission levels
- [ ] Bulk sharing
- [ ] Revoke access
- [ ] Shared document listing


---

## Configuration

### Important Files

- `config/app.php` - App configuration
- `config/auth.php` - Authentication guards
- `config/sanctum.php` - API token configuration
- `.env` - Environment variables

### Key Environment Variables

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=legal_sass
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:8000,127.0.0.1:8000
BCRYPT_ROUNDS=12
```

---

## Testing

### Run Tests
```bash
php artisan test
```

### Key Test Areas
- [ ] Authentication flows
- [ ] Authorization (role-based)
- [ ] Tenant isolation
- [ ] Subscription limits
- [ ] Document operations

---

## Security Checklist

- [x] Password hashing (bcrypt)
- [x] Authentication via Sanctum tokens
- [x] Role-based authorization (SYSTEM_ADMIN checks)
- [x] Foreign key constraints
- [x] Enum validation for roles
- [ ] CORS configuration
- [ ] Rate limiting
- [ ] Input validation (partial)
- [ ] SQL injection prevention (Eloquent ORM)
- [ ] XSS prevention (API, no rendered HTML)
- [ ] CSRF protection (for web routes)
- [ ] Audit logging infrastructure

---

## Contributing

1. Create feature branch from `develop`
2. Follow existing code structure
3. Add tests for new features
4. Version API endpoints for backward compatibility
5. Update README with API changes

---

## License

Proprietary - All rights reserved

---

## Support

For issues or questions, contact the development team.
