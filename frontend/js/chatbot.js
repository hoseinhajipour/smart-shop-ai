/**
 * Smart Shop AI — Chatbot Frontend
 */
(function () {
  'use strict';

  const config = window.ssaiChat || {};
  const REST_URL = config.restUrl || '';
  const NONCE = config.nonce || '';
  const appearance = config.appearance || {};
  const floatButton = config.floatButton || {};

  let sessionId = '';
  let conversationHistory = [];
  let conversationContext = {};
  if (config.vehicleFitment && typeof config.vehicleFitment === 'object') {
    conversationContext.fitment = config.vehicleFitment;
  }
  let isTyping = false;
  let avatarState = 'idle'; // idle | thinking | typing | speaking

  const root = document.getElementById('ssai-chatbot-root');
  if (!root) return;

  const toggle = document.getElementById('ssai-chat-toggle');
  const windowEl = document.getElementById('ssai-chat-window');
  const closeBtn = document.getElementById('ssai-chat-close');
  const messagesEl = document.getElementById('ssai-chat-messages');
  const inputEl = document.getElementById('ssai-chat-input');
  const sendBtn = document.getElementById('ssai-chat-send');
  const quickActionsEl = document.getElementById('ssai-quick-actions');
  const avatarEl = document.getElementById('ssai-chat-avatar');
  const titleEl = document.getElementById('ssai-chat-title');
  const statusEl = document.getElementById('ssai-chat-status');

  const ICONS = {
    chat: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>',
    robot: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path><line x1="8" y1="16" x2="8" y2="16"></line><line x1="16" y1="16" x2="16" y2="16"></line></svg>',
    help: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
    sparkle: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"></path><path d="M19 13l.75 2.25L22 16l-2.25.75L19 19l-.75-2.25L16 16l2.25-.75L19 13z"></path></svg>',
    cart: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>',
  };

  initTheme();
  initFloatButton();

  toggle.addEventListener('click', () => {
    const isHidden = windowEl.hasAttribute('hidden');
    if (isHidden) {
      openChat();
    } else {
      closeChat();
    }
  });

  closeBtn.addEventListener('click', closeChat);

  sendBtn.addEventListener('click', () => sendMessage());
  inputEl.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  inputEl.addEventListener('input', () => {
    inputEl.parentElement.classList.toggle('ssai-input-active', inputEl.value.length > 0);
  });

  // Close on escape.
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !windowEl.hasAttribute('hidden')) {
      closeChat();
    }
  });

  function initTheme() {
    if (appearance.title && titleEl) titleEl.textContent = appearance.title;
    if (appearance.avatar_emoji && avatarEl) avatarEl.textContent = appearance.avatar_emoji;
  }

  function initFloatButton() {
    const position = floatButton.position || 'right';
    const icon = floatButton.icon || 'chat';
    const animation = floatButton.animation || 'pulse';

    root.classList.add('ssai-position-' + position);
    toggle.classList.add('ssai-anim-' + animation);

    const iconEl = toggle.querySelector('.ssai-toggle-icon');
    if (iconEl) {
      iconEl.innerHTML = ICONS[icon] || ICONS.chat;
    }
  }

  function openChat() {
    windowEl.removeAttribute('hidden');
    toggle.setAttribute('aria-expanded', 'true');
    root.classList.add('ssai-chat-open');
    if (messagesEl.children.length === 0) {
      initChat();
    }
    setAvatarState('idle');
    setStatus('Online');
    inputEl.focus();
  }

  function closeChat() {
    windowEl.setAttribute('hidden', '');
    toggle.setAttribute('aria-expanded', 'false');
    root.classList.remove('ssai-chat-open');
    setAvatarState('idle');
  }

  function setAvatarState(state) {
    avatarState = state;
    const wrap = avatarEl ? avatarEl.closest('.ssai-chat-avatar-wrap') : null;
    if (!wrap) return;
    wrap.classList.remove('ssai-avatar-idle', 'ssai-avatar-thinking', 'ssai-avatar-typing', 'ssai-avatar-speaking');
    wrap.classList.add('ssai-avatar-' + state);
  }

  function setStatus(text) {
    if (statusEl) statusEl.textContent = text;
  }

  function initChat() {
    const welcome = config.welcome || 'Hi 👋 What product are you looking for?';
    addMessage(welcome, 'bot', true);
    renderQuickActions();
  }

  function renderQuickActions() {
    const actions = config.quickActions || [];
    quickActionsEl.innerHTML = '';

    actions.forEach((action, i) => {
      const btn = document.createElement('button');
      btn.className = 'ssai-quick-action';
      btn.style.animationDelay = (i * 0.08) + 's';
      btn.textContent = (action.icon || '') + ' ' + (action.label || '');
      btn.addEventListener('click', () => {
        if (action.query) {
          inputEl.value = action.query;
          sendMessage();
        }
      });
      quickActionsEl.appendChild(btn);
    });
  }

  async function sendMessage() {
    const text = inputEl.value.trim();
    if (!text || isTyping) return;

    inputEl.value = '';
    inputEl.parentElement.classList.remove('ssai-input-active');
    sendBtn.disabled = true;
    isTyping = true;

    addMessage(text, 'user');
    showTypingIndicator();

    conversationHistory.push({ role: 'user', content: text });

    try {
      const response = await apiRequest('/chat', {
        method: 'POST',
        body: JSON.stringify({
          message: text,
          session_id: sessionId,
          history: conversationHistory.slice(-10),
          context: conversationContext,
        }),
      });

      removeTypingIndicator();

      if (response.session_id) {
        sessionId = response.session_id;
      }

      if (response.context) {
        conversationContext = response.context;
      }

      if (response.message) {
        await addMessageAnimated(response.message, 'bot');
        conversationHistory.push({ role: 'assistant', content: response.message });
      }

      if (response.products && response.products.length > 0) {
        renderProducts(response.products);
      }

      if (response.show_support && response.support) {
        renderSupportCard(response.support);
      }

      setAvatarState('idle');
      setStatus('Online');
    } catch (err) {
      removeTypingIndicator();
      setAvatarState('idle');
      setStatus('Online');
      addMessage('Something went wrong. Please try again.', 'bot');
      console.error('SSAI Chat error:', err);
    }

    sendBtn.disabled = false;
    isTyping = false;
    inputEl.focus();
  }

  function addMessage(text, type, animate) {
    const div = document.createElement('div');
    div.className = 'ssai-message ssai-message-' + type + (animate ? ' ssai-message-enter' : '');
    div.textContent = text;
    messagesEl.appendChild(div);
    scrollToBottom();
    return div;
  }

  async function addMessageAnimated(text, type) {
    setAvatarState('speaking');
    setStatus('Replying...');

    const div = document.createElement('div');
    div.className = 'ssai-message ssai-message-' + type + ' ssai-message-enter';
    messagesEl.appendChild(div);

    // Typewriter effect for bot messages.
    const chars = text.split('');
    let i = 0;
    const speed = Math.max(8, Math.min(25, 2000 / chars.length));

    return new Promise((resolve) => {
      function typeChar() {
        if (i < chars.length) {
          div.textContent += chars[i];
          i++;
          scrollToBottom();
          setTimeout(typeChar, speed);
        } else {
          setAvatarState('idle');
          setStatus('Online');
          resolve();
        }
      }
      typeChar();
    });
  }

  function showTypingIndicator() {
    setAvatarState('thinking');
    setStatus('Thinking...');

    const div = document.createElement('div');
    div.className = 'ssai-typing-indicator';
    div.id = 'ssai-loading';
    div.innerHTML =
      '<div class="ssai-typing-avatar">' + (appearance.avatar_emoji || '🤖') + '</div>' +
      '<div class="ssai-typing-bubble">' +
        '<span class="ssai-typing-dots"><span></span><span></span><span></span></span>' +
        '<span class="ssai-typing-text">Searching...</span>' +
      '</div>';
    messagesEl.appendChild(div);
    scrollToBottom();
  }

  function removeTypingIndicator() {
    const el = document.getElementById('ssai-loading');
    if (el) {
      el.classList.add('ssai-typing-exit');
      setTimeout(() => el.remove(), 200);
    }
  }

  function scrollToBottom() {
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function renderProducts(products) {
    const container = document.createElement('div');
    container.className = 'ssai-products ssai-message-enter';

    products.slice(0, 5).forEach((product, i) => {
      const card = document.createElement('div');
      card.className = 'ssai-product-card';
      card.style.animationDelay = (i * 0.1) + 's';

      const img = document.createElement('img');
      img.className = 'ssai-product-image';
      img.src = product.image || '';
      img.alt = product.name || '';
      img.loading = 'lazy';

      const info = document.createElement('div');
      info.className = 'ssai-product-info';

      const name = document.createElement('p');
      name.className = 'ssai-product-name';
      name.textContent = product.name || '';

      const price = document.createElement('p');
      price.className = 'ssai-product-price';
      price.innerHTML = product.price_html || product.price || '';

      const stock = document.createElement('span');
      stock.className = 'ssai-product-stock ' + (product.in_stock ? 'in-stock' : 'out-of-stock');
      stock.textContent = product.in_stock ? 'In stock' : 'Out of stock';

      info.appendChild(name);
      info.appendChild(price);
      info.appendChild(stock);

      if (product.match_score) {
        const score = document.createElement('span');
        score.className = 'ssai-product-score';
        score.textContent = product.match_label || 'Match: ' + product.match_score + '%';
        info.appendChild(score);
      }

      const actions = document.createElement('div');
      actions.className = 'ssai-product-actions';

      if (product.url) {
        const viewBtn = document.createElement('a');
        viewBtn.className = 'ssai-product-btn ssai-product-btn-view';
        viewBtn.href = product.url;
        viewBtn.target = '_blank';
        viewBtn.textContent = 'View';
        actions.appendChild(viewBtn);
      }

      if (product.in_stock && product.id) {
        const cartBtn = document.createElement('button');
        cartBtn.className = 'ssai-product-btn ssai-product-btn-cart';
        cartBtn.textContent = 'Add to cart';
        cartBtn.addEventListener('click', () => addToCart(product.id));
        actions.appendChild(cartBtn);
      }

      info.appendChild(actions);
      card.appendChild(img);
      card.appendChild(info);
      container.appendChild(card);
    });

    messagesEl.appendChild(container);
    scrollToBottom();
  }

  function renderSupportCard(support) {
    if (!support || !support.channels || support.channels.length === 0) return;

    const card = document.createElement('div');
    card.className = 'ssai-support-card ssai-message-enter';

    const header = document.createElement('div');
    header.className = 'ssai-support-header';
    header.innerHTML =
      '<span class="ssai-support-icon">🤝</span>' +
      '<div class="ssai-support-header-text">' +
        '<strong>' + escapeHtml(support.title || 'Contact Support') + '</strong>' +
        (support.message ? '<p>' + escapeHtml(support.message) + '</p>' : '') +
      '</div>';
    card.appendChild(header);

    const channels = document.createElement('div');
    channels.className = 'ssai-support-channels';

    support.channels.forEach((channel, i) => {
      const link = document.createElement('a');
      link.className = 'ssai-support-channel ssai-support-' + (channel.type || 'custom');
      link.href = channel.url;
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      link.style.animationDelay = (i * 0.08) + 's';

      const displayValue = channel.type === 'instagram' || channel.type === 'telegram'
        ? (channel.value.startsWith('@') ? channel.value : '@' + channel.value.replace(/^@/, ''))
        : channel.value;

      link.innerHTML =
        '<span class="ssai-support-channel-icon">' + (channel.icon || '🔗') + '</span>' +
        '<span class="ssai-support-channel-body">' +
          '<span class="ssai-support-channel-label">' + escapeHtml(channel.label || channel.type) + '</span>' +
          '<span class="ssai-support-channel-value">' + escapeHtml(displayValue) + '</span>' +
        '</span>' +
        '<span class="ssai-support-channel-arrow">→</span>';

      channels.appendChild(link);
    });

    card.appendChild(channels);
    messagesEl.appendChild(card);
    scrollToBottom();
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
  }

  async function addToCart(productId) {
    try {
      const formData = new FormData();
      formData.append('action', 'ssai_add_to_cart');
      formData.append('product_id', productId);
      formData.append('quantity', '1');

      const response = await fetch(config.ajaxUrl || '/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: formData,
      });

      const data = await response.json();
      if (data.success) {
        addMessage('Product added to cart ✓', 'bot', true);
      } else {
        addMessage('Could not add product to cart.', 'bot');
      }
    } catch (err) {
      addMessage('Could not add product to cart.', 'bot');
    }
  }

  async function apiRequest(endpoint, options = {}) {
    const url = REST_URL.replace(/\/$/, '') + endpoint;
    const headers = {
      'Content-Type': 'application/json',
      'X-WP-Nonce': NONCE,
    };

    const response = await fetch(url, {
      ...options,
      headers: { ...headers, ...(options.headers || {}) },
    });

    if (!response.ok) {
      throw new Error('API request failed: ' + response.status);
    }

    return response.json();
  }

  // Allow external vehicle selector to pass fitment data (bolt pattern, offset, rim size).
  window.ssaiSetVehicleFitment = function (fitment) {
    if (!fitment || typeof fitment !== 'object') return;
    conversationContext.fitment = fitment;
  };

  document.addEventListener('ssai:vehicle-selected', function (event) {
    if (event.detail) {
      window.ssaiSetVehicleFitment(event.detail);
    }
  });
})();
