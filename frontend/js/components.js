/* ============================================================
   components.js — Dynamic Component Rendering
   Jobpilot Job Platform
   ============================================================ */

/* ─── Badge class helper ──────────────────────────────────── */
function getBadgeClass(status) {
  const map = {
    pending:  'badge-pending',
    reviewed: 'badge-part-time',
    accepted: 'badge-active',
    rejected: 'badge-expired',
    active:   'badge-active',
    closed:   'badge-expired',
  };
  return map[(status || '').toLowerCase()] || 'badge-pending';
}

/* ─── Job Card renderer ───────────────────────────────────── */
function renderJobCard(job) {
  const typeClass = (job.type || 'full-time').toLowerCase().replace(' ', '-');
  const typeLabel = job.type || 'Full Time';

  const logoHtml = job.company_logo
    ? `<img src="${job.company_logo}" alt="${job.company}" class="job-card__logo">`
    : `<div class="job-card__logo-placeholder">${(job.company || 'C').charAt(0)}</div>`;

  return `
    <div class="job-card" data-job-id="${job.id}">
      <div class="job-card__header">
        <span class="badge badge-${typeClass}">${typeLabel}</span>
        <button class="job-card__bookmark" title="Save job" aria-label="Save job">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
          </svg>
        </button>
      </div>

      <h3 class="job-card__title">
        <a href="${FRONTEND_BASE}/pages/jobs/single-job.html?id=${job.id}">${job.title}</a>
      </h3>

      <div class="job-card__company">
        ${logoHtml}
        <span class="job-card__company-name">${job.company || ''}</span>
      </div>

      <div class="job-card__meta">
        ${job.location ? `
        <span class="job-card__meta-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
          </svg>
          ${job.location}
        </span>` : ''}
        ${job.salary ? `
        <span class="job-card__meta-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
          </svg>
          ${job.salary}
        </span>` : ''}
      </div>
    </div>
  `;
}


/* ─── Employer Card renderer ──────────────────────────────── */
function renderEmployerCard(employer) {
  const logoHtml = employer.logo
    ? `<img src="${employer.logo}" alt="${employer.name}" class="employer-card__logo">`
    : `<div class="employer-card__logo" style="width:52px;height:52px;border-radius:var(--r-md);background:var(--clr-primary-light);display:flex;align-items:center;justify-content:center;font-size:var(--text-lg);font-weight:700;color:var(--clr-primary);margin:0 auto var(--sp-3);">${(employer.name || 'E').charAt(0)}</div>`;

  return `
    <div class="employer-card">
      ${logoHtml}
      <h3 class="employer-card__name">${employer.name || ''}</h3>
      <p class="employer-card__industry">${employer.industry || ''}</p>
      ${employer.type ? `<span class="employer-card__type">${employer.type}</span>` : ''}
      <a href="${FRONTEND_BASE}/pages/employers/single-employer.html?id=${employer.id}" class="employer-card__jobs">
        Open Position (${employer.open_positions || 0})
      </a>
    </div>
  `;
}


/* ─── Candidate Card renderer ─────────────────────────────── */
function renderCandidateCard(candidate) {
  const avatarHtml = candidate.avatar
    ? `<img src="${candidate.avatar}" alt="${candidate.name}" class="candidate-card__avatar">`
    : `<div class="candidate-card__avatar-placeholder">${(candidate.name || 'C').charAt(0)}</div>`;

  return `
    <div class="candidate-card">
      ${avatarHtml}
      <div class="candidate-card__info">
        <h3 class="candidate-card__name">${candidate.name || ''}</h3>
        <p class="candidate-card__role">${candidate.role || ''}</p>
        <div class="candidate-card__meta">
          ${candidate.location ? `
          <span class="candidate-card__meta-item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
            </svg>
            ${candidate.location}
          </span>` : ''}
          ${candidate.experience ? `
          <span class="candidate-card__meta-item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
            </svg>
            ${candidate.experience}
          </span>` : ''}
        </div>
      </div>
      <div class="candidate-card__actions">
        <button class="candidate-card__bookmark" title="Save candidate" aria-label="Save candidate">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
          </svg>
        </button>
        <a href="${FRONTEND_BASE}/pages/candidates/single-candidate.html?id=${candidate.id}" class="btn btn-outline btn-sm">
          View Profile →
        </a>
      </div>
    </div>
  `;
}


/* ─── Stat Card renderer ──────────────────────────────────── */
function renderStatCard(value, label, iconHtml, colorClass = 'blue') {
  return `
    <div class="stat-card">
      <div class="stat-card__icon stat-card__icon--${colorClass}">
        ${iconHtml}
      </div>
      <div>
        <div class="stat-card__value">${value}</div>
        <div class="stat-card__label">${label}</div>
      </div>
    </div>
  `;
}


/* ─── Badge renderer ──────────────────────────────────────── */
function renderBadge(type) {
  const map = {
    'full time':   'badge-full-time',
    'part time':   'badge-part-time',
    'internship':  'badge-internship',
    'remote':      'badge-remote',
    'contract':    'badge-contract',
    'temporary':   'badge-temporary',
    'freelance':   'badge-contract',
  };
  const cls = map[(type || '').toLowerCase()] || 'badge-full-time';
  return `<span class="badge ${cls}">${type}</span>`;
}


/* ─── Application row renderer ────────────────────────────── */
function renderApplicationRow(app) {
  return `
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:var(--sp-3)">
          <div class="job-card__logo-placeholder">${(app.company || 'C').charAt(0)}</div>
          <div>
            <div class="fw-semibold text-sm">${app.job_title || ''}</div>
            <div class="text-muted text-xs">${app.company || ''}</div>
          </div>
        </div>
      </td>
      <td class="text-sm text-muted">${formatDate ? formatDate(app.date_applied) : (app.date_applied || '')}</td>
      <td><span class="badge ${getBadgeClass(app.status)}">${app.status || ''}</span></td>
      <td>
        <a href="${FRONTEND_BASE}/pages/candidate-dashboard/applied-jobs.html" class="btn btn-outline btn-sm">View Details</a>
      </td>
    </tr>
  `;
}


/* ─── Pagination helper (alias for main.js) ───────────────── */
function buildPagination(totalPages, currentPage, onPageChange) {
  const container = document.querySelector('.pagination');
  if (container && typeof renderPagination === 'function') {
    renderPagination(container, totalPages, currentPage, onPageChange);
  }
}


/* ─── Grid renderer ───────────────────────────────────────── */
function renderGrid(items, renderFn, containerSelector, emptyMessage = 'No results found.') {
  const container = document.querySelector(containerSelector);
  if (!container) return;

  if (!items || items.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <svg class="empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <h3 class="empty-state__title">Nothing here yet</h3>
        <p class="empty-state__text">${emptyMessage}</p>
      </div>
    `;
    return;
  }

  container.innerHTML = items.map(renderFn).join('');
}
