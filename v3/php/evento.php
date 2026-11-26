<?php
// Pagina di un evento con la prenotazione dei posti. I posti rimasti si calcolano dalle prenotazioni;
// al momento di salvare la riga dell'evento viene bloccata, così due prenotazioni insieme non superano la capienza.
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';
require_once __DIR__ . '/eventi.php';

$id = (int) ($_GET['id'] ?? 0);
$evento = $id ? evento_con_posti($id) : null;
if (!$evento) {
    http_response_code(404);
    briciole([['esibizioni.php', 'Mostre ed eventi'], [null, 'Evento non trovato']]);
    pagina_inizio('Evento non trovato', 'L\'evento cercato non è in calendario.', 'esibizioni');
    ?>
    <div class="contenitore intestazione-pagina">
        <h1>Evento non trovato</h1>
        <p>Forse è stato tolto dal calendario. Trovi gli eventi in programma nella pagina <a href="esibizioni.php#eventi">Mostre ed eventi</a>.</p>
    </div>
    <?php
    pagina_fine();
    exit;
}

$u = utente();
$errori = [];
$v = ['posti' => '1', 'nome' => $u['nome'] ?? '', 'email' => $u['email'] ?? ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$evento['passato']) {
    if (!csrf_valido()) {
        $errori['posti'] = 'La sessione è scaduta: ricarica la pagina e invia di nuovo il modulo';
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        $evento = evento_con_posti($id, null, true);   // posti riletti con la riga bloccata
        [$v, $errori] = evento_leggi($_POST, $evento);
        if ($errori) {
            $pdo->rollBack();
        } else {
            $codice = codice_casuale();
            $pdo->prepare('INSERT INTO prenotazioni_eventi (codice, evento_id, nome, email, posti, utente_id) VALUES (:c, :ev, :n, :e, :p, :u)')
                ->execute(['c' => $codice, 'ev' => $id, 'n' => $v['nome'], 'e' => $v['email'], 'p' => (int) $v['posti'], 'u' => $u['id'] ?? null]);
            $pdo->commit();
            $totale = (int) $v['posti'] * (float) $evento['prezzo'];
            flash(sprintf('Prenotazione confermata: %d %s per «%s», %s. Codice %s%s. %s',
                (int) $v['posti'], (int) $v['posti'] === 1 ? 'posto' : 'posti', $evento['titolo'], evento_quando($evento), $codice,
                $totale > 0 ? ', paghi ' . prezzo($totale) . ' all\'ingresso' : '',
                $u ? 'La trovi nella tua area personale.' : 'Con il codice e la tua email puoi modificarla o cancellarla da «Gestisci un evento prenotato».'));
            vai('evento.php?id=' . $id);
        }
    }
}

briciole([['esibizioni.php', 'Mostre ed eventi'], [null, $evento['titolo']]]);
pagina_inizio($evento['titolo'], mb_substr($evento['descrizione'], 0, 155), 'esibizioni');
?>
<div class="contenitore">
    <div class="intestazione-pagina">
        <h1><?= e($evento['titolo']) ?></h1>
        <p class="introduzione"><?= e($evento['descrizione']) ?></p>
        <dl class="dati-mostra">
            <div><dt>Quando</dt><dd><time datetime="<?= e(date('c', strtotime($evento['inizio']))) ?>"><?= e(data_estesa($evento['inizio'], true)) ?>, ore <?= e(ora($evento['inizio'])) ?></time></dd></div>
            <div><dt>Durata</dt><dd><?= (int) $evento['durata_min'] ?> minuti</dd></div>
            <div><dt>Dove</dt><dd><?= e($evento['luogo']) ?></dd></div>
            <div><dt>Prezzo</dt><dd><?= e(prezzo($evento['prezzo'])) ?></dd></div>
            <div><dt>Posti liberi</dt><dd><?= $evento['passato'] ? '–' : $evento['rimasti'] . ' su ' . (int) $evento['posti'] ?></dd></div>
            <?php if ($evento['mostra']): ?>
                <div><dt>Mostra</dt><dd><a href="mostra.php?m=<?= e($evento['slug']) ?>"><?= e($evento['mostra']) ?></a></dd></div>
            <?php endif; ?>
        </dl>
    </div>

    <section id="prenota" class="blocco-form" aria-labelledby="titolo-prenota">
        <h2 id="titolo-prenota">Prenota</h2>
        <?php if ($evento['passato']): ?>
            <p>Questo evento si è già svolto. Guarda gli altri in <a href="esibizioni.php#eventi">calendario</a>.</p>
        <?php elseif ($evento['rimasti'] === 0): ?>
            <p>I posti sono finiti. Qualcuno potrebbe cancellare: riprova nei prossimi giorni, oppure scegli un altro evento in <a href="esibizioni.php#eventi">calendario</a>.</p>
        <?php else: ?>
            <p>Paghi all'ingresso il giorno dell'evento. <?= $u ? 'La prenotazione finirà nella tua <a href="account.php">area personale</a>.' : 'Hai già prenotato? <a href="gestisci-evento.php">Modifica o cancella la prenotazione</a>.' ?></p>
            <?= riepilogo_errori($errori) ?>
            <form action="evento.php?id=<?= (int) $id ?>#prenota" method="post" novalidate="novalidate" data-valida="">
                <?= campo_csrf() ?>
                <?php evento_campi($v, $errori, $evento); ?>
                <button class="bottone" type="submit">Prenota i posti</button>
            </form>
        <?php endif; ?>
    </section>
</div>
<?php pagina_fine();
