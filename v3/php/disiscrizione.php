<?php
// Annullamento dell'iscrizione alla newsletter. Il modulo invia a newsletter.php (azione "annulla"),
// che cancella l'indirizzo e torna qui con un messaggio; il link arriva dal piè di pagina, sotto l'iscrizione.
require_once __DIR__ . '/funzioni.php';
require_once __DIR__ . '/header_footer.php';

pagina_inizio('Annulla l\'iscrizione alla newsletter', 'Smetti di ricevere la newsletter del museo Kronos e cancella il tuo indirizzo.');
?>
<div class="contenitore">
    <?php briciole([[null, 'Annulla l\'iscrizione alla newsletter']]); ?>
    <div class="intestazione-pagina">
        <h1>Annulla l'iscrizione alla newsletter</h1>
        <p class="introduzione">Inserisci l'indirizzo iscritto: smetterai di ricevere la newsletter e l'indirizzo verrà cancellato dai nostri dati. Puoi iscriverti di nuovo quando vuoi dal piè di pagina.</p>
    </div>

    <section class="blocco-form" aria-labelledby="titolo-annulla">
        <h2 id="titolo-annulla">Il tuo indirizzo</h2>
        <form action="newsletter.php" method="post" novalidate="novalidate" data-valida="">
            <?= campo_csrf() ?>
            <input type="hidden" name="azione" value="annulla" />
            <input type="hidden" name="torna" value="disiscrizione.php" />
            <div class="campo">
                <label for="email-annulla">Indirizzo email <span class="obbligatorio" aria-hidden="true">*</span></label>
                <input type="email" id="email-annulla" name="email" autocomplete="email" spellcheck="false" required="required" aria-required="true"
                       data-msg-vuoto="Inserisci l'indirizzo email iscritto alla newsletter" aria-describedby="email-annulla-errore" />
                <p class="errore" id="email-annulla-errore" hidden="hidden"></p>
            </div>
            <button class="bottone" type="submit">Annulla l'iscrizione</button>
        </form>
    </section>
</div>
<?php pagina_fine();
