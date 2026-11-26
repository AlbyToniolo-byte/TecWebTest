<?php
// Prenotazione degli eventi: posti rimasti, controlli e campi del modulo,
// comuni a evento.php (nuova prenotazione) e gestisci-evento.php (modifica).

const MAX_POSTI_EVENTO = 10;   // per prenotazione, come il CHECK della tabella

// Evento con i posti già prenotati; $escludi: la prenotazione che si sta modificando non conta
function evento_con_posti(int $id, ?int $escludi = null, bool $blocca = false): ?array
{
    $e = query_riga('SELECT e.*, m.titolo AS mostra, m.slug FROM eventi e LEFT JOIN mostre m ON m.id = e.mostra_id WHERE e.id = :id'
        . ($blocca ? ' FOR UPDATE OF e' : ''), ['id' => $id]);
    if (!$e) {
        return null;
    }
    $e['prenotati'] = (int) query_riga('SELECT COALESCE(SUM(posti), 0) AS n FROM prenotazioni_eventi WHERE evento_id = :id AND id <> :x',
        ['id' => $id, 'x' => $escludi ?? 0])['n'];
    $e['rimasti'] = max(0, (int) $e['posti'] - $e['prenotati']);
    $e['passato'] = strtotime($e['inizio']) < time();
    return $e;
}

// Quanti posti si possono chiedere in una prenotazione
function posti_prenotabili(array $evento): int
{
    return min(MAX_POSTI_EVENTO, $evento['rimasti']);
}

/**
 * Legge e controlla i campi; restituisce [valori, errori].
 * @param bool $conEmail false in modifica: l'email non si cambia
 */
function evento_leggi(array $post, array $evento, bool $conEmail = true): array
{
    $v = [
        'nome'  => trim((string) ($post['nome'] ?? '')),
        'email' => trim((string) ($post['email'] ?? '')),
        'posti' => trim((string) ($post['posti'] ?? '')),
    ];
    $e = [];
    $max = posti_prenotabili($evento);
    if (filter_var($v['posti'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => MAX_POSTI_EVENTO]]) === false) {
        $e['posti'] = 'Scegli quanti posti, da 1 a ' . MAX_POSTI_EVENTO;
    } elseif ((int) $v['posti'] > $max) {
        $e['posti'] = $max === 0 ? 'I posti sono finiti' : 'Restano solo ' . $max . ($max === 1 ? ' posto' : ' posti');
    }
    if ($v['nome'] === '' || mb_strlen($v['nome']) > 120) {
        $e['nome'] = 'Inserisci il tuo nome e cognome (al massimo 120 caratteri)';
    }
    if ($conEmail && (!email_valida($v['email']) || mb_strlen($v['email']) > 200)) {
        $e['email'] = 'Inserisci un indirizzo email valido, per esempio nome@esempio.it';
    }
    return [$v, $e];
}

// Campi del modulo (sintassi XML: tag vuoti chiusi, attributi con valore)
function evento_campi(array $v, array $errori, array $evento, bool $conEmail = true): void
{
    $max = max(1, posti_prenotabili($evento));
    ?>
    <div class="campo">
        <label for="posti">Posti <span class="obbligatorio" aria-hidden="true">*</span></label>
        <p class="suggerimento" id="posti-aiuto">Da 1 a <?= $max ?><?= (float) $evento['prezzo'] > 0 ? ', ' . e(prezzo($evento['prezzo'])) . ' a persona, da pagare all\'ingresso' : ', ingresso gratuito' ?>.</p>
        <input type="number" id="posti" name="posti" min="1" max="<?= $max ?>" value="<?= e($v['posti']) ?>"
               required="required" aria-required="true" data-msg-vuoto="Scegli quanti posti"
               data-msg-formato="Scegli un numero di posti da 1 a <?= $max ?>"<?= attr_campo('posti', $errori, 'posti-aiuto') ?> />
        <?= errore_campo('posti', $errori) ?>
    </div>
    <div class="campo">
        <label for="nome">Nome e cognome <span class="obbligatorio" aria-hidden="true">*</span></label>
        <input type="text" id="nome" name="nome" autocomplete="name" maxlength="120" value="<?= e($v['nome']) ?>"
               required="required" aria-required="true" data-msg-vuoto="Inserisci il tuo nome e cognome"<?= attr_campo('nome', $errori) ?> />
        <?= errore_campo('nome', $errori) ?>
    </div>
    <?php if ($conEmail): ?>
        <div class="campo">
            <label for="email">Email <span class="obbligatorio" aria-hidden="true">*</span></label>
            <p class="suggerimento" id="email-aiuto">Con l'email e il codice della conferma potrai modificare o cancellare la prenotazione.</p>
            <input type="email" id="email" name="email" autocomplete="email" spellcheck="false" maxlength="200" value="<?= e($v['email']) ?>"
                   required="required" aria-required="true" data-msg-vuoto="Inserisci la tua email"<?= attr_campo('email', $errori, 'email-aiuto') ?> />
            <?= errore_campo('email', $errori) ?>
        </div>
    <?php endif;
}

// "sabato 7 novembre 2026, ore 18:00 (90 minuti), Auditorium"
function evento_quando(array $evento): string
{
    return data_estesa($evento['inizio'], true) . ', ore ' . ora($evento['inizio']) . ' (' . (int) $evento['durata_min'] . ' minuti), ' . $evento['luogo'];
}
