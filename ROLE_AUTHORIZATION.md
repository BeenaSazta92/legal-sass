# Role & Permission Architecture

## Architecture Decision: Single User Table vs. Multiple Tables

### ✅ We Use: Single `users` Table

**Why NOT separate tables?**

| Issue | Separate Tables | Single Table |
|-------|-----------------|--------------|
| Auth complexity | 2 login queries | 1 login query ✅ |
| Token management | Multiple tokens | Single token ✅ |
| Code duplication | High | Low ✅ |
| User context | Fragmented | Clear ✅ |
| Maintenance | Hard | Easy ✅ |
| Industry standard | No | Yes ✅ |

**Best Practice**: Laravel and all modern frameworks use a single user table with role+permission system.

---

## System Design

```
┌─────────────────────────────────────────┐
│           users TABLE                   │
├──────────┬─────────┬──────────┬─────────┤
│    id    │  email  │ role     │ firm_id │
├──────────┼─────────┼──────────┼─────────┤
│    1     │ sys...  │ SYSTEM_  │  NULL   │ ← Platform Level
│          │         │ ADMIN    │         │
├──────────┼─────────┼──────────┼─────────┤
│    5     │ adm...  │ ADMIN    │   1     │ ← Firm Level
│    6     │ lw1...  │ LAWYER   │   1     │   (Firm 1)
│    7     │ cli...  │ CLIENT   │   1     │
├──────────┼─────────┼──────────┼─────────┤
│    10    │ adm...  │ ADMIN    │   2     │ ← Firm Level
│    11    │ lw2...  │ LAWYER   │   2     │   (Firm 2)
│    12    │ cli...  │ CLIENT   │   2     │
└──────────┴─────────┴──────────┴─────────┘

SYSTEM_ADMIN (firm_id = NULL)
├─ Can: Manage all law firms
├─ Can: Manage subscription plans
├─ Can: View all documents
└─ Cannot: Own documents or be a lawyer/client

FIRM LEVEL USERS (firm_id = 1,2,3...)
├─ ADMIN
│  ├─ Can: Manage users in their firm
│  ├─ Can: View all firm documents
│  └─ Cannot: Access other firm's data
├─ LAWYER
│  ├─ Can: Upload documents
│  ├─ Can: Share documents
│  └─ Cannot: Manage users
└─ CLIENT
   ├─ Can: View shared documents
   └─ Cannot: Upload or manage
```

---

## User Model - Role Helper Methods

All methods in `app/Models/Traits/HasRoleAndPermissions.php`:

```php
// Check role type
$user->isSystemAdmin()        // bool
$user->isFirmAdmin()          // bool
$user->isLawyer()             // bool
$user->isClient()             // bool

// Check firm access
$user->belongsToFirm(1)       // Does user belong to firm 1?
$user->canManageFirm(1)       // Can user manage firm 1?
$user->canManageUsersInFirm(2) // Can manage users in firm 2?
$user->canAccessDocument(3)    // Can access document from firm 3?

// Get user context
$user->getContext()           // Returns: ['role', 'firm_id', 'is_system_admin', ...]
```

### Example Usage

```php
$user = Auth::user();

// Check if system admin
if ($user->isSystemAdmin()) {
    // Access all platform operations
}

// Check if can manage a firm
if ($user->canManageFirm($firmId)) {
    // Can update/delete this firm
}

// Check document access
if (!$user->canAccessDocument($document->firm_id)) {
    abort(403, 'No access to this document');
}

// Get what the user is working with
$context = $user->getContext();
// Returns:
// {
//   "role": "ADMIN",
//   "firm_id": 1,
//   "is_system_admin": false,
//   "is_firm_admin": true
// }
```

---

## BaseApiController - Authorization Methods

All methods in `app/Http/Controllers/Api/BaseApiController.php`:

### Core Authorization Methods

```php
// Current user helpers
$this->currentUser()        // Get Auth::user()
$this->getCurrentFirmId()   // Get user's firm_id
$this->isSystemAdmin()      // Check if SYSTEM_ADMIN
$this->isFirmAdmin()        // Check if ADMIN role

// Authorization checks (return error response if unauthorized)
$auth = $this->authorizeSystemAdmin();
if ($auth) return $auth;  // User is not system admin

$auth = $this->authorizeFirmAdmin($firmId);
if ($auth) return $auth;  // User is not firm admin of specified firm

$auth = $this->authorizeFirmAccess($firmId);
if ($auth) return $auth;  // User cannot access firm resources
```

### Usage in Controllers

```php
class LawFirmController extends BaseApiController
{
    public function index()
    {
        try {
            // Check authorization
            $authError = $this->authorizeSystemAdmin();
            if ($authError) {
                return $authError;
            }

            // Now safe to proceed
            $firms = LawFirm::paginate(15);
            return ApiResponse::success($firms, 'Retrieved');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
```

---

## Authorization Flow Examples

### Scenario 1: System Admin Creates a Law Firm

```
USER: System Admin (id=1, firm_id=NULL, role=SYSTEM_ADMIN)
REQUEST: POST /api/v1/firms
         { "name": "New Firm", "subscription_id": 1 }

CONTROLLER:
1. Check: $this->authorizeSystemAdmin()
   → Auth::user()->isSystemAdmin() = true ✅
   
2. Create firm
   → LawFirm::create([...])
   
3. Return success response

RESULT: ✅ New firm created
```

### Scenario 2: Firm Admin Tries to Access Another Firm

```
USER: Admin (id=5, firm_id=1, role=ADMIN)
REQUEST: GET /api/v1/firms/2
         
CONTROLLER:
1. Check: $this->authorizeFirmAdmin(2)
   → $user->isFirmAdmin() = true ✅
   → $user->belongsToFirm(2) = false ❌
   
2. Return error: "You can only access your own firm"

RESULT: ❌ 403 Forbidden
```

### Scenario 3: Client Tries to Upload Document

```
USER: Client (id=7, firm_id=1, role=CLIENT)
REQUEST: POST /api/v1/documents
         { "title": "...", "file": "..." }

CONTROLLER:
1. Check role (in DocumentController)
   → $user->isLawyer() = false ❌
   
2. Return error: "Only lawyers can upload documents"

RESULT: ❌ 403 Forbidden
```

### Scenario 4: Lawyer Accesses Document from Their Firm

```
USER: Lawyer (id=6, firm_id=1, role=LAWYER)
REQUEST: GET /api/v1/documents/1
         (Document from firm_id=1)

CONTROLLER:
1. Check: $this->authorizeFirmAccess($document->firm_id)
   → $user->canAccessDocument(1) = true ✅
   
2. Return document

RESULT: ✅ Document returned
```

---

## Admin/Me Endpoint Response

Shows user's complete context:

**Request:**
```
GET /api/v1/auth/me
Authorization: Bearer {token}
```

**Response (System Admin):**
```json
{
  "success": true,
  "message": "User profile retrieved successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "Platform Admin",
      "email": "admin@legal-saas.com",
      "role": "SYSTEM_ADMIN",
      "firm_id": null,
      "created_at": "2026-03-17T..."
    },
    "context": {
      "role": "SYSTEM_ADMIN",
      "firm_id": null,
      "is_system_admin": true,
      "is_firm_admin": false
    }
  }
}
```

**Response (Firm Admin):**
```json
{
  "success": true,
  "message": "User profile retrieved successfully",
  "data": {
    "user": {
      "id": 5,
      "name": "Firm Manager",
      "email": "admin@law-firm-1.com",
      "role": "ADMIN",
      "firm_id": 1,
      "created_at": "2026-03-17T..."
    },
    "context": {
      "role": "ADMIN",
      "firm_id": 1,
      "is_system_admin": false,
      "is_firm_admin": true
    }
  }
}
```

---

## Database Constraints Enforcing Roles

### Users Table
```sql
CREATE TABLE users (
  id ...
  role ENUM('SYSTEM_ADMIN', 'ADMIN', 'LAWYER', 'CLIENT')
  firm_id BIGINT UNSIGNED NULLABLE
  FOREIGN KEY (firm_id) REFERENCES law_firms(id) ON DELETE CASCADE
)
```

**Constraints:**
- `role` must be one of 4 values (DB level)
- `firm_id` can be NULL (for SYSTEM_ADMIN)
- If `firm_id` is set, foreign key ensures firm exists
- Cascading delete prevents orphaned users

### Application Level Validation

In **AuthController::register()**:
```php
$request->validate([
    'role' => 'required|in:SYSTEM_ADMIN,ADMIN,LAWYER,CLIENT',
    'firm_id' => 'required_unless:role,SYSTEM_ADMIN|exists:law_firms,id',
]);

// If role is SYSTEM_ADMIN, firm_id must be NULL
$user->firm_id = $role === 'SYSTEM_ADMIN' ? null : $firm_id;
```

---

## Key Takeaways

### ✅ One Table = Simplicity

1. **Single authentication path**
   - One login query
   - One token mechanism
   - One session management

2. **Clear separation by role+firm_id**
   - SYSTEM_ADMIN: role=SYSTEM_ADMIN, firm_id=NULL
   - Firm users: role=ADMIN/LAWYER/CLIENT, firm_id=1/2/3

3. **Easy to extend**
   - Add new roles by adding to ENUM
   - Add new permissions via traits
   - No new tables needed

4. **Better queryability**
   - Single WHERE clause: `where('firm_id', 1)`
   - No JOINs between user tables
   - Indexes more effective

### ❌ Never Do: Separate Tables

- Creates complexity
- Duplicates code
- Breaks authentication
- Wastes resources

---

## System Admin: Platform-Level Admin

Currently, there is **ONE system admin** at the platform level (firm_id = NULL):

```php
// Platform-level system admin
$admin->firm_id = NULL              // Not tied to any specific firm
$admin->role = 'SYSTEM_ADMIN'

// Responsibilities:
- Create and manage all law firms
- Create and manage subscriptions
- View all documents across all firms
- Manage user accounts across all firms
- Access platform-wide statistics and settings

// Cannot do:
- Own documents
- Upload files
- Be limited to one firm
```

**Example:**
```php
// Get the platform admin
$platformAdmin = User::where('role', 'SYSTEM_ADMIN')
                     ->where('firm_id', null)
                     ->first();

// Check with helper
if ($user->isPlatformAdmin()) {
    // This is THE platform admin
    // Can manage all firms and subscriptions
}
```

**Database:**
```sql
SELECT * FROM users WHERE role = 'SYSTEM_ADMIN' AND firm_id IS NULL;

-- Result:
-- id | name           | email               | role         | firm_id
-- ---|----------------|---------------------|--------------|--------
-- 1  | Platform Admin | admin@legal-saas.com| SYSTEM_ADMIN | NULL
```

---

## Firm-Level Admins: ADMIN Role

Each firm has **one or more ADMIN users** for local management:

```php
// Firm-level admin
$admin->firm_id = 1                // Tied to Firm 1
$admin->role = 'ADMIN'

// Responsibilities (within their firm):
- Create and manage users in their firm
- View and manage documents in their firm
- Manage sharing and permissions
- View firm-specific reports

// Cannot do:
- Create firms
- Manage other firms' data
- Access platform settings
```

**Database:**
```sql
SELECT * FROM users WHERE role = 'ADMIN' AND firm_id IS NOT NULL;

-- Result:
-- id | name        | email              | role  | firm_id
-- ---|-------------|-------------------|-------|--------
-- 5  | John Admin  | admin@firm1.com    | ADMIN | 1
-- 20 | Jane Admin  | admin@firm2.com    | ADMIN | 2
-- 35 | Bob Admin   | admin@firm3.com    | ADMIN | 3
```

---

## Future Scaling: From 1 to Multiple Platform Admins

**If in future you need multiple platform-level admins**, simply register them the same way:

```php
// Register additional platform admin (if needed later)
$user = User::create([
    'name' => 'Co-Administrator',
    'email' => 'co-admin@legal-saas.com',
    'password' => Hash::make('password'),
    'role' => 'SYSTEM_ADMIN',
    'firm_id' => null,  // Platform level
]);
```

Both admins would see all firms, create new subscriptions, etc.

---

## Firm-Specific System Admin: Reserved for Future

The architecture supports firm-specific system admins (role=SYSTEM_ADMIN, firm_id=1/2/3) for future use:

```php
// This code is ready but NOT USED YET
if ($user->isFirmSystemAdmin()) {
    // Would be used if you add per-firm admins later
}
```

When needed, you can transition to per-firm admins without code changes.

