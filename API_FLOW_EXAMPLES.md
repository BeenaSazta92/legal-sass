# API Authentication & Firm Management Flow

## Current Setup: 1 Platform Admin

**There is ONE platform-level system admin:**
- **Email**: admin@legal-saas.com
- **Password**: password
- **Firm ID**: NULL (manages all firms, not tied to any specific firm)
- **Access**: Can create/manage all firms and subscriptions

---

## Registration Flow

### Scenario 1: Login as Platform Admin

**Request:**
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "admin@legal-saas.com",
  "password": "password"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Platform Admin",
      "email": "admin@legal-saas.com",
      "role": "SYSTEM_ADMIN",
      "firm_id": null
    },
    "token": "1|abcdef1234567890..."
  }
}
```

---

### Scenario 2: Create Admin for a Firm

**Request:**
```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "Firm Admin - Johnson Legal",
  "email": "admin@johnson-law.com",
  "password": "SecurePassword123",
  "role": "ADMIN",
  "firm_id": 2
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 19,
      "name": "Firm Admin - Johnson Legal",
      "email": "admin@johnson-law.com",
      "role": "ADMIN",
      "firm_id": 2,
      "created_at": "2026-03-17T10:31:00Z"
    },
    "token": "2|zyxwvu9876543210..."
  }
}
```

---

### Scenario 3: Error - Cannot Register Another Platform Admin with firm_id

**Request:**
```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "Another Admin",
  "email": "another@saas.com",
  "password": "SecurePassword123",
  "role": "SYSTEM_ADMIN",
}
```

**Response (201 Created, but firm_id is forced to NULL):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 50,
      "name": "Another Admin",
      "email": "another@saas.com",
      "role": "SYSTEM_ADMIN",
      "firm_id": null,  // ← Automatically set to NULL
      "created_at": "2026-03-17T10:32:00Z"
    },
    "token": "50|qwerty1234567890..."
  }
}
```

---



---

## Get Current User Profile

### Request:
```http
GET /api/v1/auth/me
Authorization: Bearer 1|abcdef1234567890...
```

### Response - Platform Admin:
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
      "is_platform_admin": true,
      "is_firm_system_admin": false,
      "is_firm_admin": false
    }
  }
}
```

### Response - Firm Admin:
```json
{
  "success": true,
  "message": "User profile retrieved successfully",
  "data": {
    "user": {
      "id": 19,
      "name": "Firm Admin - Johnson Legal",
      "email": "admin@johnson-law.com",
      "role": "ADMIN",
      "firm_id": 2,
      "created_at": "2026-03-17T..."
    },
    "context": {
      "role": "ADMIN",
      "firm_id": 2,
      "is_platform_admin": false,
      "is_firm_system_admin": false,
      "is_firm_admin": true
    }
  }
}
```

---

## Firm Management Endpoints

### Get All Firms (Platform Admin Only)

**Request:**
```http
GET /api/v1/firms
Authorization: Bearer {token_for_platform_admin}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Law firms retrieved successfully",
  "data": {
    "firms": [
      {
        "id": 1,
        "name": "Smith & Associates",
        "subscription_id": 1,
        "status": "active",
        "created_at": "2026-03-17T..."
      },
      {
        "id": 2,
        "name": "Johnson Legal",
        "subscription_id": 2,
        "status": "active",
        "created_at": "2026-03-17T..."
      },
      {
        "id": 3,
        "name": "Parker & Co",
        "subscription_id": 3,
        "status": "active",
        "created_at": "2026-03-17T..."
      }
    ]
  }
}
```

### Firm Admin Tries to List All Firms (Unauthorized)

**Request:**
```http
GET /api/v1/firms
Authorization: Bearer {token_for_firm_admin}
```

**Response (403 Forbidden):**
```json
{
  "success": false,
  "message": "You are not authorized to perform this action"
}
```

---

### Create New Firm (Platform Admin Only)

**Request:**
```http
POST /api/v1/firms
Authorization: Bearer {token_for_platform_admin}
Content-Type: application/json

{
  "name": "New Law Firm LLC",
  "subscription_id": 1
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Law firm created successfully",
  "data": {
    "firm": {
      "id": 4,
      "name": "New Law Firm LLC",
      "subscription_id": 1,
      "status": "active",
      "created_at": "2026-03-17T10:45:00Z"
    }
  }
}
```

---

### Update Firm (Platform Admin Only)

**Request:**
```http
PUT /api/v1/firms/2
Authorization: Bearer {token_for_platform_admin}
Content-Type: application/json

{
  "name": "Johnson Legal - Updated",
  "status": "active"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Law firm updated successfully",
  "data": {
    "firm": {
      "id": 2,
      "name": "Johnson Legal - Updated",
      "subscription_id": 2,
      "status": "active"
    }
  }
}
```

---

### Delete Firm (Platform Admin Only)

**Request:**
```http
DELETE /api/v1/firms/3
Authorization: Bearer {token_for_platform_admin}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Law firm deleted successfully"
}
```

---

## Authorization Summary

| Action | Platform Admin | Firm Admin | Lawyer | Client |
|--------|---|---|---|---|
| List All Firms | ✅ | ❌ | ❌ | ❌ |
| View Firm Details | ✅ | ❌ | ❌ | ❌ |
| Create Firm | ✅ | ❌ | ❌ | ❌ |
| Update Firm | ✅ | ❌ | ❌ | ❌ |
| Delete Firm | ✅ | ❌ | ❌ | ❌ |
| Create Subscription | ✅ | ❌ | ❌ | ❌ |

---

## How to Test with Real Requests

### Using cURL

```bash
# 1. Login as platform admin
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@legal-saas.com",
    "password": "password"
  }' | jq '.data.token'

# Copy the token output

# 2. Get profile
curl -X GET http://localhost/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# 3. List all firms
curl -X GET http://localhost/api/v1/firms \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# 4. Create a new firm
curl -X POST http://localhost/api/v1/firms \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Legal Group",
    "subscription_id": 1
  }'

# 5. Update a firm
curl -X PUT http://localhost/api/v1/firms/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Firm Name",
    "status": "active"
  }'

# 6. Delete a firm
curl -X DELETE http://localhost/api/v1/firms/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Using Postman

1. **Login as Platform Admin**
   - Method: POST
   - URL: `{{baseUrl}}/api/v1/auth/login`
   - Body: Raw JSON
   ```json
   {
     "email": "admin@legal-saas.com",
     "password": "password"
   }
   ```
   - Save the `token` from response

2. **Set Bearer Token**
   - Authorization Tab → Type: Bearer Token
   - Token: Paste the token you got above

3. **List Firms** 
   - Method: GET
   - URL: `{{baseUrl}}/api/v1/firms`
   - Should return all 3 firms

4. **Create Firm**
   - Method: POST
   - URL: `{{baseUrl}}/api/v1/firms`
   - Body: Raw JSON
   ```json
   {
     "name": "New Firm Name",
     "subscription_id": 1
   }
   ```

5. **Test Non-Admin Access**
   - Create an ADMIN user for a firm
   - Login as that admin
   - Try GET `/api/v1/firms`
   - Should return 403 Forbidden

---




