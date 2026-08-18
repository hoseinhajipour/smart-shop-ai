/**
 * Smart Shop AI — Admin JavaScript
 */
(function () {
  'use strict';

  const config = window.ssaiAdmin || {};
  const REST_URL = config.restUrl || '';
  const NONCE = config.nonce || '';

  async function api(endpoint, options = {}) {
    const url = REST_URL.replace(/\/$/, '') + endpoint;
    const response = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': NONCE,
        ...(options.headers || {}),
      },
    });
    return response.json();
  }

  function showResult(el, success, message) {
    if (!el) return;
    el.className = 'ssai-test-result ' + (success ? 'success' : 'error');
    el.textContent = message;
  }

  // Dashboard status checks.
  const statusCard = document.getElementById('ssai-status-checks');
  if (statusCard) {
    api('/diagnostics').then((data) => {
      if (!data.checks) return;
      statusCard.innerHTML = '';
      Object.entries(data.checks).forEach(([key, check]) => {
        const div = document.createElement('div');
        div.className = 'ssai-status-item';
        const icon = check.status ? '✓' : '✗';
        const cls = check.status ? 'ssai-status-ok' : 'ssai-status-fail';
        div.innerHTML = '<span class="' + cls + '">' + icon + '</span> <strong>' + key.replace(/_/g, ' ') + '</strong>: ' + check.message;
        statusCard.appendChild(div);
      });
    });
  }

  // AI Provider form.
  const aiForm = document.getElementById('ssai-ai-form');
  if (aiForm) {
    const PROVIDER_PRESETS = {
      openai: { endpoint: 'https://api.openai.com/v1/chat/completions', model: 'gpt-4o-mini', hint: 'Get your API key from platform.openai.com' },
      anthropic: { endpoint: 'https://api.anthropic.com/v1/messages', model: 'claude-3-5-sonnet-20241022', hint: 'Get your API key from console.anthropic.com' },
      gemini: { endpoint: 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent', model: 'gemini-2.0-flash', hint: 'Get your API key from aistudio.google.com' },
      openrouter: { endpoint: 'https://openrouter.ai/api/v1/chat/completions', model: 'openai/gpt-4o-mini', hint: 'Get your API key from openrouter.ai — access 100+ models' },
      groq: { endpoint: 'https://api.groq.com/openai/v1/chat/completions', model: 'llama-3.3-70b-versatile', hint: 'Get your API key from console.groq.com' },
      together: { endpoint: 'https://api.together.xyz/v1/chat/completions', model: 'meta-llama/Llama-3.3-70B-Instruct-Turbo', hint: 'Get your API key from api.together.xyz' },
      custom: { endpoint: '', model: '', hint: 'Select a preset endpoint or enter your own URL' },
    };

    const providerSelect = document.getElementById('provider');
    const endpointInput = document.getElementById('endpoint');
    const modelInput = document.getElementById('model');
    const endpointHint = document.getElementById('ssai-endpoint-hint');
    const modelHint = document.getElementById('ssai-model-hint');
    const customPresetRow = document.getElementById('ssai-custom-endpoint-preset-row');
    const customPresetSelect = document.getElementById('custom_endpoint_preset');

    function applyProviderPreset(provider, keepExisting) {
      const preset = PROVIDER_PRESETS[provider];
      if (!preset) return;

      if (customPresetRow) {
        customPresetRow.style.display = provider === 'custom' ? '' : 'none';
      }

      if (!keepExisting) {
        if (preset.endpoint) endpointInput.value = preset.endpoint;
        if (preset.model) modelInput.value = preset.model;
      }

      if (endpointHint) endpointHint.textContent = preset.hint || '';
      if (modelHint) {
        modelHint.textContent = provider === 'openrouter'
          ? 'Use provider/model format, e.g. openai/gpt-4o, anthropic/claude-3.5-sonnet'
          : '';
      }

      endpointInput.readOnly = provider !== 'custom';
    }

    providerSelect.addEventListener('change', () => {
      applyProviderPreset(providerSelect.value, false);
    });

    if (customPresetSelect) {
      customPresetSelect.addEventListener('change', () => {
        const val = customPresetSelect.value;
        if (val && val !== '__custom__') {
          endpointInput.value = val;
          endpointInput.readOnly = true;
        } else if (val === '__custom__') {
          endpointInput.value = '';
          endpointInput.readOnly = false;
          endpointInput.focus();
        }
      });
    }

    api('/settings/ai').then((data) => {
      if (data.provider) {
        providerSelect.value = data.provider === 'openai_compatible' ? 'custom' : data.provider;
      }
      if (data.endpoint) endpointInput.value = data.endpoint;
      if (data.model) modelInput.value = data.model;
      if (data.temperature) document.getElementById('temperature').value = data.temperature;
      if (data.max_tokens) document.getElementById('max_tokens').value = data.max_tokens;
      if (data.timeout) document.getElementById('timeout').value = data.timeout;
      applyProviderPreset(data.provider || 'openai', true);
    });

    aiForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(aiForm);
      const body = Object.fromEntries(formData.entries());
      await api('/settings/ai', { method: 'POST', body: JSON.stringify(body) });
      alert('Settings saved.');
    });

    const testBtn = document.getElementById('ssai-test-ai');
    if (testBtn) {
      testBtn.addEventListener('click', async () => {
        const result = await api('/test/ai', { method: 'POST' });
        showResult(document.getElementById('ssai-ai-test-result'), result.success, result.message);
      });
    }
  }

  // MCP form.
  const mcpForm = document.getElementById('ssai-mcp-form');
  if (mcpForm) {
    api('/settings/mcp').then((data) => {
      if (data.provider) document.getElementById('mcp_provider').value = data.provider;
      if (data.endpoint) document.getElementById('mcp_endpoint').value = data.endpoint;
    });

    mcpForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(mcpForm);
      const body = Object.fromEntries(formData.entries());
      await api('/settings/mcp', { method: 'POST', body: JSON.stringify(body) });
      alert('Settings saved.');
    });

    const testMcpBtn = document.getElementById('ssai-test-mcp');
    if (testMcpBtn) {
      testMcpBtn.addEventListener('click', async () => {
        const result = await api('/test/mcp', { method: 'POST' });
        showResult(document.getElementById('ssai-mcp-test-result'), result.success, result.message);
      });
    }
  }

  // System Prompt.
  const promptForm = document.getElementById('ssai-prompt-form');
  if (promptForm) {
    api('/settings/prompt').then((data) => {
      if (data.prompt) document.getElementById('system_prompt').value = data.prompt;
    });

    promptForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const prompt = document.getElementById('system_prompt').value;
      await api('/settings/prompt', { method: 'POST', body: JSON.stringify({ prompt }) });
      alert('Prompt saved.');
    });
  }

  // Capabilities.
  const capsForm = document.getElementById('ssai-capabilities-form');
  if (capsForm) {
    api('/settings/capabilities').then((data) => {
      document.querySelectorAll('.ssai-capability').forEach((cb) => {
        const key = cb.dataset.key;
        if (data[key]) cb.checked = true;
      });
    });

    capsForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const caps = {};
      document.querySelectorAll('.ssai-capability').forEach((cb) => {
        caps[cb.dataset.key] = cb.checked;
      });
      await api('/settings/capabilities', { method: 'POST', body: JSON.stringify(caps) });
      alert('Capabilities saved.');
    });
  }

  // Attribute Mapping.
  const attrForm = document.getElementById('ssai-attributes-form');
  if (attrForm) {
    const semanticKeys = ['vehicle', 'brand', 'wheel_size', 'color', 'pcd', 'et', 'material', 'width', 'diameter'];

    api('/settings/attributes').then((data) => {
      const loading = document.getElementById('ssai-attributes-loading');
      if (loading) loading.style.display = 'none';
      attrForm.style.display = 'block';

      const tbody = document.getElementById('ssai-mapping-body');
      const mapping = data.mapping || {};
      const suggestions = data.suggestions || {};
      const attributes = data.attributes || [];

      semanticKeys.forEach((key) => {
        const tr = document.createElement('tr');
        const tdLabel = document.createElement('td');
        tdLabel.textContent = key.replace(/_/g, ' ');

        const tdSelect = document.createElement('td');
        const select = document.createElement('select');
        select.name = key;
        select.className = 'ssai-attr-select';

        const emptyOpt = document.createElement('option');
        emptyOpt.value = '';
        emptyOpt.textContent = '— Select —';
        select.appendChild(emptyOpt);

        attributes.forEach((attr) => {
          const opt = document.createElement('option');
          opt.value = attr.taxonomy;
          opt.textContent = attr.label + ' (' + attr.taxonomy + ')';
          if (mapping[key] === attr.taxonomy || suggestions[key] === attr.taxonomy) {
            opt.selected = true;
          }
          select.appendChild(opt);
        });

        tdSelect.appendChild(select);
        tr.appendChild(tdLabel);
        tr.appendChild(tdSelect);
        tbody.appendChild(tr);
      });

      // Discovered attributes.
      const discovered = document.getElementById('ssai-discovered-attributes');
      if (discovered) {
        attributes.forEach((attr) => {
          const div = document.createElement('div');
          div.className = 'ssai-attribute-item';
          div.innerHTML = '<div class="ssai-attribute-name">' + attr.label + ' (' + attr.taxonomy + ')</div>' +
            '<div class="ssai-attribute-terms">' + attr.term_count + ' terms</div>';
          discovered.appendChild(div);
        });
      }
    });

    attrForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const mapping = {};
      document.querySelectorAll('.ssai-attr-select').forEach((sel) => {
        if (sel.value) mapping[sel.name] = sel.value;
      });
      await api('/settings/attributes', { method: 'POST', body: JSON.stringify({ mapping }) });
      alert('Mapping saved.');
    });
  }

  // Chatbot settings.
  const chatbotForm = document.getElementById('ssai-chatbot-form');
  if (chatbotForm) {
    const preview = document.getElementById('ssai-chatbot-preview');
    const previewFloatBtn = document.getElementById('ssai-preview-float-btn');
    const previewWindow = preview ? preview.querySelector('.ssai-preview-window') : null;

    const FLOAT_ICONS = { chat: '💬', robot: '🤖', help: '❓', sparkle: '✨', cart: '🛒' };

    function getAppearance() {
      return {
        title: document.getElementById('chatbot_title').value,
        avatar_emoji: document.getElementById('chatbot_avatar_emoji').value,
        primary_color: document.getElementById('chatbot_primary_color').value,
        secondary_color: document.getElementById('chatbot_secondary_color').value,
        user_bubble_color: document.getElementById('chatbot_user_bubble_color').value,
        bot_bubble_color: document.getElementById('chatbot_bot_bubble_color').value,
        background_color: document.getElementById('chatbot_background_color').value,
        border_radius: parseInt(document.getElementById('chatbot_border_radius').value, 10),
        font_size: parseInt(document.getElementById('chatbot_font_size').value, 10),
      };
    }

    function getFloatButton() {
      return {
        position: document.getElementById('float_button_position').value,
        icon: document.getElementById('float_button_icon').value,
        animation: document.getElementById('float_button_animation').value,
        offset_x: parseInt(document.getElementById('float_button_offset_x').value, 10),
        offset_y: parseInt(document.getElementById('float_button_offset_y').value, 10),
        size: parseInt(document.getElementById('float_button_size').value, 10),
      };
    }

    function updatePreview() {
      if (!preview) return;
      const app = getAppearance();
      const flt = getFloatButton();

      preview.style.setProperty('--ssai-primary', app.primary_color);
      preview.style.setProperty('--ssai-secondary', app.secondary_color);
      preview.style.setProperty('--ssai-user-bubble', app.user_bubble_color);
      preview.style.setProperty('--ssai-bot-bubble', app.bot_bubble_color);
      preview.style.setProperty('--ssai-bg', app.background_color);
      preview.style.setProperty('--ssai-radius', app.border_radius + 'px');
      preview.style.setProperty('--ssai-font-size', app.font_size + 'px');
      preview.style.setProperty('--ssai-float-size', flt.size + 'px');

      const titleEl = preview.querySelector('.ssai-preview-title');
      const avatarEl = preview.querySelector('.ssai-preview-avatar');
      const avatarSm = preview.querySelector('.ssai-preview-avatar-sm');
      const iconEl = preview.querySelector('.ssai-preview-icon');
      const userMsg = preview.querySelector('.ssai-preview-msg.user');
      const botMsg = preview.querySelector('.ssai-preview-msg.bot');
      const header = preview.querySelector('.ssai-preview-header');
      const messages = preview.querySelector('.ssai-preview-messages');

      if (titleEl) titleEl.textContent = app.title;
      if (avatarEl) avatarEl.textContent = app.avatar_emoji;
      if (avatarSm) avatarSm.textContent = app.avatar_emoji;
      if (iconEl) iconEl.textContent = FLOAT_ICONS[flt.icon] || '💬';
      if (userMsg) userMsg.style.background = app.user_bubble_color;
      if (botMsg) { botMsg.style.background = app.bot_bubble_color; }
      if (header) header.style.background = 'linear-gradient(135deg, ' + app.primary_color + ', ' + app.secondary_color + ')';
      if (messages) messages.style.background = app.background_color;
      if (previewWindow) previewWindow.style.borderRadius = app.border_radius + 'px';

      previewFloatBtn.className = 'ssai-preview-float-btn ssai-anim-' + flt.animation;
      previewFloatBtn.style.width = flt.size + 'px';
      previewFloatBtn.style.height = flt.size + 'px';
      previewFloatBtn.style.background = 'linear-gradient(135deg, ' + app.primary_color + ', ' + app.secondary_color + ')';

      preview.classList.remove('ssai-preview-left', 'ssai-preview-right');
      preview.classList.add('ssai-preview-' + flt.position);
    }

    // Range value displays.
    ['chatbot_border_radius', 'chatbot_font_size', 'float_button_size'].forEach((id) => {
      const input = document.getElementById(id);
      const valEl = document.getElementById(id + '_val');
      if (input && valEl) {
        input.addEventListener('input', () => {
          valEl.textContent = input.value;
          updatePreview();
        });
      }
    });

    // Emoji picker.
    document.querySelectorAll('.ssai-emoji-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        document.getElementById('chatbot_avatar_emoji').value = btn.dataset.emoji;
        updatePreview();
      });
    });

    // Live preview on all inputs.
    chatbotForm.querySelectorAll('input, select, textarea').forEach((el) => {
      el.addEventListener('input', updatePreview);
      el.addEventListener('change', updatePreview);
    });

    api('/settings/chatbot').then((data) => {
      if (data.enabled) document.getElementById('chatbot_enabled').checked = true;
      if (data.welcome) document.getElementById('chatbot_welcome').value = data.welcome;

      const app = data.appearance || {};
      if (app.title) document.getElementById('chatbot_title').value = app.title;
      if (app.avatar_emoji) document.getElementById('chatbot_avatar_emoji').value = app.avatar_emoji;
      if (app.primary_color) document.getElementById('chatbot_primary_color').value = app.primary_color;
      if (app.secondary_color) document.getElementById('chatbot_secondary_color').value = app.secondary_color;
      if (app.user_bubble_color) document.getElementById('chatbot_user_bubble_color').value = app.user_bubble_color;
      if (app.bot_bubble_color) document.getElementById('chatbot_bot_bubble_color').value = app.bot_bubble_color;
      if (app.background_color) document.getElementById('chatbot_background_color').value = app.background_color;
      if (app.border_radius) {
        document.getElementById('chatbot_border_radius').value = app.border_radius;
        document.getElementById('chatbot_border_radius_val').textContent = app.border_radius;
      }
      if (app.font_size) {
        document.getElementById('chatbot_font_size').value = app.font_size;
        document.getElementById('chatbot_font_size_val').textContent = app.font_size;
      }

      const flt = data.float_button || {};
      if (flt.position) document.getElementById('float_button_position').value = flt.position;
      if (flt.icon) document.getElementById('float_button_icon').value = flt.icon;
      if (flt.animation) document.getElementById('float_button_animation').value = flt.animation;
      if (flt.offset_x !== undefined) document.getElementById('float_button_offset_x').value = flt.offset_x;
      if (flt.offset_y !== undefined) document.getElementById('float_button_offset_y').value = flt.offset_y;
      if (flt.size) {
        document.getElementById('float_button_size').value = flt.size;
        document.getElementById('float_button_size_val').textContent = flt.size;
      }

      updatePreview();
    });

    chatbotForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const body = {
        enabled: document.getElementById('chatbot_enabled').checked,
        welcome: document.getElementById('chatbot_welcome').value,
        appearance: getAppearance(),
        float_button: getFloatButton(),
      };
      await api('/settings/chatbot', { method: 'POST', body: JSON.stringify(body) });
      alert('Settings saved.');
    });
  }

  // Conversation Logs.
  const logsContainer = document.getElementById('ssai-logs-container');
  if (logsContainer) {
    api('/logs?limit=30').then((data) => {
      if (!data.logs || data.logs.length === 0) {
        logsContainer.innerHTML = '<p>No conversation logs yet.</p>';
        return;
      }

      logsContainer.innerHTML = '<p>Total: ' + data.total + ' conversations</p>';
      data.logs.forEach((log) => {
        const div = document.createElement('div');
        div.className = 'ssai-log-item';
        div.innerHTML =
          '<div class="ssai-log-meta">' + log.created_at + ' | Session: ' + log.session_id + ' | ' + log.response_time_ms + 'ms</div>' +
          '<div class="ssai-log-message"><span class="ssai-log-label">User:</span> ' + escapeHtml(log.user_message) + '</div>' +
          '<div class="ssai-log-message"><span class="ssai-log-label">AI:</span> ' + escapeHtml(log.ai_response) + '</div>' +
          (log.intent ? '<div class="ssai-log-message"><span class="ssai-log-label">Intent:</span> ' + log.intent + '</div>' : '') +
          (log.search_query ? '<div class="ssai-log-message"><span class="ssai-log-label">Search:</span> ' + escapeHtml(log.search_query) + '</div>' : '') +
          (log.error_message ? '<div class="ssai-log-message" style="color:red;"><span class="ssai-log-label">Error:</span> ' + escapeHtml(log.error_message) + '</div>' : '');
        logsContainer.appendChild(div);
      });
    });
  }

  // Diagnostics.
  const diagChecks = document.getElementById('ssai-diagnostics-checks');
  if (diagChecks) {
    api('/diagnostics').then((data) => {
      if (!data.checks) return;
      diagChecks.innerHTML = '';
      Object.entries(data.checks).forEach(([key, check]) => {
        const div = document.createElement('div');
        div.className = 'ssai-status-item';
        const icon = check.status ? '✓' : '✗';
        const cls = check.status ? 'ssai-status-ok' : 'ssai-status-fail';
        div.innerHTML = '<span class="' + cls + '">' + icon + '</span> <strong>' + key.replace(/_/g, ' ') + '</strong>: ' + check.message;
        diagChecks.appendChild(div);
      });
    });
  }

  const runTestBtn = document.getElementById('ssai-run-test');
  if (runTestBtn) {
    runTestBtn.addEventListener('click', async () => {
      const query = document.getElementById('ssai-test-query').value;
      const resultsEl = document.getElementById('ssai-test-results');
      resultsEl.textContent = 'Running test...';

      const data = await api('/diagnostics/test', {
        method: 'POST',
        body: JSON.stringify({ query }),
      });

      resultsEl.textContent = JSON.stringify(data, null, 2);
    });
  }

  // Add Rule form.
  const addRuleForm = document.getElementById('ssai-add-rule-form');
  if (addRuleForm) {
    addRuleForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(addRuleForm);
      const body = Object.fromEntries(formData.entries());
      await api('/rules', { method: 'POST', body: JSON.stringify(body) });
      location.reload();
    });
  }

  // Delete rule buttons.
  document.querySelectorAll('.ssai-delete-rule').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      if (!confirm('Delete this rule?')) return;
      await api('/rules/' + id, { method: 'DELETE' });
      btn.closest('.ssai-rule-item').remove();
    });
  });

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
  }
})();
