# Firms & Subscriptions CRUD - Platform Admin Guide

## Overview

The platform-level SYSTEM_ADMIN has full control over:
- **Law Firms**: Create, read, update, delete law firms
- **Subscription Plans**: Create, read, update, delete subscription plans
- **Default Subscription**: Set which subscription is assigned to new firms by default

All operations require **platform admin authentication** (firm_id = NULL).

---

## Authentication

All endpoints require Bearer token authentication:

```bash
# Login as platform admin
POST /api/v1/auth/login
{
  "email": "admin@legal-saas.com",
  "password": "password"
}

# Use the returned token in Authorization header
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 1. Subscription Plans Management

### List All Subscription Plans

**GET** `/api/v1/subscriptions`

**Response:**
```json
{
  "success": true,
  "message": "Subscription plans retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Free",
        "max_admins": 1,
        "max_lawyers": 2,
        "max_clients": 10,
        "max_documents_per_user": 20,
        "created_at": "2026-03-17T...",
        "law_firms": [
          {
            "id": 1,
            "name": "Smith & Associates",
            "status": "active"
          }
        ]
      }
    ],
    "total": 3
  }
}
```

### Create New Subscription Plan

**POST** `/api/v1/subscriptions`

**Request:**
```json
{
  "name": "Enterprise",
  "max_admins": 10,
  "max_lawyers": 50,
  "max_clients": 1000,
  "max_documents_per_user": 2000
}
```

**Response:**
```json
{
  "success": true,
  "message": "Subscription plan created successfully",
  "data": {
    "id": 4,
    "name": "Enterprise",
    "max_admins": 10,
    "max_lawyers": 50,
    "max_clients": 1000,
    "max_documents_per_user": 2000,
    "created_at": "2026-03-17T..."
  }
}
```

### Get Specific Subscription Plan

**GET** `/api/v1/subscriptions/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Subscription plan retrieved successfully",
  "data": {
    "id": 1,
    "name": "Free",
    "max_admins": 1,
    "max_lawyers": 2,
    "max_clients": 10,
    "max_documents_per_user": 20,
    "law_firms": [
      {
        "id": 1,
        "name": "Smith & Associates",
        "status": "active"
      }
    ]
  }
}
```

### Update Subscription Plan

**PUT** `/api/v1/subscriptions/{id}`

**Request:**
```json
{
  "name": "Free Plan",
  "max_clients": 15,
  "max_documents_per_user": 25
}
```

**Response:**
```json
{
  "success": true,
  "message": "Subscription plan updated successfully",
  "data": {
    "id": 1,
    "name": "Free Plan",
    "max_admins": 1,
    "max_lawyers": 2,
    "max_clients": 15,
    "max_documents_per_user": 25
  }
}
```

### Delete Subscription Plan

**DELETE** `/api/v1/subscriptions/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Subscription plan deleted successfully"
}
```

**Note:** Cannot delete subscriptions that are assigned to law firms.

---

## 2. Default Subscription Management

### Set Default Subscription

**POST** `/api/v1/subscriptions/{id}/set-default`

**Response:**
```json
{
  "success": true,
  "message": "Default subscription updated successfully",
  "data": {
    "subscription": {
      "id": 2,
      "name": "Starter",
      "max_admins": 2,
      "max_lawyers": 5,
      "max_clients": 50,
      "max_documents_per_user": 100
    },
    "default_subscription_id": 2
  }
}
```

### Get Current Default Subscription

**GET** `/api/v1/subscriptions/default`

**Response:**
```json
{
  "success": true,
  "message": "Default subscription retrieved successfully",
  "data": {
    "subscription": {
      "id": 2,
      "name": "Starter",
      "max_admins": 2,
      "max_lawyers": 5,
      "max_clients": 50,
      "max_documents_per_user": 100
    },
    "default_subscription_id": 2
  }
}
```

---

## 3. Law Firms Management

### List All Law Firms

**GET** `/api/v1/firms`

**Response:**
```json
{
  "success": true,
  "message": "Law firms retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Smith & Associates",
        "subscription_id": 1,
        "status": "active",
        "created_at": "2026-03-17T...",
        "subscription": {
          "id": 1,
          "name": "Free",
          "max_admins": 1,
          "max_lawyers": 2,
          "max_clients": 10,
          "max_documents_per_user": 20
        },
        "users": [
          {
            "id": 2,
            "name": "John Admin",
            "email": "admin@smith.com",
            "role": "ADMIN"
          }
        ]
      }
    ],
    "total": 3
  }
}
```

### Create New Law Firm

**POST** `/api/v1/firms`

**Request (with specific subscription):**
```json
{
  "name": "New Law Firm LLC",
  "subscription_id": 2
}
```

**Request (uses default subscription):**
```json
{
  "name": "Another Law Firm"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Law firm created successfully",
  "data": {
    "id": 4,
    "name": "New Law Firm LLC",
    "subscription_id": 2,
    "status": "active",
    "created_at": "2026-03-17T...",
    "subscription": {
      "id": 2,
      "name": "Starter",
      "max_admins": 2,
      "max_lawyers": 5,
      "max_clients": 50,
      "max_documents_per_user": 100
    }
  }
}
```

### Get Specific Law Firm

**GET** `/api/v1/firms/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Law firm retrieved successfully",
  "data": {
    "id": 1,
    "name": "Smith & Associates",
    "subscription_id": 1,
    "status": "active",
    "subscription": {
      "id": 1,
      "name": "Free",
      "max_admins": 1,
      "max_lawyers": 2,
      "max_clients": 10,
      "max_documents_per_user": 20
    },
    "users": [
      {
        "id": 2,
        "name": "John Admin",
        "email": "admin@smith.com",
        "role": "ADMIN"
      }
    ],
    "documents": []
  }
}
```

### Update Law Firm

**PUT** `/api/v1/firms/{id}`

**Request:**
```json
{
  "name": "Smith & Johnson Associates",
  "subscription_id": 3,
  "status": "active"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Law firm updated successfully",
  "data": {
    "id": 1,
    "name": "Smith & Johnson Associates",
    "subscription_id": 3,
    "status": "active"
  }
}
```

### Delete Law Firm

**DELETE** `/api/v1/firms/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Law firm deleted successfully"
}
```

---

## 4. Complete Workflow Example

### Step 1: Login as Platform Admin
```bash
POST /api/v1/auth/login
{
  "email": "admin@legal-saas.com",
  "password": "password"
}
# Get token: eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

### Step 2: Create New Subscription Plan
```bash
POST /api/v1/subscriptions
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
{
  "name": "Premium",
  "max_admins": 8,
  "max_lawyers": 30,
  "max_clients": 500,
  "max_documents_per_user": 1000
}
# Response: Subscription created with ID 4
```

### Step 3: Set as Default Subscription
```bash
POST /api/v1/subscriptions/4/set-default
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
# Response: Default subscription set to Premium
```

### Step 4: Create New Law Firm (uses default subscription)
```bash
POST /api/v1/firms
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
{
  "name": "Premium Law Group"
}
# Response: Firm created with subscription_id = 4 (Premium)
```

### Step 5: Update Firm Subscription
```bash
PUT /api/v1/firms/4
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
{
  "subscription_id": 2
}
# Response: Firm updated to use Starter subscription
```

---

## 5. Error Responses

### Unauthorized Access (Not Platform Admin)
```json
{
  "success": false,
  "message": "You are not authorized to perform this action"
}
```

### Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "max_admins": ["The max admins must be at least 1."]
  }
}
```

### Cannot Delete Subscription in Use
```json
{
  "success": false,
  "message": "Cannot delete subscription plan that is currently assigned to law firms"
}
```

### Firm Not Found
```json
{
  "success": false,
  "message": "Not found"
}
```

---

## 6. Testing with cURL

```bash
# 1. Login
TOKEN=$(curl -s -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@legal-saas.com","password":"password"}' | jq -r '.data.token')

# 2. List subscriptions
curl -X GET http://localhost/api/v1/subscriptions \
  -H "Authorization: Bearer $TOKEN"

# 3. Create subscription
curl -X POST http://localhost/api/v1/subscriptions \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"VIP","max_admins":15,"max_lawyers":100,"max_clients":2000,"max_documents_per_user":5000}'

# 4. Set as default
curl -X POST http://localhost/api/v1/subscriptions/4/set-default \
  -H "Authorization: Bearer $TOKEN"

# 5. Create firm (uses default)
curl -X POST http://localhost/api/v1/firms \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"VIP Law Firm"}'

# 6. List firms
curl -X GET http://localhost/api/v1/firms \
  -H "Authorization: Bearer $TOKEN"
```

---

## 7. Database Schema

### Subscriptions Table
```sql
CREATE TABLE subscriptions (
  id BIGINT PRIMARY KEY,
  name VARCHAR(255) UNIQUE,
  max_admins INT,
  max_lawyers INT,
  max_clients INT,
  max_documents_per_user INT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### Law Firms Table
```sql
CREATE TABLE law_firms (
  id BIGINT PRIMARY KEY,
  name VARCHAR(255),
  subscription_id BIGINT (FK to subscriptions),
  status ENUM('active', 'suspended'),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

---

## 8. Security Notes

- All endpoints require `auth:sanctum` middleware
- All endpoints check `isPlatformAdmin()` authorization
- Subscription deletion prevents removing plans in use
- Firm creation automatically assigns default subscription if none specified
- All operations are logged for audit purposes

---

## Next Steps

After implementing firms and subscriptions CRUD, you can move to:
1. **User Management** - Create users within firms with role validation
2. **Subscription Limits** - Enforce max_admins, max_lawyers, max_clients per firm
3. **Document Management** - Upload, share, and manage documents
4. **Audit Logging** - Track all platform and firm activities