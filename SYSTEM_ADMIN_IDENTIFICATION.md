# System Admin Identification Guide

## Current Architecture: 1 Platform-Level Admin

**There is ONE system admin** responsible for managing the entire platform:

- **Role**: SYSTEM_ADMIN
- **Firm ID**: NULL (not tied to any specific firm)
- **Credentials**: admin@legal-saas.com / password
- **Responsibilities**:
  - Create and manage law firms
  - Create and manage subscriptions
  - View all documents across all firms
  - Manage user accounts
  - Access platform-wide settings

---

## Get the Platform Admin

### Method 1: Direct Query

```php
// Get THE platform admin
$platformAdmin = User::where('role', 'SYSTEM_ADMIN')
                     ->where('firm_id', null)
                     ->first();

// Result:
// User {
//   id: 1,
//   name: "Platform Admin",
//   email: "admin@legal-saas.com",
//   role: "SYSTEM_ADMIN",
//   firm_id: null
// }
```

### Method 2: Using Helper

```php
$user = Auth::user();

if ($user->isPlatformAdmin()) {
    echo "This is THE platform admin";
    echo "Can manage all firms and subscriptions";
}
```

### Method 3: In Controller

```php
class LawFirmController extends BaseApiController
{
    public function index()
    {
        // Check if user is platform admin
        $authError = $this->authorizePlatformAdmin();
        if ($authError) {
            return $authError; // Only platform admin can view all firms
        }
        
        $firms = LawFirm::all();
        return ApiResponse::success($firms);
    }
}
```

---

## Testing

### Login as Platform Admin

```bash
POST /api/v1/auth/login
{
  "email": "admin@legal-saas.com",
  "password": "password"
}

# Response includes token
```

### Get Profile

```bash
GET /api/v1/auth/me
Authorization: Bearer {token}

# Response shows firm_id: null and is_platform_admin: true
```

### Manage Firms

```bash
# Only platform admin can do this

GET /api/v1/firms
Authorization: Bearer {token}
# Returns all firms

POST /api/v1/firms
Authorization: Bearer {token}
{
  "name": "New Law Firm",
  "subscription_id": 1
}
# Creates new firm

PUT /api/v1/firms/1
Authorization: Bearer {token}
{
  "name": "Updated Firm Name",
  "status": "active"
}
# Updates firm

DELETE /api/v1/firms/1
Authorization: Bearer {token}
# Deletes firm
```

---

## In TinkerPHP

```bash
php artisan tinker

# Verify there's only ONE system admin
User::where('role', 'SYSTEM_ADMIN')->count();
# Result: 1

# Get the admin
$admin = User::where('role', 'SYSTEM_ADMIN')->first();
# Result: Platform Admin with firm_id: null

# Check helper
$admin->isPlatformAdmin();
# Result: true

# Get all firms this admin can manage
$admin->lawFirms;
# Shows all firms (empty - they don't "belong" to any)
```

---

## Future: Multiple Platform Admins

If later you need multiple platform-level admins, simply register them:

```php
// Register additional platform admin
POST /api/v1/auth/register
{
  "name": "Co-Administrator",
  "email": "co-admin@legal-saas.com",
  "password": "SecurePassword123",
  "role": "SYSTEM_ADMIN"
  // No firm_id required - defaults to NULL
}
```

Both admins would have full platform access.

---

## Future: Firm-Specific Admins

The code supports adding **firm-specific admins** later without changes:

```php
// Future: If you add this capability
$admin = User::create([
    'name': 'Admin for Firm 1',
    'email': 'firm1-admin@saas.com',
    'role': 'SYSTEM_ADMIN',
    'firm_id': 1  // Would be tied to Firm 1 only
]);

// Helper would then work:
$admin->isFirmSystemAdmin();  // true
$admin->isSystemAdminForFirm(1);  // true
$admin->isSystemAdminForFirm(2);  // false
```

The helper methods are ready whenever you need them!



