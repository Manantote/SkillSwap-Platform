/* =====================================================
   Skill Swap — Chatbot Component (Rule-Based)
   ===================================================== */

(function () {
  'use strict';

  const BOT_NAME = 'SwapBot';

  // ── Rule-Based Response Engine ────────────────────────────────────────────────
  function getBotResponse(message) {
    const msg = message.toLowerCase().trim();

    // Greetings
    if (/\b(hi|hello|hey|hola|good morning|good evening|sup)\b/.test(msg))
      return "Hello! 👋 I'm <strong>SwapBot</strong>. How can I help you today?";

    // How it works
    if (msg.includes('how') && (msg.includes('work') || msg.includes('swap') || msg.includes('use')))
      return "🔄 Skill Swap lets you trade skills instead of money! List what you can teach and what you want to learn, get matched with complementary users, and start swapping lessons — completely <strong>free</strong>!";

    // Skills
    if (/\b(skill|learn|teach|add skill)\b/.test(msg))
      return "➕ You can add skills in the <strong>Add Skills</strong> section and explore popular skills like Python, UI/UX, Guitar, Photography, and more!";

    // Matches
    if (/\b(match|find|discover|pair)\b/.test(msg))
      return "🎯 Go to the <strong>Matches</strong> section to find users with complementary skills. Our smart algorithm pairs you with the best matches!";

    // Requests
    if (/\b(request|connect|send request|swap request)\b/.test(msg))
      return "📩 Click <strong>'Send Swap Request'</strong> on any user card to connect. They'll get notified and can accept or reject your request!";

    // Chat
    if (/\b(chat|message|talk|conversation)\b/.test(msg))
      return "💬 You can chat with your matched partners after a swap request is accepted. Head to the <strong>Chat</strong> page to start messaging!";

    // Video Call
    if (/\b(video|call|meeting|meet)\b/.test(msg))
      return "📹 You can start a video call using the video button in the chat window. Both you and your partner will join the same meeting link!";

    // Reviews
    if (/\b(review|rating|feedback|rate|stars)\b/.test(msg))
      return "⭐ You can give reviews after completing a skill exchange. Visit the <strong>Reviews</strong> page to rate your partner (1–5 stars) and leave feedback!";

    // Profile
    if (/\b(profile|edit profile|my profile|account)\b/.test(msg))
      return "👤 You can edit your profile from the <strong>Dashboard</strong>. Update your name, change your password, and manage your account settings!";

    // Dashboard
    if (/\b(dashboard|home|overview)\b/.test(msg))
      return "📊 Your <strong>Dashboard</strong> is your central hub! View your skills, manage requests, check notifications, and access all features from there.";

    // Register / Sign Up
    if (/\b(register|sign up|create account|join)\b/.test(msg))
      return "📝 Click <strong>Register</strong> in the top navigation. Fill in your details and start swapping skills in under 2 minutes!";

    // Login
    if (/\b(login|sign in|log in)\b/.test(msg))
      return "🔑 Click <strong>Login</strong> in the navigation bar and enter your email and password. First time? Click Register to create a free account!";

    // Free / Cost
    if (/\b(free|cost|money|price|payment|pay)\b/.test(msg))
      return "🎉 Skill Swap is completely <strong>FREE</strong>! No money changes hands — you pay with your knowledge and skills. That's the whole point!";

    // Dark Mode
    if (/\b(dark|theme|light mode|dark mode|night)\b/.test(msg))
      return "🌙 Toggle between dark and light mode using the moon/sun icon in the navigation bar. Your preference is saved automatically!";

    // Notification
    if (/\b(notification|bell|alert|notify)\b/.test(msg))
      return "🔔 Check the bell icon in the top navigation for real-time notifications about new messages, requests, and updates!";

    // Settings
    if (/\b(setting|settings|preference|config)\b/.test(msg))
      return "⚙️ Access your <strong>Settings</strong> from the Dashboard to toggle dark mode, manage notifications, or delete your account.";

    // Thanks
    if (/\b(thank|thanks|great|awesome|perfect|nice|cool)\b/.test(msg))
      return "😊 You're welcome! Happy swapping! If you need anything else, I'm right here.";

    // Help
    if (/\b(help|support|assist|guide)\b/.test(msg))
      return "🆘 I can help with: how Skill Swap works, finding matches, sending requests, adding skills, chat, reviews, profile, and settings. Just ask!";

    // Bye
    if (/\b(bye|goodbye|see you|later)\b/.test(msg))
      return "👋 Goodbye! Happy skill swapping! Come back anytime you need help.";

    // Who are you
    if (/\b(who are you|your name|what are you)\b/.test(msg))
      return "🤖 I'm <strong>SwapBot</strong>, your friendly Skill Swap assistant! I'm here to help you navigate the platform and answer your questions.";

    // Default
    return "🤔 I'm not sure about that. Try asking about <strong>skills</strong>, <strong>matches</strong>, <strong>requests</strong>, <strong>chat</strong>, <strong>reviews</strong>, or <strong>how Skill Swap works</strong>!";
  }

  // ── Quick Replies ─────────────────────────────────────────────────────────────
  const QUICK_REPLIES = [
    'How does it work?',
    'How to find matches?',
    'How to send a request?',
    'How to add skills?',
    'Is it free?'
  ];

  function formatTime() {
    return new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
  }

  function createWidget() {
    const widget = document.createElement('div');
    widget.className = 'chatbot-widget';
    widget.id = 'chatbotWidget';
    widget.innerHTML = `
      <div class="chatbot-panel hidden" id="chatbotPanel">
        <div class="chatbot-header">
          <div class="bot-icon"><i class="bi bi-robot"></i></div>
          <div>
            <div style="font-weight:700;font-size:.95rem">${BOT_NAME}</div>
            <div style="font-size:.72rem;opacity:.8"><span style="color:#6effc0">●</span> Online</div>
          </div>
          <button id="chatbotClose" style="margin-left:auto;background:rgba(255,255,255,.2);border:none;color:#fff;width:28px;height:28px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1rem;transition:background .2s">×</button>
        </div>
        <div class="chatbot-messages" id="chatbotMessages"></div>
        <div class="chatbot-quick-replies" id="chatbotQuickReplies"></div>
        <div class="chatbot-input-area">
          <input class="chatbot-input" id="chatbotInput" placeholder="Type a message…" autocomplete="off">
          <button class="chatbot-send" id="chatbotSend"><i class="bi bi-send-fill"></i></button>
        </div>
      </div>
      <button class="chatbot-toggle" id="chatbotToggle" title="Chat with SwapBot">
        <span class="pulse-ring"></span>
        <i class="bi bi-robot" id="chatbotToggleIcon"></i>
      </button>
    `;
    document.body.appendChild(widget);
  }

  function addMessage(text, sender, animate = true) {
    const msgs = document.getElementById('chatbotMessages');
    if (!msgs) return;
    const div = document.createElement('div');
    div.className = sender === 'bot' ? 'bot-msg' : 'user-msg';
    div.innerHTML = `${text}<span class="message-time" style="display:block;font-size:.65rem;opacity:.55;margin-top:.2rem;text-align:${sender === 'bot' ? 'left' : 'right'}">${formatTime()}</span>`;
    if (!animate) div.style.animation = 'none';
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
  }

  function addTyping() {
    const msgs = document.getElementById('chatbotMessages');
    const div = document.createElement('div');
    div.className = 'bot-msg';
    div.id = 'botTyping';
    div.innerHTML = '<span style="display:flex;gap:.3rem;align-items:center"><span style="animation:pulse 1s infinite;opacity:.5">●</span><span style="animation:pulse 1s .2s infinite;opacity:.5">●</span><span style="animation:pulse 1s .4s infinite;opacity:.5">●</span></span>';
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
    return div;
  }

  function renderQuickReplies() {
    const container = document.getElementById('chatbotQuickReplies');
    if (!container) return;
    container.innerHTML = QUICK_REPLIES.map(r =>
      `<button class="quick-reply-btn" data-reply="${r}">${r}</button>`
    ).join('');
    container.querySelectorAll('.quick-reply-btn').forEach(btn => {
      btn.addEventListener('click', () => sendMessage(btn.dataset.reply));
    });
  }

  function sendMessage(text) {
    const inputText = text || document.getElementById('chatbotInput')?.value?.trim();
    if (!inputText) return;
    const input = document.getElementById('chatbotInput');
    if (input) input.value = '';

    addMessage(inputText, 'user');

    // Show typing indicator for 1 second, then reply
    const typing = addTyping();
    const delay = 800 + Math.random() * 400;
    setTimeout(() => {
      typing.remove();
      addMessage(getBotResponse(inputText), 'bot');
    }, delay);
  }

  function initChatbot() {
    createWidget();

    const toggle = document.getElementById('chatbotToggle');
    const panel = document.getElementById('chatbotPanel');
    const closeBtn = document.getElementById('chatbotClose');
    const sendBtn = document.getElementById('chatbotSend');
    const input = document.getElementById('chatbotInput');
    const icon = document.getElementById('chatbotToggleIcon');

    let isOpen = false;
    let wasDragged = false;

    function makeDraggable(element, dragHandle1, dragHandle2) {
      let isDragging = false;
      let startX, startY, initialLeft, initialTop;

      function startDrag(e) {
        if (e.target.closest('#chatbotClose')) return; // Ignore close button
        if (e.type === 'mousedown' && e.button !== 0) return; // Only left click

        const evt = e.type.includes('touch') ? e.touches[0] : e;
        startX = evt.clientX;
        startY = evt.clientY;
        wasDragged = false;

        const rect = element.getBoundingClientRect();
        initialLeft = rect.left;
        initialTop = rect.top;
        
        document.addEventListener('mousemove', onDrag);
        document.addEventListener('mouseup', endDrag);
        document.addEventListener('touchmove', onDrag, { passive: false });
        document.addEventListener('touchend', endDrag);
      }

      function onDrag(e) {
        const evt = e.type.includes('touch') ? e.touches[0] : e;
        const dx = evt.clientX - startX;
        const dy = evt.clientY - startY;

        // Start dragging only if moved by at least 5px
        if (!isDragging && (Math.abs(dx) > 5 || Math.abs(dy) > 5)) {
          isDragging = true;
          wasDragged = true;
          
          // Switch to left/top positioning instead of right/bottom
          element.style.right = 'auto';
          element.style.bottom = 'auto';
          element.style.margin = '0';
        }

        if (!isDragging) return;
        if (e.cancelable) e.preventDefault(); // Prevent scroll while dragging

        let newLeft = initialLeft + dx;
        let newTop = initialTop + dy;
        
        // Boundaries
        const maxLeft = window.innerWidth - element.offsetWidth;
        const maxTop = window.innerHeight - element.offsetHeight;
        
        element.style.left = Math.max(0, Math.min(newLeft, maxLeft)) + 'px';
        element.style.top = Math.max(0, Math.min(newTop, maxTop)) + 'px';
      }

      function endDrag() {
        isDragging = false;
        document.removeEventListener('mousemove', onDrag);
        document.removeEventListener('mouseup', endDrag);
        document.removeEventListener('touchmove', onDrag);
        document.removeEventListener('touchend', endDrag);
      }

      // Attach to header
      dragHandle1.addEventListener('mousedown', startDrag);
      dragHandle1.addEventListener('touchstart', startDrag, { passive: true });
      dragHandle1.style.cursor = 'move';
      
      // Attach to floating button
      dragHandle2.addEventListener('mousedown', startDrag);
      dragHandle2.addEventListener('touchstart', startDrag, { passive: true });
    }

    function openPanel() {
      panel.classList.remove('hidden');
      panel.classList.add('visible');
      icon.className = 'bi bi-x-lg';
      isOpen = true;
      if (!document.getElementById('chatbotMessages').children.length) {
        setTimeout(() => addMessage('👋 Hi! I\'m <strong>SwapBot</strong>! How can I help you with Skill Swap today?', 'bot'), 300);
        setTimeout(renderQuickReplies, 600);
      }
    }

    function closePanel() {
      panel.classList.remove('visible');
      panel.classList.add('hidden');
      icon.className = 'bi bi-robot';
      isOpen = false;
    }

    const header = document.querySelector('.chatbot-header');
    const widget = document.getElementById('chatbotWidget');
    makeDraggable(widget, header, toggle);

    toggle.addEventListener('click', (e) => { 
      if (!wasDragged) { isOpen ? closePanel() : openPanel(); } 
    });
    closeBtn.addEventListener('click', closePanel);
    sendBtn.addEventListener('click', () => sendMessage());
    input.addEventListener('keydown', e => { if (e.key === 'Enter') sendMessage(); });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChatbot);
  } else {
    initChatbot();
  }
})();
