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

    /* ---- Player card modal -------------------------------------------- */
    // Click any [.js-player][data-player-id] to open a card with photo + stats.
    var modal = document.querySelector('[data-player-modal]');
    if (modal) {
        var pmBody = modal.querySelector('[data-pm-body]');

        function pmOpen() { modal.hidden = false; document.body.style.overflow = 'hidden'; }
        function pmClose() { modal.hidden = true; document.body.style.overflow = ''; }

        modal.querySelectorAll('[data-pmodal-close]').forEach(function (el) {
            el.addEventListener('click', pmClose);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) pmClose();
        });

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function pmRender(d) {
            var avatar = d.photo
                ? '<img class="avatar" src="' + esc(d.photo) + '" alt="">'
                : '<span class="avatar avatar--initials">' + esc(d.initials || '?') + '</span>';
            function stat(label, val) {
                return '<div class="pmodal__stat"><span class="pmodal__stat-val">' +
                    (val === null || val === undefined || val === '' ? '—' : esc(val)) +
                    '</span><span class="pmodal__stat-label">' + label + '</span></div>';
            }
            pmBody.innerHTML =
                '<div class="pmodal__head">' + avatar +
                    '<div class="pmodal__id">' +
                        (d.number ? '<div class="pmodal__num">#' + esc(d.number) + '</div>' : '') +
                        '<h3 class="pmodal__name">' + esc(d.name) + '</h3>' +
                        '<div class="pmodal__team">' + esc(d.team_flag || '') + ' ' + esc(d.team || '') + '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="pmodal__stats">' +
                    stat('Position', d.position_label || d.position) +
                    stat('Age', d.age) +
                    stat('Goals', d.goals) +
                    stat('Assists', d.assists) +
                    stat('Rating', d.rating) +
                '</div>' +
                (d.dob ? '<p class="pmodal__meta muted">Born ' + esc(d.dob) + '</p>' : '') +
                (d.team_slug ? '<a class="btn btn--ghost btn--sm" href="/teams/' + esc(d.team_slug) + '">View team →</a>' : '');
        }

        document.querySelectorAll('.js-player[data-player-id]').forEach(function (el) {
            function go() {
                var id = el.getAttribute('data-player-id');
                if (!id) return;
                pmBody.innerHTML = '<p class="muted" style="padding:40px;text-align:center">Loading…</p>';
                pmOpen();
                fetch('/players/' + encodeURIComponent(id), { headers: { Accept: 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(pmRender)
                    .catch(function () { pmBody.innerHTML = '<p class="muted" style="padding:40px;text-align:center">Could not load player.</p>'; });
            }
            el.addEventListener('click', go);
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); }
            });
        });
    }
})();
