<?php
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';
require_once __DIR__ . '/tessera.php';

[$livelli, $vantaggi] = livelli_tessera();

$errori = [];
$u = utente();
$v = ['livello' => '', 'nome' => $u['nome'] ?? '', 'email' => $u['email'] ?? ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$v, $errori] = tessera_leggi($_POST, $livelli);
    if (!$errori) {
        $codice = codice_casuale();
        $st = db()->prepare('INSERT INTO richieste_membership (codice, livello_id, nome, email, utente_id) VALUES (:c, :l, :n, :e, :u)');
        $st->execute(['c' => $codice, 'l' => (int) $v['livello'], 'n' => $v['nome'], 'e' => $v['email'], 'u' => $u['id'] ?? null]);
        flash('Richiesta inviata. La tessera ' . nome_livello($livelli, (int) $v['livello']) . ' ti aspetta in biglietteria: la attivi alla prima visita. '
            . 'Codice ' . $codice . ': ' . ($u ? 'la trovi nella tua area personale.' : 'con il codice e la tua email puoi cambiarla o annullarla da «Gestisci la tessera».'));
        vai('support.php');
    }
}

briciole([[null, 'Sostienici']]);
pagina_inizio('Sostienici', 'Diventa socio di Kronos o sostieni il museo con una donazione.', 'support');
?>
<div class="contenitore">
    <div class="intestazione-pagina">
        <h1>Sostieni Kronos</h1>
        <p class="introduzione">Kronos vive di biglietti, tessere e donazioni. Con una tessera entri tutte le volte che vuoi e ci aiuti a restaurare i reperti, a pagare le visite in LIS e i laboratori gratuiti per le scuole.</p>
    </div>

    <section class="blocco-form" aria-labelledby="titolo-tessere">
        <h2 id="titolo-tessere">Diventa socio</h2>
        <?php if ($u): ?>
            <p>Le tue richieste di tessera sono nella tua <a href="account.php">area personale</a>.</p>
        <?php else: ?>
            <p>Hai già richiesto una tessera? <a href="gestisci-tessera.php">Cambiala o annulla la richiesta</a>.</p>
        <?php endif; ?>
        <?= riepilogo_errori($errori) ?>
        <form action="support.php" method="post" novalidate="novalidate" data-valida="">
            <?= campo_csrf() ?>
            <?php tessera_campi($v, $errori, $livelli, $vantaggi); ?>
            <button class="bottone" type="submit">Richiedi la tessera</button>
        </form>
    </section>

    <div class="griglia-due">
        <section aria-labelledby="titolo-dona">
            <h2 id="titolo-dona">Fai una donazione</h2>
            <p>Puoi donare in biglietteria o con un bonifico intestato a Kronos, Museo dei Mondi Perduti, indicando nella causale cosa vuoi sostenere: restauri, accessibilità o didattica.</p>
            <p>Le donazioni al museo sono detraibili. Scrivi a <a href="mailto:sostieni@kronos-museo.it">sostieni@kronos-museo.it</a> per ricevere la ricevuta.</p>
        </section>
        <section aria-labelledby="titolo-volontari">
            <h2 id="titolo-volontari">Diventa volontario</h2>
            <p>Cerchiamo persone che accolgano i visitatori nei fine settimana e aiutino durante i laboratori. Non serve esperienza: la formazione la facciamo noi.</p>
            <p>Scrivi a <a href="mailto:volontari@kronos-museo.it">volontari@kronos-museo.it</a> raccontando quando sei disponibile.</p>
        </section>
    </div>
</div>
<?php pagina_fine();
