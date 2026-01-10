-- Documentazione: Politiche di Accesso Database (Least Privilege)
-- Questo file documenta i permessi minimi necessari per l'utente applicativo.

-- 1. Utente Applicativo (es. 'fratellanza_app')
-- Deve poter leggere/scrivere dati ma non alterare lo schema in produzione.
GRANT SELECT, INSERT, UPDATE, DELETE ON fratellanza_db.* TO 'fratellanza_app'@'%';

-- 2. Utente Backup (es. 'fratellanza_backup')
-- Deve poter fare LOCK TABLES e SELECT per i dump.
GRANT SELECT, LOCK TABLES, SHOW VIEW, EVENT, TRIGGER ON fratellanza_db.* TO 'fratellanza_backup'@'localhost';

-- 3. Utente Migrazioni/Admin (es. 'fratellanza_admin')
-- Solo questo utente deve avere DROP/ALTER/CREATE.
-- Usato solo durante i deploy o manutenzione.
GRANT ALL PRIVILEGES ON fratellanza_db.* TO 'fratellanza_admin'@'localhost';

-- Note di Sicurezza:
-- - Revocare FILE privilege se non strettamente necessario.
-- - Assicurare che 'fratellanza_app' NON abbia GRANT OPTION.
-- - Monitorare accessi via Audit Logs.
