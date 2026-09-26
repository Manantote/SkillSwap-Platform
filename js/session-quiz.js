/**
 * session-quiz.js
 * Frontend logic for Session Tracking + Quiz + Certificate flow.
 * Include AFTER firebase-config.js and script.js on any page.
 */

const API = {
  startSession:       '../api/start_session.php',
  endSession:         '../api/end_session.php',
  getUserSessions:    '../api/get_user_sessions.php',
  generateQuiz:       '../api/generate_quiz.php',
  submitQuiz:         '../api/submit_quiz.php',
  generateCertificate:'../api/generate_certificate.php',
};

// ── Auth helper ──────────────────────────────────────────────────────────────
async function getUID() {
  return new Promise(resolve => {
    const check = () => {
      if (window.currentFirebaseUser) resolve(window.currentFirebaseUser.uid);
      else setTimeout(check, 100);
    };
    check();
  });
}

async function authFetch(url, options = {}) {
  const uid = await getUID();
  options.headers = { ...(options.headers || {}), 'X-Firebase-UID': uid, 'Content-Type': 'application/json' };
  const res = await fetch(url, options);
  return res.json();
}

// ── State ────────────────────────────────────────────────────────────────────
const SessionState = {
  activeSessionId: parseInt(localStorage.getItem('ss_active_session')) || null,
  activeSkillId:   parseInt(localStorage.getItem('ss_active_skill'))   || null,
  sessionTimer:    null,
  sessionStart:    localStorage.getItem('ss_session_start') ? new Date(localStorage.getItem('ss_session_start')) : null,
};

// ── Utility ──────────────────────────────────────────────────────────────────
function ssToast(msg, type = 'success') {
  if (typeof showToast === 'function') { showToast(msg, type); return; }
  alert(msg);
}

function fmtMinutes(m) {
  if (m < 60) return `${m} min`;
  return `${Math.floor(m/60)}h ${m % 60}m`;
}

// ════════════════════════════════════════════════════════════════════════════
// 1.  SESSION CONTROLS
// ════════════════════════════════════════════════════════════════════════════

/**
 * Call this from the "Start Session" button.
 * @param {string} teacherUID   Firebase UID of the teacher
 * @param {number} skillId      Skill ID (0 for generic)
 * @param {string} btnId        Optional – button element id to disable
 */
window.startLearningSession = async function(teacherUID, skillId = 0, btnId = null) {
  const btn = btnId ? document.getElementById(btnId) : null;
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Starting…'; }

  try {
    const data = await authFetch(API.startSession, {
      method: 'POST',
      body: JSON.stringify({ teacher_uid: teacherUID, skill_id: skillId }),
    });

    if (!data.success) {
      ssToast(typeof data.error === 'string' ? data.error : data.error?.reason || 'Could not start session.', 'danger');
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i>Start Session'; }
      return;
    }

    // Persist state
    SessionState.activeSessionId = data.data.session_id;
    SessionState.activeSkillId   = skillId;
    SessionState.sessionStart    = new Date();
    localStorage.setItem('ss_active_session', data.data.session_id);
    localStorage.setItem('ss_active_skill',   skillId);
    localStorage.setItem('ss_session_start',  SessionState.sessionStart.toISOString());

    ssToast('Session started! Opening Google Meet…', 'success');

    // Open Meet link in new tab
    window.open(data.data.meet_link, '_blank');

    // Update UI
    updateSessionUI('active');
    startSessionTimer();

  } catch(e) {
    ssToast('Network error. Please try again.', 'danger');
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i>Start Session'; }
  }
};

/**
 * Call from "End Session" button.
 */
window.endLearningSession = async function(btnId = null) {
  if (!SessionState.activeSessionId) {
    ssToast('No active session found.', 'warning');
    return;
  }

  const btn = btnId ? document.getElementById(btnId) : null;
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Ending…'; }

  try {
    const data = await authFetch(API.endSession, {
      method: 'POST',
      body: JSON.stringify({ session_id: SessionState.activeSessionId }),
    });

    if (!data.success) {
      ssToast(data.error || 'Could not end session.', 'danger');
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-stop-circle-fill me-2"></i>End Session'; }
      return;
    }

    const result = data.data;

    // Clear state
    clearSessionTimer();
    SessionState.activeSessionId = null;
    SessionState.sessionStart    = null;
    localStorage.removeItem('ss_active_session');
    localStorage.removeItem('ss_active_skill');
    localStorage.removeItem('ss_session_start');

    if (result.valid) {
      ssToast(`✅ Session complete! ${result.duration_minutes} minutes logged.`, 'success');

      const prog = result.progress;
      if (prog) {
        updateProgressUI(prog);
        if (prog.status === 'completed') {
          ssToast('🎉 You have completed this skill! Take the quiz to earn your certificate.', 'success');
          showCertificateButton();
        }
      }
    } else {
      ssToast(`⚠️ Session too short (${result.duration_minutes} min). Minimum 15 minutes required.`, 'warning');
    }

    updateSessionUI('idle');

  } catch(e) {
    ssToast('Network error. Please try again.', 'danger');
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-stop-circle-fill me-2"></i>End Session'; }
  }
};

// ── Timer ────────────────────────────────────────────────────────────────────
function startSessionTimer() {
  clearSessionTimer();
  const el = document.getElementById('ss-timer');
  if (!el) return;

  SessionState.sessionTimer = setInterval(() => {
    if (!SessionState.sessionStart) return;
    const elapsed = Math.floor((Date.now() - SessionState.sessionStart.getTime()) / 1000);
    const m = Math.floor(elapsed / 60);
    const s = elapsed % 60;
    el.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
  }, 1000);
}

function clearSessionTimer() {
  if (SessionState.sessionTimer) {
    clearInterval(SessionState.sessionTimer);
    SessionState.sessionTimer = null;
  }
}

// ── UI state helpers ─────────────────────────────────────────────────────────
function updateSessionUI(state) {
  const startBtn  = document.getElementById('ss-start-btn');
  const endBtn    = document.getElementById('ss-end-btn');
  const timerBox  = document.getElementById('ss-timer-box');
  const statusDot = document.getElementById('ss-status-dot');

  if (state === 'active') {
    if (startBtn)  { startBtn.disabled = true;  startBtn.style.opacity = '.5'; }
    if (endBtn)    { endBtn.disabled = false;   endBtn.style.opacity = '1'; }
    if (timerBox)  timerBox.style.display = 'flex';
    if (statusDot) { statusDot.textContent = '🟢 Session Active'; statusDot.className = 'ss-status-dot active'; }
  } else {
    if (startBtn)  { startBtn.disabled = false; startBtn.style.opacity = '1'; }
    if (endBtn)    { endBtn.disabled = true;    endBtn.style.opacity = '.5'; }
    if (timerBox)  timerBox.style.display = 'none';
    if (statusDot) { statusDot.textContent = '⚪ No Active Session'; statusDot.className = 'ss-status-dot'; }
  }
}

function updateProgressUI(prog) {
  const bar     = document.getElementById('ss-progress-bar');
  const sessions = document.getElementById('ss-sessions-count');
  const time     = document.getElementById('ss-total-time');
  const badge    = document.getElementById('ss-status-badge');

  const pct = Math.min(100, Math.round((prog.sessions_completed / 3) * 100));
  if (bar)      { bar.style.width = pct + '%'; bar.setAttribute('aria-valuenow', pct); }
  if (sessions) sessions.textContent = prog.sessions_completed + '/3';
  if (time)     time.textContent = fmtMinutes(parseInt(prog.total_time));
  if (badge)    {
    badge.textContent = prog.status === 'completed' ? '✅ Eligible for Certificate' : '📚 In Progress';
    badge.className   = 'badge ' + (prog.status === 'completed' ? 'bg-success' : 'bg-primary');
  }
}

function showCertificateButton() {
  const certSection = document.getElementById('ss-cert-section');
  if (certSection) certSection.style.display = 'block';
}

// ════════════════════════════════════════════════════════════════════════════
// 2.  QUIZ SYSTEM
// ════════════════════════════════════════════════════════════════════════════

let quizState = { attemptId: null, token: null, totalQ: 0, answers: {} };

window.requestCertificate = async function(skillId) {
  const modal = new bootstrap.Modal(document.getElementById('quizModal'));

  document.getElementById('quiz-loading').style.display = 'block';
  document.getElementById('quiz-questions-container').style.display = 'none';
  document.getElementById('quiz-result-container').style.display = 'none';
  modal.show();

  try {
    const data = await authFetch(API.generateQuiz, {
      method: 'POST',
      body: JSON.stringify({ skill_id: skillId }),
    });

    document.getElementById('quiz-loading').style.display = 'none';

    if (!data.success) {
      const msg = typeof data.error === 'object' ? data.error.reason : data.error;
      document.getElementById('quiz-error-msg').textContent = msg;
      document.getElementById('quiz-error-container').style.display = 'block';
      return;
    }

    const q = data.data;
    quizState = { attemptId: q.attempt_id, token: q.quiz_token, skillId: skillId, totalQ: q.total, answers: {} };

    renderQuizQuestions(q.questions, q.skill_name);
    document.getElementById('quiz-questions-container').style.display = 'block';

  } catch(e) {
    document.getElementById('quiz-loading').style.display = 'none';
    document.getElementById('quiz-error-msg').textContent = 'Network error. Please try again.';
    document.getElementById('quiz-error-container').style.display = 'block';
  }
};

function renderQuizQuestions(questions, skillName) {
  const container = document.getElementById('quiz-questions-list');
  document.getElementById('quiz-skill-title').textContent = `Quiz: ${skillName}`;
  container.innerHTML = '';

  questions.forEach((q, qi) => {
    const card = document.createElement('div');
    card.className = 'quiz-q-card mb-4';
    card.innerHTML = `
      <div class="quiz-q-text fw-600 mb-2">
        <span class="quiz-q-num">${qi + 1}</span> ${q.question}
      </div>
      <div class="quiz-options" id="opts-${qi}">
        ${q.options.map((opt, oi) => `
          <label class="quiz-option" for="q${qi}_o${oi}">
            <input type="radio" name="q${qi}" id="q${qi}_o${oi}" value="${oi}"
              onchange="quizState.answers[${qi}]=${oi};updateQuizProgress()">
            <span class="quiz-opt-letter">${['A','B','C','D'][oi]}</span>
            <span>${opt}</span>
          </label>
        `).join('')}
      </div>`;
    container.appendChild(card);
  });

  document.getElementById('quiz-answered').textContent = '0';
  document.getElementById('quiz-total-q').textContent   = questions.length;
  document.getElementById('quiz-progress-bar').style.width = '0%';
}

window.updateQuizProgress = function() {
  const answered = Object.keys(quizState.answers).length;
  document.getElementById('quiz-answered').textContent = answered;
  const pct = Math.round((answered / quizState.totalQ) * 100);
  document.getElementById('quiz-progress-bar').style.width = pct + '%';
};

window.submitQuizAnswers = async function() {
  const answered = Object.keys(quizState.answers).length;
  if (answered < quizState.totalQ) {
    ssToast(`Please answer all ${quizState.totalQ} questions (${answered} answered so far).`, 'warning');
    return;
  }

  const btn = document.getElementById('quiz-submit-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Grading…';

  try {
    const data = await authFetch(API.submitQuiz, {
      method: 'POST',
      body: JSON.stringify({
        attempt_id: quizState.attemptId,
        quiz_token: quizState.token,
        answers:    quizState.answers,
      }),
    });

    document.getElementById('quiz-questions-container').style.display = 'none';
    document.getElementById('quiz-result-container').style.display   = 'block';

    if (!data.success) {
      document.getElementById('quiz-result-icon').textContent   = '❌';
      document.getElementById('quiz-result-title').textContent  = 'Error';
      document.getElementById('quiz-result-msg').textContent    = data.error || 'Submission failed.';
      return;
    }

    const r = data.data;
    document.getElementById('quiz-score-num').textContent  = r.score + '%';
    document.getElementById('quiz-correct-num').textContent = r.correct + '/' + r.total;

    if (r.passed) {
      document.getElementById('quiz-result-icon').textContent  = '🎉';
      document.getElementById('quiz-result-title').textContent = 'Congratulations!';
      document.getElementById('quiz-result-msg').textContent   = r.message;

      const certLink = `${API.generateCertificate}?token=${r.certificate.cert_token}`;
      document.getElementById('quiz-cert-link').href = certLink;
      document.getElementById('quiz-cert-btn').style.display = 'block';
    } else {
      document.getElementById('quiz-result-icon').textContent  = '😔';
      document.getElementById('quiz-result-title').textContent = 'Not Passed';
      document.getElementById('quiz-result-msg').textContent   = r.message;
      document.getElementById('quiz-cert-btn').style.display   = 'none';
    }

  } catch(e) {
    ssToast('Network error during submission.', 'danger');
    btn.disabled = false;
    btn.innerHTML = 'Submit Quiz';
  }
};

// ── Load sessions on page load ───────────────────────────────────────────────
window.loadUserSessions = async function(skillId = 0) {
  const container = document.getElementById('ss-session-history');
  if (!container) return;
  container.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span> Loading…</div>';

  try {
    const uid = await getUID();
    const url = `${API.getUserSessions}?skill_id=${skillId}`;
    const res = await fetch(url, { headers: { 'X-Firebase-UID': uid } });
    const data = await res.json();

    if (!data.success) { container.innerHTML = '<p class="text-muted">Could not load sessions.</p>'; return; }

    const sessions = data.data.sessions;
    const progress = data.data.progress;

    if (progress.length) updateProgressUI(progress.find(p => !skillId || p.skill_id == skillId) || progress[0]);
    if (progress.some(p => p.status === 'completed')) showCertificateButton();

    if (!sessions.length) { container.innerHTML = '<p class="text-muted text-center py-2">No sessions yet. Start your first session!</p>'; return; }

    container.innerHTML = sessions.map(s => `
      <div class="session-row d-flex align-items-center gap-3 p-3 mb-2" style="border-radius:12px;background:var(--bg-secondary)">
        <div class="flex-shrink-0">
          <span class="badge rounded-pill ${s.status === 'completed' ? 'bg-success' : s.status === 'invalid' ? 'bg-danger' : 'bg-warning text-dark'}">
            ${s.status}
          </span>
        </div>
        <div class="flex-grow-1">
          <div class="fw-600" style="font-size:.88rem">${s.skill_name || 'General'}</div>
          <div style="font-size:.78rem;color:var(--text-muted)">
            with ${s.teacher_name} · ${s.start_time ? new Date(s.start_time).toLocaleDateString() : '—'}
          </div>
        </div>
        <div class="text-end" style="font-size:.82rem;color:var(--text-muted)">
          ${s.duration_minutes ? fmtMinutes(s.duration_minutes) : (s.status === 'active' ? '🟢 Live' : '—')}
        </div>
      </div>
    `).join('');

  } catch(e) {
    container.innerHTML = '<p class="text-danger">Error loading sessions.</p>';
  }
};

// Resume active session on page load
(function resumeActiveSession() {
  if (SessionState.activeSessionId && SessionState.sessionStart) {
    updateSessionUI('active');
    startSessionTimer();
  }
})();
