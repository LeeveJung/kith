/**
 * Cookie consent with Klaro.
 *
 * Optional services (currently Matomo) are only offered when their script is on the page. Their scripts are
 * embedded as <script type="text/plain" data-name="…" data-type="text/javascript"> and only run after consent.
 * Without optional services the notice stays hidden and the modal is only opened through the "Cookie settings"
 * link in the footer. Klaro itself is only downloaded when it is needed.
 */

const translations = {
    de: {
        essential: {
            title: 'Technisch notwendige Cookies',
            description: 'Sitzung, Anmeldung, Spracheinstellung und Schutz vor Formular-Missbrauch (CSRF).',
        },
        matomo: {
            title: 'Matomo',
            description: 'Besuchsstatistik auf unserem eigenen Server, mit gekürzter IP-Adresse. Es werden keine Daten an Dritte weitergegeben.',
        },
        purposes: {
            analytics: {
                title: 'Statistik',
                description: 'Hilft uns zu verstehen, welche Seiten genutzt werden, damit wir kith verbessern können.',
            },
        },
    },
    en: {
        essential: {
            title: 'Technically necessary cookies',
            description: 'Session, sign-in, language preference and protection against form abuse (CSRF).',
        },
        matomo: {
            title: 'Matomo',
            description: 'Visitor statistics on our own server, with a shortened IP address. No data is shared with third parties.',
        },
        purposes: {
            analytics: {
                title: 'Statistics',
                description: 'Helps us understand which pages are used so we can improve kith.',
            },
        },
    },
};

/** @type {Array<{name: string, purposes: string[], required?: boolean, default?: boolean, cookies?: Array<string|RegExp>, onDecline?: Function}>} */
const services = [
    {
        name: 'essential',
        purposes: ['functional'],
        required: true,
        cookies: [/_session$/, 'XSRF-TOKEN'],
    },
];

if (document.querySelector('script[data-name="matomo"]')) {
    services.push({
        name: 'matomo',
        purposes: ['analytics'],
        default: false,
        cookies: [/^_pk_/, /^mtm_/],
        // A tracker that is already running cannot be unloaded, so the page is reloaded without it.
        onDecline: () => {
            if (window.Matomo !== undefined) {
                window.location.reload();
            }
        },
    });
}

function buildConfig() {
    const lang = document.documentElement.lang.slice(0, 2) === 'de' ? 'de' : 'en';
    const privacyPolicyUrl = document.querySelector('[data-privacy-url]')?.dataset.privacyUrl ?? '/privacy';

    return {
        version: 1,
        elementID: 'klaro',
        storageMethod: 'cookie',
        storageName: 'kith_consent',
        cookieExpiresAfterDays: 365,
        lang,
        htmlTexts: false,
        groupByPurpose: true,
        acceptAll: true,
        hideDeclineAll: false,
        hideLearnMore: false,
        noNotice: !services.some((service) => !service.required),
        styling: {
            theme: ['light', 'bottom', 'right'],
            green1: '#173b2b',
            green2: '#3f6650',
            green3: '#a9c2ae',
            blue1: '#3f6650',
            blue2: '#2b5040',
            'font-family': "'Hanken Grotesk', ui-sans-serif, system-ui, sans-serif",
            'title-font-family': "'Instrument Serif', ui-serif, Georgia, serif",
            'border-radius': '1rem',
        },
        translations: {
            zz: { privacyPolicyUrl },
            de: { privacyPolicyUrl, ...translations.de },
            en: { privacyPolicyUrl, ...translations.en },
        },
        services,
    };
}

const config = buildConfig();
let klaro = null;

function loadKlaro() {
    klaro ??= Promise.all([import('klaro/dist/klaro-no-css'), import('klaro/dist/klaro.min.css')]).then(([module]) => {
        const Klaro = module.default ?? module;
        Klaro.setup(config);

        return Klaro;
    });

    return klaro;
}

if (!config.noNotice) {
    loadKlaro();
}

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-consent-settings]')) {
        event.preventDefault();
        loadKlaro().then((Klaro) => Klaro.show(config, true));
    }
});
