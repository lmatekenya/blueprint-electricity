# POST `/api/buyelectricityprod` - Payload Example

## Endpoint Details

**URL**: `POST /api/buyelectricityprod`  
**Authentication**: Required (Bearer Token)  
**Content-Type**: `application/json`

---

## Request Payload

### Required Fields

```json
{
  "transID": "TXN123456789",
  "meterNumber": "12345678901",
  "amount": 50.00,
  "elec_token": "electricity_token_from_verify"
}
```

### Complete Payload Example

```json
{
  "transID": "TXN123456789",
  "meterNumber": "12345678901",
  "amount": 50.00,
  "elec_token": "electricity_token_from_verify_customer_endpoint"
}
```

---

## Field Descriptions

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `transID` | string | ✅ Yes | Must be unique | Unique transaction identifier |
| `meterNumber` | string | ✅ Yes | Valid meter number | Customer's prepaid electricity meter number |
| `amount` | decimal | ✅ Yes | Numeric, minimum 10.00 | Purchase amount in local currency |
| `elec_token` | string | ✅ Yes | Valid token | Electricity token obtained from verify-customer endpoint |

---

## Headers

```
Content-Type: application/json
Authorization: Bearer {your_api_token}
```

**Note**: This endpoint requires authentication. You must first login using `/api/auth/login` or `/api/electricity/verify-customer` to obtain an API token.

---

## Request Examples

### cURL Example

```bash
curl -X POST https://your-domain.com/api/buyelectricityprod \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_TOKEN_HERE" \
  -d '{
    "transID": "TXN123456789",
    "meterNumber": "12345678901",
    "amount": 50.00,
    "elec_token": "electricity_token_from_verify"
  }'
```

### Postman Example

**Method**: `POST`  
**URL**: `{{base_url}}/api/buyelectricityprod`  
**Headers**:
```
Content-Type: application/json
Authorization: Bearer {{api_token}}
```

**Body** (raw JSON):
```json
{
  "transID": "TXN123456789",
  "meterNumber": "12345678901",
  "amount": 50.00,
  "elec_token": "{{elec_token}}"
}
```

### JavaScript (Fetch) Example

```javascript
fetch('https://your-domain.com/api/buyelectricityprod', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer YOUR_API_TOKEN_HERE'
  },
  body: JSON.stringify({
    transID: 'TXN123456789',
    meterNumber: '12345678901',
    amount: 50.00,
    elec_token: 'electricity_token_from_verify'
  })
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));
```

---

## Success Response

```json
{
  "results": "SUCCESS",
  "message": null,
  "type": "DISPLAY",
  "receiptItems": {
    "customer_address": null,
    "notes": [],
    "receipt_no": "REC123456",
    "DateTime": "2024-01-01 12:00:00",
    "utility_details": {
      "address": null,
      "vat_number": null,
      "name": "John Doe",
      "contact_number": null
    },
    "Cashier": "Smartblueprient",
    "reprint": false,
    "customer_account_no": "ACC123456",
    "tariff_name": "Residential",
    "fee_details": [
      {
        "amount": "3.50",
        "tax": "0.00",
        "desc": "Total Excise Duty"
      },
      {
        "amount": "7.50",
        "tax": "0.00",
        "desc": "Total VAT"
      }
    ],
    "customer_messages": [
      "Credit Vend"
    ],
    "totals": {
      "tendered": "50.00",
      "fees": "11.00",
      "total": "50.00",
      "fbe_units": "",
      "elec": "50.00",
      "debt_remaining": "0.00",
      "tax": "",
      "units": "100",
      "debt": "",
      "debt_opening_bal": "0.00"
    },
    "fbe_tokens": [],
    "external_client_id": "PP001",
    "customer_location_ref": "Location123",
    "std_tokens": [
      {
        "amount": "50.00",
        "code": "1234567890123456789012345678901234567890123456789012345678901234",
        "receipt": "REC123456",
        "tax": "0.00",
        "tariff": "",
        "units": "",
        "sort_order": "1",
        "desc": "General TaxRes Step 0"
      }
    ],
    "meter_details": {
      "tt": "",
      "number": "12345678901",
      "sgc_ti": "123/456",
      "ti": "456",
      "krn": "789",
      "alg": "",
      "sgc": "123"
    },
    "token_gen_time": "2024-01-01 12:00:00",
    "external_reference_number": "REC123456",
    "debt_details": [],
    "tariff_blocks": [],
    "ReceiptNo": "REC123456",
    "customer_name": "John Doe",
    "AmountTendered": "50.00"
    }
}
```

---

## Error Responses

### Validation Error
```json
{
  "result": "FAILED",
  "message": "Please provide required fields or provide amount as interger.",
  "type": null
}
```

### Authentication Error
```json
{
  "message": "Unauthenticated."
}
```

### Transaction Failed
```json
{
  "results": "FAILED",
  "message": "Blueprintelectricity experinced an error while purchasing electricity. Please try again later.",
  "details": "Generic Exception"
}
```

---

## Validation Rules

1. **transID**: 
   - Required
   - Must be unique per transaction
   - String format

2. **meterNumber**: 
   - Required
   - Must be a valid prepaid electricity meter number
   - Typically 11 digits

3. **amount**: 
   - Required
   - Must be numeric
   - Minimum value: 10.00
   - Decimal format (e.g., 50.00)

4. **elec_token**: 
   - Required
   - Obtained from `/api/electricity/verify-customer` endpoint
   - Used for transaction authorization

---

## Workflow

1. **Step 1**: Verify customer using `/api/electricity/verify-customer`
   - This returns `api_token` and `elec_token`
   
2. **Step 2**: Use the tokens to purchase electricity
   - Use `api_token` in Authorization header
   - Use `elec_token` in request body

---

## Important Notes

- ⚠️ **Authentication Required**: This endpoint requires a valid API token
- ⚠️ **Minimum Amount**: The minimum purchase amount is R10.00 (or equivalent)
- ⚠️ **Token Required**: You must first verify the customer to get `elec_token`
- ✅ **Transaction Tracking**: All transactions are saved to the database
- ✅ **Provider**: Uses PrepaidPlus Botswana API (`api.prepaidplus.co.bw`)

---

## Related Endpoints

- `POST /api/electricity/verify-customer` - Get tokens before purchase
- `POST /api/electricity/purchase-production` - Same endpoint (new route)
- `GET /api/transactions` - View transaction history
- `GET /api/transactions/transaction-id/{transID}` - Get specific transaction

---

## Testing Tips

1. **Get API Token First**:
   ```bash
   POST /api/auth/login
   {
     "email": "user@example.com",
     "password": "password123"
   }
   ```

2. **Verify Customer**:
   ```bash
   POST /api/electricity/verify-customer
   {
     "transID": "TXN123456789",
     "meterNumber": "12345678901",
     "amount": 50.00,
     "email": "user@example.com",
     "password": "password123"
   }
   ```
   Response includes `api_token` and `elec_token`

3. **Purchase Electricity**:
   ```bash
   POST /api/buyelectricityprod
   {
     "transID": "TXN123456789",
     "meterNumber": "12345678901",
     "amount": 50.00,
     "elec_token": "token_from_step_2"
   }
   ```

---

## External API Used

This endpoint uses:
- **Provider**: PrepaidPlus (Botswana API)
- **Endpoint**: `https://api.prepaidplus.co.bw/apimanager/rest/basic/v1/electricity/creditvend`
- **Method**: POST
- **Authentication**: Basic Auth (handled internally)

---

## Response Fields Explained

| Field | Description |
|-------|-------------|
| `receipt_no` | Receipt number from provider |
| `code` | Electricity token code (STS Cipher) - use this to recharge meter |
| `units` | Number of units purchased |
| `customer_name` | Name of the customer |
| `meter_number` | Meter number used |
| `AmountTendered` | Amount paid |
| `fees` | Total fees (excise duty + VAT) |
| `std_tokens` | Array containing token information |

---

## Example Token Code Format

The token code in `std_tokens[0].code` is typically a long string of numbers that should be entered into the prepaid meter. It may include:
- Key change tokens (if applicable)
- STS Cipher (main token)

Format: `1234567890123456789012345678901234567890123456789012345678901234`

---

## Last Updated
January 2024

