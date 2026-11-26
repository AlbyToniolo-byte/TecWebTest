<?php
// Gestione della prenotazione di un evento: modifica (posti e nome) e cancellazione.
// Stesso schema di gestisci.php: si ritrova con codice ed email inviati in POST,
// poi il suo id resta nella sessione finché l'utente non esce o la cancella.
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';
require_once __DIR__ . '/eventi.php';

$errori = [];
$cerca = ['codice' => '', 'email' => ''];
$conferma = false;   // true: mostra la domanda "vuoi davvero cancellare?"

$id = $_SESSION['evento_gestito'] ?? null;
$pren = $id ? query_riga('SELECT * FROM prenotazioni_eventi WHERE id = :id', ['id' => $id]) : null;
if ($id && !$pren) {
    unset($_SESSION['evento_gestito']);   // cancellata nel frattempo
}
$evento = $pren ? evento_con_posti((int) $pren['evento_id'], (int) $pren['id']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = (string) ($_POST['azione'] ?? '');

    if ($azione === 'cerca') {
        $cerca['codice'] = strtoupper(trim((string) ($_POST['codice'] ?? '')));
        $cerca['email'] = trim((string) ($_POST['email'] ?? ''));
        if (!csrf_valido()) {
            $errori['codice'] = 'La sessione è scaduta: ricarica la pagina e invia di nuovo il modulo';
        } elseif (!preg_match('/^[0-9A-F]{8}$/', $cerca['codice'])) {
            $errori['codice'] = 'Il codice è di 8 caratteri, cifre e lettere dalla A alla F, per esempio 3F9A20C1';
        }
        if (!email_valida($cerca['email'])) {
            $errori['email-cerca'] = 'Inserisci l\'indirizzo email usato per prenotare, per esempio nome@esempio.it';
        }
        if (!$errori) {
            $trovata = query_riga('SELECT id FROM prenotazioni_eventi WHERE codice = :c AND lower(email) = lower(:e)',
                ['c' => $cerca['codice'], 'e' => $cerca['email']]);
            if ($trovata) {
                session_regenerate_id(true);
                $_SESSION['evento_gestito'] = (int) $trovata['id'];
                vai('gestisci-evento.php');
            }
            // stesso messaggio in entrambi i casi: non si rivela se il codice esiste
            $errori['codice'] = 'Nessuna prenotazione con questo codice e questa email: controlla il messaggio di conferma';
        }
    } elseif ($pren && csrf_valido()) {
        if ($azione === 'modifica' && !$evento['passato']) {
            $pdo = db();
            $pdo->beginTransaction();
            $evento = evento_con_posti((int) $pren['evento_id'], (int) $pren['id'], true);
            [$v, $errori] = evento_leggi($_POST, $evento, false);
            if ($errori) {
                $pdo->rollBack();
            } else {
                $pdo->prepare('UPDATE prenotazioni_eventi SET posti = :p, nome = :n WHERE id = :id')
                    ->execute(['p' => (int) $v['posti'], 'n' => $v['nome'], 'id' => $pren['id']]);
                $pdo->commit();
                flash('Prenotazione ' . $pren['codice'] . ' aggiornata: ' . (int) $v['posti'] . ((int) $v['posti'] === 1 ? ' posto' : ' posti')
                    . ' per «' . $evento['titolo'] . '».');
                vai('gestisci-evento.php');
            }
        } elseif ($azione === 'chiedi-cancellazione') {
            $conferma = true;
        } elseif ($azione === 'cancella') {
            db()->prepare('DELETE FROM prenotazioni_eventi WHERE id = :id')->execute(['id' => $pren['id']]);
            unset($_SESSION['evento_gestito']);
            flash('Prenotazione ' . $pren['codice'] . ' cancellata: i posti tornano liberi per altri visitatori.');
            vai(utente() ? 'account.php' : 'gestisci-evento.php');
        } elseif ($azione === 'esci') {
            unset($_SESSION['evento_gestito']);
            vai('gestisci-evento.php');
        }
    }
}

// Valori del modulo: quelli appena inviati se c'erano errori, altrimenti quelli salvati
if ($pren && !isset($v)) {
    $v = ['posti' => (string) $pren['posti'], 'nome' => $pren['nome'], 'email' => $pren['email']];
}

pagina_inizio('Gestisci un evento prenotato', 'Modifica o cancella la prenotazione di un evento del museo Kronos.', 'esibizioni');
?>
<div class="contenitore">
    <?php briciole([['esibizioni.php', 'Mostre ed eventi'], [null, 'Gestisci un evento prenotato']]); ?>
    <div class="intestazione-pagina">
        <h1>Gestisci un evento prenotato</h1>
        <?php if (!$pren): ?>
            <p class="introduzione">Inserisci il codice che hai ricevuto alla conferma e l'email usata per prenotare: potrai cambiare il numero di posti o cancellare la prenotazione.</p>
        <?php endif; ?>
    </div>

    <?php if (!$pren): ?>
        <section class="blocco-form" aria-labelledby="titolo-cerca">
            <h2 id="titolo-cerca">Trova la tua prenotazione</h2>
            <?= riepilogo_errori($errori) ?>
            <form action="gestisci-evento.php" method="post" novalidate="novalidate" data-valida="">
                <?= campo_csrf() ?>
                <input type="hidden" name="azione" value="cerca" />
                <div class="campo">
                    <label for="codice">Codice della prenotazione <span class="obbligatorio" aria-hidden="true">*</span></label>
                    <p class="suggerimento" id="codice-aiuto">8 caratteri, per esempio 3F9A20C1.</p>
                    <input type="text" id="codice" name="codice" autocomplete="off" spellcheck="false" maxlength="8" pattern="[0-9A-Fa-f]{8}"
                           value="<?= e($cerca['codice']) ?>" required="required" aria-required="true"
                           data-msg-vuoto="Inserisci il codice della prenotazione"
                           data-msg-formato="Il codice è di 8 caratteri, cifre e lettere dalla A alla F, per esempio 3F9A20C1"<?= attr_campo('codice', $errori, 'codice-aiuto') ?> />
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
            <p><?= (int) $pren['posti'] ?> <?= (int) $pren['posti'] === 1 ? 'posto' : 'posti' ?> per «<?= e($evento['titolo']) ?>», <?= e(evento_quando($evento)) ?>. Una volta cancellata non si può recuperare.</p>
            <div class="azioni">
                <form action="gestisci-evento.php" method="post">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="azione" value="cancella" />
                    <button class="bottone" type="submit">Sì, cancella la prenotazione</button>
                </form>
                <a class="bottone bottone-secondario" href="gestisci-evento.php">No, tienila</a>
            </div>
        </section>

    <?php else: ?>
        <section class="blocco-form" aria-labelledby="titolo-riepilogo">
            <h2 id="titolo-riepilogo">Prenotazione <?= e($pren['codice']) ?></h2>
            <dl class="dati-mostra">
                <div><dt>Evento</dt><dd><a href="evento.php?id=<?= (int) $evento['id'] ?>"><?= e($evento['titolo']) ?></a></dd></div>
                <div><dt>Quando</dt><dd><?= e(data_estesa($evento['inizio'], true)) ?>, ore <?= e(ora($evento['inizio'])) ?></dd></div>
                <div><dt>Dove</dt><dd><?= e($evento['luogo']) ?></dd></div>
                <div><dt>Posti</dt><dd><?= (int) $pren['posti'] ?></dd></div>
                <div><dt>Da pagare all'ingresso</dt><dd><?= e(prezzo((int) $pren['posti'] * (float) $evento['prezzo'])) ?></dd></div>
                <div><dt>Email</dt><dd><?= e($pren['email']) ?></dd></div>
            </dl>
        </section>

        <?php if ($evento['passato']): ?>
            <section class="blocco-form" aria-labelledby="titolo-passato">
                <h2 id="titolo-passato">Evento concluso</h2>
                <p>L'evento si è già svolto: la prenotazione non si può più modificare.</p>
            </section>
        <?php else: ?>
            <section id="modifica" class="blocco-form" aria-labelledby="titolo-modifica">
                <h2 id="titolo-modifica">Modifica</h2>
                <?= riepilogo_errori($errori) ?>
                <form action="gestisci-evento.php#modifica" method="post" novalidate="novalidate" data-valida="">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="azione" value="modifica" />
                    <?php evento_campi($v, $errori, $evento, false); ?>
                    <button class="bottone" type="submit">Salva le modifiche</button>
                </form>
            </section>
        <?php endif; ?>

        <section class="blocco-form" aria-labelledby="titolo-cancella">
            <h2 id="titolo-cancella">Cancella</h2>
            <p>Se non puoi più venire, cancella la prenotazione: i posti tornano liberi per altri visitatori.</p>
            <div class="azioni">
                <form action="gestisci-evento.php" method="post">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="azione" value="chiedi-cancellazione" />
                    <button class="bottone bottone-secondario" type="submit">Cancella la prenotazione</button>
                </form>
                <form action="gestisci-evento.php" method="post">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="azione" value="esci" />
                    <button class="bottone-testo" type="submit">Gestisci un'altra prenotazione</button>
                </form>
                <?php if (utente()): ?>
                    <a href="account.php">Torna all'area personale</a>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
<?php pagina_fine();
