/* Jobpilot — Dashboard JS */
(function () {
  'use strict';

  // ─── Sidebar toggle (mobile) ──────────────────────────────────────────
  const sidebarToggle = document.getElementById('sidebar-toggle');
  const sidebar = document.querySelector('.dashboard-sidebar');
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });
    document.addEventListener('click', (e) => {
      if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  // ─── Dashboard search (redirect to find-job) ─────────────────────────
  const dashSearch = document.getElementById('dash-search-input');
  if (dashSearch) {
    dashSearch.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && dashSearch.value.trim()) {
        window.location.href = '/jobpilot/jobs/find-job.php?q=' + encodeURIComponent(dashSearch.value.trim());
      }
    });
  }

  // ─── Notification dropdown ────────────────────────────────────────────
  const notifBtn = document.getElementById('notif-btn');
  if (notifBtn) {
    notifBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const panel = document.getElementById('notif-panel');
      if (panel) {
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) {
          // Mark as read
          fetch('/jobpilot/actions/mark-notifications.php', { method: 'POST' })
            .then(() => {
              const badge = notifBtn.querySelector('.dh-notification-badge');
              if (badge) badge.remove();
            })
            .catch(() => {});
        }
      }
    });
    document.addEventListener('click', (e) => {
      if (!notifBtn.contains(e.target)) {
        document.getElementById('notif-panel')?.classList.remove('open');
      }
    });
  }

  // ─── Tab switching (settings) ─────────────────────────────────────────
  document.querySelectorAll('.settings-tab[data-tab]').forEach(tab => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.tab;
      // If it's a link-style tab (href), let it navigate
      if (tab.tagName === 'A') return;
      document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      const panel = document.getElementById('tab-' + target);
      if (panel) panel.classList.add('active');
    });
  });

  // ─── Job status toggle (my-jobs) ─────────────────────────────────────
  document.querySelectorAll('.mark-expired-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const jobId = btn.dataset.jobId;
      if (!jobId) return;
      if (!confirm('Mark this job as expired?')) return;
      fetch('/jobpilot/actions/update-job-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'job_id=' + jobId + '&status=expired'
      })
        .then(r => r.json())
        .then(data => { if (data.ok) location.reload(); })
        .catch(() => {});
    });
  });

  // ─── CV actions dropdown ──────────────────────────────────────────────
  document.querySelectorAll('.cv-menu-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const dropdown = btn.nextElementSibling;
      if (dropdown) {
        document.querySelectorAll('.action-dropdown.open').forEach(d => {
          if (d !== dropdown) d.classList.remove('open');
        });
        dropdown.classList.toggle('open');
      }
    });
  });

  // ─── Application status change ────────────────────────────────────────
  document.querySelectorAll('.status-select').forEach(sel => {
    sel.addEventListener('change', () => {
      const appId  = sel.dataset.appId;
      const status = sel.value;
      fetch('/jobpilot/actions/update-application-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'application_id=' + appId + '&status=' + encodeURIComponent(status)
      })
        .then(r => r.json())
        .then(data => {
          if (data.ok) {
            const badge = sel.closest('td')?.querySelector('.status-badge');
            if (badge) {
              badge.className = 'status-badge status-' + status;
              badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            }
          }
        })
        .catch(() => {});
    });
  });

})();
