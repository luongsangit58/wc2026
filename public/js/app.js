/* World Cup 2026 — front-end behaviour (no build step required) */
(function () {
    'use strict';

    /* ---- Mobile nav toggle -------------------------------------------- */
    var toggle = document.querySelector('[data-nav-toggle]');
    var links = document.querySelector('[data-nav-links]');
    if (toggle && links) {
        toggle.addEventListener('click', function () {
            var open = links.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    /* ---- Countdown ----------------------------------------------------- */
    // Any element with [data-countdown="<ISO timestamp>"] containing
    // [data-cd="days|hours|minutes|seconds"] children gets a live ticker.
    document.querySelectorAll('[data-countdown]').forEach(function (root) {
        var target = new Date(root.getAttribute('data-countdown')).getTime();
        if (isNaN(target)) return;

        var fields = {
            days: root.querySelector('[data-cd="days"]'),
            hours: root.querySelector('[data-cd="hours"]'),
            minutes: root.querySelector('[data-cd="minutes"]'),
            seconds: root.querySelector('[data-cd="seconds"]')
        };

        function pad(n) { return (n < 10 ? '0' : '') + n; }

        function tick() {
            var diff = target - Date.now();
            if (diff < 0) diff = 0;
            var s = Math.floor(diff / 1000);
            var d = Math.floor(s / 86400);
            var h = Math.floor((s % 86400) / 3600);
            var m = Math.floor((s % 3600) / 60);
            var sec = s % 60;
            if (fields.days) fields.days.textContent = pad(d);
            if (fields.hours) fields.hours.textContent = pad(h);
            if (fields.minutes) fields.minutes.textContent = pad(m);
            if (fields.seconds) fields.seconds.textContent = pad(sec);
            if (diff === 0) {
                clearInterval(timer);
                var live = root.getAttribute('data-countdown-live');
                if (live) root.innerHTML = '<span class="badge badge--live">Kick-off</span>';
            }
        }
        tick();
        var timer = setInterval(tick, 1000);
    });

    /* ---- Auto-submit filters ------------------------------------------ */
    // Selects inside [data-auto-filter] submit their form on change.
    document.querySelectorAll('[data-auto-filter] select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var form = sel.closest('form');
            if (form) form.submit();
        });
    });

    /* ---- Local kick-off times ----------------------------------------- */
    // Convert UTC timestamps in [data-localtime] to the visitor's timezone.
    document.querySelectorAll('[data-localtime]').forEach(function (el) {
        var dt = new Date(el.getAttribute('data-localtime'));
        if (isNaN(dt)) return;
        try {
            el.textContent = dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } catch (e) { /* keep server-rendered fallback */ }
    });

    /* ---- Live auto-refresh -------------------------------------------- */
    // When any match is live, soft-reload every 30s so scores + standings stay
    // current (paired with the `wc:simulate` scheduler on the server).
    // Restore scroll position after the reload.
    try {
        var savedY = sessionStorage.getItem('wcScrollY');
        if (savedY !== null) {
            window.scrollTo(0, parseInt(savedY, 10) || 0);
            sessionStorage.removeItem('wcScrollY');
        }
    } catch (e) { /* ignore */ }

    if (document.querySelector('.badge--live') || document.querySelector('[data-has-live]')) {
        var SECS = 30;
        var tag = document.createElement('div');
        tag.className = 'live-refresh';
        tag.innerHTML = '<span class="live-refresh__dot"></span> LIVE · refreshing in <b>' + SECS + '</b>s';
        document.body.appendChild(tag);
        var b = tag.querySelector('b');
        var n = SECS;
        setInterval(function () {
            n -= 1;
            if (b) b.textContent = n;
            if (n <= 0) {
                try { sessionStorage.setItem('wcScrollY', String(window.scrollY)); } catch (e) { /* ignore */ }
                window.location.reload();
            }
        }, 1000);
    }
})();
