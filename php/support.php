<?php
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';

$livelli = query('SELECT id, nome, prezzo, vantaggi FROM livelli_membership ORDER BY ordine');
$idValidi = array_column($livelli, 'nome', 'id');

$errori = [];
$v = ['livello' => '', 'nome' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['livello'] = (string) ($_POST['livello'] ?? '');
    $v['nome'] = trim((string) ($_POST['nome'] ?? ''));
    $v['email'] = trim((string) ($_POST['email'] ?? ''));

    if (!csrf_valido()) {
        $errori['nome'] = 'La sessione è scaduta: ricarica la pagina e invia di nuovo il modulo';
    }
    if (!isset($idValidi[(int) $v['livello']])) {
        $errori['livello'] = 'Scegli il tipo di tessera';
    }
    if ($v['nome'] === '') {
        $errori['nome'] ??= 'Inserisci il tuo nome e cognome';
    }
    if (!email_valida($v['email'])) {
        $errori['email'] = 'Inserisci un indirizzo email valido, per esempio nome@esempio.it';
    }

    if (!$errori) {
        $st = db()->prepare('INSERT INTO richieste_membership (livello_id, nome, email) VALUES (:l, :n, :e)');
        $st->execute(['l' => (int) $v['livello'], 'n' => $v['nome'], 'e' => $v['email']]);
        flash('Richiesta inviata. La tessera ' . $idValidi[(int) $v['livello']] . ' ti aspetta in biglietteria: la attivi alla prima visita.');
        vai('support.php');
    }
}

pagina_inizio('Sostienici', 'Diventa socio di Kronos o sostieni il museo con una donazione.', 'support');
?>
<div class="contenitore">
    <div class="intestazione-pagina">
        <h1>Sostieni Kronos</h1>
        <p class="introduzione">Kronos vive di biglietti, tessere e donazioni. Con una tessera entri tutte le volte che vuoi e ci aiuti a restaurare i reperti, a pagare le visite in LIS e i laboratori gratuiti per le scuole.</p>
    </div>

    <section class="blocco-form" aria-labelledby="titolo-tessere">
        <h2 id="titolo-tessere">Diventa socio</h2>
        <?= riepilogo_errori($errori) ?>
        <form action="support.php" method="post" novalidate data-valida>
            <?= campo_csrf() ?>
            <fieldset<?= isset($errori['livello']) ? ' aria-describedby="livello-errore"' : '' ?>>
                <legend>Tipo di tessera <span class="obbligatorio" aria-hidden="true">*</span></legend>
                <div class="scelte">
                    <?php foreach ($livelli as $i => $l): $id = 'livello-' . $l['id']; ?>
                        <div class="scelta">
                            <input type="radio" id="<?= $i === 0 ? 'livello' : e($id) ?>" name="livello" value="<?= (int) $l['id'] ?>" required
                                   data-msg-vuoto="Scegli il tipo di tessera" aria-describedby="<?= e($id) ?>-vantaggi"
                                   <?= (string) $l['id'] === $v['livello'] ? 'checked' : '' ?><?= isset($errori['livello']) ? ' aria-invalid="true"' : '' ?>>
                            <div>
                                <label for="<?= $i === 0 ? 'livello' : e($id) ?>"><?= e($l['nome']) ?>
                                    <span class="prezzo-livello"><?= e(prezzo($l['prezzo'])) ?> all'anno</span></label>
                                <ul id="<?= e($id) ?>-vantaggi">
                                    <?php foreach (explode("\n", $l['vantaggi']) as $vantaggio): ?>
                                        <li><?= e($vantaggio) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?= errore_campo('livello', $errori) ?>
            </fieldset>

            <div class="campo">
                <label for="nome">Nome e cognome <span class="obbligatorio" aria-hidden="true">*</span></label>
                <input type="text" id="nome" name="nome" autocomplete="name" value="<?= e($v['nome']) ?>" required aria-required="true"
                       data-msg-vuoto="Inserisci il tuo nome e cognome"<?= attr_campo('nome', $errori) ?>>
                <?= errore_campo('nome', $errori) ?>
            </div>
            <div class="campo">
                <label for="email">Email <span class="obbligatorio" aria-hidden="true">*</span></label>
                <input type="email" id="email" name="email" autocomplete="email" value="<?= e($v['email']) ?>" required aria-required="true"
                       data-msg-vuoto="Inserisci il tuo indirizzo email"<?= attr_campo('email', $errori) ?>>
                <?= errore_campo('email', $errori) ?>
            </div>
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
