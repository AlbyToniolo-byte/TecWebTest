<?php
// Area personale: prenotazioni, richieste di tessera e ordini dell'account, la newsletter
// e il collegamento di quello che si è fatto prima senza account (con codice ed email).
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';
require_once __DIR__ . '/prenotazione.php';

$u = richiedi_accesso();

// Cose che si possono aggiungere all'account con il codice della conferma
const COLLEGABILI = [
    'prenotazione' => ['prenotazioni', 'la prenotazione'],
    'tessera'      => ['richieste_membership', 'la richiesta di tessera'],
    'ordine'       => ['ordini', 'l\'ordine'],
    'evento'       => ['prenotazioni_eventi', 'la prenotazione dell\'evento'],
];

$errori = [];
$c = ['tipo' => 'prenotazione', 'codice' => '', 'email' => $u['email']];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido()) {
    $azione = (string) ($_POST['azione'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    // "Gestisci": si riusano le pagine di modifica, dopo aver controllato che la riga sia di questo account
    if ($azione === 'apri-prenotazione'
        && query_riga('SELECT 1 FROM prenotazioni WHERE id = :id AND utente_id = :u', ['id' => $id, 'u' => $u['id']])) {
        $_SESSION['prenotazione_gestita'] = $id;
        vai('gestisci.php');
    }
    if ($azione === 'apri-evento'
        && query_riga('SELECT 1 FROM prenotazioni_eventi WHERE id = :id AND utente_id = :u', ['id' => $id, 'u' => $u['id']])) {
        $_SESSION['evento_gestito'] = $id;
        vai('gestisci-evento.php');
    }
    if ($azione === 'apri-tessera'
        && query_riga('SELECT 1 FROM richieste_membership WHERE id = :id AND utente_id = :u', ['id' => $id, 'u' => $u['id']])) {
        $_SESSION['tessera_gestita'] = $id;
        vai('gestisci-tessera.php');
    }

    if ($azione === 'collega') {
        $c['tipo'] = (string) ($_POST['tipo'] ?? '');
        $c['codice'] = strtoupper(trim((string) ($_POST['codice'] ?? '')));
        $c['email'] = trim((string) ($_POST['email'] ?? ''));
        if (!isset(COLLEGABILI[$c['tipo']])) {
            $errori['tipo'] = 'Scegli cosa vuoi aggiungere';
        }
        if (!preg_match('/^[0-9A-F]{8}$/', $c['codice'])) {
            $errori['codice'] = 'Il codice è di 8 caratteri, cifre e lettere dalla A alla F, per esempio 1BA7EED6';
        }
        if (!email_valida($c['email'])) {
            $errori['email-collega'] = 'Inserisci l\'email usata allora, per esempio nome@esempio.it';
        }
        if (!$errori) {
            [$tabella, $nome] = COLLEGABILI[$c['tipo']];
            // $tabella viene dalla lista fissa qui sopra, mai dall'utente
            $st = db()->prepare("UPDATE $tabella SET utente_id = :u WHERE codice = :c AND lower(email) = lower(:e) AND utente_id IS NULL");
            $st->execute(['u' => $u['id'], 'c' => $c['codice'], 'e' => $c['email']]);
            if ($st->rowCount() === 1) {
                flash(ucfirst($nome) . ' ' . $c['codice'] . ' ora è nel tuo account.');
                vai('account.php');
            }
            // stesso messaggio se il codice non esiste o è già di un account: non si rivela nulla
            $errori['codice'] = 'Nessun risultato libero con questo codice e questa email: controlla il messaggio di conferma';
        }
    }
}

$prenotazioni = query('SELECT p.id, p.codice, p.data_visita, p.fascia,
                              COALESCE(SUM(r.quantita), 0) AS biglietti, COALESCE(SUM(r.quantita * r.prezzo_unitario), 0) AS totale
                       FROM prenotazioni p LEFT JOIN prenotazioni_righe r ON r.prenotazione_id = p.id
                       WHERE p.utente_id = :u GROUP BY p.id ORDER BY p.data_visita DESC', ['u' => $u['id']]);
$eventi = query('SELECT pe.id, pe.codice, pe.posti, e.titolo, e.inizio, e.luogo, e.prezzo
                 FROM prenotazioni_eventi pe JOIN eventi e ON e.id = pe.evento_id
                 WHERE pe.utente_id = :u ORDER BY e.inizio DESC', ['u' => $u['id']]);
$tessere = query('SELECT r.id, r.codice, r.creata_il, l.nome AS livello, l.prezzo
                  FROM richieste_membership r JOIN livelli_membership l ON l.id = r.livello_id
                  WHERE r.utente_id = :u ORDER BY r.creata_il DESC', ['u' => $u['id']]);
$ordini = query("SELECT o.codice, o.creato_il, string_agg(r.quantita || ' × ' || p.nome, ', ' ORDER BY p.nome) AS articoli,
                        SUM(r.quantita * r.prezzo_unitario) AS totale
                 FROM ordini o JOIN ordini_righe r ON r.ordine_id = o.id JOIN prodotti p ON p.id = r.prodotto_id
                 WHERE o.utente_id = :u GROUP BY o.id ORDER BY o.creato_il DESC", ['u' => $u['id']]);
$iscritto = (bool) query_riga('SELECT 1 FROM iscrizioni_newsletter WHERE email = :e', ['e' => $u['email']]);
$oggi = date('Y-m-d');
$adesso = date('Y-m-d H:i:s');

pagina_inizio('Area personale', 'Le tue prenotazioni, tessere e ordini al museo Kronos.', 'account');
?>
<div class="contenitore">
    <?php briciole([[null, 'Area personale']]); ?>
    <div class="intestazione-pagina">
        <h1>Ciao, <?= e($u['nome']) ?></h1>
        <p class="introduzione">Qui trovi tutto quello che hai fatto con il tuo account <strong><?= e($u['nome_utente']) ?></strong>.</p>
        <div class="azioni">
            <a class="bottone bottone-secondario" href="profilo.php">Modifica i tuoi dati</a>
            <?php if ($u['ruolo'] === 'admin'): ?>
                <a class="bottone bottone-secondario" href="admin.php">Gestione del museo</a>
            <?php endif; ?>
            <form action="esci.php" method="post">
                <?= campo_csrf() ?>
                <button class="bottone-testo" type="submit">Esci dall'account</button>
            </form>
        </div>
    </div>

    <section class="blocco-form" aria-labelledby="titolo-prenotazioni">
        <h2 id="titolo-prenotazioni">Prenotazioni</h2>
        <?php if ($prenotazioni): ?>
            <div class="tabella-scorre" role="region" aria-labelledby="titolo-prenotazioni" tabindex="0">
                <table class="tabella-larga">
                    <thead><tr><th scope="col">Visita</th><th scope="col">Biglietti</th><th scope="col" class="numero">Totale</th><th scope="col">Codice</th><th scope="col"><span class="visually-hidden">Azioni</span></th></tr></thead>
                    <tbody>
                        <?php foreach ($prenotazioni as $p): ?>
                            <tr>
                                <th scope="row"><?= e(data_estesa($p['data_visita'], true)) ?>, <?= e(mb_strtolower(FASCE[$p['fascia']] ?? $p['fascia'])) ?><?= $p['data_visita'] < $oggi ? ' (passata)' : '' ?></th>
                                <td><?= (int) $p['biglietti'] ?></td>
                                <td class="numero"><?= e(prezzo($p['totale'])) ?></td>
                                <td><?= e($p['codice']) ?></td>
                                <td>
                                    <form action="account.php" method="post">
                                        <?= campo_csrf() ?>
                                        <input type="hidden" name="azione" value="apri-prenotazione" />
                                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>" />
                                        <button class="bottone-testo" type="submit">Modifica o cancella<span class="visually-hidden"> la prenotazione <?= e($p['codice']) ?></span></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Non hai ancora prenotazioni.</p>
        <?php endif; ?>
        <p><a class="bottone" href="visit.php#prenota">Prenota una visita</a></p>
    </section>

    <section class="blocco-form" aria-labelledby="titolo-eventi-account">
        <h2 id="titolo-eventi-account">Eventi</h2>
        <?php if ($eventi): ?>
            <ul class="elenco-account">
                <?php foreach ($eventi as $ev): ?>
                    <li>
                        <p><strong><?= e($ev['titolo']) ?></strong>, <?= e(data_estesa($ev['inizio'], true)) ?> ore <?= e(ora($ev['inizio'])) ?>, <?= e($ev['luogo']) ?><?= $ev['inizio'] < $adesso ? ' (concluso)' : '' ?>.
                            <?= (int) $ev['posti'] ?> <?= (int) $ev['posti'] === 1 ? 'posto' : 'posti' ?>, <?= e(prezzo((int) $ev['posti'] * (float) $ev['prezzo'])) ?>. Codice <?= e($ev['codice']) ?>.</p>
                        <form action="account.php" method="post">
                            <?= campo_csrf() ?>
                            <input type="hidden" name="azione" value="apri-evento" />
                            <input type="hidden" name="id" value="<?= (int) $ev['id'] ?>" />
                            <button class="bottone-testo" type="submit">Modifica o cancella<span class="visually-hidden"> la prenotazione <?= e($ev['codice']) ?></span></button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Non hai prenotato eventi. <a href="esibizioni.php#eventi">Guarda il calendario</a>.</p>
        <?php endif; ?>
    </section>

    <section class="blocco-form" aria-labelledby="titolo-tessere">
        <h2 id="titolo-tessere">Tessere</h2>
        <?php if ($tessere): ?>
            <ul class="elenco-account">
                <?php foreach ($tessere as $t): ?>
                    <li>
                        <p><strong>Tessera <?= e($t['livello']) ?></strong>, <?= e(prezzo($t['prezzo'])) ?> l'anno. Richiesta <?= e(data_estesa($t['creata_il'], true)) ?>, codice <?= e($t['codice']) ?>.</p>
                        <form action="account.php" method="post">
                            <?= campo_csrf() ?>
                            <input type="hidden" name="azione" value="apri-tessera" />
                            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>" />
                            <button class="bottone-testo" type="submit">Cambia o annulla<span class="visually-hidden"> la richiesta <?= e($t['codice']) ?></span></button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Non hai richiesto tessere. <a href="support.php">Scopri le tessere</a>.</p>
        <?php endif; ?>
    </section>

    <section class="blocco-form" aria-labelledby="titolo-ordini">
        <h2 id="titolo-ordini">Ordini dello shop</h2>
        <?php if ($ordini): ?>
            <ul class="elenco-account">
                <?php foreach ($ordini as $o): ?>
                    <li>
                        <p><strong>Ordine <?= e($o['codice']) ?></strong> del <?= e(data_estesa($o['creato_il'], true)) ?>: <?= e($o['articoli']) ?>. Da pagare al ritiro: <?= e(prezzo($o['totale'])) ?>.</p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Non hai ordini. <a href="shop.php">Vai allo shop</a>.</p>
        <?php endif; ?>
    </section>

    <section class="blocco-form" aria-labelledby="titolo-newsletter">
        <h2 id="titolo-newsletter">Newsletter</h2>
        <p><?= $iscritto ? 'Ricevi la newsletter a ' : 'Non ricevi la newsletter a ' ?><strong><?= e($u['email']) ?></strong>.</p>
        <form action="newsletter.php" method="post">
            <?= campo_csrf() ?>
            <input type="hidden" name="torna" value="account.php" />
            <input type="hidden" name="email" value="<?= e($u['email']) ?>" />
            <?php if ($iscritto): ?>
                <input type="hidden" name="azione" value="annulla" />
                <button class="bottone bottone-secondario" type="submit">Non riceverla più</button>
            <?php else: ?>
                <button class="bottone bottone-secondario" type="submit">Iscriviti</button>
            <?php endif; ?>
        </form>
    </section>

    <section id="collega" class="blocco-form" aria-labelledby="titolo-collega">
        <h2 id="titolo-collega">Aggiungi qualcosa fatto senza account</h2>
        <p>Se hai prenotato una visita o un evento, chiesto una tessera o ordinato prima di registrarti, inserisci il codice e l'email del messaggio di conferma: lo ritroverai qui.</p>
        <?= riepilogo_errori($errori) ?>
        <form action="account.php#collega" method="post" novalidate="novalidate" data-valida="">
            <?= campo_csrf() ?>
            <input type="hidden" name="azione" value="collega" />
            <fieldset>
                <legend>Che cosa vuoi aggiungere?</legend>
                <?php foreach (['prenotazione' => 'Una prenotazione della visita', 'evento' => 'Una prenotazione di un evento', 'tessera' => 'Una richiesta di tessera', 'ordine' => 'Un ordine dello shop'] as $val => $etichetta):
                    $id = $val === 'prenotazione' ? 'tipo' : 'tipo-' . $val;   // il primo ha l'id del campo: ci porta il riepilogo errori ?>
                    <div class="scelta-riga">
                        <input type="radio" id="<?= e($id) ?>" name="tipo" value="<?= e($val) ?>"<?= $c['tipo'] === $val ? ' checked="checked"' : '' ?> required="required"
                               data-msg-vuoto="Scegli cosa vuoi aggiungere" />
                        <label for="<?= e($id) ?>"><?= e($etichetta) ?></label>
                    </div>
                <?php endforeach; ?>
                <?= errore_campo('tipo', $errori) ?>
            </fieldset>
            <div class="campo">
                <label for="codice">Codice</label>
                <p class="suggerimento" id="codice-aiuto">8 caratteri, per esempio 1BA7EED6.</p>
                <input type="text" id="codice" name="codice" autocomplete="off" spellcheck="false" maxlength="8" pattern="[0-9A-Fa-f]{8}"
                       value="<?= e($c['codice']) ?>" required="required" aria-required="true"
                       data-msg-vuoto="Inserisci il codice"
                       data-msg-formato="Il codice è di 8 caratteri, cifre e lettere dalla A alla F, per esempio 1BA7EED6"<?= attr_campo('codice', $errori, 'codice-aiuto') ?> />
                <?= errore_campo('codice', $errori) ?>
            </div>
            <div class="campo">
                <label for="email-collega">Email usata allora</label>
                <input type="email" id="email-collega" name="email" autocomplete="email" spellcheck="false" value="<?= e($c['email']) ?>"
                       required="required" aria-required="true" data-msg-vuoto="Inserisci l'email usata allora"<?= attr_campo('email-collega', $errori) ?> />
                <?= errore_campo('email-collega', $errori) ?>
            </div>
            <button class="bottone" type="submit">Aggiungi al mio account</button>
        </form>
    </section>
</div>
<?php pagina_fine();
