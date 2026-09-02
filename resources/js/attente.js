/*
 * Dire que quelque chose se passe.
 *
 * L'application est rendue par le serveur : entre le clic et la page suivante,
 * il ne se passe rien à l'écran. Sur une connexion mobile camerounaise, cet
 * intervalle dure. L'utilisateur ne distingue pas « lent » de « cassé », alors
 * il reclique — et sa demande part deux fois.
 *
 * Deux retours, et rien de plus :
 *
 *   1. Un trait de progression en haut de la fenêtre dès qu'une navigation
 *      commence, effacé quand la page suivante prend la main.
 *   2. Le bouton qui vient d'être actionné passe en attente, et le formulaire
 *      refuse un second envoi.
 *
 * ⚠️ **Le bouton n'est jamais désactivé.** Un `disabled` posé avant que le
 * navigateur n'ait sérialisé le formulaire retire le `name`/`value` du bouton
 * de l'envoi. Trois boutons du dépôt en portent un — dont celui qui rejette un
 * litige, `name="status" value="rejected"`. Les désactiver ferait trancher le
 * litige sans statut. L'attente est donc un attribut et une classe ; le second
 * envoi est refusé par un drapeau sur le formulaire.
 */

const RALENTI = window.matchMedia('(prefers-reduced-motion: reduce)');

let trait = null;
let minuterie = null;

function poserTrait() {
    if (trait) return;

    trait = document.createElement('div');
    trait.className = 'trait-attente';
    trait.setAttribute('aria-hidden', 'true');
    document.body.appendChild(trait);

    // Deux images pour que la transition parte de zéro plutôt que d'être
    // fusionnée avec la pose de l'élément.
    requestAnimationFrame(() => requestAnimationFrame(() => {
        if (trait) trait.classList.add('trait-attente--court');
    }));
}

function retirerTrait() {
    clearTimeout(minuterie);
    minuterie = null;
    trait?.remove();
    trait = null;
}

/*
 * Le trait n'apparaît qu'au bout d'un moment : sur une réponse immédiate, un
 * éclair en haut de l'écran est un défaut, pas une information.
 */
function annoncerNavigation() {
    if (minuterie || trait) return;
    minuterie = setTimeout(poserTrait, 180);
}

function estUnLienDeNavigation(a, e) {
    if (!a || e.defaultPrevented || e.button !== 0) return false;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return false;
    if (a.target && a.target !== '_self') return false;
    if (a.hasAttribute('download')) return false;

    // Un lien piloté par Alpine ou par un `onclick` ne navigue pas forcément.
    if (a.hasAttribute('@click') || a.hasAttribute('x-on:click') || a.hasAttribute('onclick')) return false;

    const href = a.getAttribute('href');
    if (!href || href.startsWith('#') || /^(javascript|mailto|tel|sms):/i.test(href)) return false;

    const url = new URL(a.href, location.href);
    if (url.origin !== location.origin) return false;

    // Une ancre sur la même page ne recharge rien.
    return url.pathname !== location.pathname || url.search !== location.search;
}

document.addEventListener('click', (e) => {
    const a = e.target.closest?.('a[href]');
    if (estUnLienDeNavigation(a, e)) annoncerNavigation();
}, true);

document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;

    // Un formulaire intercepté par Alpine — l'assistant — ne quitte pas la
    // page : il porte son propre état d'attente.
    if (form.hasAttribute('@submit.prevent') || form.hasAttribute('x-on:submit.prevent')) return;

    if (form.dataset.envoiEnCours === '1') {
        e.preventDefault();
        return;
    }

    if (e.defaultPrevented) return;

    form.dataset.envoiEnCours = '1';
    annoncerNavigation();

    // `submitter` est le bouton réellement actionné, y compris quand le
    // formulaire en porte plusieurs.
    const bouton = e.submitter ?? form.querySelector('button[type="submit"], button:not([type])');

    if (bouton) {
        bouton.dataset.attente = '1';
        bouton.setAttribute('aria-busy', 'true');
    }
});

/*
 * Le retour arrière restaure la page depuis le cache du navigateur, avec le
 * trait et le bouton figés dans leur état d'attente. Sans cette remise à zéro,
 * un formulaire revu par retour arrière refuse d'être renvoyé.
 */
window.addEventListener('pageshow', () => {
    retirerTrait();
    document.querySelectorAll('[data-envoi-en-cours]').forEach((f) => delete f.dataset.envoiEnCours);
    document.querySelectorAll('[data-attente]').forEach((b) => {
        delete b.dataset.attente;
        b.removeAttribute('aria-busy');
    });
});

window.addEventListener('pagehide', retirerTrait);

// Une navigation abandonnée — l'utilisateur revient sur la page — ne doit pas
// laisser le trait courir indéfiniment.
window.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') retirerTrait();
});

export { RALENTI };
