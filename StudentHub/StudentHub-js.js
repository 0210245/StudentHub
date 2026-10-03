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
    initSlider();
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
