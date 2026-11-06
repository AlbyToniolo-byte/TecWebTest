-- Porta un database creato con lo schema precedente a quello attuale, senza perdere i dati.
-- Esegui una sola volta:  psql -d kronos -f database/migrazione-requisiti.sql
BEGIN;

-- Vantaggi delle tessere: da un campo con un vantaggio per riga a una tabella (prima forma normale)
CREATE TABLE vantaggi_membership (
    id         SERIAL PRIMARY KEY,
    livello_id INTEGER      NOT NULL REFERENCES livelli_membership(id) ON DELETE CASCADE,
    testo      VARCHAR(200) NOT NULL,
    ordine     SMALLINT     NOT NULL
);
INSERT INTO vantaggi_membership (livello_id, testo, ordine)
SELECT l.id, v.testo, v.ordine
FROM livelli_membership l,
     LATERAL unnest(string_to_array(l.vantaggi, E'\n')) WITH ORDINALITY AS v(testo, ordine);
ALTER TABLE livelli_membership DROP COLUMN vantaggi;

-- Totali: dati derivati dalle righe, non si salvano più
ALTER TABLE prenotazioni DROP COLUMN totale;
ALTER TABLE ordini DROP COLUMN totale;

-- Campo di testo libero della prenotazione
ALTER TABLE prenotazioni ADD COLUMN note VARCHAR(500);

COMMIT;
