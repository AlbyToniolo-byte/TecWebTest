<?php
// Profilo: modifica di nome ed email, cambio password, eliminazione dell'account.
// Cambio password ed eliminazione chiedono la password attuale: con una sessione lasciata aperta
// qualcun altro non può prendersi o cancellare l'account.
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';

$u = richiedi_accesso();
$errori = [];
$v = ['nome' => $u['nome'], 'email' => $u['email']];
$conferma = false;   // true: mostra la domanda "vuoi davvero eliminare l'account?"

// La password attuale, letta dal database solo quando serve
function password_giusta(int $id, string $password): bool
{
    $h = query_riga('SELECT password_hash FROM utenti WHERE id = :id', ['id' => $id])['password_hash'] ?? '';
    return password_verify($password, $h);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido()) {
    $azione = (string) ($_POST['azione'] ?? '');

    if ($azione === 'dati') {
        $v['nome'] = trim((string) ($_POST['nome'] ?? ''));
        $v['email'] = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        if ($v['nome'] === '' || mb_strlen($v['nome']) > 120) {
            $errori['nome'] = 'Inserisci il tuo nome e cognome (al massimo 120 caratteri)';
        }
        if (!email_valida($v['email']) || mb_strlen($v['email']) > 200) {
            $errori['email'] = 'Inserisci un indirizzo email valido, per esempio nome@esempio.it';
        } elseif (query_riga('SELECT 1 FROM utenti WHERE email = :e AND id <> :id', ['e' => $v['email'], 'id' => $u['id']])) {
            $errori['email'] = 'Con questa email c\'è già un altro account';
        }
        if (!$errori) {
            db()->prepare('UPDATE utenti SET nome = :n, email = :e WHERE id = :id')
                ->execute(['n' => $v['nome'], 'e' => $v['email'], 'id' => $u['id']]);
            flash('Dati aggiornati.');
            vai('profilo.php');
        }
    }

    if ($azione === 'password') {
        if (!password_giusta($u['id'], (string) ($_POST['attuale'] ?? ''))) {
            $errori['attuale'] = 'La password attuale non è corretta';
        }
        $nuova = (string) ($_POST['nuova'] ?? '');
        $errori += controlla_password($nuova, (string) ($_POST['ripeti'] ?? ''), 'nuova', 'ripeti');
        if (!$errori) {
            db()->prepare('UPDATE utenti SET password_hash = :h WHERE id = :id')
                ->execute(['h' => password_hash($nuova, PASSWORD_DEFAULT), 'id' => $u['id']]);
            session_regenerate_id(true);
            flash('Password cambiata.');
            vai('profilo.php');
        }
    }

    if ($azione === 'chiedi-eliminazione') {
        $conferma = true;
    }

    if ($azione === 'elimina') {
        if (password_giusta($u['id'], (string) ($_POST['password-elimina'] ?? ''))) {
            // prenotazioni, tessere e ordini restano (utente_id torna NULL): si gestiscono ancora con codice ed email
            db()->prepare('DELETE FROM utenti WHERE id = :id')->execute(['id' => $u['id']]);
            esci();
            flash('Account eliminato. Prenotazioni, tessere e ordini restano validi: li gestisci con il codice e l\'email della conferma.');
            vai('index.php');
        }
        $conferma = true;
        $errori['password-elimina'] = 'La password non è corretta';
    }
}

pagina_inizio('Il tuo profilo', 'Modifica i dati e la password del tuo account del museo Kronos.', 'account');
?>
<div class="pagina-stretta">
    <?php briciole([['account.php', 'Area personale'], [null, 'Profilo']]); ?>
    <div class="intestazione-pagina">
        <h1>Il tuo profilo</h1>
        <p class="introduzione">Nome utente: <strong><?= e($u['nome_utente']) ?></strong>. Il nome utente non si può cambiare.</p>
    </div>

    <?php if ($conferma): ?>
        <section class="blocco-form" aria-labelledby="titolo-conferma">
            <h2 id="titolo-conferma" tabindex="-1" data-focus-iniziale="">Vuoi eliminare l'account?</h2>
            <p>L'account <?= e($u['nome_utente']) ?> verrà cancellato e non si potrà recuperare. Prenotazioni, tessere e ordini restano validi: li gestirai con il codice e l'email della conferma.</p>
            <?= riepilogo_errori($errori) ?>
            <form action="profilo.php" method="post" novalidate="novalidate" data-valida="">
                <?= campo_csrf() ?>
                <input type="hidden" name="azione" value="elimina" />
                <div class="campo">
                    <label for="password-elimina">Per confermare, scrivi la tua password</label>
                    <input type="password" id="password-elimina" name="password-elimina" autocomplete="current-password"
                           required="required" aria-required="true" data-msg-vuoto="Scrivi la tua password per confermare"<?= attr_campo('password-elimina', $errori) ?> />
                    <?= errore_campo('password-elimina', $errori) ?>
                </div>
                <div class="azioni">
                    <button class="bottone" type="submit">Sì, elimina l'account</button>
                    <a class="bottone bottone-secondario" href="profilo.php">No, tienilo</a>
                </div>
            </form>
        </section>
    <?php else: ?>
        <section class="blocco-form" aria-labelledby="titolo-dati">
            <h2 id="titolo-dati">I tuoi dati</h2>
            <?= ($_POST['azione'] ?? '') === 'dati' ? riepilogo_errori($errori) : '' ?>
            <form action="profilo.php" method="post" novalidate="novalidate" data-valida="">
                <?= campo_csrf() ?>
                <input type="hidden" name="azione" value="dati" />
                <div class="campo">
                    <label for="nome">Nome e cognome</label>
                    <input type="text" id="nome" name="nome" autocomplete="name" maxlength="120" value="<?= e($v['nome']) ?>"
                           required="required" aria-required="true" data-msg-vuoto="Inserisci il tuo nome e cognome"<?= attr_campo('nome', $errori) ?> />
                    <?= errore_campo('nome', $errori) ?>
                </div>
                <div class="campo">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" autocomplete="email" spellcheck="false" maxlength="200" value="<?= e($v['email']) ?>"
                           required="required" aria-required="true" data-msg-vuoto="Inserisci la tua email"<?= attr_campo('email', $errori) ?> />
                    <?= errore_campo('email', $errori) ?>
                </div>
                <button class="bottone" type="submit">Salva i dati</button>
            </form>
        </section>

        <section class="blocco-form" aria-labelledby="titolo-password">
            <h2 id="titolo-password">Password</h2>
            <?= ($_POST['azione'] ?? '') === 'password' ? riepilogo_errori($errori) : '' ?>
            <form action="profilo.php" method="post" novalidate="novalidate" data-valida="">
                <?= campo_csrf() ?>
                <input type="hidden" name="azione" value="password" />
                <!-- il nome utente aiuta i gestori di password a capire di quale account si tratta -->
                <input type="text" name="utente" value="<?= e($u['nome_utente']) ?>" autocomplete="username" hidden="hidden" readonly="readonly" />
                <div class="campo">
                    <label for="attuale">Password attuale</label>
                    <input type="password" id="attuale" name="attuale" autocomplete="current-password"
                           required="required" aria-required="true" data-msg-vuoto="Inserisci la password attuale"<?= attr_campo('attuale', $errori) ?> />
                    <?= errore_campo('attuale', $errori) ?>
                </div>
                <div class="campo">
                    <label for="nuova">Nuova password</label>
                    <p class="suggerimento" id="nuova-aiuto">Almeno <?= PASSWORD_MIN ?> caratteri.</p>
                    <input type="password" id="nuova" name="nuova" autocomplete="new-password" minlength="<?= PASSWORD_MIN ?>" maxlength="72"
                           required="required" aria-required="true" data-msg-vuoto="Scegli la nuova password"
                           data-msg-corta="La password deve avere almeno <?= PASSWORD_MIN ?> caratteri"<?= attr_campo('nuova', $errori, 'nuova-aiuto') ?> />
                    <?= errore_campo('nuova', $errori) ?>
                </div>
                <div class="campo">
                    <label for="ripeti">Ripeti la nuova password</label>
                    <input type="password" id="ripeti" name="ripeti" autocomplete="new-password" maxlength="72"
                           required="required" aria-required="true" data-msg-vuoto="Ripeti la nuova password"
                           data-uguale-a="nuova" data-msg-diverso="Le due password non coincidono"<?= attr_campo('ripeti', $errori) ?> />
                    <?= errore_campo('ripeti', $errori) ?>
                </div>
                <button class="bottone" type="submit">Cambia la password</button>
            </form>
        </section>

        <section class="blocco-form" aria-labelledby="titolo-elimina">
            <h2 id="titolo-elimina">Elimina l'account</h2>
            <p>Puoi cancellare l'account quando vuoi. Le prenotazioni, le tessere e gli ordini non vengono cancellati: restano gestibili con codice ed email.</p>
            <form action="profilo.php" method="post">
                <?= campo_csrf() ?>
                <input type="hidden" name="azione" value="chiedi-eliminazione" />
                <button class="bottone bottone-secondario" type="submit">Elimina l'account</button>
            </form>
        </section>
    <?php endif; ?>
</div>
<?php pagina_fine();
