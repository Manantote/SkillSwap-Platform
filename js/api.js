/**
 * Skill Swap — Central API Helper (js/api.js)
 *
 * Wraps Firebase auth state + fetch calls so every page
 * can make authenticated requests without boilerplate.
 *
 * Expects firebase/firebase-config.js to be loaded first.
 */

/* ─────────────────────────────────────────────────────────────────────────────
   Auth helpers
───────────────────────────────────────────────────────────────────────────── */

/**
 * Block loading of a page if no Firebase user is signed in.
 * Redirect to login.html if unauthenticated.
 * Returns a Promise that resolves to the current user.
 */
window.requireAuth = function () {
  return new Promise((resolve) => {
    // onAuthStateChanged fires once on load
    const unsubscribe = ssAuth.onAuthStateChanged((user) => {
      unsubscribe();
      if (!user) {
        window.location.href = 'login.html';
      } else {
        window.currentFirebaseUser = user;
        resolve(user);
        
            // Start online heartbeat
        if (!window.statusInterval) {
          API.setOnline().catch(console.warn);
          window.statusInterval = setInterval(() => {
            API.setOnline().catch(console.warn);
          }, 45000); // 45 seconds (so it never hits the 1-minute expiration)
          
          // Global intercept for logouts across all pages
          document.addEventListener('click', async (e) => {
            const el = e.target.closest('a');
            if (el && (el.href.endsWith('login.html') || el.textContent.trim() === 'Logout')) {
              e.preventDefault();
              await API.setOffline().catch(console.warn);
              await ssAuth.signOut();
              window.location.href = 'login.html';
            }
          });
        }
        
        // Start notification heartbeat
        if (!window.notifInterval) {
          function _timeAgo(dateStr) {
            const s = Math.floor((Date.now() - new Date(dateStr)) / 1000);
            if(s < 60) return 'just now';
            if(s < 3600) return Math.floor(s/60) + 'm ago';
            if(s < 86400) return Math.floor(s/3600) + 'h ago';
            return Math.floor(s/86400) + 'd ago';
          }

          window.pollNotifications = async () => {
            if (localStorage.getItem('notifications_enabled') === 'false') return;
            try {
              const res = await API.getNotifications();
              const badge = document.getElementById('navNotificationBadge');
              const list = document.getElementById('navNotificationList');
              const empty = document.getElementById('navNotificationEmpty');
              if (!badge || !list) return;

              if (res.unread_count > 0) {
                badge.textContent = res.unread_count;
                badge.classList.remove('d-none');
              } else {
                badge.classList.add('d-none');
              }

              if (res.notifications.length > 0) {
                if (empty) empty.remove();
                Array.from(list.children).forEach(child => {
                  if (!child.classList.contains('sticky-top')) child.remove();
                });

                res.notifications.forEach(n => {
                  const li = document.createElement('li');
                  const time = _timeAgo(n.created_at);
                  const icon = n.type === 'video_call' ? 'bi-camera-video-fill text-danger' : 
                               n.type === 'message' ? 'bi-chat-dots-fill text-primary' : 
                               n.type === 'review' ? 'bi-star-fill text-warning' : 'bi-bell-fill text-info';
                  
                  const bg = n.is_read == 0 ? 'background:rgba(108,99,255,.05)' : '';
                  li.innerHTML = `
                    <a class="dropdown-item py-3 px-3 border-bottom" href="#" style="${bg};white-space:normal">
                      <div class="d-flex gap-3 align-items-start">
                        <div class="mt-1"><i class="bi ${icon} fs-5"></i></div>
                        <div>
                          <div style="font-size:.85rem;line-height:1.2;font-weight:${n.is_read == 0 ? '700' : '400'}">${n.message}</div>
                          <div style="font-size:.75rem;color:var(--text-muted);margin-top:4px">${time}</div>
                        </div>
                      </div>
                    </a>`;
                  list.appendChild(li);
                });

                const markBtn = document.createElement('li');
                markBtn.innerHTML = `<a class="dropdown-item text-center py-2" href="#" style="font-size:.82rem;color:var(--primary)" onclick="markAllNotificationsRead(event)">Mark all as read</a>`;
                list.appendChild(markBtn);
              }
            } catch (e) { console.warn('Notification poll failed', e); }
          };

          window.pollNotifications();
          window.notifInterval = setInterval(window.pollNotifications, 5000);
          
          window.markAllNotificationsRead = async function(e) {
            if (e && e.type !== 'click') return; // let Bootstrap dropdown click happen natively
            if (e) e.stopPropagation();
            try {
              await API.markRead();
              if(window.pollNotifications) await window.pollNotifications();
            } catch (err) { console.warn(err); }
          };
        }
        
        resolve(user);
      }
    });
  });
};

/**
 * Get the current Firebase UID (or null if not signed in).
 */
window.getCurrentUID = function () {
  return window.currentFirebaseUser?.uid ?? null;
};

/* ─────────────────────────────────────────────────────────────────────────────
   Authenticated Fetch
───────────────────────────────────────────────────────────────────────────── */

/**
 * Like fetch() but automatically attaches the Firebase UID header.
 *
 * @param {string} url
 * @param {RequestInit} [options]
 * @returns {Promise<any>} – parsed JSON data
 */
// Change this to your live PHP backend URL once you host it!
// Example: 'https://my-live-php-backend.com/skillswap/'
// Leave as empty string '' for local XAMPP testing
const API_BASE_URL = '';

window.authFetch = async function (endpoint, options = {}) {
  const uid = getCurrentUID();
  const headers = {
    'Content-Type': 'application/json',
    ...(options.headers || {}),
  };
  if (uid) {
    headers['X-Firebase-UID'] = uid;
  }

  const url = API_BASE_URL + endpoint;
  const res = await fetch(url, { ...options, headers });
  const json = await res.json().catch(() => ({ success: false, error: 'Invalid server response.' }));

  if (!json.success) {
    const msg = json.error || 'An error occurred.';
    console.error(`[API] ${url}:`, msg);
    throw new Error(msg);
  }
  return json.data;
};

/* ─────────────────────────────────────────────────────────────────────────────
   Convenience API wrappers
───────────────────────────────────────────────────────────────────────────── */

const API = {
  /** Register / update a user in MySQL after Firebase sign-up */
  registerUser(uid, name, email, teachSkills = '', learnSkills = '') {
    return authFetch('api/register_user.php', {
      method: 'POST',
      body: JSON.stringify({
        firebase_uid: uid,
        name,
        email,
        teach_skills: teachSkills,
        learn_skills: learnSkills,
      }),
    });
  },

  /** Get a user's full profile */
  getUser(uid) {
    return authFetch(`api/get_user.php?uid=${encodeURIComponent(uid)}`);
  },

  /** Get the current user's teach/learn skills */
  getUserSkills(uid) {
    return authFetch(`api/get_user_skills.php?uid=${encodeURIComponent(uid)}`);
  },

  /** Add a single skill for the current user */
  addSkill(skillName, skillType) {
    return authFetch('api/add_skill.php', {
      method: 'POST',
      body: JSON.stringify({ skill_name: skillName, skill_type: skillType }),
    });
  },

  /** Find mutually matched users */
  matchUsers(uid) {
    return authFetch(`api/match_users.php?uid=${encodeURIComponent(uid)}`);
  },

  /** Send a swap request */
  sendRequest(receiverUID, message = '') {
    return authFetch('api/send_request.php', {
      method: 'POST',
      body: JSON.stringify({ receiver_uid: receiverUID, message }),
    });
  },

  /** Accept a request */
  acceptRequest(requestId) {
    return authFetch('api/accept_request.php', {
      method: 'POST',
      body: JSON.stringify({ request_id: requestId }),
    });
  },

  /** Reject a request */
  rejectRequest(requestId) {
    return authFetch('api/reject_request.php', {
      method: 'POST',
      body: JSON.stringify({ request_id: requestId }),
    });
  },

  /** Get all requests for a user */
  getRequests(uid) {
    return authFetch(`api/get_requests.php?uid=${encodeURIComponent(uid)}`);
  },

  /** Send a chat message */
  sendMessage(receiverUID, message) {
    return authFetch('api/send_message.php', {
      method: 'POST',
      body: JSON.stringify({ receiver_uid: receiverUID, message }),
    });
  },

  /** Fetch conversation messages */
  getMessages(senderUID, receiverUID, since = null) {
    let url = `api/get_messages.php?sender_uid=${encodeURIComponent(senderUID)}&receiver_uid=${encodeURIComponent(receiverUID)}`;
    if (since) url += `&since=${encodeURIComponent(since)}`;
    return authFetch(url);
  },

  /** Submit a review */
  addReview(reviewedUID, rating, comment = '', tags = '') {
    return authFetch('api/add_review.php', {
      method: 'POST',
      body: JSON.stringify({ reviewed_uid: reviewedUID, rating, comment, tags }),
    });
  },

  /** Get reviews for a user */
  getReviews(uid) {
    return authFetch(`api/get_reviews.php?uid=${encodeURIComponent(uid)}`);
  },

  /** Get users the current user can chat with (accepted swaps) */
  getChatPartners(uid) {
    return authFetch(`api/get_chat_partners.php?uid=${encodeURIComponent(uid)}`);
  },

  /** Mark the user as online and update last_seen */
  setOnline() {
    return authFetch('api/set_online.php', { method: 'POST' });
  },

  /** Mark the user as offline manually */
  setOffline() {
    return authFetch('api/set_offline.php', { method: 'POST' });
  },

  /** Get or generate a persistent dummy Google Meet link */
  getMeetLink(partnerUID) {
    return authFetch(`api/get_meet_link.php?partner_uid=${encodeURIComponent(partnerUID)}`);
  },

  /** Clear all messages between current user and specified partner */
  clearChat(partnerUID) {
    return authFetch('api/clear_chat.php', {
      method: 'POST',
      body: JSON.stringify({ partner_uid: partnerUID })
    });
  },

  /** Block a specific user UID */
  blockUser(blockedUID) {
    return authFetch('api/block_user.php', {
      method: 'POST',
      body: JSON.stringify({ blocked_uid: blockedUID })
    });
  },

  /** Submit a safety report against a specific user UID */
  reportUser(reportedUID, reason) {
    return authFetch('api/report_user.php', {
      method: 'POST',
      body: JSON.stringify({ reported_uid: reportedUID, reason })
    });
  },

  // ── Notifications ─────────────────────────────────────────────────────────────
  
  getNotifications() {
    return authFetch('api/get_notifications.php');
  },
  
  addNotification(receiverUID, type, message) {
    return authFetch('api/add_notification.php', {
      method: 'POST',
      body: JSON.stringify({ receiver_uid: receiverUID, type, message })
    });
  },
  
  markRead(notificationId = null) {
    return authFetch('api/mark_read.php', {
      method: 'POST',
      body: JSON.stringify({ notification_id: notificationId })
    });
  },

  // ── Profile & Settings ─────────────────────────────────────────────────────────

  updateProfile(name) {
    return authFetch('api/update_profile.php', {
      method: 'POST',
      body: JSON.stringify({ name })
    });
  },
  
  deleteAccount() {
    return authFetch('api/delete_account.php', { method: 'POST' });
  },
  
  logoutAll() {
    return authFetch('api/logout_all.php', { method: 'POST' });
  }
};

window.API = API;
