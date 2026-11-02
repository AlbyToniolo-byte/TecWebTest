<?php
// Punto di ingresso: le due versioni del sito vivono in v1/ e v2/, ognuna con il proprio codice.
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kronos, Museo dei Mondi Perduti: scegli la versione</title>
    <link rel="icon" href="v1/img/favicon.svg" type="image/svg+xml">
    <style>
        body { margin: 0; min-height: 100svh; display: grid; place-content: center; gap: 1.5rem; padding: 1rem;
               background: #e5e5e5; color: #000; font: 1.125rem/1.6 system-ui, sans-serif; }
        h1 { font-family: Georgia, serif; font-weight: 400; font-size: clamp(2rem, 1.5rem + 3vw, 3.5rem); margin: 0; }
        ul { margin: 0; padding: 0; list-style: none; display: grid; gap: 1rem; }
        a { color: inherit; font-size: 1.25rem; }
        a:focus-visible { outline: 3px solid #000; outline-offset: 3px; }
    </style>
</head>
<body>
    <main>
        <h1>Kronos, Museo dei Mondi Perduti</h1>
        <p>Il sito esiste in due versioni indipendenti, con gli stessi contenuti.</p>
        <ul>
            <li><a href="v1/">Versione 1</a>: sfera armillare 3D, colori delle cinque mostre</li>
            <li><a href="v2/">Versione 2</a>: ispirata a miligram.pt, sfera di puntini e macchina da scrivere</li>
        </ul>
    </main>
</body>
</html>
