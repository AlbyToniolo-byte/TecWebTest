<?php
// Gestione del museo, solo per lo staff (ruolo admin): calendario degli eventi (aggiunta,
// modifica, cancellazione) e prenotazioni dei prossimi giorni. I controlli sono ripetuti in js/validation.js.
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';
require_once __DIR__ . '/prenotazione.php';

richiedi_admin();

$mostre = query('SELECT id, titolo FROM mostre ORDER BY ordine');
$idMostre = array_map('intval', array_column($mostre, 'id'));
$errori = [];
$vuoto = ['id' => 0, 'titolo' => '', 'mostra' => '', 'descrizione' => '', 'data' => '', 'ora' => '', 'durata' => '60',
          'luogo' => '', 'prezzo' => '0', 'posti' => '30'];
$v = $vuoto;
$daCancellare = null;   // evento per la domanda "vuoi davvero cancellarlo?"

// Legge e controlla i campi dell'evento; restituisce [valori, errori]
function evento_leggi(array $post, array $idMostre): array
{
    $v = [];
    foreach (['titolo', 'mostra', 'descrizione', 'data', 'ora', 'durata', 'luogo', 'prezzo', 'posti'] as $k) {
        $v[$k] = trim((string) ($post[$k] ?? ''));
    }
    $v['id'] = (int) ($post['id'] ?? 0);
    $e = [];
    if ($v['titolo'] === '' || mb_strlen($v['titolo']) > 120) {
        $e['titolo'] = 'Scrivi il titolo dell\'evento (al massimo 120 caratteri)';
    }
    if ($v['mostra'] !== '' && !in_array((int) $v['mostra'], $idMostre, true)) {
        $e['mostra'] = 'Scegli una mostra dell\'elenco, oppure «Nessuna»';
    }
    if ($v['descrizione'] === '' || mb_strlen($v['descrizione']) > 1000) {
        $e['descrizione'] = 'Scrivi la descrizione (al massimo 1000 caratteri)';
    }
    $d = DateTime::createFromFormat('!Y-m-d', $v['data']);
    if (!$d || $d->format('Y-m-d') !== $v['data']) {
        $e['data'] = 'Scegli la data dell\'evento';
    }
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v['ora'])) {
        $e['ora'] = 'Scrivi l\'ora di inizio, per esempio 18:30';
    }
    if (filter_var($v['durata'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 15, 'max_range' => 480]]) === false) {
        $e['durata'] = 'La durata è in minuti, tra 15 e 480';
    }
    if ($v['luogo'] === '' || mb_strlen($v['luogo']) > 80) {
        $e['luogo'] = 'Scrivi dove si tiene (al massimo 80 caratteri)';
    }
    $v['prezzo'] = str_replace(',', '.', $v['prezzo']);
    if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $v['prezzo'])) {
        $e['prezzo'] = 'Scrivi il prezzo in euro, per esempio 8 o 7,50 (0 se è gratuito)';
    }
    if (filter_var($v['posti'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]) === false) {
        $e['posti'] = 'I posti sono un numero tra 1 e 500';
    }
    return [$v, $e];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido()) {
    $azione = (string) ($_POST['azione'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($azione === 'salva') {
        [$v, $errori] = evento_leggi($_POST, $idMostre);
        // un evento già prenotato non può scendere sotto i posti venduti
        $giaPrenotati = $v['id'] ? (int) query_riga('SELECT COALESCE(SUM(posti), 0) AS n FROM prenotazioni_eventi WHERE evento_id = :id',
            ['id' => $v['id']])['n'] : 0;
        if (!isset($errori['posti']) && (int) $v['posti'] < $giaPrenotati) {
            $errori['posti'] = 'Sono già prenotati ' . $giaPrenotati . ' posti: la capienza non può essere più bassa';
        }
        if (!$errori) {
            $p = ['t' => $v['titolo'], 'm' => $v['mostra'] === '' ? null : (int) $v['mostra'], 'd' => $v['descrizione'],
                  'i' => $v['data'] . ' ' . $v['ora'], 'du' => (int) $v['durata'], 'l' => $v['luogo'],
                  'p' => $v['prezzo'], 'po' => (int) $v['posti']];
            if ($v['id']) {
                $st = db()->prepare('UPDATE eventi SET mostra_id = :m, titolo = :t, descrizione = :d, inizio = :i, durata_min = :du,
                                     luogo = :l, prezzo = :p, posti = :po WHERE id = :id');
                $st->execute($p + ['id' => $v['id']]);
                flash('Evento «' . $v['titolo'] . '» aggiornato.');
            } else {
                db()->prepare('INSERT INTO eventi (mostra_id, titolo, descrizione, inizio, durata_min, luogo, prezzo, posti)
                               VALUES (:m, :t, :d, :i, :du, :l, :p, :po)')->execute($p);
                flash('Evento «' . $v['titolo'] . '» aggiunto al calendario.');
            }
            vai('admin.php#eventi');
        }
    } elseif ($azione === 'chiedi-cancellazione') {
        $daCancellare = query_riga('SELECT id, titolo, inizio FROM eventi WHERE id = :id', ['id' => $id]);
    } elseif ($azione === 'cancella') {
        $ev = query_riga('SELECT titolo FROM eventi WHERE id = :id', ['id' => $id]);
        if ($ev) {
            db()->prepare('DELETE FROM eventi WHERE id = :id')->execute(['id' => $id]);
            flash('Evento «' . $ev['titolo'] . '» cancellato.');
        }
        vai('admin.php#eventi');
    }
}

// Modifica: i valori dell'evento scelto (l'id nell'indirizzo non è un dato personale)
if (!$errori && isset($_GET['modifica'])) {
    $ev = query_riga('SELECT * FROM eventi WHERE id = :id', ['id' => (int) $_GET['modifica']]);
    if ($ev) {
        $t = strtotime($ev['inizio']);
        $v = ['id' => (int) $ev['id'], 'titolo' => $ev['titolo'], 'mostra' => (string) $ev['mostra_id'], 'descrizione' => $ev['descrizione'],
              'data' => date('Y-m-d', $t), 'ora' => date('H:i', $t), 'durata' => (string) $ev['durata_min'], 'luogo' => $ev['luogo'],
              'prezzo' => rtrim(rtrim(number_format((float) $ev['prezzo'], 2, '.', ''), '0'), '.'), 'posti' => (string) $ev['posti']];
    }
}

$eventi = query('SELECT e.id, e.titolo, e.inizio, e.luogo, e.prezzo, e.posti, m.titolo AS mostra,
                        COALESCE((SELECT SUM(pe.posti) FROM prenotazioni_eventi pe WHERE pe.evento_id = e.id), 0) AS prenotati
                 FROM eventi e LEFT JOIN mostre m ON m.id = e.mostra_id ORDER BY e.inizio');
$giorni = query("SELECT p.data_visita, p.fascia, COUNT(DISTINCT p.id) AS prenotazioni, COALESCE(SUM(r.quantita), 0) AS biglietti
                 FROM prenotazioni p LEFT JOIN prenotazioni_righe r ON r.prenotazione_id = p.id
                 WHERE p.data_visita BETWEEN CURRENT_DATE AND CURRENT_DATE + 13
                 GROUP BY p.data_visita, p.fascia ORDER BY p.data_visita, p.fascia");
$adesso = date('Y-m-d H:i:s');
$iscritti = query('SELECT e.titolo, e.inizio, pe.nome, pe.email, pe.posti, pe.codice
                   FROM prenotazioni_eventi pe JOIN eventi e ON e.id = pe.evento_id
                   WHERE e.inizio >= NOW() ORDER BY e.inizio, pe.creata_il');

briciole([['account.php', 'Area personale'], [null, 'Gestione del museo']]);
pagina_inizio('Gestione del museo', 'Area riservata allo staff del museo Kronos: eventi e prenotazioni.', 'admin');
?>
<div class="contenitore">
    <div class="intestazione-pagina">
        <h1>Gestione del museo</h1>
        <p class="introduzione">Il calendario degli eventi che compare in home e in «Mostre ed eventi», e l'affluenza prenotata dei prossimi giorni.</p>
    </div>

    <?php if ($daCancellare): ?>
        <section class="blocco-form" aria-labelledby="titolo-conferma">
            <h2 id="titolo-conferma" tabindex="-1" data-focus-iniziale="">Vuoi cancellare l'evento «<?= e($daCancellare['titolo']) ?>»?</h2>
            <p>È in programma <?= e(data_estesa($daCancellare['inizio'], true)) ?> alle <?= e(ora($daCancellare['inizio'])) ?>. Sparisce dal sito e non si può recuperare<?= ($n = (int) query_riga('SELECT COALESCE(SUM(posti), 0) AS n FROM prenotazioni_eventi WHERE evento_id = :id', ['id' => $daCancellare['id']])['n']) ? ', insieme alle prenotazioni di ' . $n . ($n === 1 ? ' posto' : ' posti') . ': avvisa chi ha prenotato' : '' ?>.</p>
            <div class="azioni">
                <form action="admin.php" method="post">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="azione" value="cancella" />
                    <input type="hidden" name="id" value="<?= (int) $daCancellare['id'] ?>" />
                    <button class="bottone" type="submit">Sì, cancella l'evento</button>
                </form>
                <a class="bottone bottone-secondario" href="admin.php#eventi">No, tienilo</a>
            </div>
        </section>
    <?php endif; ?>

    <section id="eventi" class="blocco-form" aria-labelledby="titolo-eventi">
        <h2 id="titolo-eventi">Eventi</h2>
        <?php if ($eventi): ?>
            <div class="tabella-scorre" role="region" aria-labelledby="titolo-eventi" tabindex="0">
                <table class="tabella-larga">
                    <thead><tr><th scope="col">Evento</th><th scope="col">Quando</th><th scope="col">Dove</th><th scope="col" class="numero">Prezzo</th><th scope="col" class="numero">Prenotati</th><th scope="col"><span class="visually-hidden">Azioni</span></th></tr></thead>
                    <tbody>
                        <?php foreach ($eventi as $ev): ?>
                            <tr>
                                <th scope="row"><?= e($ev['titolo']) ?><?= $ev['mostra'] ? '<br /><span class="v2-nota">' . e($ev['mostra']) . '</span>' : '' ?></th>
                                <td><?= e(data_estesa($ev['inizio'], true)) ?>, <?= e(ora($ev['inizio'])) ?><?= $ev['inizio'] < $adesso ? ' (passato)' : '' ?></td>
                                <td><?= e($ev['luogo']) ?></td>
                                <td class="numero"><?= e(prezzo($ev['prezzo'])) ?></td>
                                <td class="numero"><?= (int) $ev['prenotati'] ?> su <?= (int) $ev['posti'] ?></td>
                                <td>
                                    <div class="azioni-riga">
                                        <a href="admin.php?modifica=<?= (int) $ev['id'] ?>#modulo-evento">Modifica<span class="visually-hidden"> «<?= e($ev['titolo']) ?>»</span></a>
                                        <form action="admin.php" method="post">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="azione" value="chiedi-cancellazione" />
                                            <input type="hidden" name="id" value="<?= (int) $ev['id'] ?>" />
                                            <button class="bottone-testo" type="submit">Cancella<span class="visually-hidden"> «<?= e($ev['titolo']) ?>»</span></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Il calendario è vuoto.</p>
        <?php endif; ?>
    </section>

    <section id="modulo-evento" class="blocco-form" aria-labelledby="titolo-modulo">
        <h2 id="titolo-modulo"><?= $v['id'] ? 'Modifica «' . e($v['titolo']) . '»' : 'Aggiungi un evento' ?></h2>
        <p>Tutti i campi sono obbligatori, tranne la mostra.</p>
        <?= riepilogo_errori($errori) ?>
        <form action="admin.php#modulo-evento" method="post" novalidate="novalidate" data-valida="">
            <?= campo_csrf() ?>
            <input type="hidden" name="azione" value="salva" />
            <input type="hidden" name="id" value="<?= (int) $v['id'] ?>" />
            <div class="campo">
                <label for="titolo">Titolo</label>
                <input type="text" id="titolo" name="titolo" maxlength="120" value="<?= e($v['titolo']) ?>" required="required" aria-required="true"
                       data-msg-vuoto="Scrivi il titolo dell'evento"<?= attr_campo('titolo', $errori) ?> />
                <?= errore_campo('titolo', $errori) ?>
            </div>
            <div class="campo">
                <label for="mostra">Mostra collegata</label>
                <select id="mostra" name="mostra"<?= attr_campo('mostra', $errori) ?>>
                    <option value="">Nessuna</option>
                    <?php foreach ($mostre as $m): ?>
                        <option value="<?= (int) $m['id'] ?>"<?= $v['mostra'] === (string) $m['id'] ? ' selected="selected"' : '' ?>><?= e($m['titolo']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= errore_campo('mostra', $errori) ?>
            </div>
            <div class="campo">
                <label for="descrizione">Descrizione</label>
                <textarea id="descrizione" name="descrizione" rows="4" cols="40" maxlength="1000" required="required" aria-required="true"
                          data-msg-vuoto="Scrivi la descrizione" data-msg-lunghezza="La descrizione può avere al massimo 1000 caratteri"<?= attr_campo('descrizione', $errori) ?>><?= e($v['descrizione']) ?></textarea>
                <?= errore_campo('descrizione', $errori) ?>
            </div>
            <div class="campi-affiancati">
                <div class="campo">
                    <label for="data">Data</label>
                    <input type="date" id="data" name="data" value="<?= e($v['data']) ?>" required="required" aria-required="true"
                           data-msg-vuoto="Scegli la data dell'evento"<?= attr_campo('data', $errori) ?> />
                    <?= errore_campo('data', $errori) ?>
                </div>
                <div class="campo">
                    <label for="ora">Ora di inizio</label>
                    <input type="time" id="ora" name="ora" value="<?= e($v['ora']) ?>" required="required" aria-required="true"
                           data-msg-vuoto="Scrivi l'ora di inizio"<?= attr_campo('ora', $errori) ?> />
                    <?= errore_campo('ora', $errori) ?>
                </div>
                <div class="campo">
                    <label for="durata">Durata in minuti</label>
                    <input type="number" id="durata" name="durata" min="15" max="480" value="<?= e($v['durata']) ?>" required="required" aria-required="true"
                           data-msg-vuoto="Scrivi la durata" data-msg-formato="La durata è in minuti, tra 15 e 480"<?= attr_campo('durata', $errori) ?> />
                    <?= errore_campo('durata', $errori) ?>
                </div>
            </div>
            <div class="campo">
                <label for="luogo">Dove</label>
                <p class="suggerimento" id="luogo-aiuto">Per esempio: Sale 5-6, Atrio, Terrazza.</p>
                <input type="text" id="luogo" name="luogo" maxlength="80" value="<?= e($v['luogo']) ?>" required="required" aria-required="true"
                       data-msg-vuoto="Scrivi dove si tiene"<?= attr_campo('luogo', $errori, 'luogo-aiuto') ?> />
                <?= errore_campo('luogo', $errori) ?>
            </div>
            <div class="campi-affiancati">
                <div class="campo">
                    <label for="prezzo">Prezzo in euro</label>
                    <p class="suggerimento" id="prezzo-aiuto">0 se è gratuito.</p>
                    <input type="text" id="prezzo" name="prezzo" inputmode="decimal" maxlength="6" pattern="\d{1,3}([.,]\d{1,2})?" value="<?= e($v['prezzo']) ?>"
                           required="required" aria-required="true" data-msg-vuoto="Scrivi il prezzo (0 se è gratuito)"
                           data-msg-formato="Scrivi il prezzo in euro, per esempio 8 o 7,50"<?= attr_campo('prezzo', $errori, 'prezzo-aiuto') ?> />
                    <?= errore_campo('prezzo', $errori) ?>
                </div>
                <div class="campo">
                    <label for="posti">Posti</label>
                    <input type="number" id="posti" name="posti" min="1" max="500" value="<?= e($v['posti']) ?>" required="required" aria-required="true"
                           data-msg-vuoto="Scrivi quanti posti ci sono" data-msg-formato="I posti sono un numero tra 1 e 500"<?= attr_campo('posti', $errori) ?> />
                    <?= errore_campo('posti', $errori) ?>
                </div>
            </div>
            <div class="azioni">
                <button class="bottone" type="submit"><?= $v['id'] ? 'Salva le modifiche' : 'Aggiungi l\'evento' ?></button>
                <?php if ($v['id']): ?>
                    <a class="bottone bottone-secondario" href="admin.php#modulo-evento">Annulla la modifica</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="blocco-form" aria-labelledby="titolo-iscritti">
        <h2 id="titolo-iscritti">Chi ha prenotato gli eventi in programma</h2>
        <?php if ($iscritti): ?>
            <div class="tabella-scorre" role="region" aria-labelledby="titolo-iscritti" tabindex="0">
                <table class="tabella-larga">
                    <thead><tr><th scope="col">Evento</th><th scope="col">Nome</th><th scope="col">Email</th><th scope="col" class="numero">Posti</th><th scope="col">Codice</th></tr></thead>
                    <tbody>
                        <?php foreach ($iscritti as $i): ?>
                            <tr>
                                <th scope="row"><?= e($i['titolo']) ?><br /><span class="v2-nota"><?= e(data_estesa($i['inizio'])) ?>, <?= e(ora($i['inizio'])) ?></span></th>
                                <td><?= e($i['nome']) ?></td>
                                <td><?= e($i['email']) ?></td>
                                <td class="numero"><?= (int) $i['posti'] ?></td>
                                <td><?= e($i['codice']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Nessuna prenotazione per gli eventi in programma.</p>
        <?php endif; ?>
    </section>

    <section class="blocco-form" aria-labelledby="titolo-affluenza">
        <h2 id="titolo-affluenza">Prenotazioni dei prossimi 14 giorni</h2>
        <?php if ($giorni): ?>
            <div class="tabella-scorre" role="region" aria-labelledby="titolo-affluenza" tabindex="0">
                <table class="tabella-larga">
                    <thead><tr><th scope="col">Giorno</th><th scope="col">Fascia</th><th scope="col" class="numero">Prenotazioni</th><th scope="col" class="numero">Biglietti</th></tr></thead>
                    <tbody>
                        <?php foreach ($giorni as $g): ?>
                            <tr>
                                <th scope="row"><?= e(ucfirst(data_estesa($g['data_visita']))) ?></th>
                                <td><?= e(FASCE[$g['fascia']] ?? $g['fascia']) ?></td>
                                <td class="numero"><?= (int) $g['prenotazioni'] ?></td>
                                <td class="numero"><?= (int) $g['biglietti'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Nessuna prenotazione nei prossimi 14 giorni.</p>
        <?php endif; ?>
    </section>
</div>
<?php pagina_fine();
