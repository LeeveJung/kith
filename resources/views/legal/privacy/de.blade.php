@php
    $operator = config('kith.operator');
    $matomoHost = filled(config('kith.matomo.url')) && filled(config('kith.matomo.site_id')) ? parse_url(config('kith.matomo.url'), PHP_URL_HOST) : null;
@endphp

<h2>Verantwortlicher</h2>
<p>Verantwortlich für die Datenverarbeitung auf dieser Website im Sinne der Datenschutz-Grundverordnung (DSGVO) ist:</p>
<x-operator-address />
@if (filled($operator['email']))
    <p>E-Mail: <a href="mailto:{{ $operator['email'] }}">{{ $operator['email'] }}</a></p>
@endif

<h2>Das Wichtigste in Kürze</h2>
<ul>
    <li>kith ist kostenlos und werbefrei.</li>
    <li>Wir geben keine Daten zu Werbezwecken weiter und binden keine Tracker von Drittanbietern ein.</li>
    <li>Schriftarten und alle übrigen Dateien werden von unserem eigenen Server geladen – beim Besuch der Seite werden keine Verbindungen zu Dritten aufgebaut.</li>
    @if ($matomoHost)
        <li>Eine Besuchsstatistik (Matomo, auf unserem eigenen Server) erstellen wir nur mit Ihrer Einwilligung.</li>
        <li>Ohne Ihre Einwilligung setzen wir nur technisch notwendige Cookies.</li>
    @else
        <li>Wir verwenden nur technisch notwendige Cookies.</li>
    @endif
</ul>

<h2>Hosting und Server-Logfiles</h2>
<p>Beim Aufruf dieser Website verarbeitet der Webserver automatisch Informationen, die Ihr Browser übermittelt: IP-Adresse, Datum und Uhrzeit des Zugriffs, aufgerufene Seite, Referrer-URL sowie Browser und Betriebssystem. Diese Daten sind technisch erforderlich, um die Website auszuliefern und ihre Sicherheit zu gewährleisten (Art. 6 Abs. 1 lit. f DSGVO). Sie werden nicht mit anderen Daten zusammengeführt und nach kurzer Zeit gelöscht, soweit sie nicht zur Aufklärung eines Sicherheitsvorfalls benötigt werden.</p>

<h2>Cookies</h2>
<p>Ohne Ihre Einwilligung verwendet kith nur Cookies, die für den Betrieb der Website erforderlich sind (§ 25 Abs. 2 Nr. 2 TDDDG, Art. 6 Abs. 1 lit. f DSGVO):</p>
<ul>
    <li><strong>Sitzungs-Cookie</strong> – hält Ihre Sitzung, Ihre Anmeldung und Ihre Spracheinstellung fest. Er wird nach Ende der Sitzung gelöscht.</li>
    <li><strong>XSRF-TOKEN</strong> – schützt Formulare vor Missbrauch (Cross-Site Request Forgery). Er wird nach Ende der Sitzung gelöscht.</li>
    <li><strong>kith_consent</strong> – speichert Ihre Auswahl in den Cookie-Einstellungen für ein Jahr.</li>
</ul>
<p>Ihre Auswahl können Sie jederzeit über die <button type="button" data-consent-settings class="cursor-pointer text-kith-700 underline decoration-kith-200 underline-offset-2 hover:text-kith-900">Cookie-Einstellungen</button> ändern.</p>

@if ($matomoHost)
    <h2>Besuchsstatistik mit Matomo</h2>
    <p>Mit Ihrer Einwilligung werten wir mit der Open-Source-Software Matomo aus, wie unsere Website genutzt wird. Matomo läuft auf unserem eigenen Server ({{ $matomoHost }}); die Daten verlassen diesen Server nicht und werden nicht an Dritte weitergegeben.</p>
    <p>Verarbeitet werden: Ihre IP-Adresse in gekürzter Form (die letzten beiden Bytes werden entfernt), aufgerufene Seiten und Verweildauer, die Seite, von der Sie kommen, Browser, Betriebssystem, Gerätetyp, Bildschirmauflösung und Spracheinstellung sowie angeklickte externe Links. Matomo setzt dafür die Cookies <strong>_pk_id</strong> (erkennt wiederkehrende Besuche, 13 Monate) und <strong>_pk_ses</strong> (aktueller Besuch, 30 Minuten).</p>
    <p>Rechtsgrundlage ist Ihre Einwilligung (Art. 6 Abs. 1 lit. a DSGVO, § 25 Abs. 1 TDDDG). Ohne Einwilligung wird Matomo nicht geladen. Sie können Ihre Einwilligung jederzeit in den <button type="button" data-consent-settings class="cursor-pointer text-kith-700 underline decoration-kith-200 underline-offset-2 hover:text-kith-900">Cookie-Einstellungen</button> widerrufen; die Matomo-Cookies werden dann gelöscht. Die Rohdaten werden spätestens nach 12 Monaten gelöscht.</p>
@endif

<h2>Benutzerkonto für Züchterinnen und Züchter</h2>
<p>Wenn Sie kith für Ihre Zucht nutzen, verarbeiten wir die Daten, die für Ihr Konto erforderlich sind (Name, E-Mail-Adresse und Passwort, das nur als sicherer Hash gespeichert wird), sowie die Inhalte, die Sie selbst eingeben – etwa Tiere, Würfe, Farben, Notizen und Fotos. Rechtsgrundlage ist die Bereitstellung des Dienstes (Art. 6 Abs. 1 lit. b DSGVO). Die Daten werden gespeichert, solange Ihr Konto besteht, und anschließend gelöscht.</p>

<h2>Öffentliche Züchterseiten</h2>
<p>Züchterinnen und Züchter können ihren Stammbaum öffentlich zeigen. Dort erscheinen nur Angaben, die für die öffentliche Ansicht bestimmt sind, zum Beispiel Namen, Farben, Geburtsdaten und Fotos der Tiere. Interne Notizen werden nicht veröffentlicht.</p>

<h2>Spenden über Ko-fi</h2>
<p>Auf unseren Seiten verlinken wir auf Ko-fi, über das Sie freiwillig spenden können. Ko-fi wird erst geladen, wenn Sie den Link anklicken. Für die dortige Datenverarbeitung ist Ko-fi Labs Ltd. verantwortlich; es gilt deren <a href="https://more.ko-fi.com/privacy" target="_blank" rel="noopener">Datenschutzerklärung</a>. Wir erhalten von Ko-fi lediglich die Angaben zur Spende, die Sie dort freigeben (etwa Name, Betrag und Nachricht).</p>

<h2>Ihre Rechte</h2>
<p>Sie haben das Recht auf Auskunft (Art. 15 DSGVO), Berichtigung (Art. 16), Löschung (Art. 17), Einschränkung der Verarbeitung (Art. 18), Datenübertragbarkeit (Art. 20) und Widerspruch gegen Verarbeitungen auf Grundlage berechtigter Interessen (Art. 21). Eine erteilte Einwilligung können Sie jederzeit mit Wirkung für die Zukunft widerrufen. Außerdem können Sie sich bei einer Datenschutz-Aufsichtsbehörde beschweren (Art. 77 DSGVO).</p>
<p>Für alle Anliegen zum Datenschutz genügt eine formlose Nachricht an die oben genannte Adresse.</p>

<p class="mt-12 text-sm">Stand: November 2026</p>
