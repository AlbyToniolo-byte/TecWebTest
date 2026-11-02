<?php
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';

$mostre = query('SELECT slug, titolo, sommario, tema FROM mostre ORDER BY ordine');
$team = query('SELECT nome, ruolo, bio FROM membri_team ORDER BY ordine');

pagina_inizio('Chi siamo', 'Perché un museo dei mondi perduti, da dove viene il nome Kronos e chi ci lavora.', 'about');
?>
<div class="contenitore">
    <div class="intestazione-pagina">
        <h1>Chi siamo</h1>
        <p class="introduzione">Kronos raccoglie sotto lo stesso tetto mondi che non esistono più, o che forse non sono mai esistiti. Li mettiamo uno accanto all'altro perché si capiscono meglio insieme: la scienza spiega il passato, il mito racconta come lo abbiamo immaginato.</p>
    </div>

    <div class="griglia-due">
        <section aria-labelledby="titolo-nome">
            <h2 id="titolo-nome">Perché Kronos</h2>
            <p>Nella mitologia greca Crono, Kronos, è il titano che governa l'età dell'oro, prima di Zeus. Già gli antichi lo confondevano con Chronos, il tempo in persona, e da allora i due nomi viaggiano insieme.</p>
            <p>Ci è sembrato il nome giusto per un museo che parla di ciò che il tempo ha portato via: specie estinte, civiltà sommerse, cieli che i nostri antenati leggevano in modo diverso dal nostro.</p>
        </section>
        <section aria-labelledby="titolo-discesa">
            <h2 id="titolo-discesa">Un percorso in discesa</h2>
            <p>Le mostre sono disposte dall'alto verso il basso. Si comincia in terrazza, sotto le stelle, si attraversano l'Olimpo, le foreste del Mesozoico e le steppe ghiacciate, e si finisce sotto il livello della strada, tra le rovine di Atlantide.</p>
            <p>Ogni mondo ha i suoi colori: li ritrovi nelle sale, sulle didascalie e in queste pagine.</p>
        </section>
    </div>

    <section aria-labelledby="titolo-temi">
        <h2 id="titolo-temi">I cinque mondi</h2>
        <ul class="temi">
            <?php foreach ($mostre as $m): ?>
                <li class="tema-<?= e($m['tema']) ?>">
                    <a href="mostra.php?m=<?= e($m['slug']) ?>"><?= e($m['titolo']) ?></a>
                    <p><?= e($m['sommario']) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section aria-labelledby="titolo-team">
        <h2 id="titolo-team">Il gruppo di lavoro</h2>
        <ul class="squadra">
            <?php foreach ($team as $t): ?>
                <li>
                    <h3><?= e($t['nome']) ?></h3>
                    <p class="ruolo"><?= e($t['ruolo']) ?></p>
                    <p><?= e($t['bio']) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
<?php pagina_fine();
