<?php
// Gestione delle richieste di tessera: modifica (tipo di tessera e nome) e annullamento.
// Stesso schema di gestisci.php: la richiesta si ritrova con codice ed email inviati in POST,
// poi il suo id resta nella sessione finché l'utente non esce o la annulla.
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';
require_once __DIR__ . '/tessera.php';

[$livelli, $vantaggi] = livelli_tessera();
$errori = [];
$cerca = ['codice' => '', 'email' => ''];
$conferma = false;   // true: mostra la domanda "vuoi davvero annullare?"

$id = $_SESSION['tessera_gestita'] ?? null;
$richiesta = $id ? query_riga('SELECT * FROM richieste_membership WHERE id = :id', ['id' => $id]) : null;
if ($id && !$richiesta) {
    unset($_SESSION['tessera_gestita']);   // annullata nel frattempo
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = (string) ($_POST['azione'] ?? '');

    if ($azione === 'cerca') {
        $cerca['codice'] = strtoupper(trim((string) ($_POST['codice'] ?? '')));
        $cerca['email'] = trim((string) ($_POST['email'] ?? ''));
        if (!csrf_valido()) {
            $errori['codice'] = 'La sessione è scaduta: ricarica la pagina e invia di nuovo il modulo';
        } elseif (!preg_match('/^[0-9A-F]{8}$/', $cerca['codice'])) {
            $errori['codice'] = 'Il codice è di 8 caratteri, cifre e lettere dalla A alla F, per esempio 76F4EF0B';
        }
        if (!email_valida($cerca['email'])) {
            $errori['email-cerca'] = 'Inserisci l\'indirizzo email usato per la richiesta, per esempio nome@esempio.it';
        }
        if (!$errori) {
            $trovata = query_riga('SELECT id FROM richieste_membership WHERE codice = :c AND lower(email) = lower(:e)',
                ['c' => $cerca['codice'], 'e' => $cerca['email']]);
            if ($trovata) {
                session_regenerate_id(true);
                $_SESSION['tessera_gestita'] = (int) $trovata['id'];
                vai('gestisci-tessera.php');
            }
            // stesso messaggio in entrambi i casi: non si rivela se il codice esiste
            $errori['codice'] = 'Nessuna richiesta di tessera con questo codice e questa email: controlla il messaggio di conferma';
        }
    } elseif ($richiesta && csrf_valido()) {
        if ($azione === 'modifica') {
            [$v, $errori] = tessera_leggi($_POST, $livelli, false);
            if (!$errori) {
                db()->prepare('UPDATE richieste_membership SET livello_id = :l, nome = :n WHERE id = :id')
                    ->execute(['l' => (int) $v['livello'], 'n' => $v['nome'], 'id' => $richiesta['id']]);
                flash('Richiesta ' . $richiesta['codice'] . ' aggiornata: ora è per la tessera ' . nome_livello($livelli, (int) $v['livello']) . '.');
                vai('gestisci-tessera.php');
            }
        } elseif ($azione === 'chiedi-annullamento') {
            $conferma = true;
        } elseif ($azione === 'annulla') {
            db()->prepare('DELETE FROM richieste_membership WHERE id = :id')->execute(['id' => $richiesta['id']]);
            unset($_SESSION['tessera_gestita']);
            flash('Richiesta di tessera ' . $richiesta['codice'] . ' annullata e cancellata dai nostri dati.');
            vai('gestisci-tessera.php');
        } elseif ($azione === 'esci') {
            unset($_SESSION['tessera_gestita']);
            vai('gestisci-tessera.php');
        }
    }
}

// Valori del modulo di modifica: quelli appena inviati se c'erano errori, altrimenti quelli salvati
if ($richiesta && !isset($v)) {
    $v = ['livello' => (string) $richiesta['livello_id'], 'nome' => $richiesta['nome'], 'email' => $richiesta['email']];
}

pagina_inizio('Gestisci la tessera', 'Cambia il tipo di tessera richiesto o annulla la richiesta di tessera del museo Kronos.', 'support');
?>
<div class="contenitore">
    <?php briciole([['support.php', 'Sostienici'], [null, 'Gestisci la tessera']]); ?>
    <div class="intestazione-pagina">
        <h1>Gestisci la tessera</h1>
        <?php if (!$richiesta): ?>
            <p class="introduzione">Inserisci il codice che hai ricevuto alla richiesta e l'email che hai indicato: potrai cambiare il tipo di tessera o il nome, oppure annullare la richiesta.</p>
        <?php endif; ?>
        <p>Qui si gestisce la richiesta, finché la tessera non è attiva. Se l'hai già attivata e pagata in biglietteria, per disdirla scrivi a <a href="mailto:sostieni@kronos-museo.it">sostieni@kronos-museo.it</a>: ti rispondiamo entro tre giorni lavorativi.</p>
    </div>

    <?php if (!$richiesta): ?>
        <section class="blocco-form" aria-labelledby="titolo-cerca">
            <h2 id="titolo-cerca">Trova la tua richiesta</h2>
            <?= riepilogo_errori($errori) ?>
            <form action="gestisci-tessera.php" method="post" novalidate="novalidate" data-valida="">
                <?= campo_csrf() ?>
                <input type="hidden" name="azione" value="cerca" />
                <div class="campo">
                    <label for="codice">Codice della richiesta <span class="obbligatorio" aria-hidden="true">*</span></label>
                    <p class="suggerimento" id="codice-aiuto">8 caratteri, per esempio 76F4EF0B.</p>
                    <input type="text" id="codice" name="codice" autocomplete="off" spellcheck="false" maxlength="8" pattern="[0-9A-Fa-f]{8}"
                           value="<?= e($cerca['codice']) ?>" required="required" aria-required="true"
                           data-msg-vuoto="Inserisci il codice della richiesta"
                           data-msg-formato="Il codice è di 8 caratteri, cifre e lettere dalla A alla F, per esempio 76F4EF0B"<?= attr_campo('codice', $errori, 'codice-aiuto') ?> />
                    <?= errore_campo('codice', $errori) ?>
                </div>
                <div class="campo">
                    <label for="email-cerca">Email della richiesta <span class="obbligatorio" aria-hidden="true">*</span></label>
                    <input type="email" id="email-cerca" name="email" autocomplete="email" spellcheck="false" value="<?= e($cerca['email']) ?>"
                           required="required" aria-required="true" data-msg-vuoto="Inserisci l'indirizzo email usato per la richiesta"<?= attr_campo('email-cerca', $errori) ?> />
                    <?= errore_campo('email-cerca', $errori) ?>
                </div>
                <button class="bottone" type="submit">Trova la richiesta</button>
            </form>
        </section>

    <?php elseif ($conferma): ?>
        <section class="blocco-form" aria-labelledby="titolo-conferma">
            <h2 id="titolo-conferma" tabindex="-1" data-focus-iniziale="">Vuoi annullare la richiesta di tessera <?= e($richiesta['codice']) ?>?</h2>
            <p>È per la tessera <?= e(nome_livello($livelli, (int) $richiesta['livello_id'])) ?>. La richiesta verrà cancellata e non si potrà recuperare: per diventare socio dovrai farne una nuova.</p>
            <div class="azioni">
                <form action="gestisci-tessera.php" method="post">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="azione" value="annulla" />
                    <button class="bottone" type="submit">Sì, annulla la richiesta</button>
                </form>
                <a class="bottone bottone-secondario" href="gestisci-tessera.php">No, tienila</a>
            </div>
        </section>

    <?php else: ?>
        <section class="blocco-form" aria-labelledby="titolo-riepilogo">
            <h2 id="titolo-riepilogo">Richiesta <?= e($richiesta['codice']) ?></h2>
            <dl class="dati-mostra">
                <div><dt>Tessera</dt><dd><?= e(nome_livello($livelli, (int) $richiesta['livello_id'])) ?></dd></div>
                <div><dt>Richiesta il</dt><dd><?= e(data_estesa($richiesta['creata_il'], true)) ?></dd></div>
                <div><dt>Email</dt><dd><?= e($richiesta['email']) ?></dd></div>
            </dl>
        </section>

        <section id="modifica" class="blocco-form" aria-labelledby="titolo-modifica">
            <h2 id="titolo-modifica">Modifica</h2>
            <?= riepilogo_errori($errori) ?>
            <form action="gestisci-tessera.php#modifica" method="post" novalidate="novalidate" data-valida="">
                <?= campo_csrf() ?>
                <input type="hidden" name="azione" value="modifica" />
                <?php tessera_campi($v, $errori, $livelli, $vantaggi, false); ?>
                <button class="bottone" type="submit">Salva le modifiche</button>
            </form>
        </section>

        <section class="blocco-form" aria-labelledby="titolo-annulla">
            <h2 id="titolo-annulla">Annulla</h2>
            <p>Se non vuoi più la tessera, annulla la richiesta: i tuoi dati verranno cancellati. Per una tessera già attiva in biglietteria, vedi la nota in cima alla pagina.</p>
            <div class="azioni">
                <form action="gestisci-tessera.php" method="post">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="azione" value="chiedi-annullamento" />
                    <button class="bottone bottone-secondario" type="submit">Annulla la richiesta</button>
                </form>
                <form action="gestisci-tessera.php" method="post">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="azione" value="esci" />
                    <button class="bottone-testo" type="submit">Gestisci un'altra richiesta</button>
                </form>
            </div>
        </section>
    <?php endif; ?>
</div>
<?php pagina_fine();
