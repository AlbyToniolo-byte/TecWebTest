<?php
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';

$_SESSION['carrello'] ??= [];
$carrello = &$_SESSION['carrello'];
$errori = [];
$v = ['nome' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido()) {
    $azione = (string) ($_POST['azione'] ?? '');
    $id = (int) ($_POST['prodotto'] ?? 0);

    if ($azione === 'aggiungi') {
        $p = query_riga('SELECT nome, disponibili FROM prodotti WHERE id = :id', ['id' => $id]);
        $q = filter_var($_POST['quantita'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10]]) ?: 1;
        if ($p && $p['disponibili'] > 0) {
            $carrello[$id] = min($p['disponibili'], ($carrello[$id] ?? 0) + $q);
            flash($p['nome'] . ' aggiunto al carrello.');
            $_SESSION['appena_aggiunto'] = $id;   // la riga del carrello viene evidenziata una volta
        }
        vai('shop.php#carrello');
    }

    if ($azione === 'rimuovi') {
        unset($carrello[$id]);
        flash('Prodotto rimosso dal carrello.');
        vai('shop.php#carrello');
    }

    if ($azione === 'ordina' && $carrello) {
        $v['nome'] = trim((string) ($_POST['nome'] ?? ''));
        $v['email'] = trim((string) ($_POST['email'] ?? ''));
        if ($v['nome'] === '') {
            $errori['nome'] = 'Inserisci il tuo nome e cognome';
        }
        if (!email_valida($v['email'])) {
            $errori['email'] = 'Inserisci un indirizzo email valido, per esempio nome@esempio.it';
        }

        if (!$errori) {
            $pdo = db();
            $pdo->beginTransaction();
            // Blocca le righe dei prodotti per evitare di vendere più pezzi di quelli disponibili
            $in = implode(',', array_map('intval', array_keys($carrello)));
            $prodotti = $pdo->query("SELECT id, nome, prezzo, disponibili FROM prodotti WHERE id IN ($in) FOR UPDATE")->fetchAll();
            $totale = 0;
            foreach ($prodotti as $p) {
                if ($carrello[$p['id']] > $p['disponibili']) {
                    $errori['nome'] = 'Di ' . $p['nome'] . ' restano solo ' . $p['disponibili'] . ' pezzi: riduci la quantità nel carrello';
                }
                $totale += $carrello[$p['id']] * (float) $p['prezzo'];
            }
            if ($errori) {
                $pdo->rollBack();
            } else {
                $codice = codice_casuale();
                $st = $pdo->prepare('INSERT INTO ordini (codice, nome, email, totale) VALUES (:c, :n, :e, :t) RETURNING id');
                $st->execute(['c' => $codice, 'n' => $v['nome'], 'e' => $v['email'], 't' => $totale]);
                $idOrdine = $st->fetchColumn();
                $riga = $pdo->prepare('INSERT INTO ordini_righe VALUES (:o, :p, :q, :u)');
                $scala = $pdo->prepare('UPDATE prodotti SET disponibili = disponibili - :q WHERE id = :p');
                foreach ($prodotti as $p) {
                    $riga->execute(['o' => $idOrdine, 'p' => $p['id'], 'q' => $carrello[$p['id']], 'u' => $p['prezzo']]);
                    $scala->execute(['q' => $carrello[$p['id']], 'p' => $p['id']]);
                }
                $pdo->commit();
                $carrello = [];
                flash("Ordine $codice confermato. Lo trovi pronto al bookshop da domani: paghi " . prezzo($totale) . ' al ritiro.');
                vai('shop.php');
            }
        }
    }
}

// Filtri dell'elenco
$categorie = array_column(query('SELECT DISTINCT categoria FROM prodotti ORDER BY categoria'), 'categoria');
$mostre = query('SELECT slug, titolo FROM mostre ORDER BY ordine');
$fCat = in_array($_GET['categoria'] ?? '', $categorie, true) ? $_GET['categoria'] : '';
$fMostra = in_array($_GET['mostra'] ?? '', array_column($mostre, 'slug'), true) ? $_GET['mostra'] : '';

$where = [];
$params = [];
if ($fCat) { $where[] = 'p.categoria = :c'; $params['c'] = $fCat; }
if ($fMostra) { $where[] = 'm.slug = :m'; $params['m'] = $fMostra; }
$prodotti = query('SELECT p.id, p.nome, p.descrizione, p.categoria, p.prezzo, p.disponibili, p.immagine, m.titolo AS mostra
                   FROM prodotti p LEFT JOIN mostre m ON m.id = p.mostra_id'
                   . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY m.ordine, p.nome', $params);

// Righe del carrello
$appenaAggiunto = $_SESSION['appena_aggiunto'] ?? null;
unset($_SESSION['appena_aggiunto']);
$righe = [];
$totale = 0;
if ($carrello) {
    $in = implode(',', array_map('intval', array_keys($carrello)));
    foreach (query("SELECT id, nome, prezzo FROM prodotti WHERE id IN ($in) ORDER BY nome") as $p) {
        $p['quantita'] = $carrello[$p['id']];
        $totale += $p['quantita'] * (float) $p['prezzo'];
        $righe[] = $p;
    }
}

pagina_inizio('Shop', 'Libri, riproduzioni e oggetti ispirati alle mostre di Kronos, da ritirare al bookshop del museo.', 'shop');
?>
<div class="contenitore">
    <div class="intestazione-pagina">
        <h1>Shop</h1>
        <p class="introduzione">Libri, riproduzioni e oggetti ispirati alle mostre. Ordini qui e ritiri al bookshop del museo, anche senza biglietto.</p>
    </div>

    <section id="carrello" class="carrello" aria-labelledby="titolo-carrello" tabindex="-1">
        <h2 id="titolo-carrello">Il tuo carrello</h2>
        <?php if (!$righe): ?>
            <p>Il carrello è vuoto. Scegli un prodotto qui sotto e premi «Aggiungi al carrello».</p>
        <?php else: ?>
            <table>
                <caption class="visually-hidden">Prodotti nel carrello</caption>
                <thead><tr><th scope="col">Prodotto</th><th scope="col" class="numero">Quantità</th><th scope="col" class="numero">Prezzo</th><th scope="col"><span class="visually-hidden">Azioni</span></th></tr></thead>
                <tbody>
                    <?php foreach ($righe as $r): ?>
                        <tr<?= $r['id'] === $appenaAggiunto ? ' class="appena-aggiunto"' : '' ?>>
                            <th scope="row"><?= e($r['nome']) ?></th>
                            <td class="numero"><?= (int) $r['quantita'] ?></td>
                            <td class="numero"><?= e(prezzo($r['quantita'] * $r['prezzo'])) ?></td>
                            <td>
                                <form action="shop.php" method="post">
                                    <?= campo_csrf() ?>
                                    <input type="hidden" name="azione" value="rimuovi">
                                    <input type="hidden" name="prodotto" value="<?= (int) $r['id'] ?>">
                                    <button class="bottone-testo" type="submit">Rimuovi<span class="visually-hidden"> <?= e($r['nome']) ?></span></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot><tr><th scope="row">Totale</th><td></td><td class="numero totale"><?= e(prezzo($totale)) ?></td><td></td></tr></tfoot>
            </table>

            <div class="blocco-form">
                <h3>Conferma l'ordine</h3>
                <p>Nessun pagamento online: paghi al ritiro, al bookshop del piano terra.</p>
                <?= riepilogo_errori($errori) ?>
                <form action="shop.php#carrello" method="post" novalidate data-valida>
                    <?= campo_csrf() ?>
                    <input type="hidden" name="azione" value="ordina">
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
                    <button class="bottone" type="submit">Conferma l'ordine</button>
                </form>
            </div>
        <?php endif; ?>
    </section>

    <section aria-labelledby="titolo-prodotti">
        <h2 id="titolo-prodotti">Prodotti</h2>
        <form class="filtro" action="shop.php" method="get">
            <div class="campo">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria">
                    <option value="">Tutte le categorie</option>
                    <?php foreach ($categorie as $c): ?>
                        <option<?= $c === $fCat ? ' selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="mostra">Mostra</label>
                <select id="mostra" name="mostra">
                    <option value="">Tutte le mostre</option>
                    <?php foreach ($mostre as $m): ?>
                        <option value="<?= e($m['slug']) ?>"<?= $m['slug'] === $fMostra ? ' selected' : '' ?>><?= e($m['titolo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="bottone" type="submit">Filtra i prodotti</button>
        </form>
        <p><?= count($prodotti) === 1 ? '1 prodotto' : count($prodotti) . ' prodotti' ?></p>

        <?php if (!$prodotti): ?>
            <p>Nessun prodotto corrisponde ai filtri scelti. <a href="shop.php">Vedi tutti i prodotti</a>.</p>
        <?php endif; ?>
        <ul class="prodotti">
            <?php foreach ($prodotti as $p): $idq = 'quantita-' . $p['id']; ?>
                <li class="prodotto">
                    <h3><?= e($p['nome']) ?></h3>
                    <figure class="prodotto-immagine"><img src="<?= e(asset($p['immagine'])) ?>" alt="" width="800" height="600" loading="lazy"></figure>
                    <p class="prodotto-mostra"><?= e($p['categoria']) ?><?= $p['mostra'] ? ', dalla mostra ' . e($p['mostra']) : '' ?></p>
                    <p><?= e($p['descrizione']) ?></p>
                    <p class="prodotto-prezzo"><?= e(prezzo($p['prezzo'])) ?></p>
                    <?php if ($p['disponibili'] > 0): ?>
                        <form action="shop.php" method="post">
                            <?= campo_csrf() ?>
                            <input type="hidden" name="azione" value="aggiungi">
                            <input type="hidden" name="prodotto" value="<?= (int) $p['id'] ?>">
                            <div class="campo">
                                <label for="<?= e($idq) ?>">Quantità<span class="visually-hidden"> di <?= e($p['nome']) ?></span></label>
                                <input type="number" id="<?= e($idq) ?>" name="quantita" value="1" min="1" max="<?= min(10, (int) $p['disponibili']) ?>" inputmode="numeric">
                            </div>
                            <button class="bottone" type="submit">Aggiungi al carrello<span class="visually-hidden"> <?= e($p['nome']) ?></span></button>
                        </form>
                    <?php else: ?>
                        <p class="esaurito">Esaurito</p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
<?php pagina_fine();
