/* Jobpilot — Main JS */
(function () {
  'use strict';

  // ─── Dark mode toggle ─────────────────────────────────────────────────
  const darkToggle = document.getElementById('dark-toggle');
  if (darkToggle) {
    darkToggle.addEventListener('click', () => {
      const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      if (isDark) {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('jp_theme', 'light');
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('jp_theme', 'dark');
      }
    });
  }

  // ─── Mobile nav ───────────────────────────────────────────────────────
  const mobileBtn = document.getElementById('mobile-menu-btn');
  const mainNav   = document.getElementById('main-nav');
  if (mobileBtn && mainNav) {
    mobileBtn.addEventListener('click', () => {
      mainNav.classList.toggle('open');
    });
    document.addEventListener('click', (e) => {
      if (!mobileBtn.contains(e.target) && !mainNav.contains(e.target)) {
        mainNav.classList.remove('open');
      }
    });
  }

  // ─── Action dropdowns ─────────────────────────────────────────────────
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.action-menu-btn');
    if (btn) {
      e.stopPropagation();
      const dropdown = btn.nextElementSibling;
      if (dropdown && dropdown.classList.contains('action-dropdown')) {
        // Close all others
        document.querySelectorAll('.action-dropdown.open').forEach(d => {
          if (d !== dropdown) d.classList.remove('open');
        });
        dropdown.classList.toggle('open');
      }
      return;
    }
    // Close all dropdowns when clicking elsewhere
    document.querySelectorAll('.action-dropdown.open').forEach(d => d.classList.remove('open'));
  });

  // ─── Bookmark / Save job ──────────────────────────────────────────────
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.bookmark-btn');
    if (!btn) return;
    const jobId = btn.dataset.jobId;
    if (!jobId) return;
    fetch('/jobpilot/actions/save-job.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'job_id=' + jobId + '&csrf_token=' + (document.querySelector('meta[name=csrf]')?.content || '')
    })
      .then(r => r.json())
      .then(data => {
        if (data.saved !== undefined) {
          btn.classList.toggle('saved', data.saved);
          const icon = btn.querySelector('svg path');
          if (icon) icon.setAttribute('fill', data.saved ? 'currentColor' : 'none');
        }
      })
      .catch(() => {});
  });

  // ─── Password toggle ──────────────────────────────────────────────────
  document.querySelectorAll('.password-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.parentElement.querySelector('input');
      if (!input) return;
      if (input.type === 'password') {
        input.type = 'text';
        btn.querySelector('svg')?.setAttribute('data-show', '1');
      } else {
        input.type = 'password';
        btn.querySelector('svg')?.removeAttribute('data-show');
      }
    });
  });

  // ─── Role toggle (register page) ──────────────────────────────────────
  document.querySelectorAll('.role-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const role = btn.dataset.role;
      const roleInput = document.getElementById('role-input');
      if (roleInput) roleInput.value = role;
    });
  });

  // ─── Modals ───────────────────────────────────────────────────────────
  document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.modalOpen;
      const modal = document.getElementById(id);
      if (modal) modal.classList.add('open');
    });
  });
  document.querySelectorAll('.modal-close, .modal-overlay').forEach(el => {
    el.addEventListener('click', (e) => {
      if (e.target === el) {
        el.closest('.modal-overlay')?.classList.remove('open');
        if (el.classList.contains('modal-overlay')) el.classList.remove('open');
      }
    });
  });

  // ─── Upload area click ────────────────────────────────────────────────
  document.querySelectorAll('.upload-area').forEach(area => {
    const input = area.querySelector('input[type=file]');
    if (!input) return;
    area.addEventListener('click', () => input.click());
    area.addEventListener('dragover', (e) => { e.preventDefault(); area.style.borderColor = '#0A65CC'; });
    area.addEventListener('dragleave', () => { area.style.borderColor = ''; });
    area.addEventListener('drop', (e) => {
      e.preventDefault();
      area.style.borderColor = '';
      const files = e.dataTransfer.files;
      if (files.length) {
        const dt = new DataTransfer();
        dt.items.add(files[0]);
        input.files = dt.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
    input.addEventListener('change', () => {
      if (input.files[0]) {
        const hint = area.querySelector('.upload-text small, .upload-hint');
        if (hint) hint.textContent = input.files[0].name;
      }
    });
  });

  // ─── Simple inline RTE toolbar ────────────────────────────────────────
  document.querySelectorAll('.rte-toolbar button').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const cmd = btn.dataset.cmd;
      if (cmd) document.execCommand(cmd, false, null);
      btn.closest('.rte-wrap')?.querySelector('.rte-content')?.focus();
    });
  });
  // Sync contenteditable to hidden textarea
  document.querySelectorAll('.rte-content').forEach(el => {
    const target = document.getElementById(el.dataset.target);
    if (!target) return;
    el.addEventListener('input', () => { target.value = el.innerHTML; });
  });

  // ─── Setup step navigation ────────────────────────────────────────────
  document.querySelectorAll('.setup-step-item[data-step]').forEach(item => {
    item.addEventListener('click', () => {
      const step = item.dataset.step;
      const form = document.getElementById('setup-form');
      if (form) {
        const input = form.querySelector('[name=goto_step]');
        if (input) { input.value = step; form.submit(); }
      }
    });
  });

  // ─── Flash auto-dismiss ───────────────────────────────────────────────
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.4s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 400);
    }, 5000);
  });

  // ─── Confirm delete ───────────────────────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
      if (!window.confirm(el.dataset.confirm || 'Are you sure?')) {
        e.preventDefault();
      }
    });
  });

})();
