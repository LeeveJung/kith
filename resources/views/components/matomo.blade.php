{{-- Matomo tracking code; Klaro (resources/js/consent.js) only activates it after consent. --}}
@php($matomo = config('kith.matomo'))

@if (filled($matomo['url']) && filled($matomo['site_id']))
    <script type="text/plain" data-type="text/javascript" data-name="matomo">
        var _paq = window._paq = window._paq || [];
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        (function () {
            var u = @js(Str::finish($matomo['url'], '/'));
            _paq.push(['setTrackerUrl', u + 'matomo.php']);
            _paq.push(['setSiteId', @js((string) $matomo['site_id'])]);
            var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
            g.async = true; g.src = u + 'matomo.js'; s.parentNode.insertBefore(g, s);
        })();
    </script>
@endif
