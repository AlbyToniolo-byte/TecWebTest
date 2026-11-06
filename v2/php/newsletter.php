<?php
// Iscrizione alla newsletter (dal piè di pagina) e annullamento (da gestisci.php).
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';

// Torna solo a pagine del sito (niente redirect verso domini esterni).
$torna = basename((string) ($_POST['torna'] ?? 'index.php'));
if (!preg_match('/^[a-z]+\.php(\?[\w=&%-]*)?$/', $torna)) {
    $torna = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    vai($torna);
}

$email = trim((string) ($_POST['email'] ?? ''));
if (!email_valida($email)) {
    flash('Iscrizione non riuscita: inserisci un indirizzo email valido, per esempio nome@esempio.it.');
    vai($torna);
}

if (($_POST['azione'] ?? '') === 'annulla') {
    db()->prepare('DELETE FROM iscrizioni_newsletter WHERE email = :e')->execute(['e' => mb_strtolower($email)]);
    // stesso messaggio che l'indirizzo fosse iscritto o no: non si rivela chi è iscritto
    flash('Fatto: l\'indirizzo ' . $email . ' non riceverà più la newsletter.');
    vai($torna);
}

$st = db()->prepare('INSERT INTO iscrizioni_newsletter (email) VALUES (:e) ON CONFLICT (email) DO NOTHING');
$st->execute(['e' => mb_strtolower($email)]);
flash('Iscrizione completata: riceverai la prossima newsletter a ' . $email . '.');
vai($torna);
