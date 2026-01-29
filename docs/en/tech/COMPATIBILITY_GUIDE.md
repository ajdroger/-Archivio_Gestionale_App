Here is the translation of the technical documentation from Italian to English:

---
# MCAG Compatibility Guide

## Why PHP 8.2+?
MCAG Enterprise v9.0 is built on a **Strict Typing** philosophy to ensure mission-critical stability.
We require PHP 8.2 for:
1.  **Readonly Classes**: Immutable DTOs for thread-safe data transport.
2.  **DNF Types**: Complex type unions (e.g., `(Request&HasSession)|null`).
3.  **Enums**: Native support for Status and Roles.

## Legacy Environment Support
If you are running on legacy hardware (e.g., Windows Server 2012, CentOS 7):

### Option A: Docker (Recommended)
Use the provided `docker-compose.universal.yml`. It bundles PHP 8.2 Alpine.
```bash
docker-compose -f docker/production/docker-compose.universal.yml up -d
```

### Option B: Polyfill (Not Supported)
We do **NOT** support polyfills for PHP 7.4. The security risk is too high for the military/civil context of this application.

## Database Compatibility
*   **MySQL**: 5.7+ or 8.0+ (Recommended)
*   **MariaDB**: 10.4+
*   **SQLite**: 3.30+ (Dev/Embedded only, NOT for production)