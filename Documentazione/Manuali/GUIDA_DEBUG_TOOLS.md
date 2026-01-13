# Guida Strumenti di Debug (DevTools)

Il toolkit avanzato di debug si trova in `bin/debug_tools/` ed è accessibile via CLI o Dashboard Web.

## Nuovi Strumenti (Path to 100 Update)

### 1. GraphQL Schema Debugger
**Script**: `graphql_debug.php`
**Scopo**: Verifica che lo schema GraphQL sia valido e lo stampa in formato SDL (Schema Definition Language).
**Uso**: Utile per debugging se l'endpoint web restituisce errori generici.

### 2. Redis Connection Check
**Script**: `redis_check.php`
**Scopo**: Verifica la connettività al server Redis configurato in `.env`.
**Test**: Esegue connessione, scrittura, lettura e cancellazione di una chiave di test.

### 3. Soft Delete Integrity
**Script**: `soft_delete_check.php`
**Scopo**: Analizza le tabelle critiche (`soci`, `users`, `documenti`) per verificare:
- Presenza colonna `deleted_at`.
- Conteggio record attivi vs eliminati.

## Accesso Dashboard
Tutti questi script sono eseguibili direttamente dalla pagina:
`http://localhost:8080/MCAG_Militare-Civile-Archivio-Gestionale/public/devtools`
Sezione **Scripts & Utilities**.

