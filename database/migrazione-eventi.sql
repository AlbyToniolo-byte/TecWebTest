-- Kronos: prenotazione online degli eventi (terza versione del sito), per un database creato prima.
--   psql -d kronos -f database/migrazione-eventi.sql
-- I posti rimasti non si salvano: si calcolano da eventi.posti meno la somma dei posti prenotati.

CREATE TABLE IF NOT EXISTS prenotazioni_eventi (
    id        SERIAL PRIMARY KEY,
    codice    VARCHAR(12)  NOT NULL UNIQUE,   -- con l'email, serve a modificarla o cancellarla
    evento_id INTEGER      NOT NULL REFERENCES eventi(id) ON DELETE CASCADE,
    nome      VARCHAR(120) NOT NULL,
    email     VARCHAR(200) NOT NULL,
    posti     SMALLINT     NOT NULL CHECK (posti BETWEEN 1 AND 10),
    utente_id INTEGER REFERENCES utenti(id) ON DELETE SET NULL,
    creata_il TIMESTAMP    NOT NULL DEFAULT NOW()
);
