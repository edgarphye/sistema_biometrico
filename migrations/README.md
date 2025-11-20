Migration instructions for sistema_biometrico

This folder contains SQL migrations to update the database schema.

File: 20251119_add_sanciones_retardos_soporte.sql
- Adds `soporte` column to `retardos` (idempotent for MySQL 8+)
- Creates `sanciones` table if not exists

How to run (from command line / Windows cmd.exe):

1) Using mysql client (recommended in test environment):

mysql -u YOUR_DB_USER -p YOUR_DB_NAME < migrations\20251119_add_sanciones_retardos_soporte.sql

2) Or, connect interactively and run the file:

mysql -u YOUR_DB_USER -p
USE YOUR_DB_NAME;
SOURCE c:/tools/nginx/html/sistema_biometrico/migrations/20251119_add_sanciones_retardos_soporte.sql;

Notes:
- The migration uses `IF NOT EXISTS` / `ADD COLUMN IF NOT EXISTS` which requires MySQL 8.0.16+. If your MySQL version is older, run a safe ALTER by first checking the column existence with a query on INFORMATION_SCHEMA, or contact your DBA.
- Always run migrations against a test/staging database first.
- Backup your database before applying migrations.

If you want, I can generate a PHP migration runner script that connects to the DB using `config.php` settings and runs the SQL safely with checks. Let me know if you want that.
