@php
    $operator = config('kith.operator');
    $matomoHost = filled(config('kith.matomo.url')) && filled(config('kith.matomo.site_id')) ? parse_url(config('kith.matomo.url'), PHP_URL_HOST) : null;
@endphp

<h2>Controller</h2>
<p>The controller responsible for data processing on this website within the meaning of the General Data Protection Regulation (GDPR) is:</p>
<x-operator-address />
@if (filled($operator['email']))
    <p>Email: <a href="mailto:{{ $operator['email'] }}">{{ $operator['email'] }}</a></p>
@endif

<h2>The essentials</h2>
<ul>
    <li>kith is free and contains no ads.</li>
    <li>We do not share data for advertising and do not embed third-party trackers.</li>
    <li>Fonts and all other files are served from our own server – visiting the site does not connect you to any third party.</li>
    @if ($matomoHost)
        <li>We only collect visitor statistics (Matomo, on our own server) with your consent.</li>
        <li>Without your consent, we only set technically necessary cookies.</li>
    @else
        <li>We only use technically necessary cookies.</li>
    @endif
</ul>

<h2>Hosting and server log files</h2>
<p>When you open this website, the web server automatically processes information sent by your browser: IP address, date and time of access, the page requested, referrer URL, browser and operating system. This data is technically required to deliver the website and keep it secure (Art. 6(1)(f) GDPR). It is not combined with other data and is deleted after a short period unless it is needed to investigate a security incident.</p>

<h2>Cookies</h2>
<p>Without your consent, kith only uses cookies that are required to operate the website (Section 25(2) No. 2 TDDDG, Art. 6(1)(f) GDPR):</p>
<ul>
    <li><strong>Session cookie</strong> – keeps your session, sign-in and language preference. It is deleted when the session ends.</li>
    <li><strong>XSRF-TOKEN</strong> – protects forms against cross-site request forgery. It is deleted when the session ends.</li>
    <li><strong>kith_consent</strong> – stores your choice in the cookie settings for one year.</li>
</ul>
<p>You can change your choice at any time in the <button type="button" data-consent-settings class="cursor-pointer text-kith-700 underline decoration-kith-200 underline-offset-2 hover:text-kith-900">cookie settings</button>.</p>

@if ($matomoHost)
    <h2>Visitor statistics with Matomo</h2>
    <p>With your consent, we use the open-source software Matomo to analyse how our website is used. Matomo runs on our own server ({{ $matomoHost }}); the data never leaves this server and is not shared with third parties.</p>
    <p>Matomo processes: your IP address in shortened form (the last two bytes are removed), the pages you view and how long you stay, the page you came from, browser, operating system, device type, screen resolution and language, and external links you click. For this, Matomo sets the cookies <strong>_pk_id</strong> (recognises returning visits, 13 months) and <strong>_pk_ses</strong> (current visit, 30 minutes).</p>
    <p>The legal basis is your consent (Art. 6(1)(a) GDPR, Section 25(1) TDDDG). Without consent, Matomo is not loaded. You can withdraw your consent at any time in the <button type="button" data-consent-settings class="cursor-pointer text-kith-700 underline decoration-kith-200 underline-offset-2 hover:text-kith-900">cookie settings</button>; the Matomo cookies are then deleted. Raw data is deleted after 12 months at the latest.</p>
@endif

<h2>Accounts for breeders</h2>
<p>If you use kith for your breeding, we process the data required for your account (name, email address and a password that is only stored as a secure hash) and the content you enter yourself – such as animals, litters, colors, notes and photos. The legal basis is the provision of the service (Art. 6(1)(b) GDPR). The data is kept as long as your account exists and deleted afterwards.</p>

<h2>Public breeder pages</h2>
<p>Breeders can show their pedigree publicly. Only information intended for the public view appears there, for example names, colors, dates of birth and photos of the animals. Internal notes are never published.</p>

<h2>Donations via Ko-fi</h2>
<p>We link to Ko-fi, where you can make a voluntary donation. Ko-fi is only loaded once you click the link. Ko-fi Labs Ltd. is responsible for the data processing there; their <a href="https://more.ko-fi.com/privacy" target="_blank" rel="noopener">privacy policy</a> applies. We only receive the donation details you choose to share on Ko-fi (such as name, amount and message).</p>

<h2>Your rights</h2>
<p>You have the right of access (Art. 15 GDPR), rectification (Art. 16), erasure (Art. 17), restriction of processing (Art. 18), data portability (Art. 20) and to object to processing based on legitimate interests (Art. 21). You can withdraw any consent at any time with effect for the future. You may also lodge a complaint with a data protection supervisory authority (Art. 77 GDPR).</p>
<p>For any privacy request, an informal message to the address above is enough.</p>

<p class="mt-12 text-sm">Last updated: November 2026</p>
