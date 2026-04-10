/* ============================================================
   theme.js — Light/Dark theme with localStorage persistence
   Default: light
   ============================================================ */

const Theme = {
  current: localStorage.getItem('jp_theme') || 'light',

  _apply(t) {
    document.documentElement.setAttribute('data-theme', t);
    this.current = t;
    localStorage.setItem('jp_theme', t);
    // Update toggle button icons if present
    document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
      const sun  = btn.querySelector('.theme-icon-sun');
      const moon = btn.querySelector('.theme-icon-moon');
      if (sun)  sun.style.display  = t === 'dark' ? 'block' : 'none';
      if (moon) moon.style.display = t === 'dark' ? 'none'  : 'block';
    });
  },

  init() { this._apply(this.current); },

  toggle() { this._apply(this.current === 'dark' ? 'light' : 'dark'); },

  isDark() { return this.current === 'dark'; },
};

// Apply immediately before first paint
Theme._apply(Theme.current);

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    Theme.init();
    document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
      btn.addEventListener('click', () => Theme.toggle());
    });
  });
} else {
  Theme.init();
  document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
    btn.addEventListener('click', () => Theme.toggle());
  });
}
