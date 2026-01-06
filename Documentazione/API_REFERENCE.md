# 📚 API REFERENCE - Fratellanza Militare Archivio

**Version**: 2.0.1 Mission-Critical Enterprise (MySQL Edition)
**Base Path**: `/` (Production Root)

---

## Authentication Endpoints

### POST `/login`
Authenticate user and establish session.

**Request Body**:
```json
{
  "username": "string",
  "password": "string"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Login successful",
  "2fa_required": false,
  "redirect": "/dashboard"
}
```

**Response** (200 OK - 2FA Required):
```json
{
  "success": true,
  "2fa_required": true,
  "message": "2FA code required"
}
```

**Errors**:
- `401 Unauthorized`: Invalid credentials
- `429 Too Many Requests`: Rate limit exceeded (5/min)

---

### POST `/verify-2fa`
Verify TOTP 2FA code.

**Request Body**:
```json
{
  "code": "123456"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "redirect": "/dashboard"
}
```

---

### GET `/logout`
Destroy user session.

**Response** (302 Found):
Redirects to `/login`

---

## Soci (Members) Endpoints

### GET `/soci`
List all members with pagination and filters.

**Query Parameters**:
- `page` (int): Page number (default: 1)
- `per_page` (int): Items per page (default: 50)
- `stato` (string): Filter by status (ATTIVO, SOSPESO, MOROSO)
- `search` (string): Search by nome/cognome/cf

**Response** (200 OK):
```json
{
  "soci": [
    {
      "codice_fiscale": "RSSMRA80A01H501Z",
      "nome": "Mario",
      "cognome": "Rossi",
      "email": "mario.rossi@example.com",
      "stato": "ATTIVO",
      "matricola": "2025/001"
    }
  ],
  "total": 21,
  "current_page": 1,
  "last_page": 1
}
```

---

### GET `/soci/{codice_fiscale}`
Get detailed member information including documents.

**Response** (200 OK):
```json
{
  "socio": {
    "codice_fiscale": "RSSMRA80A01H501Z",
    "nome": "Mario",
    "cognome": "Rossi",
    "data_nascita": "1980-01-01",
    "email": "mario.rossi@example.com",
    "telefono": "+39 123456789",
    "matricola": "2025/001",
    "stato": "ATTIVO",
    "documenti": [
      {
        "id_univoco": "doc_abc123",
        "tipo": "CARTA_IDENTITA",
        "nome_file": "ci_rossi_mario.pdf",
        "data_caricamento": "2025-01-15T10:30:00Z"
      }
    ]
  }
}
```

---

### POST `/soci/create`
Create new member with automatic document and PDF generation.

**Request Body** (multipart/form-data):
```
nome: "Mario"
cognome: "Rossi"
codice_fiscale: "RSSMRA80A01H501Z"
email: "mario.rossi@example.com"
telefono: "+39 1234567890"
data_nascita: "1980-01-01"
indirizzo: "Via Roma 1, Firenze"
sesso: "M"
luogo_nascita: "Firenze"
provincia_nascita: "FI"
documento_file: [FILE]
anno_solare: 2025
quota_versata: 50.00
metodo_pagamento: "BONIFICO"
trattamento_dati: 1
cessione_terzi: 0
marketing: 1
```

**Response** (302 Found):
Redirects to `/soci/{codice_fiscale}` with success message

**Errors**:
- `400 Bad Request`: Validation errors
- `409 Conflict`: Member already exists
- `413 Payload Too Large`: File > 5MB

---

### POST `/soci/calcola-cf`
Calculate Italian fiscal code (Codice Fiscale).

**Rate Limit**: 10 requests/minute

**Request Body**:
```json
{
  "nome": "Mario",
  "cognome": "Rossi",
  "sesso": "M",
  "data_nascita": "1980-01-01",
  "comune_nascita": "Firenze"
}
```

**Response** (200 OK):
```json
{
  "codice_fiscale": "RSSMRA80A01H501Z"
}
```

---

### PUT `/soci/{codice_fiscale}/edit`
Update member information.

**Request Body**: Same as `/soci/create` (without file upload)

**Response** (302 Found):
Redirects back with success message

---

### DELETE `/soci/{codice_fiscale}/delete`
Soft delete member (archives data).

**Response** (302 Found):
Redirects to `/soci` with confirmation

---

## Documents Endpoints

### POST `/soci/{cf}/upload-documento`
Upload additional document for member.

**Request Body** (multipart/form-data):
```
tipo_documento: "CARTA_IDENTITA"
file: [FILE]
anno_solare: 2025
quota_versata: 50.00 (optional)
metodo_pagamento: "BONIFICO" (optional)
```

**Response** (302 Found):
Redirects back with success confirmation

---

### GET `/soci/{cf}/download-documento/{id_univoco}`
Download specific document.

**Response** (200 OK):
Binary file stream with appropriate Content-Type

**Headers**:
```
Content-Type: application/pdf
Content-Disposition: attachment; filename="documento.pdf"
```

---

## Statistics & Dashboard

### GET `/dashboard`
Get aggregated statistics.

**Response** (200 OK):
```json
{
  "total_soci": 21,
  "soci_attivi": 18,
  "soci_morosi": 3,
  "total_documenti": 45,
  "documenti_validati": 40,
  "revenue_year": 1050.00,
  "charts": {
    "demographics": { ... },
    "registrations_trend": { ... }
  }
}
```

---

## Admin & DevTools Endpoints

### GET `/devtools`
Access developer dashboard (Admin only).

**Response** (200 OK):
HTML page with system diagnostics, database schema, scripts runner

**Requires**: Admin role

---

### POST `/devtools/run-script`
Execute maintenance script (Admin only).

**Request Body**:
```json
{
  "script": "bin/maintenance/backup_daily.php"
}
```

**Response** (200 OK):
```json
{
  "output": "Backup completed successfully..."
}
```

**Security**: Whitelisted scripts only

---

### GET `/settings`
User account settings page.

**Response** (200 OK):
HTML page for changing password, configuring 2FA

---

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 302 | Redirect (success action) |
| 400 | Bad Request (validation errors) |
| 401 | Unauthorized (login required) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Not Found |
| 409 | Conflict (duplicate resource) |
| 413 | Payload Too Large (file upload) |
| 429 | Too Many Requests (rate limit) |
| 500 | Internal Server Error |

---

## Rate Limits

| Endpoint | Limit |
|----------|-------|
| `/login` | 5 req/min per IP |
| `/soci/calcola-cf` | 10 req/min per session |
| `/export/*` | 20 req/min per user |
| Global | 100 req/min per IP |

---

## Security Headers

All responses include:
- `Content-Security-Policy`
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `X-XSS-Protection: 1; mode=block`
- `Strict-Transport-Security` (HTTPS only)

---

**Author**: Soobadur Mohammad Ajmeer ©  
**Last Updated**: 2025-12-27
