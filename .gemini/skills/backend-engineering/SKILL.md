---
name: backend-engineering
description: Create robust, secure, and highly-optimized backend systems. Use this skill when the user asks to build or refactor server-side logic, API endpoints, database interactions, or authentication flows, particularly in PHP/Symfony environments. Generates clean, secure, and performant backend code.
---

# Backend Engineering Skill

This skill guides the creation of production-grade backend systems. Implement real working code with exceptional attention to security, performance, and architecture. Avoid lazy shortcuts and insecure patterns.

## Architectural Direction

- **Clean Architecture**: Separate concerns. Controllers handle HTTP (request validation, response mapping), Services handle business logic, Repositories handle data access.
- **RESTful API Design**: Use proper HTTP verbs (GET, POST, PUT, PATCH, DELETE), consistent resource naming, and standard HTTP status codes.
- **Security-First**: Always validate and sanitize input. Use prepared statements for SQL (no string concatenation). Implement strong CSRF and XSS protections. Ensure correct CORS policies.

## Language Specifics (PHP/Symfony)

**DO**: Use strict typing (`declare(strict_types=1);`) in all new files.
**DO**: Use dependency injection rather than static singletons or global state.
**DO**: Write meaningful, context-rich error logs, without exposing sensitive data to the client. Keep production errors generic.
**DO**: Keep controllers thin. Move complex logic into dedicated Service classes to ensure testability and reusability.

**DON'T**: Use outdated functions (e.g., `mysql_*` or raw `md5()`/`sha1()` for passwords). Use `password_hash()` and PDO.
**DON'T**: Expose raw database IDs if avoidable (use UUIDs where appropriate to prevent enumeration attacks).
**DON'T**: Suppress errors with the `@` operator. Handle exceptions properly and gracefully.
**DON'T**: Leave `session_start()` without proper configuration and storage checks.

## Database Interaction
- Use transactions for multi-step data modifications to ensure ACID compliance.
- Optimize queries: use required indexes, avoid N+1 query problems by using joins or eager loading when appropriate.

## Implementation Principles
Write code that is reliable and self-documenting. Name variables and methods clearly so that comments are rarely needed. Prioritize maintainability and testability. Always consider edge cases, race conditions, and how the system behaves under load.
