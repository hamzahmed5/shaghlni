/* ============================================================
   navbar-auth.js — Update public navbar based on session state.
   Include AFTER api.js + main.js on every public page.
   ============================================================ */

(async function initNavbarAuth() {
  // Fast check: cached session from localStorage (instant, no server round-trip)
  const cached = Session.get();

  function updateNavbar(user) {
    // Find the actions container(s) in public navbar
    const actionContainers = document.querySelectorAll(
      '.navbar-public__actions, .navbar-mobile-menu .navbar-public__actions'
    );
    if (!actionContainers.length || !user) return;

    const role  = user.role || 'candidate';
    const dash  = FRONTEND_BASE + (role === 'employer'
      ? '/pages/employer-dashboard/dashboard.html'
      : '/pages/candidate-dashboard/dashboard.html');
    const initials = user.name
      ? user.name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
      : 'U';

    actionContainers.forEach(container => {
      container.innerHTML = `
        <div class="global-controls">
          <div class="lang-switcher">
            <button class="lang-btn" data-lang-btn="en">EN</button>
            <button class="lang-btn" data-lang-btn="ar">AR</button>
          </div>
        </div>
        <a href="${dash}" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
            <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
          </svg>
          Dashboard
        </a>
        <button class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px" data-logout>
          <span style="width:24px;height:24px;background:rgba(255,255,255,.15);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700">${initials}</span>
          Sign Out
        </button>`;
    });

    // Re-bind logout buttons (main.js binds on DOMContentLoaded, but we just replaced the DOM)
    document.querySelectorAll('[data-logout]').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        try { await API.logout(); } catch (_) {}
        Session.clear();
        window.location.reload();
      });
    });

    // Re-sync i18n if available — wire NEW buttons injected by this replacement
    if (typeof I18n !== 'undefined') {
      I18n._updateSwitcherUI();
      I18n._wireControls();   // new [data-lang-btn] buttons need event listeners
    }
    if (typeof Theme !== 'undefined') Theme._apply(Theme.current);
  }

  // Apply immediately from cache (avoids flash)
  if (cached) updateNavbar(cached);

  // Then verify against server (catches expired sessions)
  const serverUser = await Session.loadFromServer();
  if (serverUser) {
    updateNavbar(serverUser);
  } else if (cached) {
    // Cache said logged in but server says no — revert to default (page reload cleans up)
    window.location.reload();
  }
})();
