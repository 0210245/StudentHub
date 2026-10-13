# Practical 4: JavaScript DOM Manipulation, Event Handling and UI Interactivity

## Aim
Add dynamic UI components to the StudentHub portal using JavaScript: collapsible FAQ, modal popup, image/content slider, notification banner, hamburger menu and a light/dark theme switcher.

## Description
All six features are in `StudentHub-js.js` (one function per feature, started from a single `DOMContentLoaded` listener) and styled in `interactive-features.css`. Every page loads both files, so the hamburger menu, theme switcher and banner appear site-wide.

| Feature | Page | How it works |
|---|---|---|
| Hamburger menu | All (visible under 768px) | JS creates a button and toggles the `nav-open` class on `<nav>` |
| Light/Dark theme | All | Toggles `data-theme` on `<body>`; saved in `localStorage` |
| Notification banner | All | Created with `createElement`; dismissal saved in `sessionStorage` |
| Collapsible FAQ | `Faq.html` | Click a question to toggle `.open` and animate `max-height`; one item open at a time |
| Modal popup | `Profile.html` | "Edit Profile" opens `.modal-overlay`; closes via X, outside click or Esc |
| Content slider | `events.html` | Prev/next buttons, auto-generated dots, 5s autoplay that pauses on hover |

## Technologies used
HTML5, CSS3, vanilla JavaScript (DOM API, events, Web Storage). No frameworks or libraries.

## How to run
1. Keep the structure: `index.html`, the CSS/JS files at the root, and the pages inside `StudentHub/`.
2. Open `index.html` in a browser (or use VS Code Live Server).
3. Use the nav links to visit each page.

## Expected output
- Dismissible banner at the top of each page (stays hidden for the session once closed).
- 🌙/☀️ button in the header switches theme and remembers it after reload.
- Narrow the window below 768px: the nav collapses behind a hamburger icon.
- FAQ: clicking a question expands its answer smoothly and closes the others.
- Profile: "Edit Profile" opens a popup; "Save Changes" shows an alert and closes it.
- Events: the highlights slider changes slides with arrows, dots and autoplay.
