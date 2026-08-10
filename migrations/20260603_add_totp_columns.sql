-- Migración: Agregar columnas TOTP para autenticación 2FA
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS totp_secret VARCHAR(255) NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS totp_enabled TINYINT(1) DEFAULT 0;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS totp_setup_date TIMESTAMP NULL;
