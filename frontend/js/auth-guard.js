/* ============================================================
   auth-guard.js — Protect dashboard pages from unauthenticated access
   Load this BEFORE any inline page scripts on every protected page.
   ============================================================ */

const AuthGuard = {
  /**
   * Ensure a user is logged in with the correct role.
   * Returns a Promise that resolves with the user object.
   * If not logged in, redirects to login and never resolves.
   *
   * @param {string|null} requiredRole  'candidate' | 'employer' | null (any role)
   * @returns {Promise<object>}
   */
  async require(requiredRole = null) {
    const user = await Session.loadFromServer();
    if (!user) {
      Session.clear();
      window.location.replace(_resolveLoginUrl());
      return new Promise(() => {});
    }
    if (requiredRole && user.role !== requiredRole) {
      const dash = user.role === 'employer'
        ? _resolveBase() + '/pages/employer-dashboard/dashboard.html'
        : _resolveBase() + '/pages/candidate-dashboard/dashboard.html';
      window.location.replace(dash);
      return new Promise(() => {});
    }
    Session.set(user);
    return user;
  },
};

function _resolveBase() {
  const path = window.location.pathname;
  const idx  = path.lastIndexOf('/pages/');
  return idx !== -1 ? path.substring(0, idx) : '';
}

function _resolveLoginUrl() {
  return _resolveBase() + '/pages/auth/login.html';
}
