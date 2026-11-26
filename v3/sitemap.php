<?php
// Mappa del sito per i motori di ricerca (sitemaps.org), con le pagine di questa versione e una voce per mostra.
// Gli indirizzi assoluti si costruiscono dall'host della richiesta, così funziona anche dopo la pubblicazione.
require_once __DIR__ . '/php/funzioni.php';

header('Content-Type: application/xml; charset=utf-8');
$base = (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
      . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/php/';
$pagine = ['index.php', 'esibizioni.php', 'visit.php', 'shop.php', 'support.php', 'about.php'];
foreach (query('SELECT slug FROM mostre ORDER BY ordine') as $m) {
    $pagine[] = 'mostra.php?m=' . $m['slug'];
}
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n", '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($pagine as $p) {
    echo '  <url><loc>', e($base . $p), '</loc></url>', "\n";
}
echo '</urlset>', "\n";
