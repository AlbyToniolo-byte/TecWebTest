<?php
// Funzioni di supporto comuni a tutte le pagine.

session_start();

require_once __DIR__ . '/connection.php';

// Colore di sfondo di ogni tema: deve combaciare con --sfondo in css/themes.css.
// Serve alla home per sfumare ogni mondo nel successivo.
const COLORE_TEMA = [
    'celeste'   => '#14213d',
    'mitologia' => '#5e0b15',
    'dinosauri' => '#283618',
    'glaciale'  => '#243640',
    'atlantide' => '#184e77',
];

// Escape per l'output HTML.
function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function prezzo(float|string $valore): string
{
    $v = (float) $valore;
    return $v == 0 ? 'Gratis' : number_format($v, 2, ',', '.') . ' €';
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
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
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
    return '<p class="errore" id="' . e($id) . '-errore"' . (isset($errori[$id]) ? '' : ' hidden') . '>'
        . e($errori[$id] ?? '') . '</p>';
}

// Riepilogo degli errori in cima al form: riceve il focus dopo un invio fallito.
function riepilogo_errori(array $errori): string
{
    if (!$errori) {
        return '';
    }
    $html = '<div class="riepilogo-errori" tabindex="-1" data-focus-iniziale>'
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

/** Percorso di una risorsa statica (css, js, img) vista dalle pagine in php/. */
function asset(string $percorso): string
{
    return '../' . ltrim($percorso, '/');
}
