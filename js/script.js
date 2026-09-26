/* =====================================================
   Skill Swap — Main JavaScript
   ===================================================== */

(function () {
  'use strict';

  /* ── Dark / Light Mode ── */
  const darkToggleBtns = document.querySelectorAll('.dark-toggle');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const savedTheme = localStorage.getItem('ss-theme') || (prefersDark ? 'dark' : 'light');

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    darkToggleBtns.forEach(btn => btn.classList.toggle('active', theme === 'dark'));
    const icon = document.getElementById('themeIcon');
    if (icon) icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
  }

  applyTheme(savedTheme);

  darkToggleBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const current = document.documentElement.getAttribute('data-theme') || 'light';
      const next = current === 'dark' ? 'light' : 'dark';
      localStorage.setItem('ss-theme', next);
      applyTheme(next);
    });
  });

  /* ── Navbar scroll shadow ── */
  const navbar = document.querySelector('.navbar-custom');
  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.style.boxShadow = window.scrollY > 10
        ? '0 4px 30px rgba(0,0,0,.12)'
        : '0 2px 20px rgba(0,0,0,.06)';
    });
  }

  /* ── Toast helper ── */
  window.showToast = function (msg, type = 'success') {
    const container = document.getElementById('toastContainer') || (() => {
      const c = document.createElement('div');
      c.id = 'toastContainer';
      c.style.cssText = 'position:fixed;top:80px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:.5rem';
      document.body.appendChild(c);
      return c;
    })();

    const toast = document.createElement('div');
    const colors = { success: '#28a745', danger: '#dc3545', info: '#17a2b8', warning: '#ffc107' };
    toast.style.cssText = `
      background:${colors[type] || colors.success};color:#fff;
      padding:.75rem 1.25rem;border-radius:12px;font-size:.88rem;font-weight:600;
      box-shadow:0 6px 24px rgba(0,0,0,.2);animation:fadeUp .3s ease;
      display:flex;align-items:center;gap:.5rem;max-width:280px;
    `;
    const icons = { success: 'check-circle', danger: 'x-circle', info: 'info-circle', warning: 'exclamation-triangle' };
    toast.innerHTML = `<i class="bi bi-${icons[type]}"></i>${msg}`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(100%)'; toast.style.transition = '.3s'; setTimeout(() => toast.remove(), 300); }, 3500);
  };

  /* ── Tag / Chip input helper ── */
  window.initTagInput = function (inputId, tagContainerId, hiddenId) {
    const input = document.getElementById(inputId);
    const container = document.getElementById(tagContainerId);
    const hidden = document.getElementById(hiddenId);
    if (!input || !container) return;

    let tags = [];

    function render() {
      container.innerHTML = tags.map((t, i) => `
        <span class="badge-skill">${t}
          <button type="button" onclick="removeTag('${tagContainerId}','${hiddenId}',${i})" style="background:none;border:none;padding:0;margin-left:.2rem;cursor:pointer;color:inherit;line-height:1;">×</button>
        </span>`).join('');
      if (hidden) hidden.value = tags.join(',');
    }

    input.addEventListener('keydown', e => {
      if ((e.key === 'Enter' || e.key === ',') && input.value.trim()) {
        e.preventDefault();
        const val = input.value.trim().replace(/,$/, '');
        if (val && !tags.includes(val)) { tags.push(val); render(); }
        input.value = '';
      }
    });

    window[`${tagContainerId}_tags`] = tags;
    window.removeTag = function (cId, hId, idx) {
      const key = `${cId}_tags`;
      window[key].splice(idx, 1);
      render();
    };
  };

  /* ── Star Rating ── */
  window.initStarRating = function (containerId, inputId) {
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    if (!container || !input) return;

    container.innerHTML = [5, 4, 3, 2, 1].map(n => `
      <input type="radio" name="${inputId}" id="star${n}_${inputId}" value="${n}">
      <label for="star${n}_${inputId}" title="${n} stars"><i class="bi bi-star-fill"></i></label>
    `).join('');

    container.querySelectorAll('input').forEach(r => {
      r.addEventListener('change', () => { if (input) input.value = r.value; });
    });
  };

  /* ── Search filter ── */
  window.filterCards = function (query, selector) {
    const q = query.toLowerCase();
    document.querySelectorAll(selector).forEach(el => {
      const text = el.textContent.toLowerCase();
      el.closest('.col, [data-card]').style.display = text.includes(q) ? '' : 'none';
    });
  };

  /* ── Tab persistence ── */
  const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
  tabLinks.forEach(t => {
    t.addEventListener('shown.bs.tab', () => {
      localStorage.setItem('ss-active-tab-' + window.location.pathname, t.id);
    });
  });
  const savedTab = localStorage.getItem('ss-active-tab-' + window.location.pathname);
  if (savedTab) {
    const el = document.getElementById(savedTab);
    if (el) new bootstrap.Tab(el).show();
  }

  /* ── Animate on scroll (simple IntersectionObserver) ── */
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('fade-up');
        observer.unobserve(e.target);
      }
    });
  }, { threshold: 0.12 });

  document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));

  /* ── Skill autocomplete suggestions ── */
  const skillSuggestions = [
    'Python','JavaScript','React','Vue','Angular','Node.js','Java','C++','C#','Go',
    'Rust','TypeScript','PHP','Ruby','Swift','Kotlin','GraphQL','MongoDB','PostgreSQL','MySQL',
    'Docker','Kubernetes','AWS','GCP','Azure','Machine Learning','Deep Learning','Data Science',
    'Figma','Adobe XD','Photoshop','Illustrator','UI/UX Design','Graphic Design','Video Editing',
    'Photography','Music Production','Guitar','Piano','Singing','Writing','Blogging',
    'SEO','Digital Marketing','Social Media Marketing','Content Creation','Copywriting',
    'Public Speaking','Leadership','Project Management','Agile','Scrum','Excel','Power BI',
    'Tableau','Accounting','Financial Modelling','Chess','Yoga','Fitness Training',
    'Cooking','Baking','Language Learning','Spanish','French','Japanese','Mandarin','Arabic'
  ];
  window.skillSuggestions = skillSuggestions;

  window.initSkillAutocomplete = function (inputId, dropdownId) {
    const input = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);
    if (!input || !dropdown) return;

    input.addEventListener('input', () => {
      const q = input.value.toLowerCase();
      if (!q) { dropdown.style.display = 'none'; return; }
      const matches = skillSuggestions.filter(s => s.toLowerCase().includes(q)).slice(0, 6);
      if (!matches.length) { dropdown.style.display = 'none'; return; }
      dropdown.innerHTML = matches.map(m => `<div class="dropdown-item" onclick="selectSkill('${inputId}','${dropdownId}','${m}')">${m}</div>`).join('');
      dropdown.style.display = 'block';
    });

    document.addEventListener('click', e => {
      if (!input.contains(e.target) && !dropdown.contains(e.target)) dropdown.style.display = 'none';
    });
  };
  window.selectSkill = function (inputId, dropdownId, value) {
    document.getElementById(inputId).value = value;
    document.getElementById(dropdownId).style.display = 'none';
    document.getElementById(inputId).dispatchEvent(new Event('keydown', { key: 'Enter', bubbles: true }));
    // Trigger tag add
    const evt = new KeyboardEvent('keydown', { key: 'Enter', bubbles: true });
    document.getElementById(inputId).dispatchEvent(evt);
  };

  /* ── Notification dropdown mark read ── */
  document.querySelectorAll('.mark-read-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const badge = document.querySelector('.notification-badge');
      if (badge) badge.style.display = 'none';
      showToast('Notifications marked as read', 'info');
    });
  });

  /* ── Form validation helper ── */
  window.validateForm = function (formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    form.classList.add('was-validated');
    return form.checkValidity();
  };

})();
