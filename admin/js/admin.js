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
    api('/settings/ai').then((data) => {
      if (data.provider) document.getElementById('provider').value = data.provider;
      if (data.endpoint) document.getElementById('endpoint').value = data.endpoint;
      if (data.model) document.getElementById('model').value = data.model;
      if (data.temperature) document.getElementById('temperature').value = data.temperature;
      if (data.max_tokens) document.getElementById('max_tokens').value = data.max_tokens;
      if (data.timeout) document.getElementById('timeout').value = data.timeout;
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
    api('/settings/chatbot').then((data) => {
      if (data.enabled) document.getElementById('chatbot_enabled').checked = true;
      if (data.welcome) document.getElementById('chatbot_welcome').value = data.welcome;
    });

    chatbotForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const body = {
        enabled: document.getElementById('chatbot_enabled').checked,
        welcome: document.getElementById('chatbot_welcome').value,
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
