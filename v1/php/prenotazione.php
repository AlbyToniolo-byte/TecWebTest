<?php
// Prenotazioni della visita: controlli, salvataggio e campi del modulo.
// Usato da visit.php (nuova prenotazione) e da gestisci.php (modifica e cancellazione).

const FASCE = ['mattina' => 'Mattina, 9:30–12:30', 'pomeriggio' => 'Pomeriggio, 12:30–16:00', 'tardi' => 'Dalle 16:00 alla chiusura'];
const MAX_PER_TIPO = 10;
const MAX_NOTE = 500;

function tipi_biglietto(): array
{
    return query('SELECT id, nome, descrizione, prezzo FROM tipi_biglietto ORDER BY ordine');
}

// Giorni di chiusura (1 = lunedì ... 7 = domenica), dalla tabella orari
function giorni_chiusi(): array
{
    return array_map('intval', array_column(query('SELECT giorno FROM orari WHERE apertura IS NULL'), 'giorno'));
}

/**
 * Legge e controlla i campi del modulo. Gli stessi controlli sono fatti lato client da js/validation.js.
 * @param bool $conEmail false in modifica: l'email identifica la prenotazione e non si cambia
 * @return array{0: array, 1: array} valori letti ed errori per campo
 */
function prenotazione_leggi(array $post, array $tipi, bool $conEmail = true): array
{
    $errori = [];
    $v = [
        'data' => trim((string) ($post['data'] ?? '')),
        'fascia' => (string) ($post['fascia'] ?? ''),
        'nome' => trim((string) ($post['nome'] ?? '')),
        'email' => trim((string) ($post['email'] ?? '')),
        // testo libero: tolti i caratteri di controllo, tenuti gli a capo
        'note' => trim(preg_replace('/[^\P{C}\n]/u', '', str_replace("\r\n", "\n", (string) ($post['note'] ?? ''))) ?? ''),
        'q' => [],
    ];

    if (!csrf_valido()) {
        $errori['nome'] = 'La sessione è scaduta: ricarica la pagina e invia di nuovo il modulo';
    }

    $d = DateTime::createFromFormat('!Y-m-d', $v['data']);
    if (!$d || $d->format('Y-m-d') !== $v['data']) {
        $errori['data'] = 'Scegli la data della visita';
    } elseif ($v['data'] < date('Y-m-d')) {
        $errori['data'] = 'Scegli una data a partire da oggi';
    } elseif (in_array((int) $d->format('N'), giorni_chiusi(), true)) {
        $errori['data'] = 'Il ' . GIORNI[(int) $d->format('N')] . ' il museo è chiuso: scegli un altro giorno';
    }
    if (!isset(FASCE[$v['fascia']])) {
        $errori['fascia'] = 'Scegli la fascia oraria di ingresso';
    }

    $pezzi = 0;
    foreach ($tipi as $t) {
        $q = filter_var($post['q'][$t['id']] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => MAX_PER_TIPO]]);
        if ($q === false) {
            $errori['q' . $t['id']] = 'Biglietti ' . $t['nome'] . ': inserisci un numero tra 0 e ' . MAX_PER_TIPO;
            $q = 0;
        }
        $v['q'][$t['id']] = $q;
        $pezzi += $q;
    }
    if ($pezzi === 0 && !array_filter(array_keys($errori), fn($k) => str_starts_with($k, 'q'))) {
        $errori['q' . $tipi[0]['id']] = 'Scegli almeno un biglietto';
    }

    if ($v['nome'] === '') {
        $errori['nome'] ??= 'Inserisci il tuo nome e cognome';
    } elseif (mb_strlen($v['nome']) > 120) {
        $errori['nome'] ??= 'Il nome può avere al massimo 120 caratteri';
    }
    if ($conEmail && !email_valida($v['email'])) {
        $errori['email'] = 'Inserisci un indirizzo email valido, per esempio nome@esempio.it';
    }
    if (mb_strlen($v['note']) > MAX_NOTE) {
        $errori['note'] = 'Le note possono avere al massimo ' . MAX_NOTE . ' caratteri: ne hai scritti ' . mb_strlen($v['note']);
    }

    return [$v, $errori];
}

/**
 * Salva una prenotazione nuova ($id null) o aggiorna quella esistente, in un'unica transazione.
 * Le righe dei biglietti si riscrivono da capo con i prezzi di oggi. Restituisce il codice.
 */
function prenotazione_salva(array $v, array $tipi, ?int $id = null): string
{
    $pdo = db();
    $pdo->beginTransaction();
    $note = $v['note'] === '' ? null : $v['note'];
    if ($id === null) {
        $codice = codice_casuale();
        $st = $pdo->prepare('INSERT INTO prenotazioni (codice, nome, email, data_visita, fascia, note)
                             VALUES (:c, :n, :e, :d, :f, :o) RETURNING id');
        $st->execute(['c' => $codice, 'n' => $v['nome'], 'e' => $v['email'], 'd' => $v['data'], 'f' => $v['fascia'], 'o' => $note]);
        $id = (int) $st->fetchColumn();
    } else {
        $st = $pdo->prepare('UPDATE prenotazioni SET nome = :n, data_visita = :d, fascia = :f, note = :o
                             WHERE id = :id RETURNING codice');
        $st->execute(['n' => $v['nome'], 'd' => $v['data'], 'f' => $v['fascia'], 'o' => $note, 'id' => $id]);
        $codice = (string) $st->fetchColumn();
        $pdo->prepare('DELETE FROM prenotazioni_righe WHERE prenotazione_id = :id')->execute(['id' => $id]);
    }
    $riga = $pdo->prepare('INSERT INTO prenotazioni_righe VALUES (:p, :t, :q, :u)');
    foreach ($tipi as $t) {
        if ($v['q'][$t['id']] > 0) {
            $riga->execute(['p' => $id, 't' => $t['id'], 'q' => $v['q'][$t['id']], 'u' => $t['prezzo']]);
        }
    }
    $pdo->commit();
    return $codice;
}

// Il totale non è salvato: si calcola dalle righe
function prenotazione_totale(int $id): float
{
    return (float) query_riga('SELECT COALESCE(SUM(quantita * prezzo_unitario), 0) AS t FROM prenotazioni_righe WHERE prenotazione_id = :id',
        ['id' => $id])['t'];
}

/**
 * Campi del modulo: data, fascia, biglietti, nome, email (solo se $conEmail) e note.
 * Attributi in sintassi XML (tag vuoti chiusi, attributi con valore).
 */
function prenotazione_campi(array $v, array $errori, array $tipi, bool $conEmail = true): void
{
    ?>
            <p class="suggerimento">I campi con <span aria-hidden="true">*</span><span class="visually-hidden">asterisco</span> sono obbligatori.</p>

            <div class="campo">
                <label for="data">Data della visita <span class="obbligatorio" aria-hidden="true">*</span></label>
                <p class="suggerimento" id="data-aiuto">Il museo è chiuso il lunedì.</p>
                <input type="date" id="data" name="data" min="<?= e(date('Y-m-d')) ?>" value="<?= e($v['data']) ?>" required="required" aria-required="true"
                       data-msg-vuoto="Scegli la data della visita"<?= attr_campo('data', $errori, 'data-aiuto') ?> />
                <?= errore_campo('data', $errori) ?>
            </div>

            <div class="campo">
                <label for="fascia">Fascia oraria di ingresso <span class="obbligatorio" aria-hidden="true">*</span></label>
                <select id="fascia" name="fascia" required="required" aria-required="true" data-msg-vuoto="Scegli la fascia oraria di ingresso"<?= attr_campo('fascia', $errori) ?>>
                    <option value="">Scegli una fascia</option>
                    <?php foreach (FASCE as $k => $etichetta): ?>
                        <option value="<?= e($k) ?>"<?= $k === $v['fascia'] ? ' selected="selected"' : '' ?>><?= e($etichetta) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= errore_campo('fascia', $errori) ?>
            </div>

            <fieldset id="biglietti" data-almeno-uno="Scegli almeno un biglietto">
                <legend>Biglietti</legend>
                <div class="quantita-biglietti">
                    <?php foreach ($tipi as $t): $id = 'q' . $t['id']; ?>
                        <div class="riga">
                            <div>
                                <label for="<?= e($id) ?>"><?= e($t['nome']) ?>, <?= e(prezzo($t['prezzo'])) ?></label>
                                <p class="suggerimento" id="<?= e($id) ?>-aiuto"><?= e($t['descrizione']) ?></p>
                            </div>
                            <input type="number" id="<?= e($id) ?>" name="q[<?= (int) $t['id'] ?>]" min="0" max="<?= MAX_PER_TIPO ?>" step="1" inputmode="numeric"
                                   value="<?= (int) ($v['q'][$t['id']] ?? 0) ?>" data-prezzo="<?= e($t['prezzo']) ?>"<?= attr_campo($id, $errori, $id . '-aiuto') ?> />
                            <?= errore_campo($id, $errori) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="errore" id="biglietti-errore" hidden="hidden"></p>
                <p class="totale" data-totale="" aria-live="polite"></p>
            </fieldset>

            <div class="campo">
                <label for="nome">Nome e cognome <span class="obbligatorio" aria-hidden="true">*</span></label>
                <input type="text" id="nome" name="nome" autocomplete="name" maxlength="120" value="<?= e($v['nome']) ?>" required="required" aria-required="true"
                       data-msg-vuoto="Inserisci il tuo nome e cognome"<?= attr_campo('nome', $errori) ?> />
                <?= errore_campo('nome', $errori) ?>
            </div>

            <?php if ($conEmail): ?>
            <div class="campo">
                <label for="email">Email <span class="obbligatorio" aria-hidden="true">*</span></label>
                <p class="suggerimento" id="email-aiuto">Serve, con il codice, per modificare o cancellare la prenotazione.</p>
                <input type="email" spellcheck="false" id="email" name="email" autocomplete="email" value="<?= e($v['email']) ?>" required="required" aria-required="true"
                       data-msg-vuoto="Inserisci il tuo indirizzo email"<?= attr_campo('email', $errori, 'email-aiuto') ?> />
                <?= errore_campo('email', $errori) ?>
            </div>
            <?php endif; ?>

            <div class="campo">
                <label for="note">Note per la visita</label>
                <p class="suggerimento" id="note-aiuto">Facoltativo, al massimo <?= MAX_NOTE ?> caratteri. Per esempio: arriviamo con una carrozzina, ci serve una sedia pieghevole.</p>
                <textarea id="note" name="note" rows="4" cols="40" maxlength="<?= MAX_NOTE ?>"
                          data-msg-lunghezza="Le note possono avere al massimo <?= MAX_NOTE ?> caratteri"<?= attr_campo('note', $errori, 'note-aiuto') ?>><?= e($v['note']) ?></textarea>
                <?= errore_campo('note', $errori) ?>
            </div>
    <?php
}
