<?php
// Richieste di tessera: livelli, controlli e campi del modulo.
// Usato da support.php (nuova richiesta) e da gestisci-tessera.php (modifica e annullamento).

/** @return array{0: array, 1: array} livelli in ordine e vantaggi per id del livello */
function livelli_tessera(): array
{
    $livelli = query('SELECT id, nome, prezzo FROM livelli_membership ORDER BY ordine');
    // un vantaggio per riga, dalla loro tabella
    $vantaggi = [];
    foreach (query('SELECT livello_id, testo FROM vantaggi_membership ORDER BY livello_id, ordine') as $r) {
        $vantaggi[$r['livello_id']][] = $r['testo'];
    }
    return [$livelli, $vantaggi];
}

/**
 * Legge e controlla i campi; gli stessi controlli sono fatti lato client da js/validation.js.
 * @param bool $conEmail false in modifica: l'email, con il codice, identifica la richiesta e non si cambia
 * @return array{0: array, 1: array} valori letti ed errori per campo
 */
function tessera_leggi(array $post, array $livelli, bool $conEmail = true): array
{
    $errori = [];
    $v = [
        'livello' => (string) ($post['livello'] ?? ''),
        'nome' => trim((string) ($post['nome'] ?? '')),
        'email' => trim((string) ($post['email'] ?? '')),
    ];
    if (!csrf_valido()) {
        $errori['nome'] = 'La sessione è scaduta: ricarica la pagina e invia di nuovo il modulo';
    }
    if (!in_array((int) $v['livello'], array_map('intval', array_column($livelli, 'id')), true)) {
        $errori['livello'] = 'Scegli il tipo di tessera';
    }
    if ($v['nome'] === '') {
        $errori['nome'] ??= 'Inserisci il tuo nome e cognome';
    } elseif (mb_strlen($v['nome']) > 120) {
        $errori['nome'] ??= 'Il nome può avere al massimo 120 caratteri';
    }
    if ($conEmail && !email_valida($v['email'])) {
        $errori['email'] = 'Inserisci un indirizzo email valido, per esempio nome@esempio.it';
    }
    return [$v, $errori];
}

function nome_livello(array $livelli, int $id): string
{
    return array_column($livelli, 'nome', 'id')[$id] ?? '';
}

/** Campi del modulo: tipo di tessera, nome, email (solo se $conEmail). Sintassi XML. */
function tessera_campi(array $v, array $errori, array $livelli, array $vantaggi, bool $conEmail = true): void
{
    ?>
            <fieldset<?= isset($errori['livello']) ? ' aria-describedby="livello-errore"' : '' ?>>
                <legend>Tipo di tessera <span class="obbligatorio" aria-hidden="true">*</span></legend>
                <div class="scelte">
                    <?php foreach ($livelli as $i => $l): $id = 'livello-' . $l['id']; ?>
                        <div class="scelta">
                            <input type="radio" id="<?= $i === 0 ? 'livello' : e($id) ?>" name="livello" value="<?= (int) $l['id'] ?>" required="required"
                                   data-msg-vuoto="Scegli il tipo di tessera" aria-describedby="<?= e($id) ?>-vantaggi"
                                   <?= (string) $l['id'] === (string) $v['livello'] ? ' checked="checked"' : '' ?><?= isset($errori['livello']) ? ' aria-invalid="true"' : '' ?> />
                            <div>
                                <label for="<?= $i === 0 ? 'livello' : e($id) ?>"><?= e($l['nome']) ?>
                                    <span class="prezzo-livello"><?= e(prezzo($l['prezzo'])) ?> all'anno</span></label>
                                <ul id="<?= e($id) ?>-vantaggi">
                                    <?php foreach ($vantaggi[$l['id']] ?? [] as $vantaggio): ?>
                                        <li><?= e($vantaggio) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?= errore_campo('livello', $errori) ?>
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
                <p class="suggerimento" id="email-aiuto">Serve, con il codice, per modificare o annullare la richiesta.</p>
                <input type="email" spellcheck="false" id="email" name="email" autocomplete="email" value="<?= e($v['email']) ?>" required="required" aria-required="true"
                       data-msg-vuoto="Inserisci il tuo indirizzo email"<?= attr_campo('email', $errori, 'email-aiuto') ?> />
                <?= errore_campo('email', $errori) ?>
            </div>
            <?php endif; ?>
    <?php
}
