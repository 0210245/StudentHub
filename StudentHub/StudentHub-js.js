/* =========================================================
   StudentHub-js.js
   Practical 4: JavaScript DOM Manipulation, Event Handling,
   and UI Interactivity

   Features implemented:
   1. Hamburger menu (mobile nav toggle)
   2. Light / Dark theme switcher
   3. Notification banner (dismissible)
   4. Collapsible FAQ accordion
   5. Modal popup (used on Profile page)
   6. Image / content slider (used on Events page)
   ========================================================= */

document.addEventListener('DOMContentLoaded', function () {
    initHamburgerMenu();
    initThemeToggle();
    initNotificationBanner();
    initFAQAccordion();
    initModal();
    initEventsPage();   // must run before initSlider(): it builds the slides
    initSlider();
    initActiveNav();
    initScrollReveal();
    initBackToTop();
    initForms();
    initEventSearch();
    initCounters();
});

/* ---------------------------------------------------------
   1. Hamburger Menu
   Injects a hamburger button before the <nav> inside
   <header> and toggles a "nav-open" class on click.
--------------------------------------------------------- */
function initHamburgerMenu() {
    const nav = document.querySelector('header nav');
    if (!nav) return;

    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'hamburger-btn';
    toggleBtn.type = 'button';
    toggleBtn.setAttribute('aria-label', 'Toggle navigation menu');
    toggleBtn.setAttribute('aria-expanded', 'false');
    toggleBtn.innerHTML = '<span></span><span></span><span></span>';

    nav.parentElement.insertBefore(toggleBtn, nav);

    toggleBtn.addEventListener('click', function () {
        const isOpen = nav.classList.toggle('nav-open');
        toggleBtn.classList.toggle('active', isOpen);
        toggleBtn.setAttribute('aria-expanded', String(isOpen));
    });

    // Close the menu automatically once a link is tapped (mobile UX)
    nav.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            nav.classList.remove('nav-open');
            toggleBtn.classList.remove('active');
            toggleBtn.setAttribute('aria-expanded', 'false');
        });
    });
}

/* ---------------------------------------------------------
   2. Light / Dark Theme Switcher
   Injects a toggle button into <header> and persists the
   chosen theme in localStorage so it survives page loads.
--------------------------------------------------------- */
function initThemeToggle() {
    const header = document.querySelector('header');
    if (!header) return;

    const themeBtn = document.createElement('button');
    themeBtn.className = 'theme-toggle-btn';
    themeBtn.type = 'button';
    themeBtn.setAttribute('aria-label', 'Toggle light or dark theme');

    const savedTheme = localStorage.getItem('studenthub-theme') || 'light';
    document.body.setAttribute('data-theme', savedTheme);
    themeBtn.textContent = savedTheme === 'dark' ? '☀️' : '🌙';

    header.appendChild(themeBtn);

    themeBtn.addEventListener('click', function () {
        const current = document.body.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        document.body.setAttribute('data-theme', next);
        localStorage.setItem('studenthub-theme', next);
        themeBtn.textContent = next === 'dark' ? '☀️' : '🌙';
    });
}

/* ---------------------------------------------------------
   3. Notification Banner
   Shows a dismissible banner at the top of the page.
   Uses sessionStorage so it stays hidden for the rest of
   the browsing session once closed.
--------------------------------------------------------- */
function initNotificationBanner() {
    if (sessionStorage.getItem('sh-banner-dismissed') === 'true') return;

    const banner = document.createElement('div');
    banner.className = 'notification-banner';
    banner.innerHTML =
        '<span>\uD83D\uDCE2 Registrations for <strong>Hackathon 2026</strong> are now open! Don\'t miss out.</span>' +
        '<button type="button" class="banner-close" aria-label="Dismiss notification">&times;</button>';

    document.body.insertBefore(banner, document.body.firstChild);

    banner.querySelector('.banner-close').addEventListener('click', function () {
        banner.classList.add('hide');
        sessionStorage.setItem('sh-banner-dismissed', 'true');
        setTimeout(function () { banner.remove(); }, 300);
    });
}

/* ---------------------------------------------------------
   4. Collapsible FAQ Accordion
   Expects markup:
   <div class="faq-item">
       <button class="faq-question">Question</button>
       <div class="faq-answer"><p>Answer</p></div>
   </div>
   Only one item stays open at a time.
--------------------------------------------------------- */
function initFAQAccordion() {
    const items = document.querySelectorAll('.faq-item');
    if (!items.length) return;

    items.forEach(function (item) {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        question.setAttribute('aria-expanded', 'false');

        question.addEventListener('click', function () {
            const isOpen = item.classList.contains('open');

            items.forEach(function (other) {
                other.classList.remove('open');
                other.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
                other.querySelector('.faq-answer').style.maxHeight = null;
            });

            if (!isOpen) {
                item.classList.add('open');
                question.setAttribute('aria-expanded', 'true');
                answer.style.maxHeight = answer.scrollHeight + 'px';
            }
        });
    });
}

/* ---------------------------------------------------------
   5. Modal Popup
   Expects a trigger element with [data-modal-open] and a
   modal with class "modal-overlay" containing ".modal-close".
   Used on Profile.html for the "Edit Profile" action.
--------------------------------------------------------- */
function initModal() {
    const openBtn = document.querySelector('[data-modal-open]');
    const modal = document.querySelector('.modal-overlay');
    if (!openBtn || !modal) return;

    const closeBtn = modal.querySelector('.modal-close');
    const form = modal.querySelector('form');

    function openModal() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) closeModal();
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            alert('Profile updated successfully!');
            closeModal();
        });
    }
}

/* ---------------------------------------------------------
   6. Image / Content Slider
   Expects markup:
   <div class="slider">
     <div class="slider-track">
       <div class="slider-slide">...</div>
       ...
     </div>
     <button class="slider-prev">&#8249;</button>
     <button class="slider-next">&#8250;</button>
     <div class="slider-dots"></div>
   </div>
   Dots are generated automatically. Auto-advances every 5s
   and pauses while the mouse is hovering the slider.
--------------------------------------------------------- */
function initSlider() {
    const slider = document.querySelector('.slider');
    if (!slider) return;

    const track = slider.querySelector('.slider-track');
    const slides = Array.from(track.children);
    const nextBtn = slider.querySelector('.slider-next');
    const prevBtn = slider.querySelector('.slider-prev');
    const dotsContainer = slider.querySelector('.slider-dots');
    let index = 0;

    slides.forEach(function (_, i) {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'slider-dot';
        dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
        if (i === 0) dot.classList.add('active');
        dot.addEventListener('click', function () { goTo(i); });
        dotsContainer.appendChild(dot);
    });
    const dots = Array.from(dotsContainer.children);

    function update() {
        track.style.transform = 'translateX(-' + (index * 100) + '%)';
        dots.forEach(function (d, i) { d.classList.toggle('active', i === index); });
    }
    function goTo(i) {
        index = (i + slides.length) % slides.length;
        update();
    }

    if (nextBtn) nextBtn.addEventListener('click', function () { goTo(index + 1); });
    if (prevBtn) prevBtn.addEventListener('click', function () { goTo(index - 1); });

    let autoplay = setInterval(function () { goTo(index + 1); }, 5000);
    slider.addEventListener('mouseenter', function () { clearInterval(autoplay); });
    slider.addEventListener('mouseleave', function () {
        autoplay = setInterval(function () { goTo(index + 1); }, 5000);
    });
}

/* =========================================================
   Extra interactivity (UI upgrade)
   ========================================================= */

/* Toast: small message that fades in and out */
function showToast(msg) {
    const t = document.createElement('div');
    t.className = 'toast';
    t.textContent = msg;
    document.body.appendChild(t);
    requestAnimationFrame(function () { t.classList.add('show'); });
    setTimeout(function () {
        t.classList.remove('show');
        setTimeout(function () { t.remove(); }, 300);
    }, 3000);
}

/* Highlight the nav link of the page you are on */
function initActiveNav() {
    const page = (location.pathname.split('/').pop() || 'index.html').toLowerCase();
    document.querySelectorAll('header nav a').forEach(function (a) {
        if (a.getAttribute('href').split('/').pop().toLowerCase() === page) a.classList.add('active');
    });
}

/* Cards/forms fade in when scrolled into view (IntersectionObserver) */
function initScrollReveal() {
    if (!('IntersectionObserver' in window)) return;
    const obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.card, .events-section, .profile-card, main form').forEach(function (el) {
        el.classList.add('reveal');
        obs.observe(el);
    });
}

/* Floating "back to top" button, shown after scrolling 300px */
function initBackToTop() {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'back-to-top';
    btn.setAttribute('aria-label', 'Back to top');
    btn.textContent = '\u2191';
    document.body.appendChild(btn);
    window.addEventListener('scroll', function () {
        btn.classList.toggle('show', window.scrollY > 300);
    });
    btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

/* Forms: show/hide password, password-match check, demo-mode submit.
   When the page is opened as file:// (no PHP server) or the form has no
   action, the form cannot reach a server, so we show a toast instead of
   a "File not found" error. On XAMPP (http://localhost) it submits normally. */
function initForms() {
    document.querySelectorAll('input[type="password"]').forEach(function (input) {
        const link = document.createElement('button');
        link.type = 'button';
        link.className = 'pw-toggle';
        link.textContent = 'Show';
        input.insertAdjacentElement('afterend', link);
        link.addEventListener('click', function () {
            const hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            link.textContent = hidden ? 'Hide' : 'Show';
        });
    });

    // Native browser check: marks the Confirm field invalid, so the browser
    // shows "Passwords do not match" and blocks the submit by itself.
    const regPw = document.getElementById('regPassword');
    const regConfirm = document.getElementById('confirmPassword');
    if (regPw && regConfirm) {
        const checkMatch = function () {
            regConfirm.setCustomValidity(regPw.value !== regConfirm.value ? 'Passwords do not match' : '');
        };
        regPw.addEventListener('input', checkMatch);
        regConfirm.addEventListener('input', checkMatch);
    }

    document.querySelectorAll('main form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const pw = form.querySelector('#regPassword');
            const confirmPw = form.querySelector('#confirmPassword');
            if (pw && confirmPw && pw.value !== confirmPw.value) {
                e.preventDefault();
                showToast('\u26A0\uFE0F Passwords do not match');
                confirmPw.focus();
                return;
            }
            if (location.protocol === 'file:' || !form.getAttribute('action')) {
                e.preventDefault();
                showToast('\u2705 Submitted successfully (demo mode)');
                form.reset();
            }
        });
    });
}

/* Events page: live search box that filters the event list */
function initEventSearch() {
    const list = document.querySelector('.events-section ol');
    if (!list) return;
    const input = document.createElement('input');
    input.type = 'search';
    input.className = 'event-search';
    input.placeholder = '\uD83D\uDD0D Search events...';
    list.parentElement.insertBefore(input, list);
    input.addEventListener('input', function () {
        const q = input.value.toLowerCase();
        list.querySelectorAll('li').forEach(function (li) {
            li.style.display = li.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}

/* Admin dashboard: numbers count up from 0 */
function initCounters() {
    document.querySelectorAll('.cards .card h2').forEach(function (el) {
        const target = parseInt(el.textContent, 10);
        if (isNaN(target)) return;
        let n = 0;
        const step = Math.max(1, Math.ceil(target / 40));
        const timer = setInterval(function () {
            n += step;
            if (n >= target) { n = target; clearInterval(timer); }
            el.textContent = n;
        }, 30);
    });
}

/* =========================================================
   Events page (events.html)
   - Lists all events as cards (upcoming first, ended last)
   - Search box + filter chips
   - "Register" opens a popup and sends the form to Event.php
   - Live countdown on every card
   To add a new event: add one more object to the EVENTS array.
   Does nothing on other pages (no #eventGrid there).
   ========================================================= */
function initEventsPage() {
    // ---- 1. Event data (edit/add events here) ----
    const EVENTS = [
        { id: 'EVT001', name: 'Cultural Fest 2026', date: '2026-09-12', type: 'Cultural', venue: 'TBA', coordinator: 'TBA',
          desc: 'A celebration of music, dance and art across campus. Open to all students.' },
        { id: 'EVT002', name: 'Hackathon', date: '2026-09-15', type: 'Technical', venue: 'TBA', coordinator: 'TBA',
          desc: 'Build something amazing in 24 hours and compete for exciting prizes.' },
        { id: 'EVT003', name: 'Workshop on Web Development', date: '2026-09-20', type: 'Workshop', venue: 'TBA', coordinator: 'TBA',
          desc: 'Hands-on session covering HTML, CSS and JavaScript fundamentals.' },
        // Sample new events (replace with your real ones)
        { id: 'EVT004', name: 'Tech Quiz 2026', date: '2026-11-08', type: 'Technical', venue: 'TBA', coordinator: 'TBA',
          desc: 'Test your tech knowledge in a fast-paced team quiz.' },
        { id: 'EVT005', name: 'Annual Sports Meet', date: '2026-11-22', type: 'Sports', venue: 'TBA', coordinator: 'TBA',
          desc: 'Track, field and team games. Show your sporting spirit.' },
        { id: 'EVT006', name: 'Startup Pitch Day', date: '2026-12-05', type: 'Workshop', venue: 'TBA', coordinator: 'TBA',
          desc: 'Pitch your idea to mentors and get feedback.' }
    ];

    const STORE_KEY = 'studenthub-registered';
    let activeType = 'All';
    let query = '';
    let current = null;                      // event being registered for

    // ---- 2. Page elements ----
    const grid = document.getElementById('eventGrid');
    const chipsBox = document.getElementById('eventChips');
    const searchBox = document.getElementById('eventSearch');
    const overlay = document.getElementById('regOverlay');
    const form = document.getElementById('regForm');
    if (!grid) return;

    // ---- 3. Helpers ----
    function startOf(d) { return new Date(d + 'T00:00:00'); }

    function getStatus(e) {                  // 'upcoming' | 'today' | 'ended'
        const start = startOf(e.date);
        const end = new Date(start.getTime() + 86400000);
        const now = new Date();
        if (now >= end) return 'ended';
        return now >= start ? 'today' : 'upcoming';
    }

    function countdownText(e) {
        const s = getStatus(e);
        if (s === 'ended') return 'Event ended';
        if (s === 'today') return '\uD83D\uDD34 Happening today!';
        const ms = startOf(e.date) - new Date();
        const d = Math.floor(ms / 86400000);
        const h = Math.floor((ms % 86400000) / 3600000);
        const m = Math.floor((ms % 3600000) / 60000);
        return '\u23F3 Starts in ' + d + 'd ' + h + 'h ' + m + 'm';
    }

    function prettyDate(e) {
        return startOf(e.date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    // Registered event ids are remembered in the browser (localStorage)
    function getRegistered() {
        try { return JSON.parse(localStorage.getItem(STORE_KEY)) || []; } catch (err) { return []; }
    }
    function saveRegistered(id) {
        const list = getRegistered();
        if (!list.includes(id)) list.push(id);
        try { localStorage.setItem(STORE_KEY, JSON.stringify(list)); } catch (err) {}
    }

    function toast(msg) { if (typeof showToast === 'function') showToast(msg); else alert(msg); }

    // Upcoming events first (soonest first), ended events last
    function sortedEvents() {
        const up = EVENTS.filter(function (e) { return getStatus(e) !== 'ended'; })
                         .sort(function (a, b) { return startOf(a.date) - startOf(b.date); });
        const old = EVENTS.filter(function (e) { return getStatus(e) === 'ended'; })
                          .sort(function (a, b) { return startOf(b.date) - startOf(a.date); });
        return up.concat(old);
    }

    // ---- 4. Slider slides (next 3 upcoming events) ----
    function buildSlider() {
        const track = document.getElementById('sliderTrack');
        const next = sortedEvents().filter(function (e) { return getStatus(e) !== 'ended'; }).slice(0, 3);
        track.innerHTML = next.length
            ? next.map(function (e) {
                return '<div class="slider-slide"><h4>' + e.name + '</h4><p>' + prettyDate(e) + ' &mdash; ' + e.desc + '</p></div>';
              }).join('')
            : '<div class="slider-slide"><h4>No upcoming events</h4><p>Check back soon for new events.</p></div>';
    }

    // ---- 5. Filter chips ----
    function buildChips() {
        const types = ['All'].concat(EVENTS.map(function (e) { return e.type; })
                      .filter(function (t, i, arr) { return arr.indexOf(t) === i; }));
        chipsBox.innerHTML = types.map(function (t) {
            return '<button type="button" class="chip' + (t === activeType ? ' active' : '') + '" data-type="' + t + '">' + t + '</button>';
        }).join('');
    }

    // ---- 6. Draw the event cards ----
    function render() {
        const registered = getRegistered();
        const list = sortedEvents().filter(function (e) {
            const okType = activeType === 'All' || e.type === activeType;
            const okText = (e.name + ' ' + e.desc + ' ' + e.type).toLowerCase().includes(query);
            return okType && okText;
        });

        if (!list.length) {
            grid.innerHTML = '<p class="event-empty">No events match your search.</p>';
            return;
        }
        grid.innerHTML = list.map(function (e) {
            const ended = getStatus(e) === 'ended';
            let btn;
            if (ended) btn = '<button type="button" class="event-btn" disabled>Registration closed</button>';
            else if (registered.includes(e.id)) btn = '<button type="button" class="event-btn registered" disabled>\u2713 Registered</button>';
            else btn = '<button type="button" class="event-btn" data-register="' + e.id + '">Register</button>';
            return '<article class="event-card' + (ended ? ' ended' : '') + '">' +
                '<span class="event-badge">' + e.type + '</span>' +
                '<h3>' + e.name + '</h3>' +
                '<p>\uD83D\uDCC5 ' + prettyDate(e) + '</p>' +
                '<p>\uD83D\uDCCD ' + e.venue + ' &nbsp;|&nbsp; \uD83D\uDC64 ' + e.coordinator + '</p>' +
                '<p>' + e.desc + '</p>' +
                '<p class="event-count" data-countdown="' + e.id + '">' + countdownText(e) + '</p>' +
                btn + '</article>';
        }).join('');
    }

    // Keep the countdowns ticking (once a minute)
    setInterval(function () {
        document.querySelectorAll('[data-countdown]').forEach(function (el) {
            const e = EVENTS.find(function (x) { return x.id === el.getAttribute('data-countdown'); });
            if (e) el.textContent = countdownText(e);
        });
    }, 60000);

    // ---- 7. Registration popup ----
    function openModal(e) {
        current = e;
        document.getElementById('regSummary').textContent = e.name + ' \u2014 ' + prettyDate(e);
        form.elements['Event_ID'].value = e.id;
        form.elements['Event_Name'].value = e.name;
        form.elements['Event_Date'].value = e.date;
        form.elements['Event_Venue'].value = e.venue;
        form.elements['Event_Type'].value = e.type;
        form.elements['Event_coordinator'].value = e.coordinator;
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        document.getElementById('regName').focus();
    }
    function closeModal() {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();                       // stay on the page, send with fetch()
        const done = function (ok, msg, already) {
            if (ok || already) { saveRegistered(current.id); render(); form.reset(); closeModal(); }
            toast(msg);
        };
        // Opened as a plain file: PHP can't run, so just simulate it
        if (location.protocol === 'file:') {
            done(true, '\u2705 Registered! (demo mode: not saved to database)', false);
            return;
        }
        fetch(form.getAttribute('action'), { method: 'POST', body: new FormData(form) })
            .then(function (res) { return res.json(); })
            .then(function (r) { done(r.ok, (r.ok ? '\u2705 ' : '\u26A0\uFE0F ') + r.message, r.already); })
            .catch(function () { toast('\u26A0\uFE0F Could not reach the server. Are Apache and MySQL running?'); });
    });

    // ---- 8. Events (click, type, keys) ----
    grid.addEventListener('click', function (ev) {
        const btn = ev.target.closest('[data-register]');
        if (!btn) return;
        openModal(EVENTS.find(function (e) { return e.id === btn.getAttribute('data-register'); }));
    });
    chipsBox.addEventListener('click', function (ev) {
        const chip = ev.target.closest('[data-type]');
        if (!chip) return;
        activeType = chip.getAttribute('data-type');
        buildChips();
        render();
    });
    searchBox.addEventListener('input', function () { query = searchBox.value.toLowerCase(); render(); });
    overlay.querySelector('.modal-close').addEventListener('click', closeModal);
    overlay.addEventListener('click', function (ev) { if (ev.target === overlay) closeModal(); });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && overlay.classList.contains('active')) closeModal();
    });

    // ---- 9. Start ----
    buildSlider();
    buildChips();
    render();
}