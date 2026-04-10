/* ============================================================
   main.js — App Init, Global Helpers, Event Bindings
   Jobpilot Job Platform
   ============================================================ */

/* ─── Session helpers ─────────────────────────────────────── */
const Session = {
  _cache: undefined,

  /** Store user after login */
  set(user) {
    this._cache = user;
    localStorage.setItem('jp_user', JSON.stringify(user));
  },

  /** Get current user object (or null) — cached to avoid repeated JSON.parse */
  get() {
    if (this._cache !== undefined) return this._cache;
    const raw = localStorage.getItem('jp_user');
    this._cache = raw ? JSON.parse(raw) : null;
    return this._cache;
  },

  /** Always verify session against server — never trust localStorage alone */
  async loadFromServer() {
    try {
      const res  = await fetch(API_BASE + '/auth/me', { credentials: 'include' });
      const data = await res.json();
      if (res.ok && data.success && data.data) {
        this.set(data.data);
        return data.data;
      }
      // Explicit 401 = server confirmed no session — clear stale localStorage
      if (res.status === 401) this.clear();
    } catch (_) { /* network error — leave existing cache alone */ }
    return null;
  },

  /** Redirect to dashboard if already logged in (call on auth pages) */
  async redirectIfLoggedIn() {
    const user = await this.loadFromServer();
    if (!user) return;
    const dash = user.role === 'employer'
      ? FRONTEND_BASE + '/pages/employer-dashboard/dashboard.html'
      : FRONTEND_BASE + '/pages/candidate-dashboard/dashboard.html';
    window.location.replace(dash);
  },

  /** Check if logged in */
  isLoggedIn() {
    return !!this.get();
  },

  /** Get role: "candidate" | "employer" | null */
  getRole() {
    const user = this.get();
    return user ? user.role : null;
  },

  /** Store CSRF token */
  setCsrf(token) { localStorage.setItem('jp_csrf', token); },

  /** Retrieve CSRF token */
  getCsrf()      { return localStorage.getItem('jp_csrf') || ''; },

  /** Clear session (logout) */
  clear() {
    this._cache = null;
    localStorage.removeItem('jp_user');
    localStorage.removeItem('jp_csrf');
  },

  /**
   * requireLogin(role) — compatibility shim used by dashboard pages.
   * Returns a Promise that resolves with the user or redirects to login.
   * Usage:  const user = await Session.requireLogin('candidate');
   */
  requireLogin(role = null) {
    // AuthGuard is defined in auth-guard.js which loads after main.js.
    // Wrap in a microtask so AuthGuard is definitely available.
    return Promise.resolve().then(() => AuthGuard.require(role));
  },
};


/* ─── Toast notifications ─────────────────────────────────── */
const Toast = {
  container: null,

  _ensureContainer() {
    if (!this.container) {
      this.container = document.getElementById('toast-container');
      if (!this.container) {
        this.container = document.createElement('div');
        this.container.id = 'toast-container';
        document.body.appendChild(this.container);
      }
    }
    return this.container;
  },

  show(message, type = 'info', duration = 3500) {
    const container = this._ensureContainer();

    const icons = {
      success: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>`,
      error:   `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
      warning: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><triangle points="10.29 3.86 1.82 18 22.18 18"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
      info:    `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
    };

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
      <span class="toast-icon" style="color: var(--clr-${type === 'error' ? 'danger' : type === 'info' ? 'primary' : type})">${icons[type] || icons.info}</span>
      <span>${message}</span>
    `;

    container.appendChild(toast);

    setTimeout(() => {
      toast.style.animation = 'slideInRight 0.2s ease reverse forwards';
      setTimeout(() => toast.remove(), 200);
    }, duration);
  },

  success(msg) { this.show(msg, 'success'); },
  error(msg)   { this.show(msg, 'error'); },
  warning(msg) { this.show(msg, 'warning'); },
  info(msg)    { this.show(msg, 'info'); },
};


/* ─── Modal system ────────────────────────────────────────── */
const Modal = {
  /** Open a modal by ID */
  open(id) {
    const overlay = document.getElementById(id);
    if (!overlay) return;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';

    // Close on overlay click
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) this.close(id);
    });
  },

  /** Close a modal by ID */
  close(id) {
    const overlay = document.getElementById(id);
    if (!overlay) return;
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  },

  /** Close all open modals */
  closeAll() {
    document.querySelectorAll('.modal-overlay.open').forEach(m => {
      m.classList.remove('open');
    });
    document.body.style.overflow = '';
  },
};

// ESC key closes modals
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') Modal.closeAll();
});


/* ─── Dropdown system ─────────────────────────────────────── */
function initDropdowns() {
  // Pattern 1: [data-dropdown="targetId"] → find by ID
  document.querySelectorAll('[data-dropdown]').forEach(trigger => {
    const targetId = trigger.getAttribute('data-dropdown');
    const menu = document.getElementById(targetId);
    if (!menu) return;

    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = menu.classList.contains('open');
      closeAllDropdowns();
      if (!isOpen) menu.classList.add('open');
    });
  });

  // Pattern 2: .dropdown__trigger (dashboard navbars) → next sibling .dropdown__menu
  document.querySelectorAll('.dropdown__trigger:not([data-dropdown])').forEach(trigger => {
    const parent = trigger.closest('.dropdown');
    const menu   = parent ? parent.querySelector('.dropdown__menu') : null;
    if (!menu) return;

    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = menu.classList.contains('open');
      closeAllDropdowns();
      if (!isOpen) menu.classList.add('open');
    });
  });

  document.addEventListener('click', closeAllDropdowns);
}

function closeAllDropdowns() {
  document.querySelectorAll('.dropdown-menu.open, .dropdown__menu.open').forEach(m => m.classList.remove('open'));
}


/* ─── Tabs system ─────────────────────────────────────────── */
function initTabs(containerSelector = '.tabs') {
  document.querySelectorAll(containerSelector).forEach(tabs => {
    const buttons = tabs.querySelectorAll('.tab-btn');
    const panels  = tabs.querySelectorAll('.tab-panel');

    buttons.forEach((btn, i) => {
      btn.addEventListener('click', () => {
        buttons.forEach(b => b.classList.remove('active'));
        panels.forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        if (panels[i]) panels[i].classList.add('active');
      });
    });

    // Activate first tab by default if none active
    if (!tabs.querySelector('.tab-btn.active') && buttons.length) {
      buttons[0].classList.add('active');
      if (panels[0]) panels[0].classList.add('active');
    }
  });
}


/* ─── Accordion / FAQ ─────────────────────────────────────── */
function initAccordions() {
  document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', () => {
      const body = header.nextElementSibling;
      const isOpen = header.classList.contains('open');

      // Close all
      document.querySelectorAll('.accordion-header.open').forEach(h => {
        h.classList.remove('open');
        if (h.nextElementSibling) h.nextElementSibling.classList.remove('open');
      });

      // Toggle clicked
      if (!isOpen) {
        header.classList.add('open');
        if (body) body.classList.add('open');
      }
    });
  });
}


/* ─── Mobile navbar toggle ────────────────────────────────── */
function initMobileNav() {
  // Public navbar toggle → .navbar-mobile-menu
  const toggle = document.querySelector('.navbar-toggle');
  const menu   = document.querySelector('.navbar-mobile-menu');
  if (toggle && menu) {
    toggle.addEventListener('click', () => menu.classList.toggle('open'));
  }

  // Dashboard hamburger on pages without a sidebar → #mobile-drawer
  const hamburger = document.querySelector('.navbar-dashboard__hamburger');
  const drawer    = document.getElementById('mobile-drawer');
  if (hamburger && drawer && !document.querySelector('.sidebar')) {
    hamburger.addEventListener('click', () => drawer.classList.toggle('open'));
    document.addEventListener('click', (e) => {
      if (!hamburger.contains(e.target) && !drawer.contains(e.target)) {
        drawer.classList.remove('open');
      }
    });
  }
}


/* ─── Mobile sidebar toggle ──────────────────────────────── */
function initSidebar() {
  const sidebar = document.querySelector('.sidebar');
  if (!sidebar) return;

  const toggleBtns = document.querySelectorAll('[data-sidebar-toggle], .navbar-dashboard__hamburger');

  // Create overlay dynamically if not in markup
  let overlay = document.querySelector('.sidebar-overlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
  }

  toggleBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      overlay.classList.toggle('open');
      document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
    });
  });

  overlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  });
}


/* ─── Pagination helper ───────────────────────────────────── */
function renderPagination(container, totalPages, currentPage, onPageChange) {
  if (!container || totalPages <= 1) return;

  let html = '';

  // Prev
  html += `<button class="page-btn page-btn--prev" ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Prev
  </button>`;

  // Pages
  for (let i = 1; i <= totalPages; i++) {
    if (
      i === 1 || i === totalPages ||
      (i >= currentPage - 1 && i <= currentPage + 1)
    ) {
      html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
    } else if (i === currentPage - 2 || i === currentPage + 2) {
      html += `<span style="align-self:center;color:var(--clr-text-3)">…</span>`;
    }
  }

  // Next
  html += `<button class="page-btn page-btn--next" ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}">
    Next
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
  </button>`;

  container.innerHTML = html;

  container.querySelectorAll('.page-btn[data-page]').forEach(btn => {
    btn.addEventListener('click', () => {
      const page = parseInt(btn.getAttribute('data-page'));
      if (!isNaN(page)) onPageChange(page);
    });
  });
}


/* ─── Form validation helpers ─────────────────────────────── */
const Validate = {
  required(value) {
    return value !== null && value !== undefined && String(value).trim() !== '';
  },
  email(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
  },
  minLength(value, min) {
    return String(value).length >= min;
  },
  match(a, b) {
    return a === b;
  },

  /** Show error under a field */
  showError(input, message) {
    input.classList.add('error');
    let err = input.parentElement.querySelector('.form-error');
    if (!err) {
      err = document.createElement('span');
      err.className = 'form-error';
      input.parentElement.appendChild(err);
    }
    err.textContent = message;
  },

  /** Clear error from a field */
  clearError(input) {
    input.classList.remove('error');
    const err = input.parentElement.querySelector('.form-error');
    if (err) err.remove();
  },

  /** Clear all errors in a form */
  clearAll(form) {
    form.querySelectorAll('.form-input.error, .form-select.error, .form-textarea.error')
      .forEach(el => this.clearError(el));
  },
};


/* ─── Bookmark / Favorite toggle ─────────────────────────── */
function initBookmarks() {
  document.querySelectorAll('.job-card__bookmark, .candidate-card__bookmark').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      btn.classList.toggle('active');
    });
  });
}


/* ─── Active nav link highlight ──────────────────────────── */
function highlightActiveNavLink() {
  const current = window.location.pathname;
  document.querySelectorAll('.navbar-public__link, .navbar-dashboard__nav-link').forEach(link => {
    const href = link.getAttribute('href');
    if (href && current.endsWith(href)) {
      link.classList.add('active');
    }
  });
}


/* ─── Utility: format date ────────────────────────────────── */
function formatDate(isoString) {
  const d = new Date(isoString);
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}


/* ─── Utility: debounce ───────────────────────────────────── */
function debounce(fn, delay = 300) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}


/* ─── Global controls init (lang + theme) ────────────────── */
/* NOTE: HTML controls are placed statically in every navbar by the build.
   i18n.js and theme.js self-wire their own click handlers.
   This function is kept only for pages that may need a late sync. */
function initGlobalControls() {
  /* Re-sync lang button active state (in case i18n.js fired before controls were ready) */
  if (typeof I18n !== 'undefined') I18n._updateSwitcherUI();
  if (typeof Theme !== 'undefined') Theme._apply(Theme.current);
}


/* ─── Notification bell ───────────────────────────────────── */
async function initNotificationBell() {
  // Only run on dashboard pages (presence of .navbar-dashboard__icon-btn[aria-label="Notifications"])
  const bellBtn = document.querySelector('.navbar-dashboard__icon-btn[aria-label="Notifications"]');
  if (!bellBtn) return;

  // Inject badge span if not already in markup
  let badge = document.getElementById('notif-badge');
  if (!badge) {
    badge = document.createElement('span');
    badge.className = 'navbar-dashboard__badge';
    badge.id = 'notif-badge';
    badge.style.display = 'none';
    badge.textContent = '0';
    bellBtn.appendChild(badge);
  }

  const user = Session.get();
  if (!user) return;

  try {
    const res = await API.getNotifications(user.role);
    const count = res?.data?.count ?? 0;
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : String(count);
      badge.style.display = '';
    }
  } catch (_) { /* bell stays hidden on error */ }
}


/* ─── Init on DOM ready ───────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initDropdowns();
  initTabs();
  initAccordions();
  initMobileNav();
  initSidebar();
  initBookmarks();
  highlightActiveNavLink();
  initGlobalControls();
  initNotificationBell();

  // Bind modal close buttons
  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = btn.closest('.modal-overlay');
      if (modal) Modal.close(modal.id);
    });
  });

  // Bind modal open buttons
  document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.getAttribute('data-modal-open');
      Modal.open(target);
    });
  });

  // "Post A Job" guard — requires employer login
  document.querySelectorAll('a[href*="post-job"]').forEach(link => {
    link.addEventListener('click', (e) => {
      const user = Session.get();
      if (!user) {
        e.preventDefault();
        window.location.href = FRONTEND_BASE + '/pages/auth/login.html';
      } else if (user.role !== 'employer') {
        e.preventDefault();
        Toast.error('Only employers can post jobs.');
      }
    });
  });

  // Logout button — handles both <button data-logout> and <a data-logout>
  document.querySelectorAll('.sidebar__logout, [data-logout]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault(); // stop <a href> from also navigating

      // Call backend to destroy server-side session (best-effort)
      try { await API.logout(); } catch (_) { /* ignore errors on logout */ }

      // Clear client-side session
      Session.clear();

      // Navigate to login — compute a safe relative path based on page depth
      const pathParts = window.location.pathname.split('/').filter(Boolean);
      // Find the index of 'pages' in the path
      const pagesIdx = pathParts.findIndex(p => p === 'pages');
      let loginHref;
      if (pagesIdx !== -1) {
        // We are inside /pages/something/ — go up to site root + /pages/auth/login.html
        const stepsUp = pathParts.length - pagesIdx; // number of segments after 'pages' folder itself
        loginHref = '../'.repeat(stepsUp) + 'pages/auth/login.html';
      } else if (FRONTEND_BASE) {
        loginHref = FRONTEND_BASE + '/pages/auth/login.html';
      } else {
        loginHref = 'pages/auth/login.html';
      }
      window.location.href = loginHref;
    });
  });
});
