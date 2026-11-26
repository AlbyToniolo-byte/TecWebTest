<?php
// Registrazione di un nuovo account visitatore. I controlli sono ripetuti in js/validation.js.
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';

$torna = ritorno_sicuro($_GET['torna'] ?? $_POST['torna'] ?? null);
if (utente()) {
    vai($torna);
}

$errori = [];
$v = ['nome' => '', 'utente' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['nome'] = trim((string) ($_POST['nome'] ?? ''));
    $v['utente'] = mb_strtolower(trim((string) ($_POST['utente'] ?? '')));
    $v['email'] = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $conferma = (string) ($_POST['conferma'] ?? '');

    if (!csrf_valido()) {
        $errori['nome'] = 'La sessione è scaduta: ricarica la pagina e invia di nuovo il modulo';
    }
    if ($v['nome'] === '' || mb_strlen($v['nome']) > 120) {
        $errori['nome'] ??= 'Inserisci il tuo nome e cognome (al massimo 120 caratteri)';
    }
    if (!preg_match('/^[a-z0-9._-]{3,30}$/', $v['utente'])) {
        $errori['utente'] = 'Il nome utente va da 3 a 30 caratteri: lettere senza accenti, cifre, punto, trattino o trattino basso';
    }
    if (!email_valida($v['email']) || mb_strlen($v['email']) > 200) {
        $errori['email'] = 'Inserisci un indirizzo email valido, per esempio nome@esempio.it';
    }
    $errori += controlla_password($password, $conferma);

    if (!$errori) {
        $doppio = query_riga('SELECT nome_utente = :u AS stesso_utente FROM utenti WHERE nome_utente = :u OR email = :e LIMIT 1',
            ['u' => $v['utente'], 'e' => $v['email']]);
        if ($doppio) {
            if ($doppio['stesso_utente']) {
                $errori['utente'] = 'Questo nome utente è già preso: scegline un altro';
            } else {
                $errori['email'] = 'Con questa email c\'è già un account: accedi, oppure usa un\'altra email';
            }
        }
    }

    if (!$errori) {
        $st = db()->prepare('INSERT INTO utenti (nome_utente, nome, email, password_hash) VALUES (:u, :n, :e, :h) RETURNING id');
        $st->execute(['u' => $v['utente'], 'n' => $v['nome'], 'e' => $v['email'], 'h' => password_hash($password, PASSWORD_DEFAULT)]);
        entra((int) $st->fetchColumn());
        flash('Account creato: benvenuto, ' . $v['nome'] . '. Se hai già prenotato senza account, puoi aggiungere qui la prenotazione con il suo codice.');
        vai($torna);
    }
}

pagina_inizio('Registrati', 'Crea un account del museo Kronos per gestire prenotazioni, tessere e ordini in un solo posto.', 'accedi');
?>
<div class="pagina-stretta">
    <?php briciole([['accedi.php', 'Accedi'], [null, 'Registrati']]); ?>
    <div class="intestazione-pagina">
        <h1>Crea un account</h1>
        <p class="introduzione">Ti servono un nome utente e una password. L'email la usiamo solo per mostrarti le tue prenotazioni e la newsletter, se la vuoi.</p>
    </div>

    <section class="blocco-form" aria-labelledby="titolo-registrati">
        <h2 id="titolo-registrati">I tuoi dati</h2>
        <p>Tutti i campi sono obbligatori.</p>
        <?= riepilogo_errori($errori) ?>
        <form action="registrati.php" method="post" novalidate="novalidate" data-valida="">
            <?= campo_csrf() ?>
            <input type="hidden" name="torna" value="<?= e($torna) ?>" />
            <div class="campo">
                <label for="nome">Nome e cognome</label>
                <input type="text" id="nome" name="nome" autocomplete="name" maxlength="120" value="<?= e($v['nome']) ?>"
                       required="required" aria-required="true" data-msg-vuoto="Inserisci il tuo nome e cognome"<?= attr_campo('nome', $errori) ?> />
                <?= errore_campo('nome', $errori) ?>
            </div>
            <div class="campo">
                <label for="utente">Nome utente</label>
                <p class="suggerimento" id="utente-aiuto">Da 3 a 30 caratteri: lettere senza accenti, cifre, punto, trattino o trattino basso.</p>
                <input type="text" id="utente" name="utente" autocomplete="username" autocapitalize="none" spellcheck="false"
                       minlength="3" maxlength="30" pattern="[A-Za-z0-9._\-]{3,30}" value="<?= e($v['utente']) ?>"
                       required="required" aria-required="true" data-msg-vuoto="Scegli un nome utente"
                       data-msg-corta="Il nome utente ha almeno 3 caratteri"
                       data-msg-formato="Usa solo lettere senza accenti, cifre, punto, trattino o trattino basso"<?= attr_campo('utente', $errori, 'utente-aiuto') ?> />
                <?= errore_campo('utente', $errori) ?>
            </div>
            <div class="campo">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" spellcheck="false" maxlength="200" value="<?= e($v['email']) ?>"
                       required="required" aria-required="true" data-msg-vuoto="Inserisci la tua email"<?= attr_campo('email', $errori) ?> />
                <?= errore_campo('email', $errori) ?>
            </div>
            <div class="campo">
                <label for="password">Password</label>
                <p class="suggerimento" id="password-aiuto">Almeno <?= PASSWORD_MIN ?> caratteri. Una frase di più parole è facile da ricordare e difficile da indovinare.</p>
                <input type="password" id="password" name="password" autocomplete="new-password" minlength="<?= PASSWORD_MIN ?>" maxlength="72"
                       required="required" aria-required="true" data-msg-vuoto="Scegli una password"
                       data-msg-corta="La password deve avere almeno <?= PASSWORD_MIN ?> caratteri"<?= attr_campo('password', $errori, 'password-aiuto') ?> />
                <?= errore_campo('password', $errori) ?>
            </div>
            <div class="campo">
                <label for="conferma">Ripeti la password</label>
                <input type="password" id="conferma" name="conferma" autocomplete="new-password" maxlength="72"
                       required="required" aria-required="true" data-msg-vuoto="Ripeti la password"
                       data-uguale-a="password" data-msg-diverso="Le due password non coincidono"<?= attr_campo('conferma', $errori) ?> />
                <?= errore_campo('conferma', $errori) ?>
            </div>
            <button class="bottone" type="submit">Crea l'account</button>
        </form>
    </section>
    <p>Hai già un account? <a href="accedi.php">Accedi</a>.</p>
</div>
<?php pagina_fine();
