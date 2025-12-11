# Blueprint Electricity API Documentation

## Overview

This API provides endpoints for electricity token purchases and customer verification. The API uses token-based authentication.

## Base URL

```
http://localhost:8000/api
```

(Replace with your actual server URL in production)

## Authentication

The API uses token-based authentication. To authenticate:

1. Login using `/api/auth/login` to get an API token
2. Include the token in the `Authorization` header: `Bearer {your_token}`

## Endpoints

### Health & Status

#### GET `/api/health`
Simple health check endpoint.

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2024-01-01T00:00:00+00:00",
  "service": "Blueprint Electricity API",
  "version": "1.0.0"
}
```

#### GET `/api/status`
Get detailed API status and available endpoints.

**Response:**
```json
{
  "result": "SUCCESS",
  "message": null,
  "data": {
    "api_status": "operational",
    "database_status": "connected",
    "timestamp": "2024-01-01T00:00:00+00:00",
    "endpoints": { ... }
  }
}
```

### Authentication

#### POST `/api/auth/login`
Login and get API token.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "result": "SUCCESS",
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com"
    },
    "api_token": "your_api_token_here"
  }
}
```

#### GET `/api/auth/me`
Get current authenticated user information.

**Headers:**
- `Authorization: Bearer {api_token}`

**Response:**
```json
{
  "result": "SUCCESS",
  "message": null,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

#### POST `/api/auth/refresh-token`
Refresh your API token.

**Headers:**
- `Authorization: Bearer {api_token}`

**Response:**
```json
{
  "result": "SUCCESS",
  "message": "Token refreshed successfully",
  "data": {
    "api_token": "new_api_token_here"
  }
}
```

#### POST `/api/auth/logout`
Logout and invalidate current API token.

**Headers:**
- `Authorization: Bearer {api_token}`

**Response:**
```json
{
  "result": "SUCCESS",
  "message": "Logged out successfully",
  "data": null
}
```

### Electricity

#### POST `/api/electricity/verify-customer`
Verify customer meter number and authenticate. This endpoint also returns an API token and electricity token.

**Request Body:**
```json
{
  "transID": "TXN123456789",
  "meterNumber": "12345678901",
  "amount": 50.00,
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "results": "SUCCESS",
  "message": null,
  "api_token": "your_api_token",
  "elec_token": "electricity_token",
  "details": {
    "customer_name": "John Doe",
    "meter_number": "12345678901"
  }
}
```

#### POST `/api/electricity/purchase`
Purchase electricity tokens.

**Headers:**
- `Authorization: Bearer {api_token}`
- `Content-Type: application/json`

**Request Body:**
```json
{
  "transID": "TXN123456789",
  "meterNumber": "12345678901",
  "amount": 50.00,
  "elec_token": "electricity_token_from_verify"
}
```

**Response:**
```json
{
  "results": "SUCCESS",
  "message": null,
  "type": "DISPLAY",
  "receiptItems": {
    "receipt_no": "REC123456",
    "DateTime": "2024-01-01 12:00:00",
    "customer_name": "John Doe",
    "meter_number": "12345678901",
    "std_tokens": [
      {
        "code": "token_code_here",
        "amount": "50.00",
        "units": "100"
      }
    ],
    ...
  }
}
```

#### POST `/api/electricity/purchase-production`
Purchase electricity tokens using production endpoint.

**Headers:**
- `Authorization: Bearer {api_token}`
- `Content-Type: application/json`

**Request Body:**
```json
{
  "transID": "TXN123456789",
  "meterNumber": "12345678901",
  "amount": 50.00,
  "elec_token": "electricity_token_from_verify"
}
```

**Response:** Same as `/api/electricity/purchase`

## Legacy Endpoints

The following legacy endpoints are maintained for backward compatibility:

- `POST /api/verifiyCustomer` (note the typo) - Same as `/api/electricity/verify-customer`
- `POST /api/verifyCustomer` - Corrected version
- `POST /api/buyelectricity` - Same as `/api/electricity/purchase`
- `POST /api/buyelectricityprod` - Same as `/api/electricity/purchase-production`
- `POST /api/mvumba` - Alternative purchase endpoint

## Error Responses

All endpoints return errors in the following format:

```json
{
  "result": "FAILED",
  "message": "Error message here",
  "data": null
}
```

Common HTTP status codes:
- `200` - Success (even for failures, check `result` field)
- `401` - Unauthorized (invalid or missing token)
- `422` - Validation error
- `500` - Server error

## Testing with Postman

1. Import the `Blueprint_Electricity_API.postman_collection.json` file into Postman
2. Set the `base_url` variable to your API URL (default: `http://localhost:8000`)
3. Start testing endpoints. The collection includes scripts that automatically save tokens to variables.

## Setup Instructions

1. Run migrations to add `api_token` field to users table:
   ```bash
   php artisan migrate
   ```

2. Create a user (if not already created):
   ```bash
   php artisan tinker
   ```
   Then in tinker:
   ```php
   User::create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => Hash::make('password123')]);
   ```

3. Start the server:
   ```bash
   php artisan serve
   ```

## Notes

- Minimum purchase amount: R10.00
- All amounts should be numeric
- Transaction IDs should be unique
- API tokens are stored as SHA256 hashes in the database
- The `elec_token` from verify-customer is required for purchase endpoints

