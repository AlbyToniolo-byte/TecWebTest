-- Kronos: Museo dei Mondi Perduti
-- Schema PostgreSQL. Esegui con:  psql -d kronos -f database/schema.sql

DROP TABLE IF EXISTS ordini_righe, ordini, prodotti,
    richieste_membership, livelli_membership, iscrizioni_newsletter,
    prenotazioni_righe, prenotazioni, tipi_biglietto, orari,
    eventi, reperti, sezioni_mostra, mostre, membri_team CASCADE;

-- Le cinque aree tematiche (mostre permanenti e speciali)
CREATE TABLE mostre (
    id              SERIAL PRIMARY KEY,
    slug            VARCHAR(40)  NOT NULL UNIQUE,
    titolo          VARCHAR(120) NOT NULL,
    sottotitolo     VARCHAR(200) NOT NULL,
    sommario        TEXT         NOT NULL,   -- testo breve per elenchi e home
    introduzione    TEXT         NOT NULL,   -- primo paragrafo della pagina mostra
    collocazione    VARCHAR(80)  NOT NULL,   -- piano e sale
    immagine        VARCHAR(200) NOT NULL,   -- percorso relativo dell'immagine di apertura
    tema            VARCHAR(20)  NOT NULL,   -- chiave della palette in css/themes.css
    speciale        BOOLEAN      NOT NULL DEFAULT FALSE,
    nota_speciale   TEXT,
    data_inizio     DATE,
    data_fine       DATE,
    ordine          SMALLINT     NOT NULL    -- ordine di discesa: dal cielo all'abisso
);

CREATE TABLE sezioni_mostra (
    id        SERIAL PRIMARY KEY,
    mostra_id INTEGER      NOT NULL REFERENCES mostre(id) ON DELETE CASCADE,
    titolo    VARCHAR(120) NOT NULL,
    testo     TEXT         NOT NULL,
    ordine    SMALLINT     NOT NULL
);

-- Oggetti principali esposti in ogni mostra
CREATE TABLE reperti (
    id          SERIAL PRIMARY KEY,
    mostra_id   INTEGER      NOT NULL REFERENCES mostre(id) ON DELETE CASCADE,
    nome        VARCHAR(120) NOT NULL,
    datazione   VARCHAR(80)  NOT NULL,
    descrizione TEXT         NOT NULL,
    ordine      SMALLINT     NOT NULL
);

CREATE TABLE eventi (
    id          SERIAL PRIMARY KEY,
    mostra_id   INTEGER REFERENCES mostre(id) ON DELETE SET NULL,
    titolo      VARCHAR(120) NOT NULL,
    descrizione TEXT         NOT NULL,
    inizio      TIMESTAMP    NOT NULL,
    durata_min  SMALLINT     NOT NULL,
    luogo       VARCHAR(80)  NOT NULL,
    prezzo      NUMERIC(6,2) NOT NULL DEFAULT 0,
    posti       SMALLINT     NOT NULL
);

-- giorno: 1 = lunedì ... 7 = domenica (ISODOW di PostgreSQL)
CREATE TABLE orari (
    giorno    SMALLINT PRIMARY KEY CHECK (giorno BETWEEN 1 AND 7),
    apertura  TIME,
    chiusura  TIME     -- NULL = chiuso
);

CREATE TABLE tipi_biglietto (
    id          SERIAL PRIMARY KEY,
    nome        VARCHAR(60)  NOT NULL,
    descrizione VARCHAR(200) NOT NULL,
    prezzo      NUMERIC(6,2) NOT NULL,
    ordine      SMALLINT     NOT NULL
);

CREATE TABLE prenotazioni (
    id          SERIAL PRIMARY KEY,
    codice      VARCHAR(12)  NOT NULL UNIQUE,
    nome        VARCHAR(120) NOT NULL,
    email       VARCHAR(200) NOT NULL,
    data_visita DATE         NOT NULL,
    fascia      VARCHAR(20)  NOT NULL,
    totale      NUMERIC(8,2) NOT NULL,
    creata_il   TIMESTAMP    NOT NULL DEFAULT NOW()
);

CREATE TABLE prenotazioni_righe (
    prenotazione_id   INTEGER  NOT NULL REFERENCES prenotazioni(id) ON DELETE CASCADE,
    tipo_biglietto_id INTEGER  NOT NULL REFERENCES tipi_biglietto(id),
    quantita          SMALLINT NOT NULL CHECK (quantita > 0),
    prezzo_unitario   NUMERIC(6,2) NOT NULL,
    PRIMARY KEY (prenotazione_id, tipo_biglietto_id)
);

CREATE TABLE iscrizioni_newsletter (
    id        SERIAL PRIMARY KEY,
    email     VARCHAR(200) NOT NULL UNIQUE,
    creata_il TIMESTAMP    NOT NULL DEFAULT NOW()
);

CREATE TABLE livelli_membership (
    id        SERIAL PRIMARY KEY,
    nome      VARCHAR(60)  NOT NULL,
    prezzo    NUMERIC(6,2) NOT NULL,
    vantaggi  TEXT         NOT NULL,   -- un vantaggio per riga
    ordine    SMALLINT     NOT NULL
);

CREATE TABLE richieste_membership (
    id         SERIAL PRIMARY KEY,
    livello_id INTEGER      NOT NULL REFERENCES livelli_membership(id),
    nome       VARCHAR(120) NOT NULL,
    email      VARCHAR(200) NOT NULL,
    creata_il  TIMESTAMP    NOT NULL DEFAULT NOW()
);

CREATE TABLE prodotti (
    id          SERIAL PRIMARY KEY,
    mostra_id   INTEGER REFERENCES mostre(id) ON DELETE SET NULL,
    nome        VARCHAR(120) NOT NULL,
    descrizione TEXT         NOT NULL,
    categoria   VARCHAR(40)  NOT NULL,
    prezzo      NUMERIC(7,2) NOT NULL,
    disponibili SMALLINT     NOT NULL DEFAULT 0,
    immagine    VARCHAR(200) NOT NULL    -- percorso relativo, in img/prodotti/
);

-- Ordini con ritiro al bookshop del museo (nessun pagamento online)
CREATE TABLE ordini (
    id        SERIAL PRIMARY KEY,
    codice    VARCHAR(12)  NOT NULL UNIQUE,
    nome      VARCHAR(120) NOT NULL,
    email     VARCHAR(200) NOT NULL,
    totale    NUMERIC(8,2) NOT NULL,
    creato_il TIMESTAMP    NOT NULL DEFAULT NOW()
);

CREATE TABLE ordini_righe (
    ordine_id       INTEGER  NOT NULL REFERENCES ordini(id) ON DELETE CASCADE,
    prodotto_id     INTEGER  NOT NULL REFERENCES prodotti(id),
    quantita        SMALLINT NOT NULL CHECK (quantita > 0),
    prezzo_unitario NUMERIC(7,2) NOT NULL,
    PRIMARY KEY (ordine_id, prodotto_id)
);

CREATE TABLE membri_team (
    id     SERIAL PRIMARY KEY,
    nome   VARCHAR(120) NOT NULL,
    ruolo  VARCHAR(120) NOT NULL,
    bio    TEXT         NOT NULL,
    ordine SMALLINT     NOT NULL
);
