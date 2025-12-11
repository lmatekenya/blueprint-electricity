# External APIs Quick Reference

## Quick Summary

| API Provider | Purpose | Status | Primary Endpoint |
|--------------|---------|--------|-------------------|
| **PrepaidPlus** | Electricity token purchase | ✅ Primary | `bpcSaleApiKey` |
| **CraftAPI/Finclude** | Alternative electricity purchase | ⚠️ Fallback | `/rest/transaction/execute` |
| **SmartPlan Blueprint** | Internal transaction tracking | ✅ Active | `SCT_SELL`, `SCT_TRANS_DAGI` |

---

## PrepaidPlus API

### Endpoints

| Endpoint | Method | Purpose | Used In |
|----------|--------|---------|---------|
| `/api/trialcreditvendApiKey` | POST | Verify customer meter | `verifycustomer()` |
| `/api/bpcSaleApiKey` | POST | Purchase tokens (Production) | `prepaidplusconfirmelectricity()` |
| `/apimanager/rest/basic/v1/electricity/creditvend` | POST | Purchase tokens (Test/Prod) | `prepaidplusconfirmelectricityTest()` |

### Base URLs
- Production: `https://us-central1-prod-prepaidplus.cloudfunctions.net`
- Test/Prod: `https://api.prepaidplus.co.bw`
- Development: `https://us-central1-develop-prepaidplus.cloudfunctions.net`

### Auth
```
Authorization: Basic dm1pdlVRQ3FGd0xwUWhwSVhPaDU6cVJqVEVOcFFNaHdkOE1ieg
```

---

## CraftAPI / Finclude

### Endpoints

| Endpoint | Method | Purpose | Used In |
|----------|--------|---------|---------|
| `/rest/authentication/login` | POST | Get Bearer token | `verifycustomer()`, `buyelectricity()` |
| `/rest/transaction/execute` | POST | Execute transaction | `verifycustomer()`, `buyelectricity()` |

### Base URL
```
https://production.finclude.co.za
```

### Auth
- Login: `craftapi` / `wegotthis_`
- Token: Bearer token from `X-AUTH-TOKEN` header

### Transaction Parameters
- `txObjectId`: 271
- `threadId`: 71 (purchase) or 72 (verification)
- `scopeId`: 116
- `productId`: 20004 (Kazang electricity)

---

## SmartPlan Blueprint Internal APIs

### Endpoints

| Endpoint | Method | Purpose | Used In |
|----------|--------|---------|---------|
| `/api/SCT_SELL` | POST | Track sale amount | After successful purchase |
| `/api/SCT_TRANS_DAGI` | POST | Record transaction | After successful purchase |

### Base URL
```
https://peter.smartplanblueprint.net/api
```

### Data Sent
- **SCT_SELL**: `amount`
- **SCT_TRANS_DAGI**: `amount`, `service`, `servicenumber`, `voucher`, `status`, `trans_id`

---

## Request/Response Examples

### PrepaidPlus Verification
```json
// Request
{
  "meterNumber": "12345678901",
  "transactionAmount": 50.00,
  "clientSaleId": "TXN123456789",
  "createdBy": "SmartPlan BluePrint"
}

// Response
{
  "custVendDetail": {
    "name": "John Doe",
    "meterNumber": "12345678901"
  }
}
```

### PrepaidPlus Purchase
```json
// Request
{
  "meterNumber": "12345678901",
  "transactionAmount": 50.00,
  "clientSaleId": "TXN123456789",
  "createdBy": "Standard Chartered"
}

// Response
{
  "response": "Successful",
  "creditVendReceipt": {
    "receiptNo": "REC123456",
    "stsCipher": "1234 5678 9012 3456",
    "tokenUnits": "100",
    // ... more fields
  }
}
```

### CraftAPI Login
```json
// Request
{
  "identity": "craftapi",
  "credential": "wegotthis_",
  "identityType": "USERNAME"
}

// Response Header
X-AUTH-TOKEN: {bearer_token}
```

### CraftAPI Transaction
```json
// Request
{
  "txObjectId": 271,
  "threadId": 71,
  "scopeId": 116,
  "answerDeviceId": "craft_silicon",
  "answerTransactionId": "TXN123456789",
  "parameters": {
    "integration.kazang.hidden.productId": "20004",
    "integration.kazang.MeterNumber": "12345678901",
    "SALE_VALUE": 50.00
  }
}
```

---

## Code Locations

| Function | File | External APIs Used |
|----------|------|-------------------|
| `verifycustomer()` | `ElectricityController.php` | PrepaidPlus, CraftAPI |
| `buyelectricity()` | `ElectricityController.php` | PrepaidPlus (primary) |
| `prepaidplusconfirmelectricity()` | `ElectricityController.php` | PrepaidPlus, SmartPlan |
| `buyelectricityTest()` | `ElectricityBkController.php` | PrepaidPlus (Botswana) |
| `prepaidplusconfirmelectricityTest()` | `ElectricityBkController.php` | PrepaidPlus (Botswana) |

---

## Flow Diagram

```
User Request
    ↓
[verifycustomer]
    ├─→ PrepaidPlus (trialcreditvendApiKey) ✅ Primary
    └─→ CraftAPI (login + execute) ⚠️ Fallback
    ↓
[buyelectricity]
    ├─→ PrepaidPlus (bpcSaleApiKey) ✅ Primary
    │   ├─→ SmartPlan (SCT_SELL) ✅ Track sale
    │   └─→ SmartPlan (SCT_TRANS_DAGI) ✅ Record transaction
    └─→ CraftAPI (execute) ⚠️ Fallback (currently bypassed)
```

---

## Security Credentials

⚠️ **All credentials are currently hardcoded. Should be moved to `.env`:**

```env
PREPAIDPLUS_AUTH=Basic dm1pdlVRQ3FGd0xwUWhwSVhPaDU6cVJqVEVOcFFNaHdkOE1ieg
CRAFTAPI_USERNAME=craftapi
CRAFTAPI_PASSWORD=wegotthis_
FINCLUDE_BASE_URL=https://production.finclude.co.za
SMARTPLAN_BASE_URL=https://peter.smartplanblueprint.net
```

---

## Error Handling

All APIs use:
- `try-catch` with `GuzzleHttp\Exception\ClientException`
- Standardized error responses
- Transaction status tracking (pending → success/failed)

---

For detailed documentation, see `EXTERNAL_APIS_DOCUMENTATION.md`

