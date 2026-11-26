<?php
// Funzioni di supporto comuni a tutte le pagine.

// Cookie di sessione non leggibile da JavaScript e non inviato da altri siti nelle richieste POST
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require_once __DIR__ . '/connection.php';

// Escape per l'output HTML.
function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// "16 €" per le cifre tonde, "7,50 €" con i centesimi, "Gratis" per zero
function prezzo(float|string $valore): string
{
    $v = round((float) $valore, 2);
    if ($v == 0) {
        return 'Gratis';
    }
    return number_format($v, fmod($v, 1) == 0 ? 0 : 2, ',', '.') . ' €';
}

const MESI = ['gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio',
              'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'];
const GIORNI = [1 => 'lunedì', 'martedì', 'mercoledì', 'giovedì', 'venerdì', 'sabato', 'domenica'];

// "sabato 7 novembre"
function data_estesa(string $data, bool $conAnno = false): string
{
    $t = strtotime($data);
    $s = GIORNI[(int) date('N', $t)] . ' ' . date('j', $t) . ' ' . MESI[(int) date('n', $t) - 1];
    return $conAnno ? $s . ' ' . date('Y', $t) : $s;
}

function ora(?string $ora): string
{
    return $ora ? date('G:i', strtotime($ora)) : '';
}

function codice_casuale(): string
{
    return strtoupper(substr(bin2hex(random_bytes(6)), 0, 8));
}

// --- Protezione CSRF ---------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function campo_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '" />';
}

function csrf_valido(): bool
{
    return isset($_POST['csrf']) && hash_equals(csrf_token(), (string) $_POST['csrf']);
}

// --- Messaggi tra un redirect e l'altro (pattern Post/Redirect/Get) -----------

function flash(string $messaggio): void
{
    $_SESSION['flash'] = $messaggio;
}

function leggi_flash(): ?string
{
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

function vai(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

// --- Validazione e campi dei form ---------------------------------------------

function email_valida(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Attributi ARIA di un campo, con l'eventuale messaggio d'errore collegato.
function attr_campo(string $id, array $errori, ?string $suggerimento = null): string
{
    $descritto = [];
    if ($suggerimento) {
        $descritto[] = $suggerimento;
    }
    if (isset($errori[$id])) {
        $descritto[] = $id . '-errore';
    }
    $a = $descritto ? ' aria-describedby="' . e(implode(' ', $descritto)) . '"' : '';
    return $a . (isset($errori[$id]) ? ' aria-invalid="true"' : '');
}

// Paragrafo d'errore sempre presente: lo riempie anche js/validation.js.
function errore_campo(string $id, array $errori): string
{
    return '<p class="errore" id="' . e($id) . '-errore"' . (isset($errori[$id]) ? '' : ' hidden="hidden"') . '>'
        . e($errori[$id] ?? '') . '</p>';
}

// Riepilogo degli errori in cima al form: riceve il focus dopo un invio fallito.
function riepilogo_errori(array $errori): string
{
    if (!$errori) {
        return '';
    }
    $html = '<div class="riepilogo-errori" tabindex="-1" data-focus-iniziale="">'
        . '<h2>Controlla ' . (count($errori) === 1 ? 'questo campo' : 'questi campi') . '</h2><ul>';
    foreach ($errori as $id => $msg) {
        $html .= '<li><a href="#' . e($id) . '">' . e($msg) . '</a></li>';
    }
    return $html . '</ul></div>';
}

// Orario di oggi, usato in home e nella pagina Visita.
function stato_apertura_oggi(): string
{
    $o = query_riga('SELECT apertura, chiusura FROM orari WHERE giorno = :g', ['g' => (int) date('N')]);
    if (!$o || !$o['apertura']) {
        return 'Oggi il museo è chiuso';
    }
    return 'Oggi aperto dalle ' . ora($o['apertura']) . ' alle ' . ora($o['chiusura']);
}

/**
 * Percorso della pagina ("Ti trovi in"). Si chiama prima di pagina_inizio(): la testata lo stampa
 * subito sotto di sé, allineato al logo, fuori da <main> (il link "Vai al contenuto" lo salta).
 * @param array<array{0: ?string, 1: string}> $voci [indirizzo, testo]; l'ultima, senza indirizzo, è la pagina corrente.
 *        Nella home la lista è vuota: Home è la pagina corrente.
 */
function briciole(array $voci): void
{
    $GLOBALS['briciole_pagina'] = $voci;
}

function stampa_briciole(): void
{
    $voci = $GLOBALS['briciole_pagina'] ?? null;
    if ($voci === null) {
        return;
    }
    echo '<nav class="briciole" aria-label="Ti trovi in"><ol>',
        $voci ? '<li><a href="index.php">Home</a></li>' : '<li aria-current="page">Home</li>';
    foreach ($voci as [$href, $testo]) {
        echo $href
            ? '<li><a href="' . e($href) . '">' . e($testo) . '</a></li>'
            : '<li aria-current="page">' . e($testo) . '</li>';
    }
    echo '</ol></nav>', "\n";
}

/** Percorso di una risorsa statica (css, js, img) vista dalle pagine in php/. */
function asset(string $percorso): string
{
    return '../' . ltrim($percorso, '/');
}

// --- Account e accesso ---------------------------------------------------------
// In sessione c'è solo l'id dell'utente; i suoi dati si rileggono dal database a ogni pagina,
// così un account cancellato o un ruolo cambiato valgono subito.

const RUOLI = ['visitatore', 'admin'];

function utente(): ?array
{
    static $utente = false;
    if ($utente === false) {
        $id = $_SESSION['utente_id'] ?? null;
        $utente = $id ? query_riga('SELECT id, nome_utente, nome, email, ruolo FROM utenti WHERE id = :id', ['id' => $id]) : null;
        if ($id && !$utente) {
            unset($_SESSION['utente_id']);
        }
    }
    return $utente;
}

function e_admin(): bool
{
    return (utente()['ruolo'] ?? '') === 'admin';
}

// Nuovo id di sessione a ogni cambio di identità: un id rubato prima del login non serve
function entra(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['utente_id'] = $id;
}

function esci(): void
{
    session_regenerate_id(true);
    unset($_SESSION['utente_id'], $_SESSION['prenotazione_gestita'], $_SESSION['tessera_gestita'], $_SESSION['evento_gestito']);
}

// Pagine riservate: chi non ha fatto l'accesso va al login e poi torna qui
function richiedi_accesso(): array
{
    $u = utente();
    if (!$u) {
        flash('Accedi per vedere questa pagina.');
        vai('accedi.php?torna=' . rawurlencode(basename($_SERVER['SCRIPT_NAME'])));
    }
    return $u;
}

function richiedi_admin(): array
{
    $u = richiedi_accesso();
    if ($u['ruolo'] !== 'admin') {
        flash('Questa pagina è riservata allo staff del museo.');
        vai('account.php');
    }
    return $u;
}

// Indirizzo di ritorno dopo il login: solo una pagina di questo sito, mai un indirizzo esterno
function ritorno_sicuro(?string $torna, string $predefinito = 'account.php'): string
{
    return $torna !== null && preg_match('/^[a-z-]+\.php(\?[\w=&%-]*)?(#[\w-]+)?$/', $torna) ? $torna : $predefinito;
}

const PASSWORD_MIN = 8;

/**
 * Controlli comuni a registrazione e cambio password.
 * @return array<string,string> errori per campo
 */
function controlla_password(string $password, string $conferma, string $campo = 'password', string $campoConferma = 'conferma'): array
{
    $errori = [];
    if (mb_strlen($password) < PASSWORD_MIN) {
        $errori[$campo] = 'La password deve avere almeno ' . PASSWORD_MIN . ' caratteri';
    } elseif (mb_strlen($password) > 72) {
        $errori[$campo] = 'La password può avere al massimo 72 caratteri';   // limite di bcrypt
    }
    if ($conferma !== $password) {
        $errori[$campoConferma] = 'Le due password non coincidono';
    }
    return $errori;
}
