-- Kronos: account e login (terza versione del sito), per un database creato prima.
--   psql -d kronos -f database/migrazione-utenti.sql
-- Le versioni 1 e 2 continuano a funzionare: la nuova colonna utente_id resta NULL.

CREATE TABLE IF NOT EXISTS utenti (
    id            SERIAL PRIMARY KEY,
    nome_utente   VARCHAR(30)  NOT NULL UNIQUE,
    nome          VARCHAR(120) NOT NULL,
    email         VARCHAR(200) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    ruolo         VARCHAR(12)  NOT NULL DEFAULT 'visitatore' CHECK (ruolo IN ('visitatore', 'admin')),
    creato_il     TIMESTAMP    NOT NULL DEFAULT NOW()
);

ALTER TABLE prenotazioni         ADD COLUMN IF NOT EXISTS utente_id INTEGER REFERENCES utenti(id) ON DELETE SET NULL;
ALTER TABLE richieste_membership ADD COLUMN IF NOT EXISTS utente_id INTEGER REFERENCES utenti(id) ON DELETE SET NULL;
ALTER TABLE ordini               ADD COLUMN IF NOT EXISTS utente_id INTEGER REFERENCES utenti(id) ON DELETE SET NULL;

-- Account di prova: admin / admin e user / user
INSERT INTO utenti (nome_utente, nome, email, password_hash, ruolo) VALUES
('admin', 'Staff del museo', 'admin@kronos-museo.it', '$2y$12$R6wHoOKenYBky7iQIhDpPOJUsBTKGiccCb638BzonWVZd2PgmvyK.', 'admin'),
('user', 'Visitatore di prova', 'user@kronos-museo.it', '$2y$12$a3CkqOLfuC7KQLnBXznXkuItuQaXaVHrOplmWS5p6ourL/X1iL2.y', 'visitatore')
ON CONFLICT (nome_utente) DO NOTHING;
