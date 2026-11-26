<?php
// Gestione della prenotazione: modifica e cancellazione.
// La prenotazione si ritrova con codice ed email, inviati in POST (mai nell'indirizzo della pagina);
// da lì in poi il suo id resta nella sessione, finché l'utente non esce o la cancella.
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';
require_once __DIR__ . '/prenotazione.php';

$tipi = tipi_biglietto();
$errori = [];
$cerca = ['codice' => '', 'email' => ''];
$conferma = false;   // true: mostra la domanda "vuoi davvero cancellare?"

$id = $_SESSION['prenotazione_gestita'] ?? null;
$pren = $id ? query_riga('SELECT * FROM prenotazioni WHERE id = :id', ['id' => $id]) : null;
if ($id && !$pren) {
    unset($_SESSION['prenotazione_gestita']);   // cancellata nel frattempo
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = (string) ($_POST['azione'] ?? '');

    if ($azione === 'cerca') {
        $cerca['codice'] = strtoupper(trim((string) ($_POST['codice'] ?? '')));
        $cerca['email'] = trim((string) ($_POST['email'] ?? ''));
        if (!csrf_valido()) {
            $errori['codice'] = 'La sessione è scaduta: ricarica la pagina e invia di nuovo il modulo';
        } elseif (!preg_match('/^[0-9A-F]{8}$/', $cerca['codice'])) {
            $errori['codice'] = 'Il codice è di 8 caratteri, cifre e lettere dalla A alla F, per esempio 1BA7EED6';
        }
        if (!email_valida($cerca['email'])) {
            $errori['email-cerca'] = 'Inserisci l\'indirizzo email usato per prenotare, per esempio nome@esempio.it';
        }
        if (!$errori) {
            $trovata = query_riga('SELECT id FROM prenotazioni WHERE codice = :c AND lower(email) = lower(:e)',
                ['c' => $cerca['codice'], 'e' => $cerca['email']]);
            if ($trovata) {
                session_regenerate_id(true);
                $_SESSION['prenotazione_gestita'] = (int) $trovata['id'];
                vai('gestisci.php');
            }
            // stesso messaggio in entrambi i casi: non si rivela se il codice esiste
            $errori['codice'] = 'Nessuna prenotazione con questo codice e questa email: controlla il messaggio di conferma';
        }
    } elseif ($pren && csrf_valido()) {
        if ($azione === 'modifica') {
            [$v, $errori] = prenotazione_leggi($_POST, $tipi, false);
            if (!$errori) {
                prenotazione_salva($v, $tipi, (int) $pren['id']);
                flash('Prenotazione ' . $pren['codice'] . ' aggiornata: ora è per ' . data_estesa($v['data'], true) . ', '
                    . mb_strtolower(FASCE[$v['fascia']]) . '.');
                vai('gestisci.php');
            }
        } elseif ($azione === 'chiedi-cancellazione') {
            $conferma = true;
        } elseif ($azione === 'cancella') {
            // le righe dei biglietti spariscono con la prenotazione (ON DELETE CASCADE)
            db()->prepare('DELETE FROM prenotazioni WHERE id = :id')->execute(['id' => $pren['id']]);
            unset($_SESSION['prenotazione_gestita']);
            flash('Prenotazione ' . $pren['codice'] . ' cancellata. Se cambi idea puoi prenotare di nuovo dalla pagina Visita.');
            vai('gestisci.php');
        } elseif ($azione === 'esci') {
            unset($_SESSION['prenotazione_gestita']);
            vai('gestisci.php');
        }
    }
}

// Valori del modulo di modifica: quelli appena inviati se c'erano errori, altrimenti quelli salvati
if ($pren && !isset($v)) {
    $v = ['data' => $pren['data_visita'], 'fascia' => $pren['fascia'], 'nome' => $pren['nome'],
          'email' => $pren['email'], 'note' => (string) $pren['note'], 'q' => []];
    foreach (query('SELECT tipo_biglietto_id, quantita FROM prenotazioni_righe WHERE prenotazione_id = :id', ['id' => $pren['id']]) as $r) {
        $v['q'][$r['tipo_biglietto_id']] = (int) $r['quantita'];
    }
}

pagina_inizio('Gestisci la prenotazione', 'Modifica o cancella la prenotazione della visita al museo Kronos.', 'visit');
?>
<div class="contenitore">
    <?php briciole([['visit.php', 'Visita'], [null, 'Gestisci la prenotazione']]); ?>
    <div class="intestazione-pagina">
        <h1>Gestisci la prenotazione</h1>
        <?php if (!$pren): ?>
            <p class="introduzione">Inserisci il codice che hai ricevuto alla conferma e l'email usata per prenotare: potrai cambiare data, orario, biglietti e note, oppure cancellare la prenotazione.</p>
        <?php endif; ?>
    </div>

    <?php if (!$pren): ?>
        <section class="blocco-form" aria-labelledby="titolo-cerca">
            <h2 id="titolo-cerca">Trova la tua prenotazione</h2>
            <?= riepilogo_errori($errori) ?>
            <form action="gestisci.php" method="post" novalidate="novalidate" data-valida="">
                <?= campo_csrf() ?>
                <input type="hidden" name="azione" value="cerca" />
                <div class="campo">
                    <label for="codice">Codice della prenotazione <span class="obbligatorio" aria-hidden="true">*</span></label>
                    <p class="suggerimento" id="codice-aiuto">8 caratteri, per esempio 1BA7EED6.</p>
                    <input type="text" id="codice" name="codice" autocomplete="off" spellcheck="false" maxlength="8" pattern="[0-9A-Fa-f]{8}"
                           value="<?= e($cerca['codice']) ?>" required="required" aria-required="true"
                           data-msg-vuoto="Inserisci il codice della prenotazione"
                           data-msg-formato="Il codice è di 8 caratteri, cifre e lettere dalla A alla F, per esempio 1BA7EED6"<?= attr_campo('codice', $errori, 'codice-aiuto') ?> />
                    <?= errore_campo('codice', $errori) ?>
                </div>
                <div class="campo">
                    <label for="email-cerca">Email usata per prenotare <span class="obbligatorio" aria-hidden="true">*</span></label>
                    <input type="email" id="email-cerca" name="email" autocomplete="email" spellcheck="false" value="<?= e($cerca['email']) ?>"
                           required="required" aria-required="true" data-msg-vuoto="Inserisci l'indirizzo email usato per prenotare"<?= attr_campo('email-cerca', $errori) ?> />
                    <?= errore_campo('email-cerca', $errori) ?>
                </div>
                <button class="bottone" type="submit">Trova la prenotazione</button>
            </form>
        </section>

    <?php elseif ($conferma): ?>
        <section class="blocco-form" aria-labelledby="titolo-conferma">
            <h2 id="titolo-conferma" tabindex="-1" data-focus-iniziale="">Vuoi cancellare la prenotazione <?= e($pren['codice']) ?>?</h2>
            <p>È per <?= e(data_estesa($pren['data_visita'], true)) ?>, <?= e(mb_strtolower(FASCE[$pren['fascia']] ?? $pren['fascia'])) ?>. Una volta cancellata non si può recuperare.</p>
            <div class="azioni">
                <form action="gestisci.php" method="post">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="azione" value="cancella" />
                    <button class="bottone" type="submit">Sì, cancella la prenotazione</button>
                </form>
                <a class="bottone bottone-secondario" href="gestisci.php">No, tienila</a>
            </div>
        </section>

    <?php else: ?>
        <section class="blocco-form" aria-labelledby="titolo-riepilogo">
            <h2 id="titolo-riepilogo">Prenotazione <?= e($pren['codice']) ?></h2>
            <dl class="dati-mostra">
                <div><dt>Data</dt><dd><?= e(data_estesa($pren['data_visita'], true)) ?></dd></div>
                <div><dt>Ingresso</dt><dd><?= e(FASCE[$pren['fascia']] ?? $pren['fascia']) ?></dd></div>
                <div><dt>Totale da pagare all'ingresso</dt><dd><?= e(prezzo(prenotazione_totale((int) $pren['id']))) ?></dd></div>
                <div><dt>Email</dt><dd><?= e($pren['email']) ?></dd></div>
            </dl>
            <?php if ($pren['data_visita'] < date('Y-m-d')): ?>
                <p>La data di questa visita è passata: puoi spostarla a un giorno da oggi in poi, oppure cancellarla.</p>
            <?php endif; ?>
        </section>

        <section id="modifica" class="blocco-form" aria-labelledby="titolo-modifica">
            <h2 id="titolo-modifica">Modifica</h2>
            <?= riepilogo_errori($errori) ?>
            <form action="gestisci.php#modifica" method="post" novalidate="novalidate" data-valida="" data-totale-biglietti="">
                <?= campo_csrf() ?>
                <input type="hidden" name="azione" value="modifica" />
                <?php prenotazione_campi($v, $errori, $tipi, false); ?>
                <button class="bottone" type="submit">Salva le modifiche</button>
            </form>
        </section>

        <section class="blocco-form" aria-labelledby="titolo-cancella">
            <h2 id="titolo-cancella">Cancella</h2>
            <p>Se non puoi più venire, cancella la prenotazione: libera il posto per altri visitatori.</p>
            <div class="azioni">
                <form action="gestisci.php" method="post">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="azione" value="chiedi-cancellazione" />
                    <button class="bottone bottone-secondario" type="submit">Cancella la prenotazione</button>
                </form>
                <form action="gestisci.php" method="post">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="azione" value="esci" />
                    <button class="bottone-testo" type="submit">Gestisci un'altra prenotazione</button>
                </form>
            </div>
        </section>
    <?php endif; ?>

</div>
<?php pagina_fine();
