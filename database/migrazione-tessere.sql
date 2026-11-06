-- Aggiunge il codice alle richieste di tessera, per poterle modificare o annullare dal sito.
-- Da eseguire una volta, dopo migrazione-requisiti.sql:  psql -d kronos -f database/migrazione-tessere.sql
BEGIN;
ALTER TABLE richieste_membership ADD COLUMN codice VARCHAR(12);
-- codice casuale di 8 caratteri esadecimali, come quelli generati da codice_casuale() in PHP
UPDATE richieste_membership SET codice = upper(substr(md5(random()::text || id::text), 1, 8));
ALTER TABLE richieste_membership ALTER COLUMN codice SET NOT NULL, ADD CONSTRAINT richieste_membership_codice_key UNIQUE (codice);
COMMIT;
