<?php
// Accesso con nome utente (o email) e password. Il login è facoltativo: senza account si può
// ancora prenotare e gestire tutto con codice ed email.
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';

$torna = ritorno_sicuro($_GET['torna'] ?? $_POST['torna'] ?? null);
if (utente()) {
    vai($torna);
}

$errori = [];
$v = ['utente' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['utente'] = trim((string) ($_POST['utente'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (!csrf_valido()) {
        $errori['utente'] = 'La sessione è scaduta: ricarica la pagina e accedi di nuovo';
    } else {
        if ($v['utente'] === '') {
            $errori['utente'] = 'Inserisci il nome utente o l\'email';
        }
        if ($password === '') {
            $errori['password'] = 'Inserisci la password';
        }
    }
    if (!$errori) {
        $u = query_riga('SELECT id, nome, password_hash FROM utenti WHERE nome_utente = lower(:u) OR email = lower(:u)',
            ['u' => $v['utente']]);
        if ($u && password_verify($password, $u['password_hash'])) {
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE utenti SET password_hash = :h WHERE id = :id')
                    ->execute(['h' => password_hash($password, PASSWORD_DEFAULT), 'id' => $u['id']]);
            }
            entra((int) $u['id']);
            flash('Ciao ' . $u['nome'] . ', hai fatto l\'accesso.');
            vai($torna);
        }
        // shortcut: solo un piccolo ritardo contro i tentativi ripetuti; per un sito vero, un limite per indirizzo IP
        usleep(400000);
        // stesso messaggio per utente inesistente e password sbagliata: non si rivela quali account esistono
        $errori['utente'] = 'Nome utente o password non corretti';
    }
}

pagina_inizio('Accedi', 'Accedi al tuo account del museo Kronos per gestire prenotazioni, tessere e ordini.', 'accedi');
?>
<div class="pagina-stretta">
    <?php briciole([[null, 'Accedi']]); ?>
    <div class="intestazione-pagina">
        <h1>Accedi</h1>
        <p class="introduzione">Con un account trovi in un solo posto le tue prenotazioni, le tessere e gli ordini dello shop. Non è obbligatorio: puoi prenotare anche senza.</p>
    </div>

    <section class="blocco-form" aria-labelledby="titolo-accedi">
        <h2 id="titolo-accedi">I tuoi dati di accesso</h2>
        <?= riepilogo_errori($errori) ?>
        <form action="accedi.php" method="post" novalidate="novalidate" data-valida="">
            <?= campo_csrf() ?>
            <input type="hidden" name="torna" value="<?= e($torna) ?>" />
            <div class="campo">
                <label for="utente">Nome utente o email</label>
                <input type="text" id="utente" name="utente" autocomplete="username" autocapitalize="none" spellcheck="false"
                       value="<?= e($v['utente']) ?>" required="required" aria-required="true"
                       data-msg-vuoto="Inserisci il nome utente o l'email"<?= attr_campo('utente', $errori) ?> />
                <?= errore_campo('utente', $errori) ?>
            </div>
            <div class="campo">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password"
                       required="required" aria-required="true" data-msg-vuoto="Inserisci la password"<?= attr_campo('password', $errori) ?> />
                <?= errore_campo('password', $errori) ?>
            </div>
            <button class="bottone" type="submit">Accedi</button>
        </form>
    </section>
    <p>Non hai un account? <a href="registrati.php<?= $torna !== 'account.php' ? '?torna=' . e(rawurlencode($torna)) : '' ?>">Registrati</a>, ci vuole un minuto.</p>
</div>
<?php pagina_fine();
