/* ============================================================
   pages.js — Page-Specific Logic (API-driven pages only)
   Jobpilot Job Platform

   Pages using this file:
     - auth/login.html
     - auth/register.html
     - jobs/browse-jobs.html
     - jobs/single-job.html
     - candidate-dashboard/favorite-jobs.html

   Pages with their own inline scripts (not included here):
     - candidate-dashboard/dashboard.html      (inline — loadDashboard + loadRecommended)
     - employer-dashboard/dashboard.html       (inline — stats + recent apps)
     - employer-dashboard/applications.html    (inline — GET /api/employer/applications)
     - employer-dashboard/post-job.html        (inline — POST /api/employer/jobs)
     - All DEMO_DATA pages                     (inline — no API, DEMO_DATA isolated per page)
   ============================================================ */

/* ─── Detect current page ─────────────────────────────────── */
const currentPage = window.location.pathname.split('/').pop().replace('.html', '');


/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   AUTH — LOGIN  (POST /api/auth/login)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function initLoginPage() {
  const form = document.getElementById('login-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    Validate.clearAll(form);

    const email    = form.querySelector('[name="email"]');
    const password = form.querySelector('[name="password"]');
    let valid = true;

    if (!Validate.email(email.value)) {
      Validate.showError(email, 'Please enter a valid email address.');
      valid = false;
    }
    if (!Validate.minLength(password.value, 6)) {
      Validate.showError(password, 'Password must be at least 6 characters.');
      valid = false;
    }
    if (!valid) return;

    const btn = form.querySelector('[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Signing in\u2026';

    try {
      const result = await API.login({ email: email.value, password: password.value });
      Session.set(result.data);
      if (result.data.role === 'employer') {
        window.location.href = FRONTEND_BASE + '/pages/employer-dashboard/dashboard.html';
      } else {
        window.location.href = FRONTEND_BASE + '/pages/candidate-dashboard/dashboard.html';
      }
    } catch (err) {
      Toast.error(err.message || 'Login failed. Please check your credentials.');
      btn.disabled = false;
      btn.textContent = 'Sign In \u2192';
    }
  });
}


/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   AUTH — REGISTER  (POST /api/auth/register)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function initRegisterPage() {
  const form = document.getElementById('register-form');
  if (!form) return;

  // Role buttons sit outside the <form> tag — must query from document
  const roleBtns = document.querySelectorAll('.auth-role-btn');
  let selectedRole = document.querySelector('.auth-role-btn.active')?.getAttribute('data-role') || 'candidate';
  roleBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      roleBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      selectedRole = btn.getAttribute('data-role');
    });
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    Validate.clearAll(form);

    const fullName = form.querySelector('[name="full_name"]');
    const email    = form.querySelector('[name="email"]');
    const password = form.querySelector('[name="password"]');
    const confirm  = form.querySelector('[name="confirm_password"]');
    let valid = true;

    if (!Validate.required(fullName.value)) {
      Validate.showError(fullName, 'Full name is required.');
      valid = false;
    }
    if (!Validate.email(email.value)) {
      Validate.showError(email, 'Please enter a valid email address.');
      valid = false;
    }
    if (!Validate.minLength(password.value, 8)) {
      Validate.showError(password, 'Password must be at least 8 characters.');
      valid = false;
    }
    if (confirm && !Validate.match(password.value, confirm.value)) {
      Validate.showError(confirm, 'Passwords do not match.');
      valid = false;
    }
    if (!valid) return;

    const btn = form.querySelector('[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Creating account\u2026';

    try {
      await API.register({
        role:      selectedRole,
        full_name: fullName.value,
        email:     email.value,
        password:  password.value,
      });
      const role = selectedRole;
      Session.set(await API.getMe().then(r => r.data).catch(() => ({ role })));
      Toast.success('Account created! Redirecting to your dashboard\u2026');
      setTimeout(() => {
        const dest = role === 'employer'
          ? FRONTEND_BASE + '/pages/employer-dashboard/dashboard.html'
          : FRONTEND_BASE + '/pages/candidate-dashboard/dashboard.html';
        window.location.href = dest;
      }, 1200);
    } catch (err) {
      Toast.error(err.message || 'Registration failed. Please try again.');
      btn.disabled = false;
      btn.textContent = 'Create Account \u2192';
    }
  });
}


/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   BROWSE JOBS  (GET /api/jobs)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function initBrowseJobsPage() {
  const grid = document.getElementById('jobs-grid');
  if (!grid) return;

  let allJobs = [];
  const PER_PAGE = 9;

  // Populate search inputs from URL params (e.g. hero search → ?q=React&location=Amman)
  const _urlParams = new URLSearchParams(window.location.search);
  const _urlQ   = _urlParams.get('q')        || _urlParams.get('search') || '';
  const _urlLoc = _urlParams.get('location') || '';
  const searchInput   = document.getElementById('job-search');
  const locationInput = document.getElementById('job-location');
  if (searchInput   && _urlQ)   searchInput.value   = _urlQ;
  if (locationInput && _urlLoc) locationInput.value = _urlLoc;

  function bindBookmarks() {
    grid.querySelectorAll('.job-card__bookmark').forEach(btn => {
      btn.addEventListener('click', (e) => { e.stopPropagation(); btn.classList.toggle('active'); });
    });
  }

  function renderPage(p) {
    const start = (p - 1) * PER_PAGE;
    const items = allJobs.slice(start, start + PER_PAGE);
    grid.innerHTML = items.length
      ? items.map(renderJobCard).join('')
      : '<div class="empty-state"><p class="empty-state__text">No jobs found.</p></div>';
    bindBookmarks();
    const pager = document.getElementById('pagination');
    if (pager) renderPagination(pager, Math.ceil(allJobs.length / PER_PAGE), p, renderPage);
  }

  function buildFilters() {
    const f = {};
    const q = (document.getElementById('job-search')?.value || '').trim();
    if (q) f.search = q;
    const loc = (document.getElementById('job-location')?.value || '').trim();
    if (loc) f.location = loc;
    // Job type — checkboxes have data-type attribute; value attr is "on" for checkbox so read data-type
    const typeEl = document.querySelector('[data-type]:checked, .filter-type input:checked');
    const type = typeEl ? (typeEl.dataset.type || typeEl.value) : '';
    if (type) f.type = type;
    // Category — checkboxes have data-industry attribute
    const catEl = document.querySelector('[data-industry]:checked, .filter-category input:checked');
    const cat = catEl ? (catEl.dataset.industry || catEl.value) : '';
    if (cat) f.category = cat;
    // Salary range
    const salaryEl = document.getElementById('salary-range');
    if (salaryEl && parseInt(salaryEl.value) < parseInt(salaryEl.max || 120000)) {
      f.salary_min = salaryEl.value;
    }
    // Sort
    const sortEl = document.getElementById('sort-select');
    if (sortEl && sortEl.value) f.sort = sortEl.value;
    return f;
  }

  async function load(filters = {}) {
    grid.innerHTML = '<div class="job-card"><div class="skeleton skeleton-title" style="width:55%"></div><div class="skeleton skeleton-text" style="width:75%;margin-top:8px"></div></div>'.repeat(3);
    try {
      const result = await API.getJobs({ limit: 100, ...filters });
      // /api/jobs returns a raw array; apiFetch normalizes to { success, data: [...] }
      allJobs = Array.isArray(result.data) ? result.data : [];
      const countEl = document.getElementById('jobs-count');
      if (countEl) countEl.textContent = allJobs.length;
      renderPage(1);
    } catch {
      grid.innerHTML = '<div class="empty-state"><p class="empty-state__text">Failed to load jobs. Please try again.</p></div>';
    }
  }

  const searchBtn = document.getElementById('search-jobs-btn');
  if (searchBtn) {
    searchBtn.addEventListener('click', () => load(buildFilters()));
  }
  // Also trigger search on Enter key in search inputs
  [searchInput, locationInput].forEach(inp => {
    if (inp) inp.addEventListener('keydown', (e) => { if (e.key === 'Enter') load(buildFilters()); });
  });

  // Apply Filters button
  const applyBtn = document.getElementById('apply-filters-btn');
  if (applyBtn) applyBtn.addEventListener('click', () => load(buildFilters()));

  // Re-run when sidebar checkboxes change
  document.querySelectorAll('[data-type], [data-industry]').forEach(cb => {
    cb.addEventListener('change', () => load(buildFilters()));
  });

  // Salary range display
  const salaryRange = document.getElementById('salary-range');
  if (salaryRange) {
    const salDisplay = document.getElementById('salary-display') || document.getElementById('salary-min');
    salaryRange.addEventListener('input', function() {
      const v = parseInt(this.value);
      if (salDisplay) salDisplay.textContent = v >= parseInt(this.max || 120000) ? 'All salaries' : v.toLocaleString() + ' JOD+';
    });
    salaryRange.addEventListener('change', () => load(buildFilters()));
  }

  // Sort dropdown
  const sortSel = document.getElementById('sort-select');
  if (sortSel) sortSel.addEventListener('change', () => load(buildFilters()));

  // Popular search tags — populate search input and run search
  document.querySelectorAll('.tag-list .tag').forEach(tag => {
    tag.addEventListener('click', () => {
      if (searchInput) {
        searchInput.value = tag.textContent.trim();
        load(buildFilters());
      }
    });
  });

  // Run initial load (uses URL params already set into inputs above)
  load(_urlQ || _urlLoc ? buildFilters() : {});
}


/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   SINGLE JOB  (GET /api/jobs/{id}  +  POST /api/jobs/{id}/apply)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function initSingleJobPage() {
  const params = new URLSearchParams(window.location.search);
  const jobId  = params.get('id');

  const setTxt  = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
  const setHTML = (id, val) => { const el = document.getElementById(id); if (el) el.innerHTML  = val; };

  async function loadJob() {
    if (!jobId) return;
    try {
      const result = await API.getJob(jobId);
      const job = result.data;
      document.title = `${job.title} \u2014 Jobpilot`;
      setTxt('breadcrumb-title', job.title);
      setTxt('job-title', job.title);
      setTxt('job-company', job.company || '');
      setHTML('job-location',
        `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">` +
        `<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> ` +
        `${job.location || '\u2014'}`);
      const descEl = document.getElementById('job-description');
      if (descEl) {
        const p = document.createElement('p');
        p.textContent = job.description || 'No description available.';
        descEl.innerHTML = '';
        descEl.appendChild(p);
      }
      const logoEl = document.getElementById('job-logo');
      if (logoEl) logoEl.textContent = (job.company || '?').charAt(0).toUpperCase();
      if (job.salary) {
        setTxt('sidebar-salary', job.salary);
        setTxt('ov-salary', job.salary);
      }
      setTxt('ov-location', job.location || '\u2014');
    } catch {
      Toast.error('Failed to load job details.');
    }
  }

  async function loadRelated() {
    if (!jobId) return;
    try {
      const result  = await API.getJobs({ limit: 4 });
      const related = (result.data || []).filter(j => String(j.id) !== String(jobId)).slice(0, 3);
      const el = document.getElementById('related-jobs');
      if (el && related.length) el.innerHTML = related.map(renderJobCard).join('');
    } catch { /* silent */ }
  }

  function initApplyForm() {
    const form = document.getElementById('apply-form');
    if (!form || !jobId) return;
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const cover = document.getElementById('apply-cover');
      const cvEl  = document.getElementById('apply-cv');
      if (!cover.value.trim()) { Toast.error('Please write a cover letter.'); return; }
      /* Button may be outside the form using form="apply-form" */
      const btn = document.querySelector('[form="apply-form"][type="submit"]')
               || form.querySelector('[type="submit"]');
      if (btn) btn.disabled = true;
      try {
        await API.applyForJob(jobId, {
          cv_id:        cvEl ? parseInt(cvEl.value) : null,
          cover_letter: cover.value,
        });
        Modal.close('apply-modal');
        Toast.success('Application submitted!');
      } catch (err) {
        Toast.error(err.message || 'Failed to apply.');
        if (btn) btn.disabled = false;
      }
    });
  }

  function initBookmarkBtns() {
    const bm1 = document.getElementById('bookmark-btn');
    if (bm1) bm1.addEventListener('click', function() { this.classList.toggle('active'); });
    const bm2 = document.getElementById('sidebar-bookmark');
    if (bm2) bm2.addEventListener('click', function() {
      this.textContent = this.textContent.includes('Save') ? '\u2713 Saved' : 'Save Job';
    });
  }

  loadJob();
  loadRelated();
  initApplyForm();
  initBookmarkBtns();
}


/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   FAVORITE JOBS  (GET /api/candidate/favorites)
   No DELETE /api/candidate/favorites/{id} in API spec —
   removals are in-memory only for the current session.
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function initFavoriteJobsPage() {
  const user = Session.get();
  if (user) {
    const initials = user.name
      ? user.name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
      : 'U';
    const navAvatar  = document.getElementById('nav-user-avatar');
    const sideAvatar = document.getElementById('sidebar-avatar');
    const sideName   = document.getElementById('sidebar-name');
    if (navAvatar)  navAvatar.textContent  = initials;
    if (sideAvatar) sideAvatar.textContent = initials;
    if (sideName)   sideName.textContent   = user.name || 'Candidate';
  }

  const ACCENT_COLORS = ['#0A65CC','#0BA02C','#FF6550','#4640DE','#E05151','#F59E0B'];
  function colorFor(str) {
    let h = 0;
    for (const c of String(str)) h = (h * 31 + c.charCodeAt(0)) & 0xffff;
    return ACCENT_COLORS[h % ACCENT_COLORS.length];
  }

  const removedIds = new Set();
  let allFavorites = [];
  let favPage      = 1;
  const PAGE_SIZE  = 4;

  function getVisible() { return allFavorites.filter(j => !removedIds.has(j.id)); }

  function renderCard(job) {
    const color = job.color || colorFor(job.company || job.title);
    const logo  = job.logo  || String(job.company || job.title).substring(0, 2).toUpperCase();
    const jobId = job.job_id || job.jobId || job.id;
    const type  = job.type  || job.job_type || 'Full Time';
    return `
      <div style="background:#fff;border:1px solid var(--clr-border);border-radius:var(--r-lg);padding:var(--sp-5);display:flex;align-items:center;gap:var(--sp-4);flex-wrap:wrap">
        <div style="width:52px;height:52px;border-radius:var(--r-md);background:${color}20;color:${color};display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;flex-shrink:0">${logo}</div>
        <div style="flex:1;min-width:160px">
          <a href="../jobs/single-job.html?id=${jobId}" style="font-size:15px;font-weight:600;color:var(--clr-text);text-decoration:none;display:block;margin-bottom:4px">${job.title}</a>
          <div style="display:flex;align-items:center;gap:var(--sp-3);flex-wrap:wrap;font-size:13px;color:var(--clr-text-3)">
            ${job.company  ? `<span>${job.company}</span>`  : ''}
            ${job.location ? `<span>${job.location}</span>` : ''}
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:var(--sp-3);flex-wrap:wrap">
          <span style="font-size:12px;font-weight:500;padding:4px 12px;border-radius:20px;background:var(--clr-primary-light);color:var(--clr-primary)">${type}</span>
          ${job.salary ? `<span style="font-size:13px;font-weight:600;color:var(--clr-text)">${job.salary}</span>` : ''}
          <a href="../jobs/single-job.html?id=${jobId}" class="btn btn-primary btn-sm">Apply Now</a>
          <button class="btn btn-ghost btn-sm remove-fav-btn" data-id="${job.id}" style="color:var(--clr-danger)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" fill="currentColor"/></svg>
            Remove
          </button>
        </div>
      </div>`;
  }

  function doRenderPage() {
    const visible = getVisible();
    const start   = (favPage - 1) * PAGE_SIZE;
    const items   = visible.slice(start, start + PAGE_SIZE);
    const grid    = document.getElementById('favorites-grid');
    const countEl = document.getElementById('fav-count');
    if (countEl) countEl.textContent = `${visible.length} saved job${visible.length !== 1 ? 's' : ''}`;
    if (!grid) return;
    if (!visible.length) {
      grid.innerHTML = `<div class="empty-state">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        <h3>No favorite jobs yet</h3>
        <p><a href="../jobs/browse-jobs.html" style="color:var(--clr-primary)">Browse jobs</a> and save ones you like.</p>
      </div>`;
    } else {
      grid.innerHTML = items.map(renderCard).join('');
      grid.querySelectorAll('.remove-fav-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          removedIds.add(parseInt(btn.dataset.id));
          Toast.info('Removed from favorites.');
          const newVisible = getVisible();
          const newTotal   = Math.ceil(newVisible.length / PAGE_SIZE);
          if (favPage > newTotal && favPage > 1) favPage--;
          doRenderPage();
        });
      });
    }
    const pager = document.getElementById('pagination-container');
    if (pager) renderPagination(pager, Math.ceil(visible.length / PAGE_SIZE), favPage,
      p => { favPage = p; doRenderPage(); window.scrollTo(0, 0); });
  }

  const grid = document.getElementById('favorites-grid');
  if (grid) grid.innerHTML = `
    <div class="skeleton" style="height:80px;border-radius:var(--r-lg)"></div>
    <div class="skeleton" style="height:80px;border-radius:var(--r-lg)"></div>
    <div class="skeleton" style="height:80px;border-radius:var(--r-lg)"></div>`;

  (async () => {
    try {
      const res = await API.getCandidateFavorites();
      allFavorites = Array.isArray(res.data) ? res.data : [];
      doRenderPage();
    } catch {
      const g = document.getElementById('favorites-grid');
      if (g) g.innerHTML = `<div class="empty-state">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        <h3>Could not load favorites</h3>
        <p>Unable to fetch your saved jobs. Please try again later.</p>
      </div>`;
      const c = document.getElementById('fav-count');
      if (c) c.textContent = '\u2014 saved jobs';
    }
  })();
}


/* ─── Filter tag selection (browse pages) ────────────────── */
function initFilterTags() {
  // Only adds visual active-state toggling; browse-jobs initBrowseJobsPage handles the search trigger
  document.querySelectorAll('.tag-list .tag').forEach(tag => {
    tag.addEventListener('click', () => {
      document.querySelectorAll('.tag-list .tag').forEach(t => t.classList.remove('active'));
      tag.classList.add('active');
    });
  });
}


/* ─── Auto-init based on current page ────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  switch (currentPage) {
    case 'login':         initLoginPage();         break;
    case 'register':      initRegisterPage();      break;
    case 'browse-jobs':   initBrowseJobsPage();    break;
    case 'single-job':    initSingleJobPage();     break;
    // favorite-jobs: handled by inline script in favorite-jobs.html
  }
  initFilterTags();
});
