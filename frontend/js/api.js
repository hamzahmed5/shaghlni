/* ============================================================
   api.js — All API Calls (mapped to API_SPEC.md)
   Jobpilot Job Platform
   Base URL: /api
   ============================================================ */

// Detect base path dynamically so the app works regardless of where it is hosted
const FRONTEND_BASE = (() => {
  const path = window.location.pathname;
  const idx = path.lastIndexOf('/pages/');
  if (idx !== -1) return path.substring(0, idx);
  const idxIndex = path.lastIndexOf('/index.html');
  if (idxIndex !== -1) return path.substring(0, idxIndex);
  return path.replace(/\/[^/]*$/, ''); // strip last segment
})();

// Backend API base derived from frontend base (../backend/api relative to frontend root)
const API_BASE = FRONTEND_BASE.replace(/\/frontend$/, '') + '/backend/api';

/* ─── Core fetch wrapper ──────────────────────────────────── */
async function apiFetch(endpoint, options = {}) {
  const url = `${API_BASE}${endpoint}`;

  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 s

  const defaultOptions = {
    credentials: 'include',   // send session cookie on cross-origin dev setups
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': (typeof Session !== 'undefined' ? Session.getCsrf() : '') || '',
    },
    signal: controller.signal,
  };

  const mergedOptions = {
    ...defaultOptions,
    ...options,
    headers: {
      ...defaultOptions.headers,
      ...(options.headers || {}),
    },
    signal: controller.signal,
  };

  try {
    const response = await fetch(url, mergedOptions);
    clearTimeout(timeoutId);
    const data = await response.json();

    // Some endpoints (e.g. employer/applications, employer/jobs) return a raw
    // array for test-suite compatibility.  Normalise them into the standard
    // envelope so the rest of the frontend can always read `res.data`.
    if (Array.isArray(data)) {
      return { success: true, data };
    }

    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Something went wrong');
    }

    if (data.data?.csrf_token && typeof Session !== 'undefined') {
      Session.setCsrf(data.data.csrf_token);
    }

    return data;
  } catch (error) {
    clearTimeout(timeoutId);
    if (error.name === 'AbortError') throw new Error('Request timed out. Please check your connection.');
    throw error;
  }
}


/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   AUTH ENDPOINTS
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

/**
 * POST /api/auth/register
 * @param {Object} payload - { role, full_name, email, password }
 */
async function register(payload) {
  return apiFetch('/auth/register', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

/**
 * POST /api/auth/login
 * @param {Object} payload - { email, password }
 */
async function login(payload) {
  return apiFetch('/auth/login', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

/**
 * POST /api/auth/logout
 * Destroys the server-side session
 */
async function logout() {
  return apiFetch('/auth/logout', { method: 'POST' });
}

/**
 * GET /api/auth/me
 * Returns the currently authenticated user
 */
async function getMe() {
  return apiFetch('/auth/me');
}


/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   JOBS ENDPOINTS
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

/**
 * GET /api/jobs
 * Returns job listings with optional query filters
 * @param {Object} params - Optional: { limit, search, location, type, category, salary_min, sort }
 */
async function getJobs(params = {}) {
  const qs = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') qs.set(k, v); });
  const query = qs.toString();
  return apiFetch(`/jobs${query ? '?' + query : ''}`);
}

/**
 * GET /api/employers
 * Returns employer listings with optional query filters
 * @param {Object} params - Optional: { limit, page, search, location }
 */
async function getEmployers(params = {}) {
  const qs = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') qs.set(k, v); });
  const query = qs.toString();
  return apiFetch(`/employers${query ? '?' + query : ''}`);
}

/**
 * GET /api/candidates
 * Returns candidate listings with optional query filters
 * @param {Object} params - Optional: { limit, page, search, location }
 */
async function getCandidates(params = {}) {
  const qs = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') qs.set(k, v); });
  const query = qs.toString();
  return apiFetch(`/candidates${query ? '?' + query : ''}`);
}

/**
 * GET /api/employers/{id}
 * Returns a single employer's public profile + their recent jobs
 * @param {number|string} id
 */
async function getEmployer(id) {
  return apiFetch(`/employers/${id}`);
}

/**
 * GET /api/jobs/{id}
 * Returns details of a single job
 * @param {number|string} id
 */
async function getJob(id) {
  return apiFetch(`/jobs/${id}`);
}

/**
 * GET /api/jobs/recommended
 * Returns ML-ranked recommended jobs for the logged-in candidate
 */
async function getRecommendedJobs() {
  return apiFetch('/jobs/recommended');
}

/**
 * POST /api/jobs/{id}/apply
 * Apply for a job
 * @param {number|string} id
 * @param {Object} payload - { cv_id, cover_letter }
 */
async function applyForJob(id, payload) {
  return apiFetch(`/jobs/${id}/apply`, {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}


/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   CANDIDATE ENDPOINTS
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

/**
 * GET /api/candidate/dashboard
 * Returns dashboard stats for the logged-in candidate
 */
async function getCandidateDashboard() {
  return apiFetch('/candidate/dashboard');
}

/**
 * GET /api/candidate/favorites
 * Returns the candidate's saved/favorite jobs
 */
async function getCandidateFavorites() {
  return apiFetch('/candidate/favorites');
}

/**
 * POST /api/candidate/favorites/{jobId}
 * @param {number|string} jobId
 */
async function addFavorite(jobId) {
  return apiFetch(`/candidate/favorites/${jobId}`, { method: 'POST' });
}

/**
 * DELETE /api/candidate/favorites/{jobId}
 * @param {number|string} jobId
 */
async function removeFavorite(jobId) {
  return apiFetch(`/candidate/favorites/${jobId}`, { method: 'DELETE' });
}

/**
 * POST /api/candidate/upload-cv
 * Upload a CV file for the candidate
 * @param {FormData} formData - FormData containing the cv file field
 */
async function uploadCv(formData) {
  return apiFetch('/candidate/upload-cv', {
    method: 'POST',
    headers: {}, // Let browser set Content-Type with boundary for multipart
    body: formData,
  });
}


/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   EMPLOYER ENDPOINTS
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

/**
 * GET /api/employer/dashboard
 * Returns dashboard stats for the logged-in employer
 */
async function getEmployerDashboard() {
  return apiFetch('/employer/dashboard');
}

/**
 * POST /api/employer/jobs
 * Create a new job post
 * @param {Object} payload - { title, location, salary }
 */
async function postJob(payload) {
  return apiFetch('/employer/jobs', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

/**
 * GET /api/employer/applications
 * Returns all job applications for the employer
 */
async function getEmployerApplications() {
  return apiFetch('/employer/applications');
}

/**
 * GET /api/employer/jobs
 * Returns all jobs posted by the logged-in employer
 */
async function getEmployerJobs() {
  return apiFetch('/employer/jobs');
}


/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   PROFILE / CV / APPLICATION STATUS ENDPOINTS
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

/**
 * GET /api/candidate/profile
 * Returns the logged-in candidate's profile
 */
async function getCandidateProfile() {
  return apiFetch('/candidate/profile');
}

/**
 * PUT /api/candidate/profile
 * @param {Object} payload - profile fields to update
 */
async function updateCandidateProfile(payload) {
  return apiFetch('/candidate/profile', { method: 'PUT', body: JSON.stringify(payload) });
}

/**
 * GET /api/employer/profile
 * Returns the logged-in employer's profile
 */
async function getEmployerProfile() {
  return apiFetch('/employer/profile');
}

/**
 * PUT /api/employer/profile
 * @param {Object} payload - profile fields to update
 */
async function updateEmployerProfile(payload) {
  return apiFetch('/employer/profile', { method: 'PUT', body: JSON.stringify(payload) });
}

/**
 * DELETE /api/account
 * Permanently deletes the logged-in user's account
 */
async function deleteAccount() {
  return apiFetch('/account', { method: 'DELETE' });
}

/**
 * GET /api/candidate/cvs
 * Returns list of uploaded CVs for the logged-in candidate
 */
async function getCandidateCvs() {
  return apiFetch('/candidate/cvs');
}

/**
 * PATCH /api/employer/applications/{id}
 * Update the status of a job application
 * @param {number|string} id
 * @param {string} status - 'pending' | 'reviewing' | 'shortlisted' | 'rejected' | 'hired'
 */
async function updateApplicationStatus(id, status) {
  return apiFetch(`/employer/applications/${id}`, {
    method: 'PATCH',
    body: JSON.stringify({ status }),
  });
}

/**
 * GET /api/candidate/notifications  or  /api/employer/notifications
 * Returns notification count + recent items based on logged-in user's role.
 * @param {string} role - 'candidate' | 'employer'
 */
async function getNotifications(role) {
  const endpoint = role === 'employer' ? '/employer/notifications' : '/candidate/notifications';
  return apiFetch(endpoint);
}


/* ─── Export all API functions ────────────────────────────── */
const API = {
  register,
  login,
  logout,
  getMe,
  getJobs,
  getEmployers,
  getEmployer,
  getCandidates,
  getJob,
  getRecommendedJobs,
  applyForJob,
  getCandidateDashboard,
  getCandidateFavorites,
  addFavorite,
  removeFavorite,
  uploadCv,
  getEmployerDashboard,
  postJob,
  getEmployerApplications,
  getEmployerJobs,
  getCandidateProfile,
  updateCandidateProfile,
  getEmployerProfile,
  updateEmployerProfile,
  deleteAccount,
  getCandidateCvs,
  updateApplicationStatus,
  getNotifications,
};
