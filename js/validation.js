// Validazione dei form marcati con data-valida.
// Gli stessi controlli sono ripetuti lato server in PHP: questo serve solo a dare
// un riscontro immediato. I messaggi compaiono nel paragrafo #<id>-errore del campo.

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-valida]').forEach((form) => {
        form.addEventListener('submit', (ev) => {
            const errati = valida(form);
            if (errati.length) {
                ev.preventDefault();
                errati[0].focus();
            }
        });

        // Un campo già segnalato viene ricontrollato appena l'utente lo corregge
        form.addEventListener('change', (ev) => {
            const campo = ev.target;
            if (campo.getAttribute('aria-invalid') === 'true') {
                mostra(campo, controlla(campo));
            }
        });
    });
});

function valida(form) {
    const errati = [];
    const visti = new Set();

    form.querySelectorAll('input, select, textarea').forEach((campo) => {
        if (campo.type === 'hidden' || campo.type === 'submit') return;
        if (campo.type === 'radio') {
            if (visti.has(campo.name)) return;
            visti.add(campo.name);
        }
        const msg = controlla(campo);
        mostra(campo, msg);
        if (msg) errati.push(campo);
    });

    // Gruppi che richiedono almeno una quantità maggiore di zero (es. biglietti)
    form.querySelectorAll('fieldset[data-almeno-uno]').forEach((gruppo) => {
        const somma = [...gruppo.querySelectorAll('input[type="number"]')]
            .reduce((s, c) => s + (parseInt(c.value, 10) || 0), 0);
        const msg = somma > 0 ? '' : gruppo.dataset.almenoUno;
        const primo = gruppo.querySelector('input[type="number"]');
        scriviErrore(document.getElementById(gruppo.id + '-errore'), msg);
        if (msg && primo && !errati.includes(primo)) errati.push(primo);
    });

    return errati;
}

function controlla(campo) {
    const valore = campo.value.trim();
    const d = campo.dataset;

    if (campo.type === 'radio') {
        const scelto = campo.form.querySelector(`input[name="${CSS.escape(campo.name)}"]:checked`);
        return campo.required && !scelto ? (d.msgVuoto || 'Scegli un\'opzione') : '';
    }
    if (campo.required && valore === '') {
        return d.msgVuoto || `Compila il campo ${etichetta(campo)}`;
    }
    if (valore === '') return '';

    if (campo.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valore)) {
        return d.msgFormato || 'Inserisci un indirizzo email valido, per esempio nome@esempio.it';
    }
    if (campo.type === 'date' && campo.min && valore < campo.min) {
        return d.msgFormato || 'Scegli una data a partire da oggi';
    }
    if (campo.type === 'number') {
        const n = Number(valore);
        if (!Number.isInteger(n) || (campo.min !== '' && n < Number(campo.min)) || (campo.max !== '' && n > Number(campo.max))) {
            return d.msgFormato || `Inserisci un numero intero tra ${campo.min} e ${campo.max}`;
        }
    }
    return '';
}

function mostra(campo, msg) {
    const id = campo.type === 'radio' ? campo.name : campo.id;
    const errore = document.getElementById(id + '-errore');
    const destinatari = campo.type === 'radio'
        ? campo.form.querySelectorAll(`input[name="${CSS.escape(campo.name)}"]`)
        : [campo];

    destinatari.forEach((c) => {
        if (msg) {
            c.setAttribute('aria-invalid', 'true');
            if (errore) collega(c, errore.id);
        } else {
            c.removeAttribute('aria-invalid');
        }
    });
    scriviErrore(errore, msg);
}

function scriviErrore(el, msg) {
    if (!el) return;
    el.textContent = msg;
    el.hidden = !msg;
}

function collega(campo, idErrore) {
    const ids = (campo.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
    if (!ids.includes(idErrore)) {
        ids.push(idErrore);
        campo.setAttribute('aria-describedby', ids.join(' '));
    }
}

function etichetta(campo) {
    const label = campo.labels && campo.labels[0];
    const testo = label ? label.textContent.replace('*', '').replace('(obbligatorio)', '').trim() : campo.name;
    return `“${testo}”`;
}
