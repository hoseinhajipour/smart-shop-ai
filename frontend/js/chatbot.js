/**
 * Smart Shop AI — Chatbot Frontend
 */
(function () {
  'use strict';

  const config = window.ssaiChat || {};
  const REST_URL = config.restUrl || '';
  const NONCE = config.nonce || '';

  let sessionId = '';
  let conversationHistory = [];
  let conversationContext = {};

  const root = document.getElementById('ssai-chatbot-root');
  if (!root) return;

  const toggle = document.getElementById('ssai-chat-toggle');
  const windowEl = document.getElementById('ssai-chat-window');
  const closeBtn = document.getElementById('ssai-chat-close');
  const messagesEl = document.getElementById('ssai-chat-messages');
  const inputEl = document.getElementById('ssai-chat-input');
  const sendBtn = document.getElementById('ssai-chat-send');
  const quickActionsEl = document.getElementById('ssai-quick-actions');

  // Toggle chat window.
  toggle.addEventListener('click', () => {
    const isHidden = windowEl.hasAttribute('hidden');
    if (isHidden) {
      windowEl.removeAttribute('hidden');
      if (messagesEl.children.length === 0) {
        initChat();
      }
      inputEl.focus();
    } else {
      windowEl.setAttribute('hidden', '');
    }
  });

  closeBtn.addEventListener('click', () => {
    windowEl.setAttribute('hidden', '');
  });

  sendBtn.addEventListener('click', () => sendMessage());
  inputEl.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  function initChat() {
    const welcome = config.welcome || 'سلام 👋 چه محصولی دنبالش می‌گردی؟';
    addMessage(welcome, 'bot');
    renderQuickActions();
  }

  function renderQuickActions() {
    const actions = config.quickActions || [];
    quickActionsEl.innerHTML = '';

    actions.forEach((action) => {
      const btn = document.createElement('button');
      btn.className = 'ssai-quick-action';
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
    if (!text) return;

    inputEl.value = '';
    sendBtn.disabled = true;
    addMessage(text, 'user');
    showLoading();

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

      removeLoading();

      if (response.session_id) {
        sessionId = response.session_id;
      }

      if (response.context) {
        conversationContext = response.context;
      }

      if (response.message) {
        addMessage(response.message, 'bot');
        conversationHistory.push({ role: 'assistant', content: response.message });
      }

      if (response.products && response.products.length > 0) {
        renderProducts(response.products);
      }
    } catch (err) {
      removeLoading();
      addMessage('خطایی رخ داد. لطفاً دوباره تلاش کنید.', 'bot');
      console.error('SSAI Chat error:', err);
    }

    sendBtn.disabled = false;
    inputEl.focus();
  }

  function addMessage(text, type) {
    const div = document.createElement('div');
    div.className = 'ssai-message ssai-message-' + type;
    div.textContent = text;
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function showLoading() {
    const div = document.createElement('div');
    div.className = 'ssai-message-loading';
    div.id = 'ssai-loading';
    div.textContent = 'در حال جستجو...';
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function removeLoading() {
    const el = document.getElementById('ssai-loading');
    if (el) el.remove();
  }

  function renderProducts(products) {
    const container = document.createElement('div');
    container.className = 'ssai-products';

    products.slice(0, 5).forEach((product) => {
      const card = document.createElement('div');
      card.className = 'ssai-product-card';

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
      stock.textContent = product.in_stock ? 'موجود' : 'ناموجود';

      info.appendChild(name);
      info.appendChild(price);
      info.appendChild(stock);

      if (product.match_score) {
        const score = document.createElement('span');
        score.className = 'ssai-product-score';
        score.textContent = product.match_label || 'تطابق: ' + product.match_score + '%';
        info.appendChild(score);
      }

      const actions = document.createElement('div');
      actions.className = 'ssai-product-actions';

      if (product.url) {
        const viewBtn = document.createElement('a');
        viewBtn.className = 'ssai-product-btn ssai-product-btn-view';
        viewBtn.href = product.url;
        viewBtn.target = '_blank';
        viewBtn.textContent = 'مشاهده';
        actions.appendChild(viewBtn);
      }

      if (product.in_stock && product.id) {
        const cartBtn = document.createElement('button');
        cartBtn.className = 'ssai-product-btn ssai-product-btn-cart';
        cartBtn.textContent = 'افزودن به سبد';
        cartBtn.addEventListener('click', () => addToCart(product.id));
        actions.appendChild(cartBtn);
      }

      info.appendChild(actions);
      card.appendChild(img);
      card.appendChild(info);
      container.appendChild(card);
    });

    messagesEl.appendChild(container);
    messagesEl.scrollTop = messagesEl.scrollHeight;
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
        addMessage('محصول به سبد خرید اضافه شد ✓', 'bot');
      } else {
        addMessage('خطا در افزودن به سبد خرید.', 'bot');
      }
    } catch (err) {
      addMessage('خطا در افزودن به سبد خرید.', 'bot');
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
})();
