# FetchIt Parser Service

Node.js service that parses Gmail emails to extract order information.

## Setup

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Copy parser files from frontend repository:**
   - Copy `FetchIt/src/api/gmail/parsers/` to `parser-service/src/parsers/`
   - This includes parser-router.js and all brand-specific parsers

3. **Update server.js:**
   - Uncomment the parser import and usage code
   - The parser files need to be in place first

4. **Start with PM2:**
   ```bash
   pm2 start server.js --name parser-service
   pm2 save
   pm2 startup  # Follow instructions to enable auto-start
   ```

## Endpoints

- `GET /health` - Health check
- `POST /parse-email` - Parse email and extract order data

## Request Format

```json
{
  "from": "shipment-tracking@amazon.com",
  "subject": "Your Amazon.com order has shipped",
  "body": "Plain text email body...",
  "htmlBody": "<html>HTML email body...</html>",
  "replyTo": "ship-confirm@amazon.com"
}
```

## Response Format

**Order Found:**
```json
{
  "orderId": "123-4567890-1234567",
  "vendor": "amazon",
  "status": "shipped",
  "category": "e-com",
  "totalAmount": "99.99",
  "orderDate": "2024-01-09T10:30:00Z",
  "deliveryDate": "2024-01-15T18:00:00Z",
  "items": [...],
  "deeplink": "...",
  "otp": null
}
```

**Not an Order:**
```json
null
```
