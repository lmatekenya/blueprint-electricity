# External APIs Documentation

This document provides a comprehensive overview of all external APIs used in the Blueprint Electricity system, their purposes, endpoints, authentication, and usage.

---

## 1. PrepaidPlus API

### Overview
PrepaidPlus is the primary electricity token provider used for purchasing prepaid electricity tokens. It provides multiple endpoints for different environments and use cases.

### Base URLs

#### Production Environment
- **Production (Google Cloud Functions)**: `https://us-central1-prod-prepaidplus.cloudfunctions.net`
- **Production API (Botswana)**: `https://api.prepaidplus.co.bw`

#### Development Environment
- **Development**: `https://us-central1-develop-prepaidplus.cloudfunctions.net`

### Authentication
- **Type**: Basic Authentication
- **Authorization Header**: `Basic dm1pdlVRQ3FGd0xwUWhwSVhPaDU6cVJqVEVOcFFNaHdkOE1ieg`
- **Note**: Same credentials used across all PrepaidPlus endpoints

### Endpoints

#### 1.1 Trial Credit Vend (Customer Verification)
**Purpose**: Verify customer meter number and validate transaction before purchase

**Endpoint**: 
```
POST https://us-central1-prod-prepaidplus.cloudfunctions.net/api/trialcreditvendApiKey
```

**Request Body**:
```json
{
  "meterNumber": "12345678901",
  "transactionAmount": 50.00,
  "clientSaleId": "TXN123456789",
  "createdBy": "SmartPlan BluePrint"
}
```

**Response** (Success):
```json
{
  "custVendDetail": {
    "name": "John Doe",
    "meterNumber": "12345678901"
  }
}
```

**Used In**:
- `ElectricityController::verifycustomer()` - Primary verification
- `ElectricityController::verifycustomer1()` - Alternative verification
- `ElectricityBkController::verifycustomer()` - Backup verification

**Purpose**: Validates meter number and returns customer details before actual purchase

---

#### 1.2 BPC Sale (Production Purchase)
**Purpose**: Execute actual electricity token purchase for production

**Endpoint**:
```
POST https://us-central1-prod-prepaidplus.cloudfunctions.net/api/bpcSaleApiKey
```

**Request Body**:
```json
{
  "meterNumber": "12345678901",
  "transactionAmount": 50.00,
  "clientSaleId": "TXN123456789",
  "createdBy": "Standard Chartered"
}
```

**Response** (Success):
```json
{
  "response": "Successful",
  "creditVendReceipt": {
    "receiptNo": "REC123456",
    "date": "2024-01-01 12:00:00",
    "name": "John Doe",
    "account": "ACC123456",
    "tariff": "Residential",
    "standardCharge": "2.50",
    "governmentLevy": "1.00",
    "vat": "7.50",
    "amtTendered": "50.00",
    "costUnits": "50.00",
    "tokenUnits": "100",
    "stsCipher": "1234 5678 9012 3456",
    "meterNumber": "12345678901",
    "sgc": "123",
    "ti": "456",
    "krn": "789",
    "location": "Location Ref"
  }
}
```

**Used In**:
- `ElectricityController::prepaidplusconfirmelectricity()` - Main production purchase
- `ElectricityBkController::prepaidplusconfirmelectricity()` - Backup production purchase

**Purpose**: Purchases electricity tokens and returns receipt with token code

---

#### 1.3 Credit Vend (Test/Production API)
**Purpose**: Alternative endpoint for electricity token purchase (Botswana API)

**Endpoint**:
```
POST https://api.prepaidplus.co.bw/apimanager/rest/basic/v1/electricity/creditvend
```

**Request Body**:
```json
{
  "meterNumber": "12345678901",
  "transactionAmount": 50.00,
  "clientSaleId": "TXN123456789",
  "terminalId": "Web",
  "outletId": "3928-01",
  "operatorId": "Smart Plan Blueprint-01"
}
```

**Response**: Same structure as BPC Sale endpoint

**Used In**:
- `ElectricityBkController::prepaidplusconfirmelectricityTest()` - Test/production purchase

**Purpose**: Alternative purchase endpoint with additional terminal/outlet information

---

### Key Features
- **Customer Verification**: Validates meter numbers before purchase
- **Token Generation**: Generates electricity tokens (STS Cipher)
- **Receipt Generation**: Provides detailed receipts with all transaction information
- **Multiple Environments**: Supports both production and development environments

---

## 2. CraftAPI / Finclude (Kazang Integration)

### Overview
CraftAPI is accessed through Finclude's production environment. It provides an alternative method for electricity token purchases using Kazang integration.

### Base URL
- **Production**: `https://production.finclude.co.za`

### Authentication Flow
1. **Login** to get Bearer token
2. **Use Bearer token** for transaction execution

### Endpoints

#### 2.1 Authentication Login
**Purpose**: Authenticate and obtain Bearer token for API access

**Endpoint**:
```
POST https://production.finclude.co.za/rest/authentication/login
```

**Request Body**:
```json
{
  "identity": "craftapi",
  "credential": "wegotthis_",
  "identityType": "USERNAME"
}
```

**Response**:
- **Header**: `X-AUTH-TOKEN` contains the Bearer token
- Token is used in subsequent API calls

**Used In**:
- `ElectricityController::verifycustomer()` - Fallback authentication
- `ElectricityController::buyelectricity()` - Alternative purchase method
- `ElectricityBkController::verifycustomer()` - Backup authentication
- `ElectricityBkController::buyelectricity()` - Backup purchase method

---

#### 2.2 Transaction Execute
**Purpose**: Execute electricity token purchase transaction

**Endpoint**:
```
POST https://production.finclude.co.za/rest/transaction/execute
```

**Request Headers**:
```
Authorization: Bearer {token_from_login}
Accept: application/json
Content-Type: application/json
```

**Request Body**:
```json
{
  "txObjectId": 271,
  "threadId": 71,  // or 72 for verification
  "scopeId": 116,
  "requesterIdentification": {
    "identityType": "ANONYMOUS"
  },
  "answerDeviceId": "craft_silicon",
  "answerTransactionId": "TXN123456789",
  "parameters": {
    "integration.kazang.hidden.productId": "20004",
    "integration.kazang.MeterNumber": "12345678901",
    "SALE_VALUE": 50.00
  }
}
```

**Response** (Success):
```json
{
  "result": "SUCCESS",
  "receiptItems": {
    "customer_name": "John Doe",
    "meter_number": "12345678901",
    // ... other receipt details
  }
}
```

**Thread IDs**:
- `71` - Used for actual purchase transactions
- `72` - Used for verification/validation transactions

**Product ID**: `20004` - Kazang electricity product identifier

**Used In**:
- `ElectricityController::verifycustomer()` - Fallback verification
- `ElectricityController::buyelectricity()` - Alternative purchase (currently bypassed)
- `ElectricityBkController::verifycustomer()` - Backup verification
- `ElectricityBkController::buyelectricity()` - Backup purchase (currently bypassed)

**Purpose**: Alternative electricity token purchase method through Kazang integration

---

## 3. SmartPlan Blueprint Internal APIs

### Overview
These are internal APIs used for tracking and recording transactions within the SmartPlan Blueprint system.

### Base URL
- **Internal System**: `https://peter.smartplanblueprint.net/api`

### Endpoints

#### 3.1 SCT_SELL (Sell Tracking)
**Purpose**: Record sale amount for internal tracking/reporting

**Endpoint**:
```
POST https://peter.smartplanblueprint.net/api/SCT_SELL
```

**Request Body** (Form Data):
```
amount: 50.00
```

**Used In**:
- `ElectricityController::prepaidplusconfirmelectricity()` - After successful purchase
- `ElectricityBkController::prepaidplusconfirmelectricity()` - After successful purchase

**Purpose**: Tracks total sales amount for reporting/accounting

---

#### 3.2 SCT_TRANS_DAGI (Transaction Recording)
**Purpose**: Record complete transaction details in internal system

**Endpoint**:
```
POST https://peter.smartplanblueprint.net/api/SCT_TRANS_DAGI
```

**Request Body** (Form Data):
```
amount: 50.00
service: Electricity
servicenumber: 12345678901
voucher: 1234567890123456
status: Processed
trans_id: REC123456
```

**Used In**:
- `ElectricityController::prepaidplusconfirmelectricity()` - After successful purchase
- `ElectricityBkController::prepaidplusconfirmelectricity()` - After successful purchase

**Purpose**: Records complete transaction details including voucher code and status

---

## API Usage Flow

### Primary Flow (PrepaidPlus)
1. **Verify Customer** → `trialcreditvendApiKey` endpoint
2. **Purchase Electricity** → `bpcSaleApiKey` endpoint
3. **Record Sale** → `SCT_SELL` endpoint (internal)
4. **Record Transaction** → `SCT_TRANS_DAGI` endpoint (internal)

### Fallback Flow (CraftAPI/Finclude)
1. **Login** → `/rest/authentication/login` endpoint
2. **Verify/Purchase** → `/rest/transaction/execute` endpoint (with threadId 72 or 71)

---

## Authentication Credentials Summary

| API | Type | Credentials |
|-----|------|------------|
| **PrepaidPlus** | Basic Auth | `Basic dm1pdlVRQ3FGd0xwUWhwSVhPaDU6cVJqVEVOcFFNaHdkOE1ieg` |
| **CraftAPI/Finclude** | Username/Password | `craftapi` / `wegotthis_` |
| **SmartPlan Blueprint** | None (Internal) | N/A |

---

## Error Handling

All external APIs use try-catch blocks with `GuzzleHttp\Exception\ClientException`:
- Failed requests return standardized error responses
- Errors are logged in transaction records
- Fallback mechanisms exist between PrepaidPlus and CraftAPI

---

## Security Notes

⚠️ **IMPORTANT**: 
- API credentials are hardcoded in the controllers
- Consider moving credentials to environment variables (`.env`)
- Basic Auth credentials should be encrypted or stored securely
- Bearer tokens from CraftAPI should be stored securely

---

## Provider Comparison

| Feature | PrepaidPlus | CraftAPI/Finclude |
|---------|-------------|------------------|
| **Primary Use** | ✅ Yes | ⚠️ Fallback |
| **Verification** | ✅ Dedicated endpoint | ✅ Via transaction execute |
| **Purchase** | ✅ Dedicated endpoint | ✅ Via transaction execute |
| **Token Format** | STS Cipher | Varies |
| **Receipt Details** | ✅ Comprehensive | ✅ Basic |
| **Environment** | Production + Development | Production only |

---

## Integration Points in Code

### ElectricityController
- `verifycustomer()` - Uses PrepaidPlus trial endpoint, falls back to CraftAPI
- `buyelectricity()` - Uses PrepaidPlus BPC Sale (primary), CraftAPI (commented/fallback)
- `prepaidplusconfirmelectricity()` - PrepaidPlus production purchase

### ElectricityBkController
- `verifycustomer()` - Same as ElectricityController
- `buyelectricity()` - Same as ElectricityController
- `buyelectricityTest()` - Uses PrepaidPlus Botswana API
- `prepaidplusconfirmelectricityTest()` - PrepaidPlus test/production endpoint
- `prepaidplusconfirmelectricity()` - PrepaidPlus production (backup)

---

## Recommendations

1. **Environment Variables**: Move all API credentials to `.env` file
2. **API Service Class**: Create dedicated service classes for each external API
3. **Error Logging**: Implement comprehensive error logging for all API calls
4. **Retry Logic**: Add retry mechanisms for failed API calls
5. **Rate Limiting**: Implement rate limiting for external API calls
6. **Monitoring**: Add monitoring/alerting for API failures
7. **Documentation**: Keep this document updated as APIs change

---

## Last Updated
January 2024

