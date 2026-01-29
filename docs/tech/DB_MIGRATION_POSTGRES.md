# Database Migration Guide: MySQL to PostgreSQL

## Overview
MCAG v9.1 supports Database Independence. While tailored for MySQL 8, the architecture is compatible with PostgreSQL 15+.
This guide details the process to migrate a production instance.

## Prerequisites
*   **pgloader**: Required for automated schema conversion.
*   **PostgreSQL 15+**: Installed and running.
*   **Access**: Root/Admin access to both databases.

## Step 1: Automated Data Migration
We use `pgloader` to handle dialect differences (e.g., `` ` `` vs `"` quotes, `INT` vs `SERIAL`).

1.  Create a command file `migration.load`:
    ```lisp
    LOAD DATABASE
         FROM      mysql://user:pass@localhost/mcag_db
         INTO postgresql://postgres:pass@localhost/mcag_pg

    WITH include drop, create tables, create indexes, reset sequences

    CAST type datetime to timestamp drop default drop not null using zero-dates-to-null,
         type date drop not null drop default using zero-dates-to-null;
    ```

2.  Run migration:
    ```bash
    pgloader migration.load
    ```

## Step 2: Configuration Update
Update your `.env` file to switch drivers:

```ini
# Old
# DB_DSN=mysql:host=localhost;dbname=mcag_db;charset=utf8mb4

# New
DB_DRIVER=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_NAME=mcag_pg
DB_USER=postgres
DB_PASS=secret
```

## Step 3: Verification
Run the database connection test:
```bash
php bin/test-db-connection.php
```

## Known Differences
*   **Case Sensitivity**: PostgreSQL is strict about lowercase table names unless quoted. MCAG uses lowercase standard, so this should match.
*   **Group By**: Postgres is stricter on non-aggregated columns.
*   **Backticks**: The `DatabaseConnection` class handles query quoting abstraction, but raw SQL in controllers might need review if not using the Query Builder.
