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
      replicate: { endpoint: 'https://api.replicate.com/v1/predictions', model: 'openai/gpt-5.1', hint: 'Get your API token from replicate.com/account/api-tokens' },
      custom: { endpoint: '', model: '', hint: 'Select a preset endpoint or enter your own URL' },
    };

    const providerSelect = document.getElementById('provider');
    const endpointInput = document.getElementById('endpoint');
    const modelSelect = document.getElementById('model');
    const modelCustomInput = document.getElementById('model_custom');
    const endpointHint = document.getElementById('ssai-endpoint-hint');
    const modelHint = document.getElementById('ssai-model-hint');
    const modelFetchStatus = document.getElementById('ssai-model-fetch-status');
    const fetchModelsBtn = document.getElementById('ssai-fetch-models');
    const customPresetRow = document.getElementById('ssai-custom-endpoint-preset-row');
    const customPresetSelect = document.getElementById('custom_endpoint_preset');
    const CUSTOM_MODEL_VALUE = '__custom__';

    function getSelectedModel() {
      if (!modelSelect) return '';
      if (modelSelect.value === CUSTOM_MODEL_VALUE) {
        return modelCustomInput ? modelCustomInput.value.trim() : '';
      }
      return modelSelect.value;
    }

    function setSelectedModel(modelId) {
      if (!modelSelect || !modelId) return;

      ensureCustomModelOption();

      const existing = Array.from(modelSelect.options).some((opt) => opt.value === modelId);
      if (!existing) {
        const opt = document.createElement('option');
        opt.value = modelId;
        opt.textContent = modelId;
        const customOpt = modelSelect.querySelector('option[value="' + CUSTOM_MODEL_VALUE + '"]');
        if (customOpt) {
          modelSelect.insertBefore(opt, customOpt);
        } else {
          modelSelect.appendChild(opt);
        }
      }

      modelSelect.value = modelId;
      if (modelCustomInput) {
        modelCustomInput.style.display = 'none';
        modelCustomInput.value = '';
      }
    }

    function ensureCustomModelOption() {
      if (!modelSelect || modelSelect.querySelector('option[value="' + CUSTOM_MODEL_VALUE + '"]')) return;
      const opt = document.createElement('option');
      opt.value = CUSTOM_MODEL_VALUE;
      opt.textContent = 'Custom model...';
      modelSelect.appendChild(opt);
    }

    function populateModelOptions(models, selectedModel) {
      if (!modelSelect) return;

      const placeholder = modelSelect.querySelector('option[value=""]');
      modelSelect.innerHTML = '';
      if (placeholder) {
        modelSelect.appendChild(placeholder);
      } else {
        const emptyOpt = document.createElement('option');
        emptyOpt.value = '';
        emptyOpt.textContent = '— Select model —';
        modelSelect.appendChild(emptyOpt);
      }

      models.forEach((model) => {
        const opt = document.createElement('option');
        opt.value = model.id;
        opt.textContent = model.label === model.id ? model.id : model.label + ' (' + model.id + ')';
        modelSelect.appendChild(opt);
      });

      ensureCustomModelOption();

      if (selectedModel) {
        setSelectedModel(selectedModel);
      }
    }

    function applyProviderPreset(provider, keepExisting) {
      const preset = PROVIDER_PRESETS[provider];
      if (!preset) return;

      if (customPresetRow) {
        customPresetRow.style.display = provider === 'custom' ? '' : 'none';
      }

      if (!keepExisting) {
        if (preset.endpoint) endpointInput.value = preset.endpoint;
        if (preset.model) {
          populateModelOptions([{ id: preset.model, label: preset.model }], preset.model);
        }
        if (provider === 'replicate') {
          const timeoutInput = document.getElementById('timeout');
          if (timeoutInput) timeoutInput.value = 60;
        }
      }

      if (endpointHint) endpointHint.textContent = preset.hint || '';
      if (modelHint) {
        if (provider === 'openrouter') {
          modelHint.textContent = 'Use provider/model format, e.g. openai/gpt-4o, anthropic/claude-3.5-sonnet';
        } else if (provider === 'replicate') {
          modelHint.textContent = 'Use owner/model format, e.g. openai/gpt-5.1. See replicate.com/openai/gpt-5.1/api/schema';
        } else {
          modelHint.textContent = 'Click "Fetch Models" after entering your API key to load available models.';
        }
      }

      endpointInput.readOnly = provider !== 'custom';
    }

    if (modelSelect) {
      modelSelect.addEventListener('change', () => {
        if (!modelCustomInput) return;
        const isCustom = modelSelect.value === CUSTOM_MODEL_VALUE;
        modelCustomInput.style.display = isCustom ? '' : 'none';
        if (isCustom) modelCustomInput.focus();
      });
    }

    providerSelect.addEventListener('change', () => {
      applyProviderPreset(providerSelect.value, false);
      if (modelFetchStatus) modelFetchStatus.textContent = '';
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
      if (data.temperature) document.getElementById('temperature').value = data.temperature;
      if (data.max_tokens) document.getElementById('max_tokens').value = data.max_tokens;
      if (data.timeout) document.getElementById('timeout').value = data.timeout;
      applyProviderPreset(data.provider || 'openai', true);
      if (data.model) {
        populateModelOptions([{ id: data.model, label: data.model }], data.model);
      } else {
        ensureCustomModelOption();
      }
    });

    if (fetchModelsBtn) {
      fetchModelsBtn.addEventListener('click', async () => {
        const apiKey = document.getElementById('api_key').value.trim();
        if (modelFetchStatus) {
          modelFetchStatus.textContent = 'Fetching models...';
          modelFetchStatus.className = 'description';
        }
        fetchModelsBtn.disabled = true;

        try {
          const result = await api('/settings/ai/models', {
            method: 'POST',
            body: JSON.stringify({
              provider: providerSelect.value,
              endpoint: endpointInput.value,
              api_key: apiKey,
            }),
          });

          if (!result.success) {
            if (modelFetchStatus) {
              modelFetchStatus.textContent = result.error || 'Could not fetch models.';
              modelFetchStatus.className = 'description ssai-model-fetch-error';
            }
            return;
          }

          populateModelOptions(result.models || [], getSelectedModel());
          if (modelFetchStatus) {
            modelFetchStatus.textContent = (result.models || []).length + ' models loaded.';
            modelFetchStatus.className = 'description ssai-model-fetch-success';
          }
        } catch (err) {
          if (modelFetchStatus) {
            modelFetchStatus.textContent = 'Could not fetch models.';
            modelFetchStatus.className = 'description ssai-model-fetch-error';
          }
        } finally {
          fetchModelsBtn.disabled = false;
        }
      });
    }

    aiForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(aiForm);
      const body = Object.fromEntries(formData.entries());
      body.model = getSelectedModel();
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
    const quickActionsEditor = document.getElementById('ssai-quick-actions-editor');
    const previewQuickActions = document.getElementById('ssai-preview-quick-actions');
    const previewWelcome = document.getElementById('ssai-preview-welcome');
    let defaultQuickActions = [
      { icon: '🚗', label: 'Find wheels', query: 'I need wheels for my car' },
      { icon: '🔋', label: 'Find a battery', query: 'I need a battery for my car' },
      { icon: '🛞', label: 'Find tires', query: 'I need tires for my car' },
      { icon: '🔎', label: 'Search products', query: 'Search products' },
    ];

    const FLOAT_ICONS = { chat: '💬', robot: '🤖', help: '❓', sparkle: '✨', cart: '🛒' };

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text || '';
      return div.innerHTML;
    }

    function renderQuickActionsEditor(actions) {
      if (!quickActionsEditor) return;
      quickActionsEditor.innerHTML = '';

      (actions || []).forEach((action, index) => {
        const row = document.createElement('div');
        row.className = 'ssai-quick-action-row';
        row.innerHTML =
          '<div class="ssai-quick-action-field ssai-quick-action-icon">' +
            '<label>Icon</label>' +
            '<input type="text" class="small-text ssai-quick-action-input" data-field="icon" maxlength="4" value="' + escapeHtml(action.icon || '') + '" />' +
          '</div>' +
          '<div class="ssai-quick-action-field ssai-quick-action-label">' +
            '<label>Button Label</label>' +
            '<input type="text" class="regular-text ssai-quick-action-input" data-field="label" value="' + escapeHtml(action.label || '') + '" />' +
          '</div>' +
          '<div class="ssai-quick-action-field ssai-quick-action-query">' +
            '<label>Prompt</label>' +
            '<input type="text" class="large-text ssai-quick-action-input" data-field="query" value="' + escapeHtml(action.query || '') + '" />' +
          '</div>' +
          '<button type="button" class="button ssai-remove-quick-action" data-index="' + index + '" aria-label="Remove quick action">&times;</button>';

        quickActionsEditor.appendChild(row);
      });

      quickActionsEditor.querySelectorAll('.ssai-quick-action-input').forEach((input) => {
        input.addEventListener('input', updatePreview);
      });

      quickActionsEditor.querySelectorAll('.ssai-remove-quick-action').forEach((btn) => {
        btn.addEventListener('click', () => {
          const actions = getQuickActions();
          actions.splice(parseInt(btn.dataset.index, 10), 1);
          renderQuickActionsEditor(actions);
          updatePreview();
        });
      });
    }

    function getQuickActions() {
      if (!quickActionsEditor) return [];

      const rows = quickActionsEditor.querySelectorAll('.ssai-quick-action-row');
      const actions = [];

      rows.forEach((row) => {
        const icon = row.querySelector('[data-field="icon"]')?.value.trim() || '';
        const label = row.querySelector('[data-field="label"]')?.value.trim() || '';
        const query = row.querySelector('[data-field="query"]')?.value.trim() || '';

        if (!label && !query) return;

        actions.push({ icon, label, query });
      });

      return actions;
    }

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
      if (previewWelcome) {
        previewWelcome.textContent = document.getElementById('chatbot_welcome').value || 'Hi 👋 What product are you looking for?';
      }

      if (previewQuickActions) {
        previewQuickActions.innerHTML = '';
        getQuickActions().forEach((action) => {
          const btn = document.createElement('span');
          btn.className = 'ssai-preview-quick-action';
          btn.textContent = (action.icon ? action.icon + ' ' : '') + (action.label || action.query);
          previewQuickActions.appendChild(btn);
        });
      }

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

    document.getElementById('ssai-add-quick-action')?.addEventListener('click', () => {
      const actions = getQuickActions();
      actions.push({ icon: '✨', label: 'New action', query: '' });
      renderQuickActionsEditor(actions);
      updatePreview();
    });

    document.getElementById('ssai-reset-quick-actions')?.addEventListener('click', () => {
      renderQuickActionsEditor(defaultQuickActions.slice());
      updatePreview();
    });

    api('/settings/chatbot').then((data) => {
      if (data.default_quick_actions) {
        defaultQuickActions = data.default_quick_actions;
      }
      if (data.enabled) document.getElementById('chatbot_enabled').checked = true;
      if (data.welcome) document.getElementById('chatbot_welcome').value = data.welcome;
      renderQuickActionsEditor(data.quick_actions || defaultQuickActions);

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
        quick_actions: getQuickActions(),
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
