<?php
// Uscita dall'account: solo con POST e token CSRF, così un link di un altro sito non può far uscire nessuno.
require_once __DIR__ . '/funzioni.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido()) {
    esci();
    flash('Sei uscito dal tuo account.');
}
vai('index.php');
