{{--
    Paints the last-rendered sidebar / top header immediately (before the JS
    bundle loads) so they don't blink out on every page navigation.
    Snapshots are written by resources/js/utils/shellCache.js on pagehide.
    Usage: @include('layouts.partials.shell-restore', ['ids' => ['a-root', 'b-root']])
--}}
<script>
    (function () {
        try {
            var uid = (window.__appConfig && window.__appConfig.userId) || 'anon';
            @json($ids).forEach(function (id) {
                var el = document.getElementById(id);
                if (!el || el.firstChild) return;
                var html = sessionStorage.getItem('shell:' + uid + ':' + id);
                if (!html) return;
                el.innerHTML = html;

                var props = el.dataset.props ? JSON.parse(el.dataset.props) : {};

                // Header: show this page's title, not the previous one
                var title = el.querySelector('[data-shell-title]');
                if (title && props.title) title.textContent = props.title;
            });
        } catch (e) { /* snapshot is best-effort */ }
    })();
</script>
