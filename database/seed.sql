-- Dati iniziali. Esegui dopo schema.sql:  psql -d kronos -f database/seed.sql

INSERT INTO mostre (slug, titolo, sottotitolo, sommario, introduzione, collocazione, immagine, tema, speciale, nota_speciale, data_inizio, data_fine, ordine) VALUES
('mondo-celeste', 'Il Mondo Celeste', 'Come abbiamo imparato a leggere il cielo',
 'Costellazioni, calendari e strumenti: per millenni il cielo è stato orologio, mappa e racconto. Qui si parte dall''alto.',
 'Prima dei telescopi, il cielo era il primo libro che l''umanità sapeva leggere. Le stelle indicavano quando seminare, dove navigare e quali storie raccontare. Questa mostra ripercorre quel dialogo, dalle tavolette babilonesi alla macchina di Anticitera, fino alle sonde che oggi lasciano il Sistema Solare.',
 'Terzo piano, Sale 11–12 e terrazza', 'img/mostre/mondo-celeste.svg', 'celeste', FALSE, NULL, NULL, NULL, 1),

('mitologia', 'Dèi ed eroi della Grecia', 'Il mito, da Omero al grande schermo',
 'L''Olimpo, gli eroi e i loro mostri. Al centro della sala, a grandezza naturale, il cavallo di Troia.',
 'Per i Greci il mito non era una favola: era il modo di spiegare il mondo, la guerra, il destino. Il percorso attraversa l''Olimpo, le imprese degli eroi e il lungo ritorno di Odisseo verso Itaca, accompagnati da ceramiche, calchi di statue e ricostruzioni.',
 'Secondo piano, Sale 8–10', 'img/mostre/mitologia.svg', 'mitologia', TRUE,
 'In occasione dell''uscita del film The Odyssey, la sala centrale ospita fino al 31 gennaio 2027 un''installazione dedicata al cavallo di Troia, con materiali sull''Iliade e sull''Odissea.',
 '2026-07-01', '2027-01-31', 2),

('dinosauri', 'Il regno dei dinosauri', 'Centosessantacinque milioni di anni sulla Terra',
 'Dal Triassico all''impatto che chiuse il Cretaceo: scheletri, uova e impronte di chi ha dominato il pianeta prima di noi.',
 'I dinosauri sono comparsi circa 230 milioni di anni fa e hanno abitato ogni continente fino a 66 milioni di anni fa. Non sono scomparsi del tutto: gli uccelli sono i loro discendenti diretti. La mostra segue questa storia attraverso tre periodi geologici e un finale improvviso.',
 'Primo piano, Sale 1–4', 'img/mostre/dinosauri.svg', 'dinosauri', FALSE, NULL, NULL, NULL, 3),

('era-glaciale', 'L''era glaciale', 'Il mondo sotto il ghiaccio',
 'Mammut, tigri dai denti a sciabola e i primi esseri umani che hanno attraversato un continente gelato.',
 'Durante il Pleistocene, iniziato 2,6 milioni di anni fa, enormi calotte di ghiaccio sono avanzate e si sono ritirate più volte. Nell''ultimo massimo glaciale, circa 20.000 anni fa, il livello del mare era più basso di oltre cento metri e si poteva camminare dall''Asia all''America.',
 'Piano terra, Sale 5–6', 'img/mostre/era-glaciale.svg', 'glaciale', FALSE, NULL, NULL, NULL, 4),

('atlantide', 'Atlantide', 'La città che il mare ha inghiottito',
 'Il mito raccontato da Platone e le città sommerse che esistono davvero. Si chiude in fondo al mare.',
 'Platone descrive Atlantide nel Timeo e nel Crizia come una potenza marittima scomparsa «in un solo giorno e in una notte terribile». Nessuna prova archeologica la conferma, ma il mare ha davvero sommerso città e coste. La mostra mette il racconto accanto ai luoghi reali che potrebbero averlo ispirato.',
 'Piano interrato, Sale 7–8', 'img/mostre/atlantide.svg', 'atlantide', FALSE, NULL, NULL, NULL, 5);

INSERT INTO sezioni_mostra (mostra_id, titolo, testo, ordine) VALUES
((SELECT id FROM mostre WHERE slug='mondo-celeste'), 'Il cielo come calendario',
 'Babilonesi ed Egizi osservavano il sorgere delle stelle per prevedere stagioni e piene dei fiumi. Lo zodiaco, i dodici mesi e la settimana di sette giorni nascono da queste osservazioni.', 1),
((SELECT id FROM mostre WHERE slug='mondo-celeste'), 'Strumenti per misurare l''infinito',
 'Astrolabi, sfere armillari e quadranti permettevano di calcolare l''ora, la latitudine e la posizione dei pianeti. In sala puoi manovrare una riproduzione di astrolabio del XIV secolo.', 2),
((SELECT id FROM mostre WHERE slug='mondo-celeste'), 'Oltre l''atmosfera',
 'L''ultima sala racconta l''esplorazione spaziale, dalle prime fotografie della Terra vista dalla Luna alle sonde Voyager, oggi oltre il confine del Sistema Solare.', 3),

((SELECT id FROM mostre WHERE slug='mitologia'), 'L''Olimpo',
 'Zeus, Era, Atena, Poseidone: gli dèi greci litigano, si innamorano e si vendicano come gli esseri umani. Ceramiche e calchi mostrano come venivano riconosciuti grazie ai loro attributi.', 1),
((SELECT id FROM mostre WHERE slug='mitologia'), 'La guerra di Troia',
 'Dieci anni di assedio raccontati nell''Iliade e un inganno che li conclude. Il cavallo ricostruito al centro della sala è alto nove metri e si può osservare dall''interno.', 2),
((SELECT id FROM mostre WHERE slug='mitologia'), 'Il ritorno di Odisseo',
 'Il Ciclope, Circe, le Sirene, Scilla e Cariddi: le tappe del viaggio verso Itaca sono raccontate attraverso le immagini che hanno ispirato artisti per oltre duemila anni, fino al cinema.', 3),

((SELECT id FROM mostre WHERE slug='dinosauri'), 'Triassico: i primi passi',
 'I primi dinosauri erano piccoli e bipedi, e condividevano il supercontinente Pangea con rettili molto più grandi di loro. Nel corso di pochi milioni di anni si sarebbero diffusi ovunque.', 1),
((SELECT id FROM mostre WHERE slug='dinosauri'), 'Giurassico: i giganti',
 'I sauropodi come Brachiosaurus superavano i venti metri di lunghezza. Le loro impronte, conservate nel fango diventato roccia, raccontano come si muovevano in branco.', 2),
((SELECT id FROM mostre WHERE slug='dinosauri'), 'Cretaceo: la fine',
 'Sessantasei milioni di anni fa un asteroide di circa dieci chilometri colpì l''attuale penisola dello Yucatán. Il cratere di Chicxulub è la firma dell''estinzione che chiuse l''era dei dinosauri non aviani.', 3),

((SELECT id FROM mostre WHERE slug='era-glaciale'), 'Giganti col pelo',
 'Mammut lanosi, rinoceronti lanosi e alci giganti erano adattati a steppe fredde e asciutte. Alcuni mammut sono sopravvissuti sull''isola di Wrangel fino a circa 4.000 anni fa.', 1),
((SELECT id FROM mostre WHERE slug='era-glaciale'), 'Predatori del freddo',
 'Smilodon, leoni delle caverne e lupi preistorici: i loro crani mostrano come cacciavano prede molto più grandi di loro.', 2),
((SELECT id FROM mostre WHERE slug='era-glaciale'), 'I primi artisti',
 'Mentre il ghiaccio copriva l''Europa, gli esseri umani dipingevano le pareti delle grotte. La sala riproduce in scala reale una parete della grotta di Chauvet.', 3),

((SELECT id FROM mostre WHERE slug='atlantide'), 'Il racconto di Platone',
 'Secondo Platone, Atlantide si trovava oltre le Colonne d''Ercole e fu punita dagli dèi per la sua arroganza. Il racconto è una lezione politica più che una cronaca, e così lo leggevano già molti antichi.', 1),
((SELECT id FROM mostre WHERE slug='atlantide'), 'Thera e i Minoici',
 'Intorno al 1600 a.C. un''eruzione devastò l''isola di Thera, l''attuale Santorini, e colpì la civiltà minoica di Creta. Molti studiosi vi vedono un possibile eco del mito.', 2),
((SELECT id FROM mostre WHERE slug='atlantide'), 'Città davvero sommerse',
 'Pavlopetri in Grecia, Baia nel golfo di Napoli, Heracleion in Egitto: il mare ha davvero inghiottito strade, case e templi. L''ultima sala è un fondale ricostruito che si percorre a piedi.', 3);

INSERT INTO reperti (mostra_id, nome, datazione, descrizione, ordine) VALUES
((SELECT id FROM mostre WHERE slug='mondo-celeste'), 'Macchina di Anticitera (riproduzione)', 'II–I secolo a.C.', 'Un calcolatore a ingranaggi che prevedeva eclissi e posizioni dei pianeti.', 1),
((SELECT id FROM mostre WHERE slug='mondo-celeste'), 'Astrolabio planisferico', 'XIV secolo', 'Strumento in ottone per calcolare l''ora osservando il Sole o le stelle.', 2),
((SELECT id FROM mostre WHERE slug='mondo-celeste'), 'Frammento di meteorite ferroso', 'circa 4,5 miliardi di anni', 'Si può toccare: è più antico di qualsiasi roccia terrestre.', 3),

((SELECT id FROM mostre WHERE slug='mitologia'), 'Cavallo di Troia (ricostruzione)', '2026', 'Struttura in legno alta nove metri, con una passerella interna.', 1),
((SELECT id FROM mostre WHERE slug='mitologia'), 'Anfora a figure nere con Eracle', 'VI secolo a.C. (calco)', 'L''eroe affronta il leone di Nemea, la prima delle dodici fatiche.', 2),
((SELECT id FROM mostre WHERE slug='mitologia'), 'Atena Parthénos in scala', 'V secolo a.C. (ricostruzione)', 'Modello della statua di Fidia che si trovava nel Partenone.', 3),

((SELECT id FROM mostre WHERE slug='dinosauri'), 'Calco di Tyrannosaurus rex', '68–66 milioni di anni fa', 'Scheletro completo lungo dodici metri, nella posa di caccia.', 1),
((SELECT id FROM mostre WHERE slug='dinosauri'), 'Nido di uova di oviraptoride', 'circa 75 milioni di anni fa', 'Dimostra che alcuni dinosauri covavano le uova come gli uccelli.', 2),
((SELECT id FROM mostre WHERE slug='dinosauri'), 'Impronte di sauropode', 'circa 150 milioni di anni fa', 'Una pista di sei metri ricomposta sul pavimento della sala.', 3),

((SELECT id FROM mostre WHERE slug='era-glaciale'), 'Scheletro di mammut lanoso', 'circa 30.000 anni fa', 'Alto tre metri e mezzo al garrese, con zanne di oltre due metri.', 1),
((SELECT id FROM mostre WHERE slug='era-glaciale'), 'Cranio di Smilodon', 'circa 12.000 anni fa', 'I canini superiori misurano ventotto centimetri.', 2),
((SELECT id FROM mostre WHERE slug='era-glaciale'), 'Punte di lancia in selce', 'circa 13.000 anni fa', 'Lavorate a pressione, sono tra le più antiche tracce di caccia organizzata.', 3),

((SELECT id FROM mostre WHERE slug='atlantide'), 'Affresco minoico dei delfini (riproduzione)', 'circa 1500 a.C.', 'Dal palazzo di Cnosso, a Creta.', 1),
((SELECT id FROM mostre WHERE slug='atlantide'), 'Pomice dell''eruzione di Thera', 'circa 1600 a.C.', 'Campioni raccolti sull''isola di Santorini.', 2),
((SELECT id FROM mostre WHERE slug='atlantide'), 'Plastico di Pavlopetri', 'circa 3000 a.C.', 'La città sommersa più antica conosciuta, ricostruita in scala 1:200.', 3);

INSERT INTO eventi (mostra_id, titolo, descrizione, inizio, durata_min, luogo, prezzo, posti) VALUES
((SELECT id FROM mostre WHERE slug='mitologia'), 'Dall''Odissea al cinema', 'Un incontro su come il viaggio di Odisseo è stato raccontato, dipinto e filmato.', '2026-11-07 18:00', 90, 'Auditorium', 0, 180),
((SELECT id FROM mostre WHERE slug='mondo-celeste'), 'Osservazione in terrazza', 'Con i telescopi del museo e un astronomo che guida l''osservazione. Si annulla in caso di nuvole.', '2026-11-13 20:30', 120, 'Terrazza', 8, 40),
((SELECT id FROM mostre WHERE slug='dinosauri'), 'Piccoli paleontologi', 'Laboratorio per bambini dai 6 ai 10 anni: si scava, si pulisce e si riconosce un fossile.', '2026-11-15 10:30', 75, 'Laboratorio didattico', 5, 20),
((SELECT id FROM mostre WHERE slug='atlantide'), 'Visita guidata: le città sommerse', 'Un''archeologa subacquea racconta come si scava sotto il mare.', '2026-11-21 16:00', 60, 'Sale 7–8', 4, 25),
((SELECT id FROM mostre WHERE slug='era-glaciale'), 'Notte tra i ghiacci', 'Apertura serale con visita a lume di torcia nella sala della grotta.', '2026-11-28 21:00', 90, 'Sale 5–6', 10, 60),
(NULL, 'Kronos per tutti: visita in LIS', 'Visita guidata a tutto il museo in Lingua dei Segni Italiana.', '2026-12-05 15:00', 90, 'Atrio', 0, 25),
((SELECT id FROM mostre WHERE slug='mitologia'), 'Dentro il cavallo', 'Visita guidata all''installazione con apertura del vano interno.', '2026-12-12 11:00', 45, 'Sala 9', 4, 15);

INSERT INTO orari (giorno, apertura, chiusura) VALUES
(1, NULL, NULL),
(2, '09:30', '19:00'),
(3, '09:30', '19:00'),
(4, '09:30', '19:00'),
(5, '09:30', '22:00'),
(6, '09:30', '20:00'),
(7, '09:30', '20:00');

INSERT INTO tipi_biglietto (nome, descrizione, prezzo, ordine) VALUES
('Intero', 'Dai 26 ai 64 anni', 16.00, 1),
('Ridotto', 'Dai 18 ai 25 anni e dai 65 anni in su', 11.00, 2),
('Ragazzi', 'Dai 6 ai 17 anni', 7.00, 3),
('Bambini', 'Fino a 5 anni', 0.00, 4);

INSERT INTO livelli_membership (nome, prezzo, vantaggi, ordine) VALUES
('Amico', 45.00, E'Ingresso illimitato per un anno\nSconto del 10% al bookshop\nNewsletter con le anteprime', 1),
('Famiglia', 90.00, E'Ingresso illimitato per 2 adulti e fino a 3 ragazzi\nUn laboratorio gratuito a semestre\nSconto del 10% al bookshop', 2),
('Mecenate', 250.00, E'Ingresso illimitato con un ospite\nInviti alle inaugurazioni\nVisita privata con un curatore\nSconto del 15% al bookshop', 3);

INSERT INTO prodotti (mostra_id, nome, descrizione, categoria, prezzo, disponibili, immagine) VALUES
((SELECT id FROM mostre WHERE slug='dinosauri'), 'Kit di scavo del fossile', 'Un blocco di gesso con dentro la replica di un dente di T. rex, scalpello e pennello.', 'Bambini', 18.00, 40, 'img/prodotti/kit-scavo-fossile.svg'),
((SELECT id FROM mostre WHERE slug='dinosauri'), 'Atlante dei dinosauri', 'Duecento specie illustrate, con mappe dei ritrovamenti. 240 pagine.', 'Libri', 29.00, 25, 'img/prodotti/atlante-dinosauri.svg'),
((SELECT id FROM mostre WHERE slug='mitologia'), 'Odissea, edizione illustrata', 'Traduzione integrale con le tavole della mostra. 512 pagine.', 'Libri', 34.00, 30, 'img/prodotti/odissea-illustrata.svg'),
((SELECT id FROM mostre WHERE slug='mitologia'), 'Cavallo di Troia in legno', 'Modellino da montare, 28 pezzi in legno di betulla.', 'Riproduzioni', 24.00, 15, 'img/prodotti/cavallo-troia-legno.svg'),
((SELECT id FROM mostre WHERE slug='mitologia'), 'Kylix a figure rosse', 'Riproduzione in ceramica di una coppa attica, dipinta a mano.', 'Riproduzioni', 65.00, 6, 'img/prodotti/kylix-figure-rosse.svg'),
((SELECT id FROM mostre WHERE slug='era-glaciale'), 'Mammut di peluche', 'Alto 30 cm, in fibra riciclata.', 'Bambini', 22.00, 0, 'img/prodotti/mammut-peluche.svg'),
((SELECT id FROM mostre WHERE slug='era-glaciale'), 'Tazza termocromica Pleistocene', 'Con l''acqua calda il ghiaccio si ritira e compare la steppa.', 'Casa', 14.00, 50, 'img/prodotti/tazza-pleistocene.svg'),
((SELECT id FROM mostre WHERE slug='atlantide'), 'Platone, Timeo e Crizia', 'I due dialoghi che raccontano Atlantide, con testo greco a fronte.', 'Libri', 16.00, 20, 'img/prodotti/platone-timeo-crizia.svg'),
((SELECT id FROM mostre WHERE slug='mondo-celeste'), 'Planisfero celeste girevole', 'Mostra il cielo visibile in ogni ora dell''anno alla latitudine dell''Italia.', 'Riproduzioni', 19.00, 35, 'img/prodotti/planisfero-celeste.svg'),
((SELECT id FROM mostre WHERE slug='mondo-celeste'), 'Poster delle costellazioni', 'Stampa 50×70 cm su carta di cotone.', 'Casa', 25.00, 18, 'img/prodotti/poster-costellazioni.svg');

INSERT INTO membri_team (nome, ruolo, bio, ordine) VALUES
('Elena Marchetti', 'Direttrice', 'Archeologa, ha guidato scavi a Creta e a Santorini prima di fondare Kronos.', 1),
('Davide Rinaldi', 'Curatore, Dinosauri ed Era glaciale', 'Paleontologo, studia i vertebrati del Pleistocene europeo.', 2),
('Sofia Greco', 'Curatrice, Mitologia e Atlantide', 'Filologa classica, si occupa della ricezione di Omero nel cinema.', 3),
('Marco Bellini', 'Curatore, Mondo Celeste', 'Astrofisico e divulgatore, cura le serate di osservazione in terrazza.', 4),
('Giulia Ferri', 'Accessibilità e servizi educativi', 'Progetta percorsi tattili, visite in LIS e laboratori per le scuole.', 5);
